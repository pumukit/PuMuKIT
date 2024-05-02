<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectVoterTest extends WebTestCase
{
    private $voter;
    private $userService;

    private $i18nService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->voter = static::$kernel->getContainer()->get('pumukitschema.multimedia_object_voter');
        $this->userService = static::$kernel->getContainer()->get('pumukitschema.user');
        $this->i18nService = new i18nService(['en', 'es'], 'en');
    }

    public function tearDown(): void
    {
        $this->voter = null;
        $this->userService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testTrackAccessAnonymous()
    {
        $mmobj = new MultimediaObject();
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $track = $this->generateTrackMedia();
        $mmobj->addTrack($track);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        static::assertFalse($can);

        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $mmobj->addTag($tag);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_HIDDEN);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        static::assertFalse($can);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $brodcast = new EmbeddedBroadcast();
        $brodcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mmobj->setEmbeddedBroadcast($brodcast);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        static::assertFalse($can);

        $brodcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        static::assertFalse($can);
    }

    public function testTrackAccessGlobalScope()
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        $mmobj = new MultimediaObject();
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $track = $this->generateTrackMedia();
        $mmobj->addTrack($track);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $mmobj->addTag($tag);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_HIDDEN);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $brodcast = new EmbeddedBroadcast();
        $brodcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mmobj->setEmbeddedBroadcast($brodcast);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $brodcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);
    }

    public function testTrackAccessPersonalScope()
    {
        $user = new User();
        $user->setRoles([PermissionProfile::SCOPE_PERSONAL]);

        $mmobj = new MultimediaObject();
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $track = $this->generateTrackMedia();
        $mmobj->addTrack($track);
        $series = new Series();
        $mmobj->setSeries($series);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertFalse($can);

        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $mmobj->addTag($tag);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_HIDDEN);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertFalse($can);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $brodcast = new EmbeddedBroadcast();
        $brodcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mmobj->setEmbeddedBroadcast($brodcast);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $brodcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertFalse($can);

        $group = new Group('key1');
        $brodcast->addGroup($group);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertFalse($can);

        $user->addGroup($group);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $this->userService->addOwnerUserToMultimediaObject($mmobj, $user, false);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);

        $mmobj->removeTag($tag);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        static::assertTrue($can);
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    private function generateTrackMedia(): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create(['display']);
        $views = 0;
        $url = StorageUrl::create('');
        $path = Path::create('public/storage');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"format":{"duration":"10.000000"}}');

        return Track::create(
            $originalName,
            $description,
            $language,
            $tags,
            false,
            true,
            $views,
            $storage,
            $mediaMetadata
        );
    }
}
