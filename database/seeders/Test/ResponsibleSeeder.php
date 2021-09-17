<?php

namespace Database\Seeders\Test;

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
     * @throws Throwable
     */
    public function run()
    {
        $structures = Structure::all();
        foreach ($structures as $structure) {
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
        }
    }
}
