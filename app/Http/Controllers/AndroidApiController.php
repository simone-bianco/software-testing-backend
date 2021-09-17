<?php

namespace App\Http\Controllers;

use App\Exceptions\NoAvailableStockException;
use App\Exceptions\PatientNotFoundException;
use App\Models\Account;
use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Structure;
use App\Models\User;
use App\Repositories\PatientRepository;
use App\Repositories\ReservationRepository;
use App\Validators\Controller\RegistrationValidator;
use Arr;
use Auth;
use Exception;
use Hash;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;
use Validator;

/**
 * @OA\Info(
 *     title="API Endpoints",
 *     description="API per interfacciarsi con l'applicazione Android",
 *     version="1.0.0"
 * )
 */
class AndroidApiController extends Controller
{
    protected PatientRepository $patientRepository;
    protected ReservationRepository $reservationRepository;
    protected Structure $structure;
    protected RegistrationValidator $registrationValidator;

    /**
     * TestApiController constructor.
     * @param  PatientRepository  $patientRepository
     * @param  ReservationRepository  $reservationRepository
     * @param  RegistrationValidator  $registrationValidator
     * @param  Structure  $structure
     */
    public function __construct(
        PatientRepository $patientRepository,
        ReservationRepository $reservationRepository,
        RegistrationValidator $registrationValidator,
        Structure $structure
    ) {
        $this->patientRepository = $patientRepository;
        $this->reservationRepository=$reservationRepository;
        $this->registrationValidator = $registrationValidator;
        $this->structure=$structure;
    }

