<?php

namespace App\Service\Account;

use App\Dto\Account\RegistrationInput;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application service owning user self-registration.
 */
final class RegistrationService
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EmailVerifier $emailVerifier,
    ) {
    }

    /**
     * Create and persist a new account from the registration input,
     * then send the email confirmation link.
     */
    public function register(RegistrationInput $input): Account
    {
        $account = new Account();
        $account->setName($input->name);
        $account->setLastname($input->lastname);
        $account->setEmail($input->email);
        $account->setPassword(
            $this->passwordHasher->hashPassword($account, (string) $input->plainPassword)
        );

        $this->accounts->save($account);

        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $account,
            (new TemplatedEmail())
                ->from(new Address('register@taskio.com', 'Taskio'))
                ->to((string) $account->getEmail())
                ->subject('Just one more step to join Taskio !')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        return $account;
    }
}
