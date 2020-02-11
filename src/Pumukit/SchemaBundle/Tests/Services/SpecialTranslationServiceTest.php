<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 * @coversNothing
 */
class SpecialTranslationServiceTest extends PumukitTestCase
{
    private $mmRepo;
    private $specialTranslationService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->mmRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->specialTranslationService = static::$kernel->getContainer()->get('pumukitschema.special_translation');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->mmRepo = null;
        $this->specialTranslationService = null;
        gc_collect_cycles();
    }

    public function testGetI18nEmbeddedBroadcast()
    {
        $group = new Group();
        $group->setKey('key');
        $group->setName('group');
        $this->dm->persist($group);
        $this->dm->flush();

        $this->assertEquals(0, count($this->mmRepo->findWithGroupInEmbeddedBroadcast($group)->toArray()));

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');
        $emb1 = new EmbeddedBroadcast();
        $emb1->addGroup($group);
        $emb1->setType(EmbeddedBroadcast::TYPE_GROUPS);
        $mm1->setEmbeddedBroadcast($emb1);

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');
        $emb2 = new EmbeddedBroadcast();
        $emb2->setType(EmbeddedBroadcast::TYPE_PUBLIC);
        $mm2->setEmbeddedBroadcast($emb2);

        $mm3 = new MultimediaObject();
        $mm3->setNumericalID(3);
        $mm3->setTitle('mm3');
        $emb3 = new EmbeddedBroadcast();
        $emb3->setType(EmbeddedBroadcast::TYPE_PASSWORD);
        $emb3->setPassword('test');
        $mm3->setEmbeddedBroadcast($emb3);

        $mm4 = new MultimediaObject();
        $mm4->setNumericalID(4);
        $mm4->setTitle('mm2');
        $emb4 = new EmbeddedBroadcast();
        $emb4->setType(EmbeddedBroadcast::TYPE_LOGIN);
        $mm4->setEmbeddedBroadcast($emb4);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->persist($mm4);
        $this->dm->flush();

        $locale = 'en';
        $this->assertEquals((string) $emb1, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb1, $locale));
        $this->assertEquals((string) $emb2, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb2, $locale));
        $this->assertEquals((string) $emb3, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb3, $locale));
        $this->assertEquals((string) $emb4, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb4, $locale));

        $locale = 'es';
        $this->assertNotEquals((string) $emb1, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb1, $locale));
        $this->assertNotEquals((string) $emb2, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb2, $locale));
        $this->assertNotEquals((string) $emb3, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb3, $locale));
        $this->assertNotEquals((string) $emb4, $this->specialTranslationService->getI18nEmbeddedBroadcast($emb4, $locale));
    }
}
