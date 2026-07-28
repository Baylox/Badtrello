<?php

namespace App\Entity;

use App\Repository\LaneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LaneRepository::class)]
#[ORM\Table(
    name: 'lane',
    uniqueConstraints: [new ORM\UniqueConstraint(
        name: 'uniq_lane_board_position',
        columns: ['board_id', 'position']
    )],
    indexes: [new ORM\Index(columns: ['board_id', 'position'])]
)]
// #[Broadcast]
class Lane
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'lanes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Board $board = null;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(1)]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $position = null;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'lane')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
    }

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
    public function getBoard(): ?Board
    {
        return $this->board;
    }

    public function setBoard(?Board $board): static
    {
        $this->board = $board;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setLane($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getLane() === $this) {
                $card->setLane(null);
            }
        }

        return $this;
    }
    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }
}
