<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Account;
use App\Entity\BoardInvitation;
use App\Factory\AccountFactory;
use App\Factory\BoardFactory;
use App\Repository\BoardInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises the collaborator invitation flow end to end:
 * invite by email, duplicate protection, token acceptance, cancellation and removal.
 *
 * Note: BoardFactory::initialize() automatically attaches two collaborator
 * accounts to every board, which the membership assertions below rely on.
 */
final class BoardInvitationFlowTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private const INVITEE_EMAIL = 'invitee@example.com';

    public function testOwnerCanInviteCollaboratorByEmail(): void
    {
        $client = static::createClient();
        $owner = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $owner]);
        $client->loginUser($owner->_real());

        $crawler = $client->request('GET', '/board/' . $board->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $client->submit($crawler->selectButton('Send Invitation')->form([
            'add_collaborator[email]' => self::INVITEE_EMAIL,
        ]));

        $this->assertResponseRedirects('/board/' . $board->getId() . '/edit');
        $this->assertEmailCount(1);

        $invitation = static::getContainer()->get(BoardInvitationRepository::class)
            ->findOneBy(['email' => self::INVITEE_EMAIL, 'board' => $board->getId()]);
        $this->assertNotNull($invitation);
        $this->assertFalse($invitation->isAccepted());
    }

    public function testInvitingSameEmailTwiceDoesNotCreateDuplicate(): void
    {
        $client = static::createClient();
        $owner = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $owner]);
        $client->loginUser($owner->_real());

        $editUrl = '/board/' . $board->getId() . '/edit';

        $crawler = $client->request('GET', $editUrl);
        $client->submit($crawler->selectButton('Send Invitation')->form([
            'add_collaborator[email]' => self::INVITEE_EMAIL,
        ]));

        $crawler = $client->request('GET', $editUrl);
        $client->submit($crawler->selectButton('Send Invitation')->form([
            'add_collaborator[email]' => self::INVITEE_EMAIL,
        ]));

        $this->assertResponseRedirects($editUrl);
        // Enumeration-safe: the duplicate is silently ignored, no second email leaves.
        $this->assertEmailCount(0);

        $this->assertSame(1, static::getContainer()->get(BoardInvitationRepository::class)
            ->count(['email' => self::INVITEE_EMAIL, 'board' => $board->getId()]));
    }

    public function testInvitedUserBecomesMemberWhenAcceptingValidToken(): void
    {
        $client = static::createClient();
        $owner = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $owner]);
        $invitee = AccountFactory::createOne(['email' => self::INVITEE_EMAIL]);

        $invitation = new BoardInvitation();
        $invitation->setBoard($board->_real());
        $invitation->setEmail(self::INVITEE_EMAIL);
        $invitation->setInvitedBy($owner->_real());
        static::getContainer()->get(BoardInvitationRepository::class)->save($invitation);

        $client->loginUser($invitee->_real());
        $client->request('GET', '/board/invitation/' . $invitation->getToken() . '/accept');

        $this->assertResponseRedirects('/board/' . $board->getId() . '/edit');

        $board->_refresh();
        $memberIds = $board->getAccounts()->map(fn (Account $account) => $account->getId())->toArray();
        $this->assertContains($invitee->getId(), $memberIds);

        $accepted = static::getContainer()->get(BoardInvitationRepository::class)->find($invitation->getId());
        $this->assertNotNull($accepted);
        $this->assertTrue($accepted->isAccepted());
    }

    public function testUnknownTokenRedirectsToBoardIndex(): void
    {
        $client = static::createClient();
        $client->loginUser(AccountFactory::createOne()->_real());

        $client->request('GET', '/board/invitation/' . str_repeat('0', 64) . '/accept');

        // The controller flashes an error and sends the user back to the board list.
        $this->assertResponseRedirects('/board');
    }

    public function testOwnerCanCancelPendingInvitation(): void
    {
        $client = static::createClient();
        $owner = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $owner]);

        $invitation = new BoardInvitation();
        $invitation->setBoard($board->_real());
        $invitation->setEmail(self::INVITEE_EMAIL);
        $invitation->setInvitedBy($owner->_real());
        static::getContainer()->get(BoardInvitationRepository::class)->save($invitation);
        $invitationId = $invitation->getId();

        $client->loginUser($owner->_real());
        $crawler = $client->request('GET', '/board/' . $board->getId() . '/edit');

        // Submit the rendered cancel form so the CSRF token is the real one.
        $cancelAction = '/board/' . $board->getId() . '/invitation/' . $invitationId . '/cancel';
        $client->submit($crawler->filter('form[action="' . $cancelAction . '"]')->form());

        $this->assertResponseRedirects('/board/' . $board->getId() . '/edit');
        $this->assertNull(static::getContainer()->get(BoardInvitationRepository::class)->find($invitationId));
    }

    public function testOwnerCanRemoveCollaborator(): void
    {
        $client = static::createClient();
        $owner = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $owner]);

        // BoardFactory auto-attaches two collaborators; remove the first one.
        $collaboratorId = $board->getAccounts()->first()->getId();

        $client->loginUser($owner->_real());
        $crawler = $client->request('GET', '/board/' . $board->getId() . '/edit');

        $removeAction = '/board/' . $board->getId() . '/collaborator/' . $collaboratorId . '/remove';
        $client->submit($crawler->filter('form[action="' . $removeAction . '"]')->form());

        $this->assertResponseRedirects('/board/' . $board->getId() . '/edit');

        $board->_refresh();
        $memberIds = $board->getAccounts()->map(fn (Account $account) => $account->getId())->toArray();
        $this->assertNotContains($collaboratorId, $memberIds);
        $this->assertCount(1, $board->getAccounts());
    }

    public function testMemberWithoutManagePermissionCannotInvite(): void
    {
        $client = static::createClient();
        $owner = AccountFactory::createOne();
        $board = BoardFactory::createOne(['owner' => $owner]);

        // A plain collaborator (auto-created by BoardFactory) is not the owner.
        $member = $board->getAccounts()->first();
        $client->loginUser($member);

        $client->request('POST', '/board/' . $board->getId() . '/collaborator/invite');

        $this->assertResponseStatusCodeSame(403);
    }
}
