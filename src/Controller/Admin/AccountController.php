<?php

namespace App\Controller\Admin;

use App\Dto\Account\AdminAccountInput;
use App\Entity\Account;
use App\Form\AdminAccountType;
use App\Repository\AccountRepository;
use App\Service\Account\AccountService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('admin/account', name: 'admin_account_')]
final class AccountController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    public function index(AccountRepository $accountRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $availableRoles = $accountRepository->distinctRoles();
        $role = $request->query->get('role');
        $search = $request->query->get('search');

        // if the passed value does not exist in the database, we remove the filter
        if ($role && !in_array($role, $availableRoles, true)) {
            $role = null;
        }

        $qb = $accountRepository->qbByRoleAndSearch($role, $search);
        $accounts = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        return $this->render('admin/account/index.html.twig', [
            'accounts'        => $accounts,
            'current_role'    => $role,
            'current_search'  => $search,
            'available_roles' => $availableRoles,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Account $account): Response
    {
        return $this->render('admin/account/show.html.twig', [
            'account' => $account,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Account $account, AccountService $accountService): Response
    {
        $input = AdminAccountInput::fromEntity($account);
        $form = $this->createForm(AdminAccountType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountService->adminUpdate($account, $input);
            return $this->redirectToRoute('admin_account_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/account/edit.html.twig', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Account $account, AccountService $accountService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$account->getId(), $request->request->get('_token'))) {
            $accountService->delete($account);
            // Account deleted.
            $this->addFlash('success', 'Account deleted.');
        } else {
            // Invalid CSRF token.
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_account_index', [], Response::HTTP_SEE_OTHER);
    }
}
