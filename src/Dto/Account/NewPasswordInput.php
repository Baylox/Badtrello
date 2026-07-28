<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for choosing a new password (reset flow).
 */
final class NewPasswordInput
{
    #[Assert\NotBlank(message: 'Please enter a password')]
    #[Assert\Length(
        min: 12,
        max: 4096,
        minMessage: 'Your password should be at least {{ limit }} characters'
    )]
    #[Assert\PasswordStrength]
    #[Assert\NotCompromisedPassword]
    public ?string $plainPassword = null;
}
