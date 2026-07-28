<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Factory\AccountFactory;
use App\Repository\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises the admin account flow: Controller -> AdminAccountInput -> AccountService.
 */
final class AccountControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private function accounts(): AccountRepository
    {
        return static::getContainer()->get(AccountRepository::class);
    }

    public function testNonAdminIsForbidden(): void
    {
        $client = static::createClient();
        $client->loginUser(AccountFactory::createOne(['role' => 'ROLE_USER'])->_real());

        $client->request('GET', '/admin/account');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanEditAccount(): void
    {
        $client = static::createClient();
        $admin = AccountFactory::createOne(['role' => 'ROLE_ADMIN']);
        $target = AccountFactory::createOne(['name' => 'Old', 'role' => 'ROLE_USER']);
        $client->loginUser($admin->_real());

        $crawler = $client->request('GET', '/admin/account/' . $target->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $client->submit($crawler->selectButton('Save Changes')->form([
            'admin_account[email]' => $target->getEmail(),
            'admin_account[name]' => 'Updated',
            'admin_account[lastname]' => $target->getLastname(),
            'admin_account[role]' => 'ROLE_USER',
        ]));

        $this->assertResponseRedirects('/admin/account');

        $target->_refresh();
        $this->assertSame('Updated', $target->getName());
    }

    public function testAdminCanDeleteAccount(): void
    {
        $client = static::createClient();
        $admin = AccountFactory::createOne(['role' => 'ROLE_ADMIN']);
        $target = AccountFactory::createOne(['role' => 'ROLE_USER']);
        $id = $target->getId();
        $client->loginUser($admin->_real());

        $crawler = $client->request('GET', '/admin/account/' . $id . '/edit');
        $client->submit($crawler->filter('form[action="/admin/account/' . $id . '"]')->form());

        $this->assertResponseRedirects('/admin/account');
        $this->assertNull($this->accounts()->find($id));
    }
}
