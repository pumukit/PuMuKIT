<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class UserProfileControllerTest extends PumukitTestCase
{
    private KernelBrowser $client;
    private User $user;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->user = new User();
        $this->user->setUsername('profile');
        $this->user->setEmail('profile@pumukit.local');
        $this->user->setPassword('unused');
        $this->user->setEnabled(true);
        $this->user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    public function testTheStatsOfAUserWithoutPersonShowAWarning(): void
    {
        $this->client->loginUser($this->user, 'pumukit');
        $this->client->request('GET', static::getContainer()->get('router')->generate('pumukitnewadmin_profile_user_index'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-warning', 'Your user has no associated person, so its stats cannot be shown.');
        $this->assertSelectorNotExists('.user_profile_video_storage_block');
    }

    public function testTheStatsOfAUserWithPersonAreShown(): void
    {
        $person = new Person();
        $person->setName('Profile');
        $person->setEmail('profile@pumukit.local');
        $this->dm->persist($person);
        $this->user->setPerson($person);
        $this->dm->flush();

        $this->client->loginUser($this->user, 'pumukit');
        $this->client->request('GET', static::getContainer()->get('router')->generate('pumukitnewadmin_profile_user_index'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.user_profile_video_storage_block');
        $this->assertSelectorNotExists('.alert-warning');
    }
}
