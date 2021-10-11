<?php

namespace Tests\Feature\Blackbox;

use App\Models\Patient;
use App\Models\Reservation;
use App\Models\Responsible;
use App\Models\Stock;
use App\Models\Structure;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Tests\ReservationTestCase;
use Throwable;

/**
 * Test per verificare il funzionamento di ciascuna operazione che l'operatore sanitario può effettuare sulla
 * prenotazione
 * @covers \App\Http\Controllers\ReservationController
 * @covers \App\Repositories\ReservationRepository
 * @covers \App\Models\Reservation
 * @covers \App\Models\Stock
 */
final class ResponsibleHandleReservationTest extends ReservationTestCase
{
    protected ?Structure $structure = null;
    protected ?Responsible $responsible = null;
    protected ?Patient $patient = null;
    protected ?Reservation $reservation = null;

    /**
     * @throws Throwable
     */
    public function setUp() : void
    {
        parent::setUp();
        $this->structure = Structure::first();
        /** @var Responsible $responsible */
        $responsible = $this->structure->responsibles()->first();
        $this->responsible = $responsible;
        $this->patient = Patient::first();
        $this->reservation = $this->createReservation($this->structure, $this->patient);
        /** Login */
        //Devo loggare come responsabile sanitario, prendo l'user associato all'unico responsabile memorizzato
        $user = $this->responsible->account->user;
        //Verico che l'utente esista
        $this->assertNotNull($user);
        //Simulo il login tramite l'utente
        $this->be($user);
        //Il responsabile sanitario deve necessariamente eseguire l'autenticazione tramite 2fa
        //TODO::forse sarebbe meglio simulare per bene anche questo
        Session::put('2fa', true);
    }

    protected function assertPreConditions(): void
    {
        parent::assertPreConditions();
        //Mi assicuro che i dati siano stati caricati correttamente
        $this->assertNotNull($this->structure);
        $this->assertNotNull($this->responsible);
        $this->assertNotNull($this->patient);
        $this->assertNotNull($this->reservation);
        //Mi assicuro che l'utente loggato nella sessione sia quello giusto
        $this->assertAuthenticatedAs($this->responsible->account->user);
        //Mi assicuro che la 2FA sia stata effettuata
        $this->assertTrue(Session::get('2fa'));
    }

    /**
     * @throws Throwable
     */
    public function tearDown(): void
    {
        parent::tearDown();
        //Resetto i dati caricati
        $this->structure = null;
        $this->responsible = null;
        $this->patient = null;
        $this->reservation = null;
        //Resetto la sessione
        Session::flush();
    }

