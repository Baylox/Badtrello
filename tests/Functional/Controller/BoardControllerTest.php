<?php

namespace App\Tests\Functional\Controller;

use App\Factory\AccountFactory;
use App\Factory\BoardFactory;
use App\Repository\BoardRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises the Board write flow end to end: Controller -> DTO -> Service -> Repository.
 */
final class BoardControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/board');

        $this->assertResponseRedirects();
    }

    public function testUserCanCreateBoard(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/board/new');
        $this->assertResponseIsSuccessful();

        $client->submit($crawler->selectButton('Save')->form([
            'board[title]' => 'Roadmap 2026',
        ]));

        $this->assertResponseRedirects('/board');

        $board = static::getContainer()->get(BoardRepository::class)->findOneBy(['title' => 'Roadmap 2026']);
        $this->assertNotNull($board);
        $this->assertSame($user->getId(), $board->getOwner()->getId());
    }

    public function testCreateBoardWithBlankTitleIsRejected(): void
    {
        $client = static::createClient();
        $client->loginUser(AccountFactory::createOne()->_real());

        $crawler = $client->request('GET', '/board/new');
        $client->submit($crawler->selectButton('Save')->form([
            'board[title]' => '',
        ]));

        // Invalid submission re-renders the form (HTTP 422) and persists nothing.
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(0, static::getContainer()->get(BoardRepository::class)->findAll());
    }

    public function testOwnerCanEditBoardTitle(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/board/' . $board->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $client->submit($crawler->selectButton('Update')->form([
            'board[title]' => 'Renamed board',
        ]));

        $this->assertResponseRedirects('/board');

        $board->_refresh();
        $this->assertSame('Renamed board', $board->getTitle());
    }

    public function testOwnerCanDeleteBoard(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $id = $board->getId();
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/board/' . $id . '/edit');
        $client->submit($crawler->filter('form[action="/board/' . $id . '"]')->form());

        $this->assertResponseRedirects('/board');
        $this->assertNull(static::getContainer()->get(BoardRepository::class)->find($id));
    }

    public function testNonMemberCannotViewEditPage(): void
    {
        $client = static::createClient();
        $board = BoardFactory::createOne(); // owned by someone else
        $client->loginUser(AccountFactory::createOne()->_real());

        $client->request('GET', '/board/' . $board->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }
}
