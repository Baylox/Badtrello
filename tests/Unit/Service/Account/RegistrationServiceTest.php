<?php

namespace App\Tests\Unit\Service\Account;

use App\Dto\Account\RegistrationInput;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Security\EmailVerifier;
use App\Service\Account\RegistrationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(RegistrationService::class)]
final class RegistrationServiceTest extends TestCase
{
    public function testRegisterCreatesHashesPersistsAndSendsConfirmation(): void
    {
        $repo = $this->createMock(AccountRepository::class);
        $repo->expects($this->once())->method('save')->with($this->isInstanceOf(Account::class));

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturnCallback(
            static fn ($user, string $plain): string => 'hashed:' . $plain
        );

        $verifier = $this->createMock(EmailVerifier::class);
        $verifier->expects($this->once())
            ->method('sendEmailConfirmation')
            ->with('app_verify_email', $this->isInstanceOf(Account::class), $this->anything());

        $service = new RegistrationService($repo, $hasher, $verifier);

        $input = new RegistrationInput();
        $input->name = 'Grace';
        $input->lastname = 'Hopper';
        $input->email = 'grace@example.com';
        $input->plainPassword = 'Cobol#1234';

        $account = $service->register($input);

        $this->assertSame('Grace', $account->getName());
        $this->assertSame('Hopper', $account->getLastname());
        $this->assertSame('grace@example.com', $account->getEmail());
        $this->assertSame('hashed:Cobol#1234', $account->getPassword());
    }
}
