<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for requesting a password reset email.
 */
final class PasswordResetRequestInput
{
    #[Assert\NotBlank(message: 'Please enter your email')]
    #[Assert\Email]
    public string $email = '';
}
