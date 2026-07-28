<?php

namespace App\Dto\Lane;

use App\Entity\Lane;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO carrying the writable data of a Lane.
 *
 * Forms map to this DTO; LaneService turns it into a persisted Lane.
 * Position is managed by the service, never by the client.
 */
final class LaneInput
{
    #[Assert\NotBlank(message: 'The title field cannot be empty.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Title must be at most {{ limit }} characters.'
    )]
    public string $title = '';

    public static function fromEntity(Lane $lane): self
    {
        $input = new self();
        $input->title = $lane->getTitle() ?? '';

        return $input;
    }
}
