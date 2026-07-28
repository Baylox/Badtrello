<?php

namespace App\Controller;

use App\Dto\Account\ProfileInput;
use App\Entity\Account;
use App\Form\ProfileType;
use App\Service\Account\AccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/account', name: 'app_account_')]
final class AccountController extends AbstractController
{
    #[Route('/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, AccountService $accountService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        $input = ProfileInput::fromEntity($user);
        $form = $this->createForm(ProfileType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountService->updateProfile($user, $input);
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_account_edit', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
