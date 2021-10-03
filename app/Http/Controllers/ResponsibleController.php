<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Responsible;
use App\Models\User;
use App\Repositories\ResponsibleRepository;
use App\Validators\Controller\RegistrationValidator;
use App\Validators\Controller\ResponsibleCreationValidator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ResponsibleController extends Controller
{
    protected ResponsibleRepository $responsibleRepository;
    protected ResponsibleCreationValidator $responsibleCreationValidator;

    /**
     * ReservationController constructor.
     * @param  ResponsibleRepository  $responsibleRepository
     * @param  ResponsibleCreationValidator  $responsibleCreationValidator
     */
    public function __construct(
        ResponsibleRepository $responsibleRepository,
        ResponsibleCreationValidator $responsibleCreationValidator
    ) {
        $this->responsibleRepository = $responsibleRepository;
        $this->responsibleCreationValidator = $responsibleCreationValidator;
    }

    /**
     * @param  Request  $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Responsible/Create', [
            'date' => Carbon::now()->subYears(18)->format('Y-m-d')
        ]);
    }

    /**
     * @param  Request  $request
     * @return RedirectResponse
     * @throws ValidationException
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $this->responsibleCreationValidator->validateData($request->toArray());

        $this->responsibleRepository->saveOrCreate(
            Responsible::factory()->make(
                array_merge(
                    ['structure_id' => $currentUser->responsible->structure_id],
                    $request->toArray()
                )
            ),
            Account::factory()->make($request->toArray()),
            $request->get('email'),
            'test'
        );

        return Redirect::route('reservations.index')->with(['success' => 'Richiamo prenotato con successo']);
    }
}
