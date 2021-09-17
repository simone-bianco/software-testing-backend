<?php

namespace App\Repositories;

use App\Exceptions\UserNotFoundException;
use App\Models\User;
use App\Validators\UserValidator;
use Database\Factories\UserFactory;
use Exception;
use Hash;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Log;

/**
 * Class UserRepository
 * @package App\Repositories
 */
class UserRepository
{
    private UserValidator $userValidator;

    public function __construct(
        UserValidator $userValidator
    ) {
        $this->userValidator = $userValidator;
    }

    /**
     * @param string $email
     * @return User
     * @throws UserNotFoundException
     */
    public function get(string $email): User
    {
        try {
            $user = User::where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new UserNotFoundException($e->getMessage());
        }

        return $user;
    }


    /**
     * @param User $user
     * @param string $password
     * @return User
     * @throws Exception
     */
    public function firstLoginPasswordChange(User $user, string $password): User
    {
        try {
            $user->password = $password;
            return $this->save($user);
        } catch (Exception $exception) {
            Log::error("UserRepository firstLoginPasswordChange Exception:\n" . $exception->getMessage());
            Log::error($exception->getTraceAsString());
            throw $exception;
        }
    }

    /**
     * @param User $user
     * @return User
     * @throws Exception
     */
    public function saveOrCreate(User $user): User
    {
        try {
            /** @var User $newUser */
            $newUser = $user->id ? User::findOrFail($user->id) : UserFactory::new()->newModel();
            return $this->assignAndSave($newUser, $user);
        } catch (Exception $exception) {
            Log::error('UserRepository saveOrCreate: ' . $exception->getMessage());
            Log::error($exception->getTraceAsString());
            throw $exception;
        }
    }

    /**
     * @param User $user
     * @return User
     * @throws UserNotFoundException
     * @throws ValidationException
     */
    public function save(User $user): User
    {
        return $this->assignAndSave(User::findOrFail($user->id), $user);
    }

    /**
     * @param User $newUser
     * @param User $user
     * @return User
     * @throws UserNotFoundException
     * @throws ValidationException
     */
    private function assignAndSave(User $newUser, User $user): User
    {
        $newUser->email = $user->email;
        $newUser->name = $user->name;

        if ($user->password && is_string($user->password)) {
            $newUser->password = Hash::make($user->password);
        }

        $this->userValidator->validateData(
            array_merge($newUser->toArray(), ['password' => $user->password]), ['user' => $newUser]
        );
        $newUser->save();

        return $this->get($newUser->email);
    }
}