    /**
     * Test per verificare la funzionalità tramite la quale un responsabile sanitario accetta la prenotazione da parte
     * di un paziente
     * @group reservation
     * @group responsible
     * @group blackbox
     * @throws Throwable
     */
    public function testAcceptReservation()
    {
        //Effettuo la richiesta al controller della prenotazione. Il vaccino scelto non è cambiato, è lo stesso che era stato
        //assegnato dal sistema, mentre l'operazione selezionata è "conferma"
        $response = $this->call(
            'PUT',
            "/prenotazione/{$this->reservation->id}/update",
            array_merge(
                $this->reservation->toArray(),
                ['vaccine' => $this->reservation->stock->batch->vaccine->name, 'state' => Reservation::CONFIRMED_STATE]
            ));
        $response->assertRedirect(route('reservations.index'));

        //Prelevo la prenotazione aggiornata
        $updatedReservation = $this->reservationRepository->get($this->reservation->code);

        //Verifico le post condizioni
        /** Post Conditions */
        $this->stockIsSameAfterConfirm($this->reservation->stock);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $updatedReservation->state);
        $this->reservationsAreEqual($this->reservation, $updatedReservation);
        $this->assertEquals($this->reservation->stock_id, $updatedReservation->stock_id);
    }

    /**
     * @group reservation
     * @group responsible
     * @group blackbox
     * @throws Throwable
     */
    public function testAcceptReservationAndChangeVaccine()
    {
        $anotherVaccine = Vaccine::where('id', '!=', $this->reservation->stock->batch->vaccine->id)->first();
        $this->assertNotNull($anotherVaccine);

        /** @var Stock $differentAvailableStock */
        $differentAvailableStock = $this->structure->getMaxStock([$anotherVaccine]);
        $this->assertNotNull($differentAvailableStock);
        $differentAvailableStockQty = $differentAvailableStock->quantity;
        $this->assertGreaterThanOrEqual(1, $differentAvailableStockQty);

        $response = $this->call(
            'PUT',
            route('reservations.update', $this->reservation->id),
            array_merge(
                $this->reservation->toArray(),
                ['vaccine' => $anotherVaccine->name, 'state' => Reservation::CONFIRMED_STATE]
            ));
        $response->assertRedirect(route('reservations.index'));

        $updatedReservation = $this->reservationRepository->get($this->reservation->code);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $updatedReservation->state);
        $this->assertEquals($differentAvailableStock->batch->vaccine->name, $updatedReservation->stock->batch->vaccine->name);
        $this->reservationsAreEqual($this->reservation, $updatedReservation);
        $this->assertEquals($differentAvailableStock->id, $updatedReservation->stock_id);

        $updatedDifferentAvailableStock = Stock::findOrFail($differentAvailableStock->id);
        $updatedStock = Stock::findOrFail($this->reservation->stock->id);
        $this->assertEquals($differentAvailableStockQty - 1, $updatedDifferentAvailableStock->quantity);
        $this->assertEquals($this->reservation->stock->quantity + 1, $updatedStock->quantity);
    }

    /**
     * @group reservation
     * @group responsible
     * @group blackbox
     * @throws Throwable
     */
    public function testCompleteReservationAndCreateRecall()
    {
        //Accetta reservation
        $response = $this->call(
            'PUT',
            "/prenotazione/{$this->reservation->id}/update",
            array_merge(
                $this->reservation->toArray(),
                ['vaccine' => $this->reservation->stock->batch->vaccine->name, 'state' => Reservation::CONFIRMED_STATE]
            ));

        //Verifico che la prenotazione è stata confermata con successo
        $confirmedReservation = Reservation::whereId($this->reservation->id)->first();
        $this->assertNotNull($confirmedReservation);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $confirmedReservation->state);

        //Completa reservation
        $response->assertStatus(Response::HTTP_FOUND);
        $response = $this->call(
            'PUT',
            "/prenotazione/{$this->reservation->id}/update",
            array_merge(
                $this->reservation->toArray(),
                ['vaccine' => $this->reservation->stock->batch->vaccine->name, 'state' => Reservation::COMPLETED_STATE]
            ));
        $response->assertRedirect(route('reservations.edit', $this->reservation->id));

        $updatedReservation = $this->reservationRepository->get($this->reservation->code);
        $this->assertEquals(Reservation::COMPLETED_STATE, $updatedReservation->state);
        $this->reservationsAreEqual($this->reservation, $updatedReservation);
        $this->assertEquals($this->reservation->stock_id, $updatedReservation->stock_id);
        $this->stockIsSameAfterComplete($this->reservation->stock);

        /** @var Vaccine $recallVaccine */
        $recallVaccine = $this->structure->stocks()->where('quantity', '>', 0)->firstOrFail()->batch->vaccine;
        $recallStock = $this->structure->getMaxStock([$recallVaccine]);

        //Crea recall
        $response = $this->call(
            'POST',
            "/prenotazione/salva",
            [
                'patient' => $this->patient->id,
                'date' => Carbon::now()->addDays(100),
                'time' => '12:00',
                'vaccine' => $recallVaccine->name,
                'structure' => $this->structure->name
            ]);
        $response->assertRedirect(route('reservations.index'));

        $recall = Reservation::where('patient_id', '=', $this->patient->id)
            ->orderBy('date', 'desc')
            ->first();
        $updatedRecallStock = Stock::whereId($recallStock->id)->first();

        /** Post Condizioni */
        $this->assertNotNull($recall);
        $this->assertEquals(Reservation::CONFIRMED_STATE, $recall->state);
        $this->assertEquals($recallVaccine->name, $recall->stock->batch->vaccine->name);
        $this->assertEquals($updatedReservation->patient_id, $recall->patient_id);
        $this->assertEquals($recallStock->id, $recall->stock_id);
        $this->assertEquals("12:00", $recall->time->format('H:i'));
        $this->assertEquals(Carbon::now()->addDays(100)->format('Y-m-d'), $recall->date->format('Y-m-d'));
        $this->assertEquals($recallStock->quantity - 1, $updatedRecallStock->quantity);
    }

    /**
     * @group reservation
     * @group responsible
     * @group blackbox
     * @throws Throwable
     */
    public function testRefuseReservation()
    {
        $response = $this->call(
            'PUT',
            "/prenotazione/{$this->reservation->id}/update",
            array_merge(
                $this->reservation->toArray(),
                ['vaccine' => $this->reservation->stock->batch->vaccine->name, 'state' => Reservation::CANCELED_STATE]
            ));
        $response->assertRedirect(route('reservations.index'));

        $updatedReservation = $this->reservationRepository->get($this->reservation->code);

        /** Post Condizioni */
        $this->assertEquals(Reservation::CANCELED_STATE, $updatedReservation->state);
        $this->reservationsAreEqual($this->reservation, $updatedReservation);
        $this->assertEquals($this->reservation->stock_id, $updatedReservation->stock_id);
        $this->stockIsUpdatedAfterCancel($this->reservation->stock);
    }

    protected function reservationsAreEqual(Reservation $reservation, Reservation $updatedReservation)
    {
        $this->assertEquals($reservation->id, $updatedReservation->id);
        $this->assertEquals($reservation->patient_id, $updatedReservation->patient_id);
        $this->assertEquals($reservation->date->format('Y-m-d'), $updatedReservation->date->format('Y-m-d'));
        $this->assertEquals($reservation->time, $updatedReservation->time);
    }

    /**
     * Chiama semplicemente il repository, non simula la creazione di una reservation da parte di un paziente
     * (non chiama le API)
     * @param  Structure  $structure
     * @param  Patient  $patient
     * @return Reservation
     * @throws Throwable
     * @throws ValidationException
     */
    protected function createReservation(Structure $structure, Patient $patient): Reservation
    {
        /** @var Stock $availableStock */
        $availableStock = $structure->stocks()->where('quantity', '>', 0)->first();
        $this->assertNotNull($availableStock);

        return $this->reservationRepository->createAndStockDecrement(
            Reservation::factory()->make([
                'date' => Carbon::now()->addDay(),
                'patient_id' => $patient->id,
                'stock_id' => $availableStock->id
            ])
        );
    }

    protected function stockIsSameAfterConfirm(Stock $stock)
    {
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($stock->quantity, $updatedStock->quantity);
    }

    protected function stockIsSameAfterComplete(Stock $stock)
    {
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($stock->quantity, $updatedStock->quantity);
    }

    protected function stockIsUpdatedAfterCancel(Stock $stock)
    {
        $updatedStock = Stock::findOrFail($stock->id);
        $this->assertEquals($stock->quantity + 1, $updatedStock->quantity);
    }
}
