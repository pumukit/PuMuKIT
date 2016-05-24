<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;

class EmbeddedBroadcastServiceTest extends WebTestCase
{
    private $dm;
    private $mmRepo;
    private $embeddedBroadcastService;
    private $mmsService;
    private $dispatcher;
    private $authorizationChecker;
    private $templating;
    private $router;

    public function __construct()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->embeddedBroadcastService = static::$kernel->getContainer()
            ->get('pumukitschema.embeddedbroadcast');
        $this->mmsService = static::$kernel->getContainer()
            ->get('pumukitschema.multimedia_object');
        $this->dispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.multimediaobject_dispatcher');
        $this->authorizationChecker = static::$kernel->getContainer()
            ->get('security.authorization_checker');
        $this->templating = static::$kernel->getContainer()
            ->get('templating');
        $this->router = static::$kernel->getContainer()
            ->get('router');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Group')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:User')->remove(array());
        $this->dm->flush();
    }

    public function testCreateEmbeddedBroadcastByType()
    {
        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $this->authorizationChecker, $this->templating, $this->router, false);
        $passwordBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_PASSWORD);
        $ldapBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_LOGIN);
        $groupsBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_GROUPS);
        $publicBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_PUBLIC);
        $defaultBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType();

        $this->assertEquals(EmbeddedBroadcast::TYPE_PASSWORD, $passwordBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PASSWORD, $passwordBroadcast->getName());
        $this->assertEquals(EmbeddedBroadcast::TYPE_LOGIN, $ldapBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_LOGIN, $ldapBroadcast->getName());
        $this->assertEquals(EmbeddedBroadcast::TYPE_GROUPS, $groupsBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_GROUPS, $groupsBroadcast->getName());
        $this->assertEquals(EmbeddedBroadcast::TYPE_PUBLIC, $publicBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PUBLIC, $publicBroadcast->getName());
        $this->assertEquals(EmbeddedBroadcast::TYPE_PUBLIC, $defaultBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PUBLIC, $defaultBroadcast->getName());
    }

    public function testSetByType()
    {
        $mm = new MultimediaObject();
        $mm->setTitle('test');
        $this->dm->persist($mm);
        $this->dm->flush();

        $mm = $this->embeddedBroadcastService->setByType($mm, EmbeddedBroadcast::TYPE_PASSWORD);
        $mm = $this->mmRepo->find($mm->getId());

        $this->assertEquals(EmbeddedBroadcast::TYPE_PASSWORD, $mm->getEmbeddedBroadcast()->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PASSWORD, $mm->getEmbeddedBroadcast()->getName());

        $mm = $this->embeddedBroadcastService->setByType($mm, EmbeddedBroadcast::TYPE_LOGIN);
        $mm = $this->mmRepo->find($mm->getId());

        $this->assertEquals(EmbeddedBroadcast::TYPE_LOGIN, $mm->getEmbeddedBroadcast()->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_LOGIN, $mm->getEmbeddedBroadcast()->getName());

        $mm = $this->embeddedBroadcastService->setByType($mm, EmbeddedBroadcast::TYPE_PUBLIC);
        $mm = $this->mmRepo->find($mm->getId());

        $this->assertEquals(EmbeddedBroadcast::TYPE_PUBLIC, $mm->getEmbeddedBroadcast()->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PUBLIC, $mm->getEmbeddedBroadcast()->getName());

        $mm = $this->embeddedBroadcastService->setByType($mm, EmbeddedBroadcast::TYPE_GROUPS);
        $mm = $this->mmRepo->find($mm->getId());

        $this->assertEquals(EmbeddedBroadcast::TYPE_GROUPS, $mm->getEmbeddedBroadcast()->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_GROUPS, $mm->getEmbeddedBroadcast()->getName());

        $mm = $this->embeddedBroadcastService->setByType($mm);
        $mm = $this->mmRepo->find($mm->getId());

        $this->assertEquals(EmbeddedBroadcast::TYPE_PUBLIC, $mm->getEmbeddedBroadcast()->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PUBLIC, $mm->getEmbeddedBroadcast()->getName());
    }

    public function testCloneResource()
    {
        $group1 = new Group();
        $group1->setKey('test1');
        $group1->setName('test1');

        $group2 = new Group();
        $group2->setKey('test2');
        $group2->setName('test2');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $password = 'password';

        $ldapBroadcast = new EmbeddedBroadcast();
        $ldapBroadcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $ldapBroadcast->setName(EmbeddedBroadcast::NAME_LOGIN);
        $ldapBroadcast->setPassword($password);
        $ldapBroadcast->addGroup($group1);
        $ldapBroadcast->addGroup($group2);

        $clonedLdapBroadcast = $this->embeddedBroadcastService->cloneResource($ldapBroadcast);
        $this->assertEquals($ldapBroadcast, $clonedLdapBroadcast);
    }

    public function testGetAllBroadcastTypes()
    {
        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $this->authorizationChecker, $this->templating, $this->router, false);
        $broadcasts = array(
                            EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                            EmbeddedBroadcast::TYPE_PASSWORD => EmbeddedBroadcast::NAME_PASSWORD,
                            EmbeddedBroadcast::TYPE_LOGIN => EmbeddedBroadcast::NAME_LOGIN,
                            EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS
                            );
        $this->assertEquals($broadcasts, $embeddedBroadcastService->getAllTypes());

        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $this->authorizationChecker, $this->templating, $this->router, true);
        $broadcasts = array(
                            EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                            EmbeddedBroadcast::TYPE_LOGIN => EmbeddedBroadcast::NAME_LOGIN,
                            EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS
                            );
        $this->assertEquals($broadcasts, $embeddedBroadcastService->getAllTypes());
    }

    public function testCreatePublicEmbeddedBroadcast()
    {
        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $this->authorizationChecker, $this->templating, $this->router, false);
        $publicBroadcast = $embeddedBroadcastService->createPublicEmbeddedBroadcast();
        $this->assertEquals(EmbeddedBroadcast::TYPE_PUBLIC, $publicBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PUBLIC, $publicBroadcast->getName());
    }

    public function testUpdateTypeAndName()
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('test');

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $mm = $this->embeddedBroadcastService->setByType($multimediaObject, EmbeddedBroadcast::TYPE_PASSWORD);
        $embeddedBroadcast = $mm->getEmbeddedBroadcast();

        $this->assertEquals(EmbeddedBroadcast::TYPE_PASSWORD, $embeddedBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PASSWORD, $embeddedBroadcast->getName());
        $this->assertNotEquals(EmbeddedBroadcast::TYPE_LOGIN, $embeddedBroadcast->getType());
        $this->assertNotEquals(EmbeddedBroadcast::NAME_LOGIN, $embeddedBroadcast->getName());

        $mm = $this->embeddedBroadcastService->updateTypeAndName(EmbeddedBroadcast::TYPE_LOGIN, $multimediaObject);

        $this->assertNotEquals(EmbeddedBroadcast::TYPE_PASSWORD, $embeddedBroadcast->getType());
        $this->assertNotEquals(EmbeddedBroadcast::NAME_PASSWORD, $embeddedBroadcast->getName());
        $this->assertEquals(EmbeddedBroadcast::TYPE_LOGIN, $embeddedBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_LOGIN, $embeddedBroadcast->getName());
    }

    public function testUpdatePassword()
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('test');

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $mm = $this->embeddedBroadcastService->setByType($multimediaObject, EmbeddedBroadcast::TYPE_PASSWORD);
        $embeddedBroadcast = $mm->getEmbeddedBroadcast();

        $this->assertNull($embeddedBroadcast->getPassword());

        $password = 'testing_password';
        $mm = $this->embeddedBroadcastService->updatePassword($password, $multimediaObject);

        $this->assertEquals($password, $embeddedBroadcast->getPassword());
    }

    public function testAddGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $mm = $this->embeddedBroadcastService->setByType($multimediaObject, EmbeddedBroadcast::TYPE_PASSWORD);
        $embeddedBroadcast = $mm->getEmbeddedBroadcast();

        $this->assertEquals(0, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->addGroup($group1, $multimediaObject);

        $this->assertEquals(1, count($embeddedBroadcast->getGroups()));
        $this->assertTrue($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->addGroup($group2, $multimediaObject);

        $this->assertEquals(2, count($embeddedBroadcast->getGroups()));
        $this->assertTrue($embeddedBroadcast->containsGroup($group1));
        $this->assertTrue($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->addGroup($group3, $multimediaObject);

        $this->assertEquals(3, count($embeddedBroadcast->getGroups()));
        $this->assertTrue($embeddedBroadcast->containsGroup($group1));
        $this->assertTrue($embeddedBroadcast->containsGroup($group2));
        $this->assertTrue($embeddedBroadcast->containsGroup($group3));
    }

    public function testDeleteGroup()
    {
        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $mm = $this->embeddedBroadcastService->setByType($multimediaObject, EmbeddedBroadcast::TYPE_PASSWORD);
        $embeddedBroadcast = $mm->getEmbeddedBroadcast();

        $this->assertEquals(0, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->addGroup($group1, $multimediaObject);

        $this->assertEquals(1, count($embeddedBroadcast->getGroups()));
        $this->assertTrue($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->deleteGroup($group1, $multimediaObject);

        $this->assertEquals(0, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->deleteGroup($group2, $multimediaObject);

        $this->assertEquals(0, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->addGroup($group3, $multimediaObject);

        $this->assertEquals(1, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertTrue($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->deleteGroup($group1, $multimediaObject);

        $this->assertEquals(1, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertTrue($embeddedBroadcast->containsGroup($group3));

        $this->embeddedBroadcastService->deleteGroup($group3, $multimediaObject);

        $this->assertEquals(0, count($embeddedBroadcast->getGroups()));
        $this->assertFalse($embeddedBroadcast->containsGroup($group1));
        $this->assertFalse($embeddedBroadcast->containsGroup($group2));
        $this->assertFalse($embeddedBroadcast->containsGroup($group3));
    }

    public function testIsUserRelatedToMultimediaObject()
    {
        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@mail.com');

        $mm = new MultimediaObject();
        $mm->setTitle('mm');

        $this->dm->persist($user);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $owners1 = array($user->getId());
        $mm->setProperty('owners', $owners1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $owners2 = array();
        $mm->setProperty('owners', $owners2);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $group1 = new Group();
        $group1->setKey('key1');
        $group1->setName('name1');

        $group2 = new Group();
        $group2->setKey('key2');
        $group2->setName('name2');

        $group3 = new Group();
        $group3->setKey('key3');
        $group3->setName('name3');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->flush();

        $user->addGroup($group1);
        $mm->addGroup($group2);
        $this->dm->persist($user);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $user->addGroup($group2);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $user->removeGroup($group2);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $embeddedBroadcast = new EmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_GROUPS);
        $mm->setEmbeddedBroadcast($embeddedBroadcast);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->addGroup($group3);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $user->addGroup($group3);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $user->removeGroup($group3);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertFalse($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $user->addGroup($group2);
        $user->addGroup($group3);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $owners1 = array($user->getId());
        $mm->setProperty('owners', $owners1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));
    }

    public function testCanUserPlayMultimediaObject()
    {
        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@mail.com');

        $mm = new MultimediaObject();
        $mm->setTitle('mm');

        $this->dm->persist($user);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '', false));

        $embeddedBroadcast = new EmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_PUBLIC);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_PUBLIC);
        $mm->setEmbeddedBroadcast($embeddedBroadcast);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '', false));

        //TODO:
        // TEST LOGIN
        // TEST GROUPS
        // TEST PASSWORD
    }
}