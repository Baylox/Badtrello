<?php

namespace App\Tests\Unit\Service\Account;

use App\Dto\Account\AdminAccountInput;
use App\Dto\Account\ProfileInput;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Service\Account\AccountService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(AccountService::class)]
final class AccountServiceTest extends TestCase
{
    private function hasher(): UserPasswordHasherInterface
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturnCallback(
            static fn ($user, string $plain): string => 'hashed:' . $plain
        );

        return $hasher;
    }

    public function testUpdateProfileWithoutPasswordKeepsCurrentHash(): void
    {
        $account = (new Account())->setPassword('original');

        $repo = $this->createMock(AccountRepository::class);
        $repo->expects($this->once())->method('save')->with($account);

        $service = new AccountService($repo, $this->hasher());

        $input = new ProfileInput();
        $input->name = 'John';
        $input->lastname = 'Doe';
        $input->plainPassword = null;

        $service->updateProfile($account, $input);

        $this->assertSame('John', $account->getName());
        $this->assertSame('Doe', $account->getLastname());
        $this->assertSame('original', $account->getPassword());
    }

    public function testUpdateProfileWithPasswordHashesIt(): void
    {
        $account = (new Account())->setPassword('original');

        $repo = $this->createMock(AccountRepository::class);
        $repo->expects($this->once())->method('save')->with($account);

        $service = new AccountService($repo, $this->hasher());

        $input = new ProfileInput();
        $input->name = 'John';
        $input->lastname = 'Doe';
        $input->plainPassword = 'Secret#1';

        $service->updateProfile($account, $input);

        $this->assertSame('hashed:Secret#1', $account->getPassword());
    }

    public function testAdminUpdateAppliesAllFields(): void
    {
        $account = new Account();

        $repo = $this->createMock(AccountRepository::class);
        $repo->expects($this->once())->method('save')->with($account);

        $service = new AccountService($repo, $this->hasher());

        $input = new AdminAccountInput();
        $input->email = 'Admin@Example.com';
        $input->name = 'Ada';
        $input->lastname = 'Lovelace';
        $input->role = 'role_admin';

        $service->adminUpdate($account, $input);

        $this->assertSame('admin@example.com', $account->getEmail());
        $this->assertSame('Ada', $account->getName());
        $this->assertSame('Lovelace', $account->getLastname());
        $this->assertSame('ROLE_ADMIN', $account->getRole());
    }

    public function testChangePasswordHashesAndPersists(): void
    {
        $account = new Account();

        $repo = $this->createMock(AccountRepository::class);
        $repo->expects($this->once())->method('save')->with($account);

        $service = new AccountService($repo, $this->hasher());

        $service->changePassword($account, 'BrandNewPass#12');

        $this->assertSame('hashed:BrandNewPass#12', $account->getPassword());
    }

    public function testDeleteRemovesAccount(): void
    {
        $account = new Account();

        $repo = $this->createMock(AccountRepository::class);
        $repo->expects($this->once())->method('remove')->with($account);

        (new AccountService($repo, $this->hasher()))->delete($account);
    }
}
