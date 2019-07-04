<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\PermissionProfile;

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

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmRepo = $this->dm
            ->getRepository(MultimediaObject::class);
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

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Group::class)->remove([]);
        $this->dm->getDocumentCollection(User::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmRepo = null;
        $this->embeddedBroadcastService = null;
        $this->mmsService = null;
        $this->dispatcher = null;
        $this->authorizationChecker = null;
        $this->templating = null;
        $this->router = null;
        gc_collect_cycles();
        parent::tearDown();
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
        $broadcasts = [
                            EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                            EmbeddedBroadcast::TYPE_PASSWORD => EmbeddedBroadcast::NAME_PASSWORD,
                            EmbeddedBroadcast::TYPE_LOGIN => EmbeddedBroadcast::NAME_LOGIN,
                            EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS,
                            ];
        $this->assertEquals($broadcasts, $embeddedBroadcastService->getAllTypes());

        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $this->authorizationChecker, $this->templating, $this->router, true);
        $broadcasts = [
                            EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                            EmbeddedBroadcast::TYPE_LOGIN => EmbeddedBroadcast::NAME_LOGIN,
                            EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS,
                            ];
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

        $owners1 = [$user->getId()];
        $mm->setProperty('owners', $owners1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->isUserRelatedToMultimediaObject($mm, $user));

        $owners2 = [];
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

        $owners1 = [$user->getId()];
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

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setScope(PermissionProfile::SCOPE_NONE);
        $permissionProfile->setName('permission profile');

        $mm = new MultimediaObject();
        $mm->setTitle('mm');

        $this->dm->persist($user);
        $this->dm->persist($permissionProfile);
        $this->dm->persist($mm);
        $this->dm->flush();

        // Test No EmbeddedBroadcast

        $this->assertTrue($this->embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        // Test TYPE_PUBLIC

        $embeddedBroadcast = new EmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_PUBLIC);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_PUBLIC);
        $mm->setEmbeddedBroadcast($embeddedBroadcast);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($this->embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        // Test TYPE_LOGIN

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_LOGIN);
        $this->dm->persist($mm);
        $this->dm->flush();

        $authorizationChecker = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $content = 'test';
        $templating = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnValue($content));

        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $authorizationChecker, $templating, $this->router, false);

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, null, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $authorizationChecker = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm, $this->mmsService, $this->dispatcher, $authorizationChecker, $templating, $this->router, false);

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        // Test TYPE_GROUPS

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

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_GROUPS);
        $mm->addGroup($group2);
        $user->addGroup($group1);
        $this->dm->persist($user);
        $this->dm->persist($mm);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->addGroup($group3);
        $this->dm->persist($mm);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $user->setPermissionProfile($permissionProfile);
        $this->dm->persist($user);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $permissionProfile->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $permissionProfile->setScope(PermissionProfile::SCOPE_GLOBAL);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        $permissionProfile->setScope(PermissionProfile::SCOPE_PERSONAL);
        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $mm->addGroup($group1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        $mm->removeGroup($group1);
        $mm->addGroup($group3);
        $this->dm->persist($mm);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->addGroup($group1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->removeGroup($group1);
        $embeddedBroadcast->addGroup($group2);
        $this->dm->persist($mm);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, '');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $owners = [$user->getId()];
        $mm->setProperty('owners', $owners);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        $mm->addGroup($group1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->addGroup($group1);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, ''));

        // Test TYPE_PASSWORD

        $series = new Series();
        $series->setTitle('series');
        $this->dm->persist($series);
        $this->dm->flush();

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_PASSWORD);
        $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_PASSWORD);
        $mm->setSeries($series);
        $this->dm->persist($mm);
        $this->dm->flush();

        $password = '';
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, $password);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->setPassword($password);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $password = 'password';
        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, $password);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->setPassword('not matching password');
        $this->dm->persist($mm);
        $this->dm->flush();

        $response = $embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, $password);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $embeddedBroadcast = $mm->getEmbeddedBroadcast();
        $embeddedBroadcast->setPassword($password);
        $this->dm->persist($mm);
        $this->dm->flush();

        $this->assertTrue($embeddedBroadcastService->canUserPlayMultimediaObject($mm, $user, $password));
    }

    public function testDeleteAllFromGroup()
    {
        $group = new Group();
        $group->setKey('key');
        $group->setName('group');
        $this->dm->persist($group);
        $this->dm->flush();

        $this->assertEquals(0, count($this->mmRepo->findWithGroupInEmbeddedBroadcast($group)->toArray()));

        $mm1 = new MultimediaObject();
        $mm1->setTitle('mm1');
        $emb1 = new EmbeddedBroadcast();
        $emb1->addGroup($group);
        $mm1->setEmbeddedBroadcast($emb1);

        $mm2 = new MultimediaObject();
        $mm2->setTitle('mm2');
        $emb2 = new EmbeddedBroadcast();
        $emb2->addGroup($group);
        $mm2->setEmbeddedBroadcast($emb2);

        $mm3 = new MultimediaObject();
        $mm3->setTitle('mm3');
        $mm3->addGroup($group);
        $emb3 = new EmbeddedBroadcast();
        $emb3->addGroup($group);
        $mm3->setEmbeddedBroadcast($emb3);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        $this->assertEquals(3, count($this->mmRepo->findWithGroupInEmbeddedBroadcast($group)->toArray()));

        $this->embeddedBroadcastService->deleteAllFromGroup($group);
        $this->assertEquals(0, count($this->mmRepo->findWithGroupInEmbeddedBroadcast($group)->toArray()));
    }
}
