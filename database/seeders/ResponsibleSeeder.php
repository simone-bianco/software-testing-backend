<?php

namespace Database\Seeders;

use App\Helper\Generator\AccountGenerator;
use App\Helper\Generator\EmailGenerator;
use App\Models\Account;
use App\Models\Structure;
use App\Repositories\ResponsibleRepository;
use Arr;
use Database\Factories\ResponsibleFactory;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

class ResponsibleSeeder extends Seeder
{
    protected AccountGenerator $accountGenerator;
    protected ResponsibleRepository $responsibleRepository;
    protected EmailGenerator $emailGenerator;

    /**
     * ResponsiblesSeeder constructor.
     * @param  AccountGenerator  $accountGenerator
     * @param  EmailGenerator  $emailGenerator
     * @param  ResponsibleRepository  $responsibleRepository
     */
    public function __construct(
        AccountGenerator $accountGenerator,
        EmailGenerator $emailGenerator,
        ResponsibleRepository $responsibleRepository
    ) {
        $this->accountGenerator = $accountGenerator;
        $this->emailGenerator = $emailGenerator;
        $this->responsibleRepository = $responsibleRepository;
    }

    /**
     * @param  int  $minNumberOfResponsibles
     * @param  int  $maxNumberOfResponsibles
     * @throws Exception
     */
    public function run(int $minNumberOfResponsibles = 5, int $maxNumberOfResponsibles = 10)
    {
        $structures = Structure::all();
        foreach ($structures as $structure) {
            $numberOfResponsibles = random_int($minNumberOfResponsibles, $maxNumberOfResponsibles);
            for ($i = 0; $i < $numberOfResponsibles; $i++) {
                try {
                    $accountAttributes = $this->accountGenerator->generateAccountAttributes();

                    $this->responsibleRepository->saveOrCreate(
                        ResponsibleFactory::new()->make(['structure_id' => $structure->id]),
                        Account::factory()->make($accountAttributes),
                        $this->emailGenerator->generateEmail(
                            Arr::get($accountAttributes, 'first_name'),
                            Arr::get($accountAttributes, 'last_name'),
                        ),
                        'test'
                    );
                } catch (ValidationException $validationException) {
                    var_dump($validationException->getMessage());
                    Log::error(print_r($validationException->errorBag, true));
                } catch (Throwable $exception) {
                    var_dump($exception->getMessage());
                    Log::error($exception->getMessage());
                }
            }
        }
    }
}
