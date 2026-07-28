<?php

namespace App\Service\Board;

use App\Entity\Board;
use App\Entity\Account;
use App\Entity\BoardInvitation;
use App\Repository\AccountRepository;
use App\Repository\BoardInvitationRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

final class BoardInvitationService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly BoardInvitationRepository $invitationRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {}

    public function canInvite(string $email, Board $board): bool
    {
        // Check if invitation already exists
        if ($this->invitationRepository->findPendingByEmailAndBoard($email, $board)) {
            return false;
        }

        // Check if user is already a collaborator or owner
        $existingUser = $this->accountRepository->findOneBy(['email' => $email]);
        if ($existingUser && $this->isUserAlreadyMember($existingUser, $board)) {
            return false;
        }

        return true;
    }

    public function createInvitation(string $email, Board $board, Account $invitedBy): BoardInvitation
    {
        $invitation = new BoardInvitation();
        $invitation->setBoard($board);
        $invitation->setEmail($email);
        $invitation->setInvitedBy($invitedBy);

        $this->invitationRepository->save($invitation);

        $this->logger->info('Board invitation created', [
            'board_id' => $board->getId(),
            'email' => $email,
            'invited_by' => $invitedBy->getId(),
            'token' => $invitation->getToken()
        ]);

        return $invitation;
    }

    public function sendInvitationEmail(BoardInvitation $invitation): bool
    {
        try {
            $acceptUrl = $this->urlGenerator->generate(
                'app_board_accept_invitation',
                ['token' => $invitation->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->from('noreply@ask-cge.com')
                ->to($invitation->getEmail())
                ->subject('You have been invited to collaborate on a board')
                ->html($this->buildEmailContent($invitation, $acceptUrl));

            $this->mailer->send($email);

            $this->logger->info('Invitation email sent', [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send invitation email', [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function acceptInvitation(BoardInvitation $invitation, Account $user): void
    {
        $board = $invitation->getBoard();

        if (!$this->isUserAlreadyMember($user, $board)) {
            $board->addAccount($user);
        }

        $invitation->setIsAccepted(true);
        $this->invitationRepository->save($invitation);

        $this->logger->info('Invitation accepted', [
            'invitation_id' => $invitation->getId(),
            'user_id' => $user->getId(),
            'board_id' => $board->getId()
        ]);
    }

    public function cancelInvitation(BoardInvitation $invitation): void
    {
        $this->logger->info('Invitation cancelled', [
            'invitation_id' => $invitation->getId(),
            'board_id' => $invitation->getBoard()->getId()
        ]);

        $this->invitationRepository->remove($invitation);
    }

    /**
     * Remove a collaborator from a board and persist the change.
     */
    public function removeCollaborator(Board $board, Account $collaborator): void
    {
        $board->removeAccount($collaborator);
        // Account is the owning side of the relation; saving it flushes the change.
        $this->accountRepository->save($collaborator);

        $this->logger->info('Collaborator removed', [
            'board_id' => $board->getId(),
            'collaborator_id' => $collaborator->getId(),
        ]);
    }

    public function cancelInvitationById(int $invitationId, Board $board): void
    {
        $invitation = $this->invitationRepository->find($invitationId);

        if (!$invitation || $invitation->getBoard()->getId() !== $board->getId()) {
            throw new \InvalidArgumentException('Invalid invitation');
        }

        $this->cancelInvitation($invitation);
    }

    public function processInvitation(string $email, Board $board, Account $invitedBy): void
    {
        // Always return success message to prevent enumeration
        if (!$this->canInvite($email, $board)) {
            return;
        }

        try {
            $invitation = $this->createInvitation($email, $board, $invitedBy);
            $this->sendInvitationEmail($invitation);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process invitation', [
                'error' => $e->getMessage(),
                'board_id' => $board->getId(),
                'email' => $email
            ]);
            // Don't throw - return success to prevent enumeration
        }
    }

    private function isUserAlreadyMember(Account $user, Board $board): bool
    {
        return $user === $board->getOwner() || $board->getAccounts()->contains($user);
    }

    private function buildEmailContent(BoardInvitation $invitation, string $acceptUrl): string
    {
        return sprintf(
            '<p>Hello,</p>
            <p>%s has invited you to collaborate on the board "<strong>%s</strong>".</p>
            <p><a href="%s">Click here to accept the invitation</a></p>
            <p>This invitation will expire on %s.</p>
            <p>If you did not expect this invitation, you can safely ignore this email.</p>',
            htmlspecialchars($invitation->getInvitedBy()->getFullName(), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($invitation->getBoard()->getTitle(), ENT_QUOTES, 'UTF-8'),
            $acceptUrl,
            $invitation->getExpiresAt()->format('F j, Y \a\t g:i A')
        );
    }
}
