<?php

namespace App\Tests\Functional\Controller;

use App\Repository\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises registration: Controller -> RegistrationInput -> RegistrationService.
 */
final class RegistrationControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testUserCanRegister(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign up')->form();
        $form['registration_form[name]'] = 'Grace';
        $form['registration_form[lastname]'] = 'Hopper';
        $form['registration_form[email]'] = 'grace@example.test';
        $form['registration_form[plainPassword][first]'] = 'Cobol@1234';
        $form['registration_form[plainPassword][second]'] = 'Cobol@1234';
        $form['registration_form[agreeTerms]']->tick();
        $client->submit($form);

        // On success the user is logged in and redirected.
        $this->assertResponseRedirects();

        $account = static::getContainer()->get(AccountRepository::class)->findOneByEmail('grace@example.test');
        $this->assertNotNull($account);
        $this->assertSame('Grace', $account->getName());
        // Password is stored hashed, never in clear text.
        $this->assertNotSame('Cobol@1234', $account->getPassword());
        $this->assertNotEmpty($account->getPassword());
    }

    public function testRegistrationRequiresAgreeingToTerms(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Sign up')->form();
        $form['registration_form[name]'] = 'Grace';
        $form['registration_form[lastname]'] = 'Hopper';
        $form['registration_form[email]'] = 'grace2@example.test';
        $form['registration_form[plainPassword][first]'] = 'Cobol@1234';
        $form['registration_form[plainPassword][second]'] = 'Cobol@1234';
        // agreeTerms left unchecked
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertNull(static::getContainer()->get(AccountRepository::class)->findOneByEmail('grace2@example.test'));
    }
}
