<?php

namespace App\Dto\Account;

use App\Entity\Account;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;

/**
 * Input DTO for user self-registration.
 */
#[UniqueEntity(
    fields: ['email'],
    entityClass: Account::class,
    message: 'This email is already used.'
)]
final class RegistrationInput
{
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public string $name = '';

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public string $lastname = '';

    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(mode: EmailConstraint::VALIDATION_MODE_STRICT)]
    #[Assert\Length(max: 255)]
    public string $email = '';

    #[Assert\NotBlank(message: 'Please enter a password')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        message: 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character (@$!%*?&)'
    )]
    #[Assert\NotCompromisedPassword(
        message: 'This password has been compromised in a data breach. Please choose a different one.'
    )]
    public ?string $plainPassword = null;

    #[Assert\IsTrue(message: 'You should agree to our terms.')]
    public bool $agreeTerms = false;
}
