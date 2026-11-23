<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class PlaylistModalTest extends PumukitTestCase
{
    private const NO_PERSON = 'Your user has no associated person, so its videos cannot be searched.';

    private KernelBrowser $client;
    private Series $playlist;
    private Role $owner;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->owner = new Role();
        $this->owner->setCod('owner');
        $this->dm->persist($this->owner);

        $this->playlist = new Series();
        $this->playlist->setNumericalID(1);
        $this->playlist->setType(Series::TYPE_PLAYLIST);
        $this->dm->persist($this->playlist);
        $this->dm->flush();
    }

    public function testAPersonalUserWithoutPersonSeesAWarningInMyVideos(): void
    {
        $this->client->loginUser($this->user('personal', [PermissionProfile::SCOPE_PERSONAL]), 'pumukit');

        $crawler = $this->client->xmlHttpRequest('GET', $this->url('pumukitnewadmin_playlistmms_modal'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#my_videos .alert-warning', self::NO_PERSON);
        $this->assertSame('0', $crawler->filter('[data-admin--playlist-modal-pages-value]')->attr('data-admin--playlist-modal-pages-value'));
    }

    public function testAPersonalUserWithoutPersonGetsAWarningInsteadOfTheirVideos(): void
    {
        $this->client->loginUser($this->user('personal', [PermissionProfile::SCOPE_PERSONAL]), 'pumukit');

        $this->client->xmlHttpRequest('GET', $this->url('pumukitnewadmin_playlistmms_modal_mymmobjs'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-warning', self::NO_PERSON);
    }

    public function testThePagesCountEveryVideo(): void
    {
        foreach ([1, 2, 3] as $number) {
            $this->video($number);
        }
        $this->client->loginUser($this->user('global', ['ROLE_SUPER_ADMIN']), 'pumukit');

        $crawler = $this->client->xmlHttpRequest('GET', $this->url('pumukitnewadmin_playlistmms_modal', ['modal_limit' => 2]));

        $this->assertResponseIsSuccessful();
        $this->assertSame('2', $crawler->filter('[data-admin--playlist-modal-pages-value]')->attr('data-admin--playlist-modal-pages-value'));
    }

    public function testAPersonalUserOnlyGetsTheVideosTheyOwn(): void
    {
        $person = new Person();
        $person->setName('Owner');
        $this->dm->persist($person);
        $user = $this->user('personal', [PermissionProfile::SCOPE_PERSONAL]);
        $user->setPerson($person);
        $person->setUser($user);
        $owned = $this->video(1, $person);
        $this->video(2);
        $this->client->loginUser($user, 'pumukit');

        $crawler = $this->client->xmlHttpRequest('GET', $this->url('pumukitnewadmin_playlistmms_modal', ['modal_limit' => 1]));
        $this->assertSame('1', $crawler->filter('[data-admin--playlist-modal-pages-value]')->attr('data-admin--playlist-modal-pages-value'));

        $crawler = $this->client->xmlHttpRequest('GET', $this->url('pumukitnewadmin_playlistmms_modal_mymmobjs', ['modal_limit' => 1]));
        $this->assertResponseIsSuccessful();
        $this->assertSame([$owned->getId()], $crawler->filter('input[type="checkbox"]')->each(static fn ($input) => $input->attr('value')));
    }

    private function video(int $number, ?Person $owner = null): MultimediaObject
    {
        $series = new Series();
        $series->setNumericalID(100 + $number);
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID($number);
        $multimediaObject->setSeries($series);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        if ($owner) {
            $multimediaObject->addPersonWithRole($owner, $this->owner);
        }
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    private function user(string $username, array $roles): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles($roles);
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    private function url(string $route, array $query = []): string
    {
        return static::getContainer()->get('router')->generate($route, ['id' => $this->playlist->getId(), ...$query]);
    }
}
