<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Group;

class EmbeddedBroadcastServiceTest extends WebTestCase
{
    private $dm;
    private $mmRepo;
    private $embeddedBroadcastService;

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
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->flush();
    }

    public function testCreateEmbeddedBroadcastByType()
    {
        $embeddedBroadcastService = new EmbeddedBroadcastService($this->dm);
        $passwordBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_PASSWORD);
        $ldapBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_LDAP);
        $groupsBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_GROUPS);
        $publicBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_PUBLIC);
        $defaultBroadcast = $embeddedBroadcastService->createEmbeddedBroadcastByType();

        $this->assertEquals(EmbeddedBroadcast::TYPE_PASSWORD, $passwordBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_PASSWORD, $passwordBroadcast->getName());
        $this->assertEquals(EmbeddedBroadcast::TYPE_LDAP, $ldapBroadcast->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_LDAP, $ldapBroadcast->getName());
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

        $mm = $this->embeddedBroadcastService->setByType($mm, EmbeddedBroadcast::TYPE_LDAP);
        $mm = $this->mmRepo->find($mm->getId());

        $this->assertEquals(EmbeddedBroadcast::TYPE_LDAP, $mm->getEmbeddedBroadcast()->getType());
        $this->assertEquals(EmbeddedBroadcast::NAME_LDAP, $mm->getEmbeddedBroadcast()->getName());

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
        $ldapBroadcast->setType(EmbeddedBroadcast::TYPE_LDAP);
        $ldapBroadcast->setName(EmbeddedBroadcast::NAME_LDAP);
        $ldapBroadcast->setPassword($password);
        $ldapBroadcast->addGroup($group1);
        $ldapBroadcast->addGroup($group2);

        $clonedLdapBroadcast = $this->embeddedBroadcastService->cloneResource($ldapBroadcast);
        $this->assertEquals($ldapBroadcast, $clonedLdapBroadcast);
    }

    public function testGetAllBroadcastTypes()
    {
        $broadcasts = array(
                            EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                            EmbeddedBroadcast::TYPE_PASSWORD => EmbeddedBroadcast::NAME_PASSWORD,
                            EmbeddedBroadcast::TYPE_LDAP => EmbeddedBroadcast::NAME_LDAP,
                            EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS
                            );
        $this->assertEquals($broadcasts, $this->embeddedBroadcastService->getAllTypes());
    }
}