    /**
     * @OA\Get(
     *     path="/get-structures-by-region/{region}",
     *     summary="Prendi Strutture",
     *     description="Restituisce tutte le strutture di una regione",
     *     operationId="getStructuresByRegion",
     *     tags={"reservation"},
     *     security={ {"bearer": {} }},
     *     @OA\Parameter(
     *        description="Nome di una regione italiana",
     *        in="path",
     *        name="Nome Regione",
     *        required=true,
     *        example="Abruzzo",
     *        @OA\Schema(
     *           type="string",
     *           format="UTF-8"
     *        )
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="Array di strutture prelevato con success",
     *        @OA\JsonContent(
     *           @OA\Property(property="structures", type="json", example="['Struttura A', 'Struttura B', ...]"),
     *           @OA\Property(property="message", type="string", example="ok"),
     *           @OA\Property(property="code", type="integer", example="200"),
     *            )
     *         ),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     * @param  string  $region
     * @return JsonResponse
     * @throws Exception
     */
    public function getStructuresByRegion(string $region): JsonResponse
    {
        $this->validateUser();
        return response()->json(['message' => 'ok', 'structures' => Structure::whereRegion($region)->get(), 'code' => 200]);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Effettua Login",
     *     description="Effettua il login restituendo il token",
     *     operationId="loginPost",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *        required=true,
     *        description="Info richieste per il login",
     *        @OA\JsonContent(
     *           required={"email", "password"},
     *           @OA\Property(property="email", type="email", example="marco.predoni@email.it"),
     *           @OA\Property(property="password", type="string", example="secret1234"),
     *        ),
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="Login effettuato con successo",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="token", type="string", example="169|12ls5OwUVOJ3fCQEmdQehejq5QM5PCjFA4HCLSuP"),
     *           @OA\Property(property="patient", type="json", example="{'first_name': 'Marco', ...}"),
     *           @OA\Property(property="reservation", type="json", example="{'date': '2021-09-13', ...}"),
     *           @OA\Property(property="code", type="integer", example="200"),
     *            )
     *         ),
     *     @OA\Response(
     *        response=500,
     *        description="Errore interno del server",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="validation"),
     *           @OA\Property(property="errors", type="json", example="{'email': ['length': 'email troppo lunga']}"),
     *           @OA\Property(property="code", type="integer", example="500"),
     *            )
     *         ),
     *     @OA\Response(
     *        response=401,
     *        description="Email o password errati",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Email o password errati"),
     *           @OA\Property(property="code", type="integer", example="401"),
     *            )
     *         )
     *     )
     * )
     * @param  Request  $request
     * @return JsonResponse
     * @throws ValidationException|AuthorizationException
     */
    public function loginPost(Request $request): JsonResponse
    {
        $email = $request->post("email");
        $password = $request->post("password");

        Validator::validate(['email' => $email, 'password' => $password], [
            'email' => [Rule::exists('users', 'email'), 'required', 'string', 'min:5', 'email'],
            'password' => ['required', 'string', 'min:3']
        ]);

        $user = User::whereEmail($email)->firstOrFail();

        if (!$user->patient()->exists()) {
            throw new AuthorizationException('Utente non autorizzato');
        }

        if (Hash::check($password, $user->password)) {
            try {
                $patientData = $this->getPatientData($email);
                $patient = $user->account->patient;
            } catch (PatientNotFoundException $e) {
                return response()->json(["login" => "error", "message" => "Paziente non trovato", "code" => 500]);
            }

            $this->clearTokens($user);
            $token = $user->createToken('app-token', [$email]);

            return response()->json([
                "message" => "success",
                "login" => "success",
                "token" => $token->plainTextToken,
                "patient" => $patientData,
                "reservation" => $this->getPatientLastReservation($patient),
                "code" => 200
            ]);
        }

        return response()->json(["login" => "error", "message" => "Email o password errati", "code" => 401], 401);
    }

    /**
     * @OA\Get(
     *     path="/get-last-reservation-by-patient-email/{email}",
     *     summary="Prende ultima prenotazione",
     *     description="Prende la prenotazione più recente in base all'email del paziente",
     *     operationId="getLastReservationByPatientEmail",
     *     tags={"reservation"},
     *     security={ {"bearer": {} }},
     *     @OA\Parameter(
     *        description="Email valida di un paziente del quale si vuole prendere la reservation",
     *        in="path",
     *        name="Email Paziente",
     *        required=true,
     *        example="marc.predoni@email.it",
     *        @OA\Schema(
     *           type="email",
     *           format="UTF-8"
     *        )
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="Dati ritirati con successo",
     *        @OA\JsonContent(
     *           @OA\Property(property="reservation", type="json", example="{'date': '2021-06-16', ...}"),
     *           @OA\Property(property="message", type="string", example="ok"),
     *           @OA\Property(property="code", type="integer", example="200"),
     *            )
     *         ),
     *     @OA\Response(
     *        response=500,
     *        description="Errore interno del server",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="validation"),
     *           @OA\Property(property="errors", type="json", example="{'email': ['length': 'email troppo lunga']}"),
     *           @OA\Property(property="code", type="integer", example="500"),
     *            )
     *         ),
     *     @OA\Response(
     *        response=401,
     *        description="Email o password errati",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Email o password errati"),
     *           @OA\Property(property="code", type="integer", example="401"),
     *            )
     *         )
     * )
     * @param  Request  $request
     * @param  string  $email
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws PatientNotFoundException
     */
    public function getLastReservationByPatientEmail(Request $request, string $email): JsonResponse
    {
        $this->validateUser();

        $patient = $this->patientRepository->get($email);

        $token = $this->getTokenByPatientOrFail($patient);
        $this->tokenValidation($patient, $token);

        /** @var Reservation $patientReservation */
        $patientReservation = $patient->reservations()
            ->where('patient_id', '=', $patient->id)
            ->join('patients', 'patients.id', '=', 'reservations.patient_id')
            ->join('stocks', 'stocks.id', '=', 'reservations.stock_id')
            ->join('structures', 'structures.id', '=', 'stocks.structure_id')
            ->join('batches', 'batches.id', '=', 'stocks.batch_id')
            ->join('vaccines', 'vaccines.id', '=', 'batches.vaccine_id')
            ->orderBy('reservations.updated_at', 'desc')
            ->get(['*', 'structures.name as structure_name', 'patients.id as id', 'reservations.updated_at as updated_at'])
            ->first();

        return response()->json(["message" => "ok", 'reservation' => $patientReservation, 'code' => 200]);
    }

    /**
     * @OA\Post(
     *     path="/registerPost",
     *     summary="Effettua Registrazione",
     *     description="Effettua la registrazione del paziente",
     *     operationId="registerPost",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *        required=true,
     *        description="Info richieste per la registrazione",
     *        @OA\JsonContent(
     *           required={"first_name", "last_name", "email", "password", "date_of_birth", "fiscal_code", "mobile_phone", "city", "address", "cap"},
     *           @OA\Property(property="first_name", type="string", example="marco"),
     *           @OA\Property(property="last_name", type="string", example="predoni"),
     *           @OA\Property(property="email", type="email", example="marco.predoni@email.it"),
     *           @OA\Property(property="password", type="string", example="secret1234"),
     *           @OA\Property(property="date_of_birth", type="date:Y-m-d", example="2021-05-16"),
     *           @OA\Property(property="fiscal_code", type="string", example="MRCPRD95F839G"),
     *           @OA\Property(property="mobile_phone", type="string", example="3951302553"),
     *           @OA\Property(property="city", type="string", example="milano"),
     *           @OA\Property(property="address", type="string", example="via napoleone 57"),
     *           @OA\Property(property="cap", type="string", example="80931"),
     *        ),
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="Registrazione effettuata con successo",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="code", type="integer", example="200"),
     *            )
     *         ),
     *     @OA\Response(
     *        response=500,
     *        description="Errore interno del server",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="validation"),
     *           @OA\Property(property="errors", type="json", example="{'email': ['length': 'email troppo lunga']}"),
     *           @OA\Property(property="code", type="integer", example="500"),
     *            )
     *         )
     *     )
     * )
     * @param  Request  $request
     * @return JsonResponse
     * @throws Throwable
     * @throws ValidationException
     */
    public function registerPost(Request $request): JsonResponse
    {
        $this->registrationValidator->validateData($request->toArray());
        $this->patientRepository->saveOrCreate(
            Patient::factory()->make([
                'heart_disease' => $request->post('heart_disease'),
                'allergy' => $request->post('allergy'),
                'immunosuppression' => $request->post('immunosuppression'),
                'anticoagulants' => $request->post('anticoagulants'),
                'covid' => $request->post('covid')
            ]),
            Account::factory()->make([
                'first_name' => $request->post('first_name'),
                'last_name' => $request->post('last_name'),
                'date_of_birth' => $request->post('date_of_birth'),
                'gender' => $request->post('gender'),
                'fiscal_code' => $request->post('fiscal_code'),
                'city' => $request->post('city'),
                'cap' => $request->post('cap'),
                'mobile_phone' => $request->post('mobile_phone'),
                'address' => "somewhere 22",
            ]),
            $request->post("email"),
            $request->post("password")
        );

        return response()->json(["message" => "success", "code" => 200]);
    }

    /**
     * @OA\Post(
     *     path="/reservation",
     *     summary="Crea prenotazione",
     *     description="Crea una prenotazione per il paziente",
     *     operationId="reservationPost",
     *     security={ {"bearer": {} }},
     *     tags={"reservation"},
     *     @OA\RequestBody(
     *        required=true,
     *        description="Info richieste per creare la prenotazione",
     *        @OA\JsonContent(
     *           required={"first_name", "last_name", "email", "password", "date_of_birth", "fiscal_code", "mobile_phone", "city", "address", "cap"},
     *           @OA\Property(property="patient_id", type="integer", example="54"),
     *           @OA\Property(property="structure_id", type="integer", example="2"),
     *           @OA\Property(property="date", type="date:Y-m-d", example="2021-05-16")
     *        ),
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="Prenotazione effettuata con successo",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="code", type="integer", example="200"),
     *            )
     *         ),
     *     @OA\Response(
     *        response=500,
     *        description="Errore interno del server",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="validation"),
     *           @OA\Property(property="errors", type="json", example="{'patient_id': ['exists': 'id paziente non valido']}"),
     *           @OA\Property(property="code", type="integer", example="500"),
     *            )
     *         )
     *     )
     * )
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Throwable
     * @throws ValidationException
     * @throws NoAvailableStockException
     */
    public function reservationPost(Request $request): JsonResponse
    {
        $this->validateUser();

        $patientId = $request->post('patient_id');
        $patient = Patient::whereId($patientId)->firstOrFail();

        $token = $this->getTokenByPatientOrFail($patient);
        $this->tokenValidation($patient, $token);

        $id = $request->post('structure_id');
        $stock_id = Structure::whereId($id)->firstOrFail()->getMaxStock($patient->getAllowedVaccines())->id;

        $this->reservationRepository->createAndStockDecrement(
            Reservation::factory()->make([
                'patient_id' => $patientId,
                'date' => $request->post('date'),
                'stock_id' => $stock_id
            ])
        );

        return response()->json(["message" => "success", "code" => 200]);
    }

    protected function clearTokens(User $user)
    {
        $user->tokens()->where('name', '=', 'app-token')->delete();
    }

    /**
     * @param  Patient  $patient
     * @return array
     * @throws AuthorizationException
     */
    protected function getTokenByPatientOrFail(Patient $patient): array
    {
        $token = $patient->account->user->tokens()->orderBy('created_at')->first();

        if (!$token) {
            throw new AuthorizationException("token non presente 1");
        }

        if (!Arr::get($token->toArray(), 'id')) {
            throw new AuthorizationException("token non presente 2");
        }

        return $token->toArray();
    }

    /**
     * @param  Patient  $patient
     * @param array $token
     * @return JsonResponse|null
     * @throws AuthorizationException
     */
    protected function tokenValidation(Patient $patient, array $token): ?JsonResponse
    {
        if (!in_array($patient->email, Arr::get($token, 'abilities') ?? [])) {
            throw new AuthorizationException("token non presente 3");
        }

        return null;
    }

    /**
     * @param  string  $email
     * @return array
     * @throws PatientNotFoundException
     */
    protected function getPatientData(string $email): array
    {
        $patient = $this->patientRepository->get($email);
        unset($patient['account']);
        return array_merge(
            $patient->account()->firstOrFail()->toArray(),
            $patient->toArray(),
            $patient->account->user->toArray(),
            ['id' => $patient->id]
        );
    }

    /**
     * @param  Patient  $patient
     * @return Reservation|null
     */
    protected function getPatientLastReservation(Patient $patient): ?Reservation
    {
        return $patient->reservations()
            ->where('patient_id', '=', $patient->id)
            ->orderBy('date', 'desc')
            ->join('patients', 'patients.id', '=', 'reservations.patient_id')
            ->join('stocks', 'stocks.id', '=', 'reservations.stock_id')
            ->join('structures', 'structures.id', '=', 'stocks.structure_id')
            ->join('batches', 'batches.id', '=', 'stocks.batch_id')
            ->join('vaccines', 'vaccines.id', '=', 'batches.vaccine_id')
            ->get(['*', 'structures.name as structure_name', 'patients.id as id'])
            ->first();
    }

    /**
     * @throws AuthorizationException
     */
    protected function validateUser()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->patient()->exists()) {
            throw new AuthorizationException('Utente non autorizzato');
        }
    }
}
