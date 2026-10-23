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
class PlaylistVideoSearchTest extends PumukitTestCase
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
        $this->user->setUsername('playlistsearch');
        $this->user->setEmail('playlistsearch@pumukit.local');
        $this->user->setPassword('unused');
        $this->user->setEnabled(true);
        $this->user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    public function testAUserWithoutPersonGetsAWarning(): void
    {
        $this->client->loginUser($this->user, 'pumukit');
        $this->client->xmlHttpRequest('GET', static::getContainer()->get('router')->generate('pumukitnewadmin_playlistmms_modal_search', ['search' => 'video']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-warning', 'Your user has no associated person, so its videos cannot be searched.');
        $this->assertSelectorNotExists('.mmobj');
    }

    public function testAUserWithPersonGetsTheResults(): void
    {
        $person = new Person();
        $person->setName('Playlist search');
        $person->setEmail('playlistsearch@pumukit.local');
        $this->dm->persist($person);
        $this->user->setPerson($person);
        $this->dm->flush();

        $this->client->loginUser($this->user, 'pumukit');
        $this->client->xmlHttpRequest('GET', static::getContainer()->get('router')->generate('pumukitnewadmin_playlistmms_modal_search', ['search' => 'video']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('b', 'Mm. Obj.');
        $this->assertSelectorNotExists('.alert-warning');
    }
}
