<?php

namespace App\Entity;

use App\Enum\CardStatus;
use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(
    name: 'card',
    uniqueConstraints: [new ORM\UniqueConstraint(
        name: 'uniq_card_lane_position',
        columns: ['lane_id', 'position']
    )]
)]
// #[Broadcast]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: CardStatus::class, nullable: true)]
    private ?CardStatus $status = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ?Lane $lane = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?CardStatus
    {
        return $this->status;
    }

    public function setStatus(?CardStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLane(): ?Lane
    {
        return $this->lane;
    }

    public function setLane(?Lane $lane): static
    {
        $this->lane = $lane;

        return $this;
    }

    /**
     * Get the value of position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set the value of position
     *
     * @return  self
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }
}
