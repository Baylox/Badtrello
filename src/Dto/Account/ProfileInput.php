<?php

namespace App\Dto\Account;

use App\Entity\Account;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for a user editing their own profile.
 *
 * The password is optional: when left blank the current password is kept.
 */
final class ProfileInput
{
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'First name must be at least {{ limit }} characters long.'
    )]
    public string $name = '';

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Last name must be at least {{ limit }} characters long.'
    )]
    public string $lastname = '';

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

    public static function fromEntity(Account $account): self
    {
        $input = new self();
        $input->name = $account->getName() ?? '';
        $input->lastname = $account->getLastname() ?? '';

        return $input;
    }
}
