<?php

namespace App\Http\Controllers;

use App\Exceptions\MaxCapacityExceededException;
use App\Exceptions\NoAvailableStockException;
use App\Exceptions\ResponsibleNotFoundException;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Structure;
use App\Models\User;
use App\Models\Vaccine;
use App\Repositories\ReservationRepository;
use App\Repositories\ResponsibleRepository;
use App\Repositories\StructureRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ReservationController extends Controller
{
    protected ResponsibleRepository $responsibleRepository;
    protected StructureRepository $structureRepository;
    protected ReservationRepository $reservationRepository;

    /**
     * ReservationController constructor.
     * @param  ResponsibleRepository  $responsibleRepository
     * @param  StructureRepository  $structureRepository
     * @param  ReservationRepository  $reservationRepository
     */
    public function __construct(
        ResponsibleRepository $responsibleRepository,
        StructureRepository $structureRepository,
        ReservationRepository $reservationRepository
    ) {
        $this->responsibleRepository = $responsibleRepository;
        $this->structureRepository = $structureRepository;
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * @param  Request  $request
     * @return Response
     * @throws AuthorizationException
     * @throws ResponsibleNotFoundException
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reservation::class);

        $currentOperator = $this->responsibleRepository->get(Auth::user()->email);

        /** @var Builder $query */
        $query = Reservation::query()
            ->filter(\Illuminate\Support\Facades\Request::only(Reservation::getFilters()));

        if (!strcmp($request->method(), 'GET')) {
            $query->whereIn('state', [Reservation::PENDING_STATE]);
        }

        return Inertia::render("Reservation/Index", [
            'reservations' => $query
                ->whereIn('stock_id', $currentOperator->structure->stocks()->pluck('id'))
                ->with('patient.account.user')
                ->with('stock.batch.vaccine')
                ->paginate($request->get('items_per_page') ?? 10, ["*"], 'page', $request->get('current_page'))
                ->withQueryString()
                ->through(function (Reservation $reservation) {
                    $isRecall = Reservation::where('patient_id', '=', $reservation->patient_id)
                            ->where('id', '!=', $reservation->id)
                            ->where('state', '=', Reservation::COMPLETED_STATE)
                            ->get()
                            ->count() >= 1;

                    return array_merge([
                        'id' => $reservation->id],
                        $reservation->toArray(),
                        [
                            'is_recall' => $isRecall,
                            'date' => Carbon::make($reservation->date)->format('d/m/Y'),
                            'created_at' => Carbon::make($reservation->created_at)->format('d/m/Y H:i'),
                            'updated_at' => Carbon::make($reservation->created_at)->format('d/m/Y H:i'),
                        ]
                    );
                }),
            'last_update' => $currentOperator->structure->last_reservation_update
        ]);
    }

    /**
     * @param  Request  $request
     * @return int
     * @throws AuthorizationException
     */
    public function reservationsChanged(Request $request): int
    {
        $structure = Structure::whereId($request->get('structure_id'))->first();

        $this->authorize('poll', [Reservation::class, $structure]);

//        if (!$structure) {
//            return 0;
//        }

        $lastUpdate = $request->get('last_update');

        $lastStructureUpdate = $structure->last_reservation_update;

        if (!$lastStructureUpdate) {
            return 0;
        }

        if (!$lastUpdate) {
            return 1;
        }

        $lastUpdateCarbon = Carbon::make($lastUpdate);
        $mostRecentUpdateCarbon = Carbon::make($lastStructureUpdate);

        if ($mostRecentUpdateCarbon->greaterThan($lastUpdateCarbon)) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  Request  $request
     * @param  Reservation  $reservation
     * @return Response
     * @throws AuthorizationException
     */
    public function create(Request $request, Reservation $reservation): Response
    {
        $this->authorize('create', [Reservation::class, $reservation]);

        $structure = $reservation->stock->structure;
        $patient = $reservation->patient;
        $stock = $reservation->stock;
        $vaccine = $stock->batch->vaccine;

        return Inertia::render('Reservation/Create', [
            'reservation' => $reservation,
            'patient' => $patient,
            'account' => $patient->account,
            'user' => $patient->account->user,
            'structure' => $structure,
            'vaccine' => $vaccine,
            'availableVaccines' => $structure->getAvailableVaccines()->pluck('name'),
            'busyDates' => $structure->getBusyDates(),
            'vaccinesWithQty' => $structure->getVaccinesWithQty(),
            'oldReservations' => $patient
                ->reservations()
                ->orderBy('date')
                ->with('stock.batch.vaccine')
                ->get()
        ]);
    }

    /**
     * @param  Request  $request
     * @return RedirectResponse
     * @throws NoAvailableStockException
     * @throws Throwable
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $structure = Structure::whereName($request->post('structure'))->firstOrFail();
        $this->authorize('store', [Reservation::class, $structure]);

        $this->reservationRepository->createRecallAndStockDecrement(
            Patient::whereId($request->post('patient'))->firstOrFail(),
            $request->post('date'),
            $request->post('time'),
            Vaccine::whereName($request->post('vaccine'))->firstOrFail(),
            $structure
        );

        return Redirect::route('reservations.index')->with(['success' => 'Richiamo prenotato con successo']);
    }

    /**
     * @param  Request  $request
     * @return string[]
     * @throws AuthorizationException
     */
    public function getBusyTimes(Request $request): array
    {
        if (!$request->get('date')) {
            return [];
        }

        $date = Carbon::make($request->get('date'))->format('Y-m-d');

        if ($request->exists('structure_id')) {
            $structure = Structure::whereId($request->get('structure_id'))->firstOrFail();
            $this->authorize('store', [Reservation::class, $structure]);
            $excludeHours = [];
        } else if ($request->exists('reservation_id')) {
            $reservation = Reservation::whereId($request->get('reservation_id'))->firstOrFail();
            $structure = $reservation->stock->structure;
            $this->authorize('store', [Reservation::class, $structure]);
            // se sto esaminando la data attuale della prenotazione devo escludere l'orario già scelto
            $excludeHours = !strcmp($reservation->date->format('Y-m-d'), $date) ? [$reservation->time] : [];
        } else {
            return [];
        }

        return $structure->getBusyTimes($date, $excludeHours);
    }

    /**
     * @param  Request  $request
     * @param  Reservation  $reservation
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Request $request, Reservation $reservation): Response
    {
        $this->authorize('update', [Reservation::class, $reservation]);
        $patient=$reservation->patient;
        $stock=$reservation->stock;
        $vaccine=$stock->batch->vaccine;

        /** @var User $currentUser */
        $currentUser = $request->user();
        $currentStructure = $currentUser->responsible->structure;

        return Inertia::render('Reservation/Edit', [
                'reservation' => $reservation,
                'patient' => $patient,
                'account' => $patient->account,
                'user' => $patient->account->user,
                'vaccine' => $vaccine,
                'availableVaccines' => array_merge(
                    $currentStructure->getAvailableVaccines()->pluck('name')->toArray(),
                    [$reservation->stock->batch->vaccine->name]
                ),
                'busyDates' => $currentStructure->getBusyDates(),
                'vaccinesWithQty' => $currentStructure->getVaccinesWithQty(),
                'oldReservations' => $patient
                    ->reservations()
                    ->orderBy('date')
                    ->with('stock.batch.vaccine')
                    ->get()
        ]);
    }

    /**
     * @param  Request  $request
     * @param  Reservation  $reservation
     * @return RedirectResponse
     * @throws MaxCapacityExceededException
     * @throws Throwable
     * @throws ValidationException
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('update', [Reservation::class, $reservation]);

        $reservation->date = $request->post('date');
        $reservation->time = $request->post('time');
        $vaccineName = $request->post('vaccine');
        $state = $request->post('state');

        if (!strcmp($state, Reservation::CANCELED_STATE)) {
            $this->reservationRepository->cancelAndStockIncrement($reservation, $request->get('notes') ?? '');
            return Redirect::route('reservations.index')->with(['success' => 'Prenotazione annullata con successo']);
        } elseif (strcmp($vaccineName, $reservation->stock->batch->vaccine->name)) {
            $this->reservationRepository->changeVaccineAndConfirm(
                $reservation, Vaccine::where('name', '=', $vaccineName)->firstOrFail()
            );
            return Redirect::route('reservations.index')->with(['success' => 'Prenotazione confermata con successo']);
        } elseif (!strcmp($state, Reservation::COMPLETED_STATE)) {
            $reservation = $this->reservationRepository->completeAndSave($reservation);
            return Redirect::route('reservations.edit', $reservation->id)
                ->with(['success' => 'Somministrazione confermata con successo']);
        } else {
            $this->reservationRepository->confirmAndSave($reservation, $request->get('notes') ?? '');
            return Redirect::route('reservations.index')->with(['success' => 'Prenotazione confermata con successo']);
        }
    }
}
