<?php

namespace App\Controller;

use App\Dto\Lane\LaneInput;
use App\Entity\Lane;
use App\Entity\Board;
use App\Form\LaneType;
use App\Service\Lane\LaneService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lane')]
#[IsGranted('ROLE_USER')]
final class LaneController extends AbstractController
{
    // PRG from the dashboard (adding a lane)
    // The Board is received in the URL to know where to attach the lane
    #[Route('/boards/{id}/lanes/new', name: 'lane_new', methods: ['POST', 'GET'])]
    public function new(Board $board, Request $request, LaneService $laneService): Response
    {
        $this->denyAccessUnlessGranted('BOARD_EDIT', $board);

        $input = new LaneInput();
        $form = $this->createForm(LaneType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $laneService->createForBoard($input, $board);
            return $this->redirectToRoute('app_board_dashboard', ['id' => $board->getId()]);
        }

        return $this->render('dashboard/index.html.twig', [
            'board'         => $board,
            'laneForm'      => $form->createView(),
            'laneEditForms' => $this->buildLaneEditForms($board),
            'openLaneModal' => $form->isSubmitted(),
        ]);
    }

    #[Route('/{id}', name: 'app_lane_delete', methods: ['POST'])]
    public function delete(Request $request, Lane $lane, LaneService $laneService): Response
    {
        $board = $lane->getBoard();

        if ($this->isCsrfTokenValid('delete' . $lane->getId(), $request->getPayload()->getString('_token'))) {
            $laneService->delete($lane);
        }

        return $this->redirectToRoute('app_board_dashboard', ['id' => $board->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/lanes/{id}/edit', name: 'lane_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lane $lane, LaneService $laneService): Response
    {
        $board = $lane->getBoard();
        $this->denyAccessUnlessGranted('BOARD_EDIT', $board);

        $input = LaneInput::fromEntity($lane);
        $form = $this->createForm(LaneType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $laneService->update($lane, $input);
            return $this->redirectToRoute('app_board_dashboard', ['id' => $board->getId()]);
        }

        // Errors → redisplay dashboard with the UPDATE modal for THIS lane open
        return $this->render('dashboard/index.html.twig', [
            'board'           => $board,
            'laneForm'        => $this->createForm(LaneType::class, new LaneInput())->createView(),
            'laneEditForms'   => $this->buildLaneEditForms($board, $form, $lane),
            'openEditLaneId'  => $lane->getId(),
        ]);
    }

    /**
     * Build the edit forms (mapped on LaneInput) for every lane of the board.
     *
     * @return array<int, \Symfony\Component\Form\FormView>
     */
    private function buildLaneEditForms(Board $board, ?\Symfony\Component\Form\FormInterface $currentForm = null, ?Lane $currentLane = null): array
    {
        $forms = [];
        foreach ($board->getLanes() as $ln) {
            if ($currentLane && $ln->getId() === $currentLane->getId() && $currentForm) {
                $forms[$ln->getId()] = $currentForm->createView();
            } else {
                $forms[$ln->getId()] = $this->createForm(LaneType::class, LaneInput::fromEntity($ln))->createView();
            }
        }
        return $forms;
    }
}
