<?php

namespace App\Controller;

use App\Dto\Card\CardInput;
use App\Dto\Card\CardMoveInput;
use App\Dto\Lane\LaneInput;
use App\Entity\Card;
use App\Entity\Lane;
use App\Form\CardType;
use App\Form\LaneType;
use App\Repository\CardRepository;
use App\Repository\LaneRepository;
use App\Service\Card\CardService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/card')]
final class CardController extends AbstractController
{
    #[Route('/lanes/{laneId}/cards', name: 'card_new', methods: ['POST'])]
    public function createForLane(
        #[MapEntity(mapping: ['laneId' => 'id'])] Lane $lane,
        Request $request,
        CardService $cardService
    ): Response {
        $this->denyAccessUnlessGranted('BOARD_EDIT', $lane->getBoard());

        $input = new CardInput();
        $form = $this->createForm(CardType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cardService->createForLane($input, $lane);

            return $this->redirectToRoute(
                'app_board_dashboard',
                ['id' => $lane->getBoard()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        // Invalid case: return only the strict minimum
        return $this->render('dashboard/index.html.twig', [
            'board' => $lane->getBoard(),
            'laneForm' => $this->createForm(LaneType::class, new LaneInput())->createView(),
            'cardForms' => [$lane->getId() => $form->createView()],
            'openCardModalId' => $lane->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_card_delete', methods: ['POST'])]
    public function delete(Request $request, Card $card, CardService $cardService): Response
    {
        $board = $card->getLane()->getBoard();

        if ($this->isCsrfTokenValid('delete' . $card->getId(), $request->getPayload()->getString('_token'))) {
            $cardService->delete($card);
        }

        return $this->redirectToRoute('app_board_dashboard', ['id' => $board->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/cards/{id}/edit', name: 'card_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Card $card, CardService $cardService): Response
    {
        $lane = $card->getLane();
        $board = $lane->getBoard();
        $this->denyAccessUnlessGranted('BOARD_EDIT', $board);

        $input = CardInput::fromEntity($card);
        $form = $this->createForm(CardType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cardService->update($card, $input);
            return $this->redirectToRoute('app_board_dashboard', ['id' => $board->getId()]);
        }

        // Errors → redisplay dashboard with the UPDATE modal for THIS card open
        $cardEditForms = [];
        foreach ($board->getLanes() as $boardLane) {
            foreach ($boardLane->getCards() as $boardCard) {
                $cardEditForms[$boardCard->getId()] = $boardCard->getId() === $card->getId()
                    ? $form->createView() // keep errors
                    : $this->createForm(CardType::class, CardInput::fromEntity($boardCard))->createView();
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'board'           => $board,
            'laneForm'        => $this->createForm(LaneType::class, new LaneInput())->createView(),
            'laneEditForms'   => $this->buildLaneEditForms($board),
            'cardEditForms'   => $cardEditForms,
            'openEditCardId'  => $card->getId(),
        ]);
    }

    /** @return array<int, \Symfony\Component\Form\FormView> */
    private function buildLaneEditForms(\App\Entity\Board $board): array
    {
        $forms = [];
        foreach ($board->getLanes() as $lane) {
            $forms[$lane->getId()] = $this->createForm(LaneType::class, LaneInput::fromEntity($lane))->createView();
        }
        return $forms;
    }

    #[Route('/cards/move', name: 'card_move', methods: ['POST'])]
    public function move(
        #[MapRequestPayload] CardMoveInput $input,
        CardRepository $cardRepo,
        LaneRepository $laneRepo,
        CardService $cardService
    ): JsonResponse {
        $card = $cardRepo->find($input->cardId);
        $lane = $laneRepo->find($input->toLaneId);

        if (!$card || !$lane) {
            return $this->json(['error' => 'not found'], 404);
        }

        $this->denyAccessUnlessGranted('BOARD_EDIT', $lane->getBoard());
        $cardService->move($card, $lane, $input->newIndex);

        return $this->json(['ok' => true]);
    }
}
