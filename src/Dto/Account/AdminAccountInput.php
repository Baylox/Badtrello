<?php

namespace App\Dto\Account;

use App\Entity\Account;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;

/**
 * Input DTO for an administrator editing an account.
 */
final class AdminAccountInput
{
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(mode: EmailConstraint::VALIDATION_MODE_STRICT)]
    #[Assert\Length(max: 255)]
    public string $email = '';

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public string $name = '';

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 50)]
    public string $lastname = '';

    public ?string $role = null;

    public static function fromEntity(Account $account): self
    {
        $input = new self();
        $input->email = $account->getEmail() ?? '';
        $input->name = $account->getName() ?? '';
        $input->lastname = $account->getLastname() ?? '';
        $input->role = $account->getRole();

        return $input;
    }
}
