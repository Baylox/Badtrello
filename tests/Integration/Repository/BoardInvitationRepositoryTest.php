<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Board;
use App\Entity\BoardInvitation;
use App\Factory\BoardFactory;
use App\Repository\BoardInvitationRepository;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises BoardInvitationRepository queries against a real database.
 *
 * There is no BoardInvitation factory: invitations are built by hand and
 * persisted through the repository. The entity constructor generates the
 * token and a "+7 days" expiry, so a freshly built invitation is pending.
 */
#[CoversClass(BoardInvitationRepository::class)]
final class BoardInvitationRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private BoardInvitationRepository $repository;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get(BoardInvitationRepository::class);
    }

    /**
     * Build a pending invitation for the given board (not persisted yet).
     */
    private function makeInvitation(Board $board, string $email): BoardInvitation
    {
        $invitation = new BoardInvitation();
        $invitation->setBoard($board);
        $invitation->setEmail($email);
        $invitation->setInvitedBy($board->getOwner());

        return $invitation;
    }

    public function testFindPendingByEmailAndBoardReturnsPendingInvitation(): void
    {
        $board = BoardFactory::createOne()->_real();

        $invitation = $this->makeInvitation($board, 'invitee@example.com');
        $this->repository->save($invitation);

        $found = $this->repository->findPendingByEmailAndBoard('invitee@example.com', $board);

        $this->assertNotNull($found);
        $this->assertSame($invitation->getId(), $found->getId());
    }

    public function testFindPendingByEmailAndBoardNormalizesTheSearchedEmail(): void
    {
        $board = BoardFactory::createOne()->_real();

        $invitation = $this->makeInvitation($board, 'invitee@example.com');
        $this->repository->save($invitation);

        // The repository lowercases and trims the searched email before comparing.
        $found = $this->repository->findPendingByEmailAndBoard('  INVITEE@Example.COM  ', $board);

        $this->assertNotNull($found);
        $this->assertSame($invitation->getId(), $found->getId());
    }

    public function testFindPendingByEmailAndBoardIgnoresAcceptedInvitations(): void
    {
        $board = BoardFactory::createOne()->_real();

        $invitation = $this->makeInvitation($board, 'invitee@example.com');
        $invitation->setIsAccepted(true);
        $this->repository->save($invitation);

        $this->assertNull($this->repository->findPendingByEmailAndBoard('invitee@example.com', $board));
    }

    public function testFindPendingByEmailAndBoardIgnoresExpiredInvitations(): void
    {
        $board = BoardFactory::createOne()->_real();

        $invitation = $this->makeInvitation($board, 'invitee@example.com');
        $invitation->setExpiresAt(new DateTimeImmutable('-1 hour'));
        $this->repository->save($invitation);

        $this->assertNull($this->repository->findPendingByEmailAndBoard('invitee@example.com', $board));
    }

    public function testFindPendingByEmailAndBoardIsScopedToEmailAndBoard(): void
    {
        $board = BoardFactory::createOne()->_real();
        $otherBoard = BoardFactory::createOne()->_real();

        $this->repository->save($this->makeInvitation($board, 'invitee@example.com'));

        // Same board, different email: no match.
        $this->assertNull($this->repository->findPendingByEmailAndBoard('someone.else@example.com', $board));
        // Same email, different board: no match.
        $this->assertNull($this->repository->findPendingByEmailAndBoard('invitee@example.com', $otherBoard));
    }

    public function testSavePersistsInvitationWithConstructorDefaults(): void
    {
        $board = BoardFactory::createOne()->_real();

        $invitation = $this->makeInvitation($board, 'invitee@example.com');
        $this->repository->save($invitation);

        $this->assertNotNull($invitation->getId());
        $this->assertSame(64, strlen((string) $invitation->getToken()));
        $this->assertFalse($invitation->isAccepted());
        $this->assertNull($invitation->getAcceptedAt());
        $this->assertNotNull($invitation->getCreatedAt());
        $this->assertGreaterThan(new DateTimeImmutable(), $invitation->getExpiresAt());
    }

    public function testRemoveDeletesInvitation(): void
    {
        $board = BoardFactory::createOne()->_real();

        $invitation = $this->makeInvitation($board, 'invitee@example.com');
        $this->repository->save($invitation);

        $id = $invitation->getId();
        $this->assertNotNull($id);

        $this->repository->remove($invitation);

        $this->assertNull($this->repository->find($id));
    }
}
