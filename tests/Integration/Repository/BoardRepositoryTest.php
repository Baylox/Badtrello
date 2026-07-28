<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Board;
use App\Entity\Card;
use App\Entity\Lane;
use App\Factory\AccountFactory;
use App\Factory\BoardFactory;
use App\Factory\CardFactory;
use App\Factory\LaneFactory;
use App\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises BoardRepository queries against a real database.
 *
 * Note: BoardFactory::initialize() automatically attaches two freshly created
 * member accounts to every board it builds; the assertions below are written
 * with those extra members in mind.
 */
#[CoversClass(BoardRepository::class)]
final class BoardRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private BoardRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->repository = $container->get(BoardRepository::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    public function testFindVisibleForUserReturnsOwnedAndMemberBoardsWithoutDuplicates(): void
    {
        $user = AccountFactory::createOne();

        // Owned only: visible even though the user is not a member.
        $ownedBoard = BoardFactory::createOne(['owner' => $user]);

        // Member only: visible even though someone else owns it.
        $memberBoard = BoardFactory::createOne();

        // Owner AND member: must appear only once thanks to distinct().
        $ownedAndMemberBoard = BoardFactory::createOne(['owner' => $user]);

        // Unrelated board (foreign owner, auto-created members only): excluded.
        BoardFactory::createOne();

        // Capture the real entity once: every _real() call on a proxy that was
        // flushed since may auto-refresh the entity and silently discard the
        // not-yet-flushed collection change made through the previous call.
        $realUser = $user->_real();
        $memberBoard->_real()->addAccount($realUser);
        $ownedAndMemberBoard->_real()->addAccount($realUser);
        $this->em->flush();

        $boards = $this->repository->findVisibleForUser($user->_real());

        $this->assertSame(
            [$ownedBoard->getId(), $memberBoard->getId(), $ownedAndMemberBoard->getId()],
            array_map(static fn (Board $board): int => $board->getId(), $boards)
        );
    }

    public function testIsBoardMemberDistinguishesMembersFromOwnerAndStrangers(): void
    {
        $owner = AccountFactory::createOne();
        $member = AccountFactory::createOne();
        $stranger = AccountFactory::createOne();

        $board = BoardFactory::createOne(['owner' => $owner]);
        $board->_real()->addAccount($member->_real());
        $this->em->flush();

        $this->assertTrue($this->repository->isBoardMember($board->_real(), $member->_real()));
        // Owning a board does not make the owner a member.
        $this->assertFalse($this->repository->isBoardMember($board->_real(), $owner->_real()));
        $this->assertFalse($this->repository->isBoardMember($board->_real(), $stranger->_real()));
    }

    public function testFindByAccountReturnsOnlyBoardsWhereAccountIsMember(): void
    {
        $account = AccountFactory::createOne();

        // Owner only: must not be returned by findByAccount().
        BoardFactory::createOne(['owner' => $account]);

        $memberBoard = BoardFactory::createOne();
        $memberBoard->_real()->addAccount($account->_real());
        $this->em->flush();

        $boards = $this->repository->findByAccount($account->_real());

        $this->assertCount(1, $boards);
        $this->assertSame($memberBoard->getId(), $boards[0]->getId());
    }

    public function testFindWithLanesAndCardsReturnsNullForUnknownId(): void
    {
        $this->assertNull($this->repository->findWithLanesAndCards(999999));
    }

    public function testFindWithLanesAndCardsOrdersLanesAndCardsByPosition(): void
    {
        $board = BoardFactory::createOne();

        // Created out of order on purpose: the query must sort by position.
        $secondLane = LaneFactory::createOne(['board' => $board, 'title' => 'Second lane', 'position' => 2]);
        $firstLane = LaneFactory::createOne(['board' => $board, 'title' => 'First lane', 'position' => 1]);

        CardFactory::createOne(['lane' => $firstLane, 'title' => 'Second card', 'position' => 2]);
        CardFactory::createOne(['lane' => $firstLane, 'title' => 'First card', 'position' => 1]);

        $boardId = $board->getId();
        $secondLaneId = $secondLane->getId();

        // Detach everything so the collections are hydrated by the query under test.
        $this->em->clear();

        $found = $this->repository->findWithLanesAndCards($boardId);

        $this->assertNotNull($found);
        $this->assertSame($boardId, $found->getId());

        $lanes = $found->getLanes()->toArray();
        $this->assertCount(2, $lanes);
        $this->assertSame(
            ['First lane', 'Second lane'],
            array_map(static fn (Lane $lane): string => $lane->getTitle(), $lanes)
        );
        $this->assertSame($secondLaneId, $lanes[1]->getId());

        $cards = $lanes[0]->getCards()->toArray();
        $this->assertCount(2, $cards);
        $this->assertSame(
            ['First card', 'Second card'],
            array_map(static fn (Card $card): string => $card->getTitle(), $cards)
        );
    }

    public function testQbForAdminWithoutSearchReturnsAllBoardsOrderedByTitle(): void
    {
        BoardFactory::createOne(['title' => 'Zephyr Roadmap']);
        BoardFactory::createOne(['title' => 'Alpha Backlog']);

        $boards = $this->repository->qbForAdmin(null)->getQuery()->getResult();

        $this->assertSame(
            ['Alpha Backlog', 'Zephyr Roadmap'],
            array_map(static fn (Board $board): string => $board->getTitle(), $boards)
        );

        // An empty search string is treated the same as no search at all.
        $this->assertCount(2, $this->repository->qbForAdmin('')->getQuery()->getResult());
    }

    public function testQbForAdminFiltersByTitleFragmentCaseInsensitively(): void
    {
        BoardFactory::createOne(['title' => 'Zephyr Roadmap']);
        BoardFactory::createOne(['title' => 'Alpha Backlog']);

        $boards = $this->repository->qbForAdmin('zEpHyR')->getQuery()->getResult();

        $this->assertCount(1, $boards);
        $this->assertSame('Zephyr Roadmap', $boards[0]->getTitle());
    }

    public function testQbForAdminFiltersByOwnerEmailFragmentCaseInsensitively(): void
    {
        $matchingOwner = AccountFactory::createOne(['email' => 'quartz.owner@example.test']);
        $otherOwner = AccountFactory::createOne(['email' => 'other.owner@example.test']);

        $matchingBoard = BoardFactory::createOne(['title' => 'First board', 'owner' => $matchingOwner]);
        BoardFactory::createOne(['title' => 'Second board', 'owner' => $otherOwner]);

        $boards = $this->repository->qbForAdmin('QUARTZ')->getQuery()->getResult();

        $this->assertCount(1, $boards);
        $this->assertSame($matchingBoard->getId(), $boards[0]->getId());
    }

    public function testSavePersistsAndRemoveDeletesBoard(): void
    {
        $owner = AccountFactory::createOne();

        $board = new Board();
        $board->setTitle('Persisted by repository');
        $board->setOwner($owner->_real());

        $this->repository->save($board);

        $id = $board->getId();
        $this->assertNotNull($id);
        $this->assertNotNull($this->repository->find($id));

        $this->repository->remove($board);

        $this->assertNull($this->repository->find($id));
    }
}
