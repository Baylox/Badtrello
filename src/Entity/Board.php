<?php

namespace App\Entity;

use App\Repository\BoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: BoardRepository::class)]
// #[Broadcast]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $title = null;

    /**
     * @var Collection<int, Account>
     */
    #[ORM\ManyToMany(targetEntity: Account::class, mappedBy: 'boards')]
    private Collection $accounts;


    #[ORM\ManyToOne(inversedBy: 'ownedBoards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $owner = null;

    /**
     * @var Collection<int, Lane>
     */
    #[ORM\OneToMany(mappedBy: 'board', targetEntity: Lane::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lanes;


    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->lanes = new ArrayCollection();
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

    /**
     * @return Collection<int, Account>
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function addAccount(Account $account): static
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
            $account->addBoard($this);
        }

        return $this;
    }

    public function removeAccount(Account $account): static
    {
        if ($this->accounts->removeElement($account)) {
            $account->removeBoard($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Lane>
     */
    public function getLanes(): Collection
    {
        return $this->lanes;
    }

    public function addLane(Lane $lane): static
    {
        if (!$this->lanes->contains($lane)) {
            $this->lanes->add($lane);
            $lane->setBoard($this);
        }

        return $this;
    }

    public function removeLane(Lane $lane): static
    {
        if ($this->lanes->removeElement($lane)) {
            // set the owning side to null (unless already changed)
            if ($lane->getBoard() === $this) {
                $lane->setBoard(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?Account
    {
        return $this->owner;
    }

    public function setOwner(?Account $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
