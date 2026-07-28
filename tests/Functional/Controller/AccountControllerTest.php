<?php

namespace App\Tests\Functional\Controller;

use App\Factory\AccountFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises the profile write flow: Controller -> ProfileInput -> AccountService.
 */
final class AccountControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testUserCanUpdateTheirProfile(): void
    {
        $client = static::createClient();
        $user = AccountFactory::createOne(['name' => 'Old', 'lastname' => 'Name']);
        $client->loginUser($user->_real());

        $crawler = $client->request('GET', '/account/edit');
        $this->assertResponseIsSuccessful();

        $client->submit($crawler->selectButton('Save Changes')->form([
            'profile[name]' => 'Brand',
            'profile[lastname]' => 'Newname',
        ]));

        $this->assertResponseRedirects('/account/edit');

        $user->_refresh();
        $this->assertSame('Brand', $user->getName());
        $this->assertSame('Newname', $user->getLastname());
    }

    public function testProfileEditRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/account/edit');

        $this->assertResponseRedirects();
    }
}
