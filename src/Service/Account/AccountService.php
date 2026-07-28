<?php

namespace App\Service\Account;

use App\Dto\Account\AdminAccountInput;
use App\Dto\Account\ProfileInput;
use App\Entity\Account;
use App\Repository\AccountRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application service owning the Account write use-cases (profile, admin, password).
 */
final class AccountService
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Update the profile of the given account. Password is changed only when provided.
     */
    public function updateProfile(Account $account, ProfileInput $input): void
    {
        $account->setName($input->name);
        $account->setLastname($input->lastname);

        if ($input->plainPassword) {
            $this->hashPasswordInto($account, $input->plainPassword);
        }

        $this->accounts->save($account);
    }

    /**
     * Update an account from the admin panel.
     */
    public function adminUpdate(Account $account, AdminAccountInput $input): void
    {
        $account->setEmail($input->email);
        $account->setName($input->name);
        $account->setLastname($input->lastname);

        if ($input->role) {
            $account->setRole($input->role);
        }

        $this->accounts->save($account);
    }

    /**
     * Hash and set a new password, then persist.
     */
    public function changePassword(Account $account, string $plainPassword): void
    {
        $this->hashPasswordInto($account, $plainPassword);

        $this->accounts->save($account);
    }

    public function delete(Account $account): void
    {
        $this->accounts->remove($account);
    }

    private function hashPasswordInto(Account $account, string $plainPassword): void
    {
        $account->setPassword($this->passwordHasher->hashPassword($account, $plainPassword));
    }
}
