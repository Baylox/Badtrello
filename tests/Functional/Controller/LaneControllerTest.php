<?php

namespace App\Tests\Functional\Controller;

use App\Factory\AccountFactory;
use App\Factory\BoardFactory;
use App\Factory\LaneFactory;
use App\Repository\LaneRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises the Lane write flow: Controller -> DTO -> Service -> Repository.
 */
final class LaneControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private function lanes(): LaneRepository
    {
        return static::getContainer()->get(LaneRepository::class);
    }

    public function testOwnerCanCreateLaneAtNextPosition(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/boards/' . $board->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[action="/lane/boards/' . $board->getId() . '/lanes/new"]')->form();
        $form['lane[title]'] = 'Review';
        $client->submit($form);

        $this->assertResponseRedirects('/boards/' . $board->getId());

        $created = $this->lanes()->findOneBy(['title' => 'Review']);
        $this->assertNotNull($created);
        $this->assertSame(2, $created->getPosition());
    }

    public function testOwnerCanEditLane(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/boards/' . $board->getId());
        $form = $crawler->filter('form[action="/lane/lanes/' . $lane->getId() . '/edit"]')->form();
        $form['lane[title]'] = 'Shipped';
        $client->submit($form);

        $this->assertResponseRedirects('/boards/' . $board->getId());

        $lane->_refresh();
        $this->assertSame('Shipped', $lane->getTitle());
    }

    public function testOwnerCanDeleteLane(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $user]);
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $id = $lane->getId();
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/boards/' . $board->getId());
        $client->submit($crawler->filter('form[action="/lane/' . $id . '"]')->form());

        $this->assertResponseRedirects('/boards/' . $board->getId());
        $this->assertNull($this->lanes()->find($id));
    }
}
