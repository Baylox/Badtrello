<?php

namespace App\Tests\Unit\Service\Board;

use App\Entity\Account;
use App\Entity\Board;
use App\Entity\BoardInvitation;
use App\Repository\AccountRepository;
use App\Repository\BoardInvitationRepository;
use App\Service\Board\BoardInvitationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(BoardInvitationService::class)]
final class BoardInvitationServiceTest extends TestCase
{
    private const ACCEPT_URL = 'https://taskio.test/invitations/accept';

    // --- canInvite ---

    public function testCanInviteReturnsFalseWhenPendingInvitationExists(): void
    {
        $board = $this->createBoard();

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->expects($this->once())
            ->method('findPendingByEmailAndBoard')
            ->with('invitee@example.com', $board)
            ->willReturn(new BoardInvitation());

        // The account lookup must be short-circuited by the pending invitation.
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->expects($this->never())->method('findOneBy');

        $service = $this->createService(
            accountRepository: $accountRepository,
            invitationRepository: $invitationRepository,
        );

        $this->assertFalse($service->canInvite('invitee@example.com', $board));
    }

    public function testCanInviteReturnsFalseWhenAccountIsBoardOwner(): void
    {
        $owner = $this->createAccount('owner@example.com');
        $board = $this->createBoard()->setOwner($owner);

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('findPendingByEmailAndBoard')->willReturn(null);

        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'owner@example.com'])
            ->willReturn($owner);

        $service = $this->createService(
            accountRepository: $accountRepository,
            invitationRepository: $invitationRepository,
        );

        $this->assertFalse($service->canInvite('owner@example.com', $board));
    }

    public function testCanInviteReturnsFalseWhenAccountIsAlreadyCollaborator(): void
    {
        $member = $this->createAccount('member@example.com');
        $board = $this->createBoard();
        $board->addAccount($member);

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('findPendingByEmailAndBoard')->willReturn(null);

        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('findOneBy')->willReturn($member);

        $service = $this->createService(
            accountRepository: $accountRepository,
            invitationRepository: $invitationRepository,
        );

        $this->assertFalse($service->canInvite('member@example.com', $board));
    }

    public function testCanInviteReturnsTrueWhenAccountIsNeitherOwnerNorCollaborator(): void
    {
        $stranger = $this->createAccount('stranger@example.com');
        $board = $this->createBoard()->setOwner($this->createAccount('owner@example.com'));

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('findPendingByEmailAndBoard')->willReturn(null);

        // The account exists but is not related to the board in any way.
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('findOneBy')->willReturn($stranger);

        $service = $this->createService(
            accountRepository: $accountRepository,
            invitationRepository: $invitationRepository,
        );

        $this->assertTrue($service->canInvite('stranger@example.com', $board));
    }

    // --- createInvitation ---

    public function testCreateInvitationPersistsAndReturnsConfiguredInvitation(): void
    {
        $board = $this->createBoard();
        $inviter = $this->createAccount('john.doe@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(BoardInvitation::class));

        $service = $this->createService(invitationRepository: $invitationRepository);

        $invitation = $service->createInvitation('invitee@example.com', $board, $inviter);

        $this->assertSame($board, $invitation->getBoard());
        $this->assertSame('invitee@example.com', $invitation->getEmail());
        $this->assertSame($inviter, $invitation->getInvitedBy());
        // Token and expiry are initialized by the entity constructor.
        $this->assertNotEmpty($invitation->getToken());
        $this->assertGreaterThan(new \DateTimeImmutable(), $invitation->getExpiresAt());
    }

    // --- acceptInvitation ---

    public function testAcceptInvitationAddsAccountToBoardAndMarksAccepted(): void
    {
        $board = $this->createBoard();
        $user = $this->createAccount('new.member@example.com');
        $invitation = $this->createInvitationFor($board, 'new.member@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->expects($this->once())->method('save')->with($invitation);

        $service = $this->createService(invitationRepository: $invitationRepository);

        $service->acceptInvitation($invitation, $user);

        $this->assertTrue($board->getAccounts()->contains($user));
        $this->assertTrue($invitation->isAccepted());
    }

    public function testAcceptInvitationDoesNotAddAccountTwiceButStillMarksAccepted(): void
    {
        $board = $this->createBoard();
        $user = $this->createAccount('already.member@example.com');
        $board->addAccount($user);
        $invitation = $this->createInvitationFor($board, 'already.member@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->expects($this->once())->method('save')->with($invitation);

        $service = $this->createService(invitationRepository: $invitationRepository);

        $service->acceptInvitation($invitation, $user);

        $this->assertCount(1, $board->getAccounts());
        $this->assertTrue($invitation->isAccepted());
    }

    // --- cancelInvitation / cancelInvitationById ---

    public function testCancelInvitationDelegatesToRepositoryRemove(): void
    {
        $invitation = $this->createInvitationFor($this->createBoard(), 'invitee@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->expects($this->once())->method('remove')->with($invitation);

        $service = $this->createService(invitationRepository: $invitationRepository);

        $service->cancelInvitation($invitation);
    }

    public function testCancelInvitationByIdThrowsWhenInvitationIsUnknown(): void
    {
        $board = $this->createBoard();

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('find')->with(999)->willReturn(null);
        $invitationRepository->expects($this->never())->method('remove');

        $service = $this->createService(invitationRepository: $invitationRepository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid invitation');

        $service->cancelInvitationById(999, $board);
    }

    public function testCancelInvitationByIdThrowsWhenInvitationBelongsToAnotherBoard(): void
    {
        $ownBoard = $this->createBoard();
        $this->setEntityId($ownBoard, 1);

        $otherBoard = $this->createBoard('Other board');
        $this->setEntityId($otherBoard, 2);

        $invitation = $this->createInvitationFor($otherBoard, 'invitee@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('find')->with(42)->willReturn($invitation);
        $invitationRepository->expects($this->never())->method('remove');

        $service = $this->createService(invitationRepository: $invitationRepository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid invitation');

        $service->cancelInvitationById(42, $ownBoard);
    }

    public function testCancelInvitationByIdRemovesInvitationOfTheSameBoard(): void
    {
        $board = $this->createBoard();
        $this->setEntityId($board, 7);

        $invitation = $this->createInvitationFor($board, 'invitee@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('find')->with(42)->willReturn($invitation);
        $invitationRepository->expects($this->once())->method('remove')->with($invitation);

        $service = $this->createService(invitationRepository: $invitationRepository);

        $service->cancelInvitationById(42, $board);
    }

    // --- removeCollaborator ---

    public function testRemoveCollaboratorDetachesAccountAndPersistsIt(): void
    {
        $board = $this->createBoard();
        $collaborator = $this->createAccount('collab@example.com');
        $board->addAccount($collaborator);

        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->expects($this->once())->method('save')->with($collaborator);

        $service = $this->createService(accountRepository: $accountRepository);

        $service->removeCollaborator($board, $collaborator);

        $this->assertFalse($board->getAccounts()->contains($collaborator));
        $this->assertFalse($collaborator->getBoards()->contains($board));
    }

    // --- processInvitation ---

    public function testProcessInvitationDoesNothingWhenInvitationIsPending(): void
    {
        $board = $this->createBoard();
        $inviter = $this->createAccount('john.doe@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('findPendingByEmailAndBoard')
            ->willReturn(new BoardInvitation());
        $invitationRepository->expects($this->never())->method('save');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $service = $this->createService(
            invitationRepository: $invitationRepository,
            mailer: $mailer,
        );

        $service->processInvitation('invitee@example.com', $board, $inviter);
    }

    public function testProcessInvitationDoesNotPropagateMailerFailure(): void
    {
        $board = $this->createBoard();
        $inviter = $this->createAccount('john.doe@example.com');

        $invitationRepository = $this->createMock(BoardInvitationRepository::class);
        $invitationRepository->method('findPendingByEmailAndBoard')->willReturn(null);
        $invitationRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(BoardInvitation::class));

        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('findOneBy')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('SMTP server unreachable'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $service = $this->createService(
            accountRepository: $accountRepository,
            invitationRepository: $invitationRepository,
            mailer: $mailer,
            logger: $logger,
        );

        // Must not throw: failures are swallowed to prevent email enumeration.
        $service->processInvitation('invitee@example.com', $board, $inviter);

        $this->addToAssertionCount(1); // Reaching this line means nothing propagated.
    }

    // --- sendInvitationEmail ---

    public function testSendInvitationEmailReturnsTrueOnSuccess(): void
    {
        $invitation = $this->createInvitationFor($this->createBoard(), 'invitee@example.com');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'app_board_accept_invitation',
                ['token' => $invitation->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn(self::ACCEPT_URL);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(
                static fn (Email $email): bool => $email->getTo()[0]->getAddress() === 'invitee@example.com'
                    && str_contains((string) $email->getHtmlBody(), self::ACCEPT_URL)
            ));

        $service = $this->createService(mailer: $mailer, urlGenerator: $urlGenerator);

        $this->assertTrue($service->sendInvitationEmail($invitation));
    }

    public function testSendInvitationEmailReturnsFalseWhenMailerThrows(): void
    {
        $invitation = $this->createInvitationFor($this->createBoard(), 'invitee@example.com');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('SMTP server unreachable'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $service = $this->createService(mailer: $mailer, logger: $logger);

        $this->assertFalse($service->sendInvitationEmail($invitation));
    }

    // --- Helpers ---

    private function createService(
        ?AccountRepository $accountRepository = null,
        ?BoardInvitationRepository $invitationRepository = null,
        ?MailerInterface $mailer = null,
        ?UrlGeneratorInterface $urlGenerator = null,
        ?LoggerInterface $logger = null,
    ): BoardInvitationService {
        return new BoardInvitationService(
            $accountRepository ?? $this->createMock(AccountRepository::class),
            $invitationRepository ?? $this->createMock(BoardInvitationRepository::class),
            $mailer ?? $this->createMock(MailerInterface::class),
            $urlGenerator ?? $this->createMock(UrlGeneratorInterface::class),
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    private function createBoard(string $title = 'Project board'): Board
    {
        return (new Board())->setTitle($title);
    }

    private function createAccount(string $email): Account
    {
        return (new Account())
            ->setEmail($email)
            ->setName('John')
            ->setLastname('Doe');
    }

    /**
     * Build a fully wired invitation (board, email, inviter) so that the
     * email content can be rendered without null values.
     */
    private function createInvitationFor(Board $board, string $email): BoardInvitation
    {
        return (new BoardInvitation())
            ->setBoard($board)
            ->setEmail($email)
            ->setInvitedBy($this->createAccount('inviter@example.com'));
    }

    /**
     * Entities never expose an id setter; force one through reflection to
     * exercise the board ownership check of cancelInvitationById().
     */
    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}
