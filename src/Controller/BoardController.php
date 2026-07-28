<?php

namespace App\Controller;

use App\Dto\Board\BoardInput;
use App\Dto\Board\InvitationInput;
use App\Entity\Board;
use App\Entity\Account;
use App\Entity\BoardInvitation;
use App\Form\BoardType;
use App\Form\AddCollaboratorType;
use App\Repository\AccountRepository;
use App\Repository\BoardRepository;
use App\Repository\BoardInvitationRepository;
use App\Service\Board\BoardService;
use App\Service\Board\BoardInvitationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/board')]
final class BoardController extends AbstractController
{

    /**
     * Display all boards visible to the current user (owned and shared)
     *
     * @param BoardRepository $boardRepository
     * @return Response
     */
    #[Route(name: 'app_board_index', methods: ['GET'])]
    public function index(BoardRepository $boardRepository): Response
    {
        return $this->render('board/index.html.twig', [
            'boards' => $boardRepository->findVisibleForUser($this->getUser()),
        ]);
    }

    /**
     * Create a new board and set the current user as owner
     *
     * @param Request $request
     * @param BoardService $boardService
     * @return Response
     */
    #[Route('/new', name: 'app_board_new', methods: ['GET', 'POST'])]
    public function new(Request $request, BoardService $boardService): Response
    {
        $input = new BoardInput();
        $form = $this->createForm(BoardType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boardService->create($input, $this->getUser());

            return $this->redirectToRoute('app_board_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('board/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Edit a board's information and display collaborator management interface
     * Requires BOARD_EDIT permission
     *
     * @param Request $request
     * @param Board $board
     * @param BoardService $boardService
     * @param BoardInvitationRepository $invitationRepository
     * @return Response
     */
    #[IsGranted('BOARD_EDIT', subject: 'board')]
    #[Route('/{id}/edit', name: 'app_board_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Board $board, BoardService $boardService, BoardInvitationRepository $invitationRepository): Response
    {
        $input = BoardInput::fromEntity($board);
        $form = $this->createForm(BoardType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boardService->update($board, $input);
            return $this->redirectToRoute('app_board_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('board/edit.html.twig', [
            'board' => $board,
            'form' => $form,
            'collaborator_form' => $this->createCollaboratorForm($board),
            'pending_invitations' => $this->getPendingInvitations($board, $invitationRepository),
        ]);
    }

    /**
     * Delete a board permanently
     * Requires BOARD_DELETE permission and valid CSRF token
     *
     * @param Request $request
     * @param Board $board
     * @param BoardService $boardService
     * @return Response
     */
    #[IsGranted('BOARD_DELETE', subject: 'board')]
    #[Route('/{id}', name: 'app_board_delete', methods: ['POST'])]
    public function delete(Request $request, Board $board, BoardService $boardService): Response
    {
        if ($this->isCsrfTokenValid('delete' . $board->getId(), $request->getPayload()->getString('_token'))) {
            $boardService->delete($board);
        }

        return $this->redirectToRoute('app_board_index', [], Response::HTTP_SEE_OTHER);
    }

// Collaborator Management

    /**
     * Send an invitation email to add a new collaborator to the board
     * Requires BOARD_MANAGE_COLLABORATORS permission and applies rate limiting
     *
     * @param Request $request
     * @param Board $board
     * @param RateLimiterFactory $addCollaboratorLimiter
     * @param BoardInvitationService $invitationService
     * @return Response
     */
    #[IsGranted('BOARD_MANAGE_COLLABORATORS', subject: 'board')]
    #[Route('/{id}/collaborator/invite', name: 'app_board_invite_collaborator', methods: ['POST'])]
    public function inviteCollaborator(Request $request, Board $board, RateLimiterFactory $addCollaboratorLimiter, BoardInvitationService $invitationService): Response
    {
        if (!$this->checkRateLimit($addCollaboratorLimiter)) {
            return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
        }

        $input = new InvitationInput();
        $form = $this->createForm(AddCollaboratorType::class, $input, ['board' => $board]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitationService->processInvitation($input->email, $board, $this->getUser());
            $this->addFlash('success', 'An invitation has been sent to this email address.');
        }

        return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
    }

    /**
     * Remove a collaborator from the board
     * Requires BOARD_MANAGE_COLLABORATORS permission and valid CSRF token
     * Prevents removal of board owner and validates collaborator membership
     *
     * @param Request $request
     * @param Board $board
     * @param int $userId
     * @param AccountRepository $accountRepository
     * @param BoardInvitationService $invitationService
     * @return Response
     */
    #[IsGranted('BOARD_MANAGE_COLLABORATORS', subject: 'board')]
    #[Route('/{id}/collaborator/{userId}/remove', name: 'app_board_remove_collaborator', methods: ['POST'])]
    public function removeCollaborator(Request $request, Board $board, int $userId, AccountRepository $accountRepository, BoardInvitationService $invitationService): Response
    {
        if (!$this->isCsrfTokenValid('remove_collaborator' . $userId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
        }

        $collaborator = $accountRepository->find($userId);

        // IDOR Protection: Verify user can be removed
        if (!$this->canRemoveCollaborator($board, $collaborator)) {
            $this->addFlash('error', 'Invalid user.');
            return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
        }

        $invitationService->removeCollaborator($board, $collaborator);

        $this->addFlash('success', 'Collaborator removed successfully.');
        return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
    }

// Invitation Management

    /**
     * Accept a board invitation using its unique token
     * Validates the invitation, checks user email matches and adds them as collaborator
     * Handles cases where user is already a member
     *
     * @param string $token
     * @param BoardInvitationRepository $invitationRepository
     * @param BoardInvitationService $invitationService
     * @return Response
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/invitation/{token}/accept', name: 'app_board_accept_invitation', methods: ['GET'])]
    public function acceptInvitation(string $token, BoardInvitationRepository $invitationRepository, BoardInvitationService $invitationService): Response
    {
        $invitation = $invitationRepository->findValidByToken($token);

        if (!$invitation) {
            $this->addFlash('error', 'This invitation is invalid or has expired.');
            return $this->redirectToRoute('app_board_index');
        }

        $user = $this->getUser();

        if (!$this->validateInvitationForUser($invitation, $user)) {
            return $this->redirectToRoute('app_board_index');
        }

        $board = $invitation->getBoard();

        if ($board->getAccounts()->contains($user) || $board->getOwner() === $user) {
            $invitationService->acceptInvitation($invitation, $user);
            $this->addFlash('info', 'You are already a member of this board.');
            return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
        }

        $invitationService->acceptInvitation($invitation, $user);
        $this->addFlash('success', sprintf('You have successfully joined the board "%s".', $board->getTitle()));

        return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
    }

    /**
     * Cancel a pending invitation
     * Requires BOARD_MANAGE_COLLABORATORS permission and valid CSRF token
     * Validates invitation belongs to the specified board
     *
     * @param Request $request
     * @param Board $board
     * @param int $invitationId
     * @param BoardInvitationService $invitationService
     * @return Response
     */
    #[IsGranted('BOARD_MANAGE_COLLABORATORS', subject: 'board')]
    #[Route('/{id}/invitation/{invitationId}/cancel', name: 'app_board_cancel_invitation', methods: ['POST'])]
    public function cancelInvitation(Request $request, Board $board, int $invitationId, BoardInvitationService $invitationService): Response
    {
        if (!$this->validateCsrfForInvitation($request, $invitationId)) {
            return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
        }

        try {
            $invitationService->cancelInvitationById($invitationId, $board);
            $this->addFlash('success', 'Invitation cancelled.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Invalid invitation.');
        }

        return $this->redirectToRoute('app_board_edit', ['id' => $board->getId()]);
    }

// Private Helper Methods

    /**
     * Check rate limit for the current user
     * Adds flash message if limit is exceeded
     *
     * @param RateLimiterFactory $limiterFactory
     * @return bool True if request is allowed, false if rate limit exceeded
     */
    private function checkRateLimit(RateLimiterFactory $limiterFactory): bool
    {
        $limiter = $limiterFactory->create($this->getUser()->getUserIdentifier());

        if (!$limiter->consume(1)->isAccepted()) {
            $this->addFlash('error', 'Too many attempts. Please try again later.');
            return false;
        }

        return true;
    }

    /**
     * Create collaborator invitation form if user has permission
     *
     * @param Board $board
     * @return \Symfony\Component\Form\FormView|null Form view or null if user lacks permission
     */
    private function createCollaboratorForm(Board $board): ?\Symfony\Component\Form\FormView
    {
        if (!$this->isGranted('BOARD_MANAGE_COLLABORATORS', $board)) {
            return null;
        }

        return $this->createForm(AddCollaboratorType::class, new InvitationInput(), ['board' => $board])->createView();
    }

    /**
     * Get pending invitations for a board if user has permission
     *
     * @param Board $board
     * @param BoardInvitationRepository $repository
     * @return array List of pending invitations or empty array if user lacks permission
     */
    private function getPendingInvitations(Board $board, BoardInvitationRepository $repository): array
    {
        if (!$this->isGranted('BOARD_MANAGE_COLLABORATORS', $board)) {
            return [];
        }

        return $repository->findPendingByBoard($board);
    }

    /**
     * Validate that the invitation email matches the current user's email
     *
     * @param BoardInvitation $invitation
     * @param Account $user
     * @return bool True if email matches, false otherwise
     */
    private function validateInvitationForUser(BoardInvitation $invitation, Account $user): bool
    {
        if ($user->getEmail() !== $invitation->getEmail()) {
            $this->addFlash('error', 'This invitation was sent to a different email address.');
            return false;
        }

        return true;
    }

    /**
     * Validate CSRF token for invitation operations
     *
     * @param Request $request
     * @param int $invitationId
     * @return bool True if token is valid, false otherwise
     */
    private function validateCsrfForInvitation(Request $request, int $invitationId): bool
    {
        if (!$this->isCsrfTokenValid('cancel_invitation' . $invitationId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return false;
        }

        return true;
    }

    /**
     * Check if a collaborator can be removed from the board
     * Prevents removal of null accounts, board owner, and non-members
     *
     * @param Board $board
     * @param Account|null $collaborator
     * @return bool True if collaborator can be removed, false otherwise
     */
    private function canRemoveCollaborator(Board $board, ?Account $collaborator): bool
    {
        if (!$collaborator) {
            return false;
        }

        if ($collaborator === $board->getOwner()) {
            return false;
        }

        if (!$board->getAccounts()->contains($collaborator)) {
            return false;
        }

        return true;
    }
}
