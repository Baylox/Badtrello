<?php

namespace App\Dto\Board;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for inviting a collaborator to a board by email.
 */
final class InvitationInput
{
    #[Assert\NotBlank(message: 'Please enter an email address.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    public string $email = '';
}
