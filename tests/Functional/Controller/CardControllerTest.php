<?php

namespace App\Tests\Functional\Controller;

use App\Factory\AccountFactory;
use App\Factory\BoardFactory;
use App\Factory\CardFactory;
use App\Factory\LaneFactory;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises the Card write flow, including the JSON move endpoint
 * (Controller -> #[MapRequestPayload] CardMoveInput -> CardService).
 */
final class CardControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private function cards(): CardRepository
    {
        return static::getContainer()->get(CardRepository::class);
    }

    public function testOwnerCanCreateCardAtBottomOfLane(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/boards/' . $board->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[action="/card/lanes/' . $lane->getId() . '/cards"]')->form();
        $form['card[title]'] = 'Ship the refactor';
        $client->submit($form);

        $this->assertResponseRedirects('/boards/' . $board->getId());

        $card = $this->cards()->findOneBy(['title' => 'Ship the refactor']);
        $this->assertNotNull($card);
        $this->assertSame(2, $card->getPosition());
        $this->assertSame($lane->getId(), $card->getLane()->getId());
    }

    public function testOwnerCanEditCard(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $card = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/boards/' . $board->getId());
        $form = $crawler->filter('form[action="/card/cards/' . $card->getId() . '/edit"]')->form();
        $form['card[title]'] = 'Reviewed task';
        $client->submit($form);

        $this->assertResponseRedirects('/boards/' . $board->getId());

        $card->_refresh();
        $this->assertSame('Reviewed task', $card->getTitle());
    }

    public function testOwnerCanDeleteCard(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $card = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $id = $card->getId();
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/boards/' . $board->getId());
        $client->submit($crawler->filter('form[action="/card/' . $id . '"]')->form());

        $this->assertResponseRedirects('/boards/' . $board->getId());
        $this->assertNull($this->cards()->find($id));
    }

    public function testCardCanBeMovedToAnotherLaneViaJson(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $from = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $to = LaneFactory::createOne(['board' => $board, 'position' => 2]);
        $card = CardFactory::createOne(['lane' => $from, 'position' => 1]);
        $client->loginUser($user->_real());

        $client->request(
            'POST',
            '/card/cards/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'cardId' => $card->getId(),
                'toLaneId' => $to->getId(),
                'newIndex' => 0,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $card->_refresh();
        $this->assertSame($to->getId(), $card->getLane()->getId());
        $this->assertSame(0, $card->getPosition());
    }

    public function testMoveWithUnknownCardReturns404(): void
    {
        $client = static::createClient();
        $client->loginUser(AccountFactory::createOne()->_real());

        $client->request(
            'POST',
            '/card/cards/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['cardId' => 999999, 'toLaneId' => 999999, 'newIndex' => 0])
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
