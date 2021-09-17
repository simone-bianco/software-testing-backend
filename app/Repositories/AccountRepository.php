<?php

namespace App\Repositories;

use App\Exceptions\AccountNotFoundException;
use App\Models\Account;
use App\Models\User;
use App\Validators\AccountValidator;
use Database\Factories\UserFactory;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

/**
 * Class AccountRepository
 * @package App\Repositories
 */
class AccountRepository
{
    private AccountValidator $accountValidator;
    private UserRepository $userRepository;

    public function __construct(
        AccountValidator $accountValidator,
        UserRepository $userRepository
    ) {
        $this->accountValidator = $accountValidator;
        $this->userRepository = $userRepository;
    }

    /**
     * @param  string  $fiscalCode
     * @return Account
     * @throws AccountNotFoundException
     */
    public function get(string $fiscalCode): Account
    {
        try {
            /** @var Account $account */
            $account = Account::where('fiscal_code', '=', $fiscalCode)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new AccountNotFoundException($e->getMessage());
        }

        return $account;
    }

    /**
     * @param  Account  $account
     * @param  string|null  $email
     * @param  string|null  $password
     * @return Account
     * @throws Throwable
     * @throws ValidationException
     */
    public function saveOrCreate(Account $account, ?string $email = null, ?string $password = null): Account
    {
        try {
            DB::beginTransaction();

            /** @var User $user */
            $user = UserFactory::new()->make([
                'name' => $account->first_name . ' ' . $account->last_name,
                'email' => $email,
                'password' => $password,
                'id' => $account->user_id ?? null
            ]);

            $user = $this->userRepository->saveOrCreate($user);

            $account->user_id = $user->id;

            $newAccount = $this->assignAndSave(
                $account->id ? Account::findOrFail($account->id) : Account::factory()->newModel(),
                $account
            );

            DB::commit();

            return $newAccount;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Account saveOrCreate Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            Log::error('Data:');
            Log::error(print_r($account->toArray(), true));
            Log::error('$email = ' . $email);
            Log::error('$password = ' . $password);
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Account saveOrCreate:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  Account  $account
     * @param  string|null  $email
     * @param  string|null  $password
     * @return Account
     * @throws Throwable
     * @throws ValidationException
     */
    public function save(Account $account, ?string $email = null, ?string $password = null): Account
    {
        try {
            DB::beginTransaction();

            if (!$email) {
                $email = $account->email;
            }

            $oldAccount = Account::findOrFail($account->id);

            /** @var User $user */
            $user = UserFactory::new()->make([
                'name' => $account->first_name . ' ' . $account->last_name,
                'email' => $email ?? $oldAccount->email,
                'password' => $password,
                'id' => $oldAccount->user->id
            ]);

            $user = $this->userRepository->save($user);
            $account->user_id = $user->id;
            $newAccount = $this->assignAndSave($oldAccount, $account);
            DB::commit();
            
            return $newAccount;
        } catch (ValidationException $validationException) {
            DB::rollBack();
            Log::error("Account save Validation:\n" . $validationException->getMessage());
            Log::error(print_r($validationException->errors(), true));
            throw $validationException;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Account save:\n" . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param Account $newAccount
     * @param Account $account
     * @return Account
     * @throws AccountNotFoundException
     * @throws ValidationException
     */
    private function assignAndSave(Account $newAccount, Account $account): Account
    {
        $newAccount->first_name = $account->first_name;
        $newAccount->last_name = $account->last_name;
        $newAccount->date_of_birth = $account->date_of_birth;
        $newAccount->gender = $account->gender;
        $newAccount->fiscal_code = $account->fiscal_code;
        $newAccount->city = $account->city;
        $newAccount->address = $account->address;
        $newAccount->cap = $account->cap;
        $newAccount->mobile_phone = $account->mobile_phone;
        $newAccount->user_id = $account->user_id;

        $this->accountValidator->validateData($newAccount->toArray(), ['account' => $newAccount]);

        $newAccount->save();

        return $this->get($newAccount->fiscal_code);
    }
}
