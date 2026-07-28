<?php

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests for the anonymous public pages (marketing + authentication entry points).
 *
 * None of these pages read or write the database, so this test intentionally
 * skips ResetDatabase/Factories to stay fast.
 */
final class PublicPagesTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function publicPathProvider(): iterable
    {
        yield 'home' => ['/'];
        yield 'solutions' => ['/solutions'];
        yield 'roadmap' => ['/roadmap'];
        yield 'contact' => ['/contact'];
        yield 'login' => ['/login'];
        yield 'register' => ['/register'];
    }

    #[DataProvider('publicPathProvider')]
    public function testPublicPageRespondsSuccessfully(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        $this->assertResponseIsSuccessful();
    }

    public function testRemovedPricingPageReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/price');

        // The pricing page was removed on purpose; this locks in its absence.
        $this->assertResponseStatusCodeSame(404);
    }
}
