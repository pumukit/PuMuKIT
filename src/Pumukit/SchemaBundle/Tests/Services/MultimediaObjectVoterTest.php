<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class MultimediaObjectVoterTest extends WebTestCase
{
    private $voter;
    private $userService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->voter = static::$kernel->getContainer()
          ->get('pumukitschema.multimedia_object_voter');

        $this->userService = static::$kernel->getContainer()
          ->get('pumukitschema.user');
    }

    public function tearDown()
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
        $track = new Track();
        $mmobj->addTrack($track);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        $this->assertFalse($can);

        $tag = new Tag();
        $tag->setCod('PUCHWEBTV');
        $mmobj->addTag($tag);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_HIDDEN);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        $this->assertFalse($can);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $brodcast = new EmbeddedBroadcast();
        $brodcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mmobj->setEmbeddedBroadcast($brodcast);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        $this->assertFalse($can);

        $brodcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, null]);
        $this->assertFalse($can);
    }

    public function testTrackAccessGlobalScope()
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        $mmobj = new MultimediaObject();
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $track = new Track();
        $mmobj->addTrack($track);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $tag = new Tag();
        $tag->setCod('PUCHWEBTV');
        $mmobj->addTag($tag);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_HIDDEN);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $brodcast = new EmbeddedBroadcast();
        $brodcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mmobj->setEmbeddedBroadcast($brodcast);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $brodcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);
    }

    public function testTrackAccessPersonalScope()
    {
        $user = new User();
        $user->setRoles([PermissionProfile::SCOPE_PERSONAL]);

        $mmobj = new MultimediaObject();
        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $track = new Track();
        $mmobj->addTrack($track);
        $series = new Series();
        $mmobj->setSeries($series);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertFalse($can);

        $tag = new Tag();
        $tag->setCod('PUCHWEBTV');
        $mmobj->addTag($tag);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_HIDDEN);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertFalse($can);

        $mmobj->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $brodcast = new EmbeddedBroadcast();
        $brodcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mmobj->setEmbeddedBroadcast($brodcast);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $brodcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertFalse($can);

        $group = new Group('key1');
        $brodcast->addGroup($group);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertFalse($can);

        $user->addGroup($group);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $this->userService->addOwnerUserToMultimediaObject($mmobj, $user, false);

        $mmobj->setStatus(MultimediaObject::STATUS_BLOCKED);

        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);

        $mmobj->removeTag($tag);
        $can = $this->invokeMethod($this->voter, 'canPlay', [$mmobj, $user]);
        $this->assertTrue($can);
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
