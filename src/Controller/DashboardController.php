<?php

namespace App\Controller;

use App\Dto\Card\CardInput;
use App\Dto\Lane\LaneInput;
use App\Form\CardType;
use App\Form\LaneType;
use App\Repository\BoardRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    #[Route('/boards/{id<\d+>}', name: 'app_board_dashboard', methods: ['GET'])]
    public function index(int $id, BoardRepository $boards): Response
    {
        $board = $boards->findWithLanesAndCards($id);
        if (!$board) {
            throw $this->createNotFoundException('Board not found');
        }

        $this->denyAccessUnlessGranted('BOARD_VIEW', $board);

        // Create the form for new lanes (mapped on the DTO)
        $laneForm = $this->createForm(LaneType::class, new LaneInput());

        // Create the forms for creating cards (one per lane)
        $cardForms = [];
        foreach ($board->getLanes() as $lane) {
            $cardForms[$lane->getId()] = $this->createForm(CardType::class, new CardInput());
        }

        // Create the forms for editing lanes
        $laneEditForms = [];
        foreach ($board->getLanes() as $lane) {
            $laneEditForms[$lane->getId()] = $this->createForm(LaneType::class, LaneInput::fromEntity($lane))->createView();
        }

        // Create the forms for editing cards
        $cardEditForms = [];
        foreach ($board->getLanes() as $lane) {
            foreach ($lane->getCards() as $card) {
                $cardEditForms[$card->getId()] = $this->createForm(CardType::class, CardInput::fromEntity($card))->createView();
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'board' => $board,
            'laneForm' => $laneForm->createView(),
            'cardForms' => array_map(fn($form) => $form->createView(), $cardForms),
            'laneEditForms' => $laneEditForms,
            'cardEditForms' => $cardEditForms,
            'openLaneModal' => false,
        ]);
    }
}
