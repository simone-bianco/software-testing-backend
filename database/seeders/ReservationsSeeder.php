<?php

namespace Database\Seeders;

use App\Helper\PatientCreator;
use App\Models\Reservation;
use App\Models\Structure;
use App\Repositories\ReservationRepository;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Seeder;
use Log;
use Throwable;

class ReservationsSeeder extends Seeder
{
    protected ReservationRepository $reservationRepository;
    protected PatientCreator $patientCreator;

    /**
     * ReservationsSeeder constructor.
     * @param  ReservationRepository  $reservationRepository
     * @param  PatientCreator  $patientCreator
     */
    public function __construct(
        ReservationRepository $reservationRepository,
        PatientCreator $patientCreator
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->patientCreator = $patientCreator;
    }

    public function run(int $minPerStock = 0, int $maxPerStock = 100)
    {
        $structures = Structure::all();

        foreach ($structures as $structure) {
            $stocks = $structure->stocks;
            foreach ($stocks as $stock) {
                try {
                    $numberOfReservations = 24;
                    for ($i = 0; $i < $numberOfReservations; $i++) {
                        DB::beginTransaction();
                        $newPatient = $this->patientCreator->execute();

                        $date = Carbon::now()->subDays(random_int(0, 30) - 15);
//                        $date = Carbon::make("2021-09-15");

                        $reservation = $this->reservationRepository->createAndStockDecrement(
                            Reservation::factory()->make([
                                'date' => $date,
                                'notes' => 'test',
                                'patient_id' => $newPatient->id,
                                'stock_id' => $stock->id
                            ])
                        );

                        if ($date->greaterThanOrEqualTo(Carbon::now())) {
                            if (random_int(0, 100) >= 50) {
                                $this->reservationRepository->confirmAndSave($reservation, 'test');
                            }
                        } else {
                            if (random_int(0, 100) >= 90) {
                                $this->reservationRepository->cancelAndStockIncrement($reservation, 'test');
                            } elseif (random_int(0, 100) >= 80) {
                                $this->reservationRepository->completeAndSave($reservation);
                            }
                        }
                        DB::commit();

                        if (!$stock->quantity) {
                            break;
                        }
                    }
                } catch (Exception | Throwable $e) {
                    DB::rollBack();
                    Log::info(sprintf("ReservationSeeder exception: %s", print_r($e->getMessage(), true)));
                }
            }
        }
    }
}
