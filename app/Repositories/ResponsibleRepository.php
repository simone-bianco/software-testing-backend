<?php

namespace App\Repositories;

use App\Exceptions\ResponsibleNotFoundException;
use App\Models\Account;
use App\Models\Responsible;
use App\Models\User;
use App\Validators\ResponsibleValidator;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

/**
 * Class ResponsibleRepository
 * @package App\Repositories
 */
class ResponsibleRepository
{
    private ResponsibleValidator $responsibleValidator;
    private AccountRepository $accountRepository;

    public function __construct(
        ResponsibleValidator $responsibleValidator,
        AccountRepository $accountRepository
    ) {
        $this->responsibleValidator = $responsibleValidator;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @param  string  $email
     * @return Responsible
     * @throws ResponsibleNotFoundException
     */
    public function get(string $email): Responsible
    {
        try {
            $responsible = User::where('email', $email)
                ->firstOrFail()
                ->account()
                ->firstOrFail()
                ->responsible()
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new ResponsibleNotFoundException($e->getMessage());
        }

        return $responsible;
    }

    /**
     * @param  Responsible  $responsible
     * @param  Account|null  $account
     * @param  string|null  $email
     * @param  string|null  $password
     * @return Responsible
     * @throws Throwable
     * @throws ValidationException
     */
    public function saveOrCreate(
        Responsible $responsible,
        ?Account $account = null,
        ?string $email = null,
        ?string $password = null
    ): Responsible {
        try {
            DB::beginTransaction();

            if (!$account) {
                if ($responsible->id) {
                    $account = $responsible->account;
                }
            } else {
                $account->id = $account->id ?? $responsible->account_id;
                $account->user_id = $responsible->account_id ?? null;
            }

            $account = $this->accountRepository->saveOrCreate($account, $email, $password);
            $responsible->account_id = $account->id;

            $newResponsible = $this->assignAndSave(
                $responsible->id ? Responsible::findOrFail($responsible->id) : Responsible::factory()->newModel(),
                $responsible
            );

            DB::commit();

            return $newResponsible;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Responsible saveOrCreate Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Responsible saveOrCreate:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Responsible  $responsible
     * @param  Account|null  $account
     * @param  string|null  $email
     * @param  string|null  $password
     * @return Responsible
     * @throws Throwable
     * @throws ValidationException
     */
    public function save(
        Responsible $responsible,
        ?Account $account = null,
        ?string $email = null,
        ?string $password = null
    ): Responsible {
        try {
            DB::beginTransaction();

            $oldResponsible = Responsible::findOrFail($responsible->id);

            if (!$account) {
                $account = $oldResponsible->account;
            }

            $account->id = $responsible->account_id;
            $account->user_id = $responsible->account->user->id;

            $this->accountRepository->save($account, $email, $password);

            $newResponsible = $this->assignAndSave($oldResponsible, $responsible);

            DB::commit();

            return $newResponsible;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Responsible save Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Responsible save:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Responsible  $newResponsible
     * @param  Responsible  $responsible
     * @return Responsible
     * @throws ResponsibleNotFoundException
     * @throws ValidationException
     */
    private function assignAndSave(Responsible $newResponsible, Responsible $responsible): Responsible
    {
        $newResponsible->structure_id = $responsible->structure_id;
        $newResponsible->account_id = $responsible->account_id;

        $this->responsibleValidator->validateData($newResponsible->toArray(), ['responsible' => $newResponsible]);
        $newResponsible->save();

        return $this->get($newResponsible->email);
    }
}
