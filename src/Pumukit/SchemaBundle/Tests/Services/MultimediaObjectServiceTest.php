<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Document\ValueObject\Url;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;

/**
 * @internal
 *
 * @coversNothing
 */
class MultimediaObjectServiceTest extends PumukitTestCase
{
    private $repo;
    private $tagRepo;
    private $factory;
    private $mmsService;
    private $tagService;
    private $i18nService;
    private $projectDir;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->tagRepo = $this->dm->getRepository(Tag::class);
        $this->factory = static::$kernel->getContainer()->get(FactoryService::class);
        $this->mmsService = static::$kernel->getContainer()->get(MultimediaObjectService::class);
        $this->tagService = static::$kernel->getContainer()->get(TagService::class);
        $this->i18nService = new i18nService(['en', 'es'], 'en');
        $this->projectDir = static::$kernel->getContainer()->getParameter('kernel.project_dir');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->repo = null;
        $this->tagRepo = null;
        $this->factory = null;
        $this->mmsService = null;
        $this->tagService = null;
        gc_collect_cycles();
    }

    public function testIsPublished()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = PumukitWebTVBundle::WEB_TV_TAG;
        static::assertFalse($this->mmsService->isPublished($mm, $webTVCode));

        $webTVTag = $this->tagRepo->findOneBy(['cod' => $webTVCode]);
        $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        static::assertFalse($this->mmsService->isPublished($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);

        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->mmsService->isPublished($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_HIDDEN);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->mmsService->isPublished($mm, $webTVCode));
    }

    public function testIsHidden()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = PumukitWebTVBundle::WEB_TV_TAG;
        static::assertFalse($this->mmsService->isHidden($mm, $webTVCode));

        $webTVTag = $this->tagRepo->findOneBy(['cod' => $webTVCode]);
        $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        static::assertFalse($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_HIDDEN);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertTrue($this->mmsService->isHidden($mm, $webTVCode));

        $mm->setStatus(MultimediaObject::STATUS_BLOCKED);
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->mmsService->isHidden($mm, $webTVCode));
    }

    public function testHasPlayableResource()
    {
        $series = $this->factory->createSeries();
        $mm1 = $this->factory->createMultimediaObject($series);

        static::assertFalse($this->mmsService->hasPlayableResource($mm1));

        $track2 = $this->generateTrackMedia(['display']);
        $mm1->addTrack($track2);
        $this->dm->persist($mm1);
        $this->dm->flush();

        static::assertTrue($this->mmsService->hasPlayableResource($mm1));
    }

    public function testCanBeDisplayed()
    {
        $this->createTags();

        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $webTVCode = PumukitWebTVBundle::WEB_TV_TAG;

        $webTVTag = $this->tagRepo->findOneBy(['cod' => $webTVCode]);
        $this->tagService->addTagToMultimediaObject($mm, $webTVTag->getId());

        $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mm->setProperty('opencast', 'opencast_id');
        $this->dm->persist($mm);
        $this->dm->flush();

        static::assertFalse($this->mmsService->canBeDisplayed($mm, $webTVCode));
    }

    public function testResetMagicUrl()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $secret = $mm->getSecret();

        static::assertNotEquals($secret, $this->mmsService->resetMagicUrl($mm));
    }

    public function testUpdateMultimediaObject()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        $title = 'First title';
        $mm->setTitle($title);
        $this->dm->persist($mm);
        $this->dm->flush();

        $newTitle = 'New Title';
        $mm->setTitle($newTitle);
        $mm = $this->mmsService->updateMultimediaObject($mm);

        $multimediaObject = $this->repo->find($mm->getId());
        static::assertEquals($newTitle, $multimediaObject->getTitle());
    }

    public function testIncNumView()
    {
        $series = $this->factory->createSeries();
        $mm = $this->factory->createMultimediaObject($series);

        static::assertEquals(0, $mm->getNumView());

        $this->mmsService->incNumView($mm);
        static::assertEquals(1, $mm->getNumView());
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
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group1, $multimediaObject);

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group2, $multimediaObject);

        static::assertCount(2, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertTrue($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group3, $multimediaObject);

        static::assertCount(3, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertTrue($multimediaObject->containsGroup($group2));
        static::assertTrue($multimediaObject->containsGroup($group3));
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
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setTitle('test');

        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->persist($group3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertTrue($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group2, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));

        $this->mmsService->addGroup($group3, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertTrue($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group1, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(1, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertTrue($multimediaObject->containsGroup($group3));

        $this->mmsService->deleteGroup($group3, $multimediaObject);

        $multimediaObject = $this->repo->find($multimediaObject->getId());

        static::assertCount(0, $multimediaObject->getGroups());
        static::assertFalse($multimediaObject->containsGroup($group1));
        static::assertFalse($multimediaObject->containsGroup($group2));
        static::assertFalse($multimediaObject->containsGroup($group3));
    }

    public function testIsUserOwner()
    {
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setEmail('user1@mail.com');

        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setEmail('user2@mail.com');

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $owners1 = [];
        $owners2 = [$user1->getId()];
        $owners3 = [$user1->getId(), $user2->getId()];

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');
        $mm1->setProperty('owners', $owners1);

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');
        $mm2->setProperty('owners', $owners2);

        $mm3 = new MultimediaObject();
        $mm3->setNumericalID(3);
        $mm3->setTitle('mm3');
        $mm3->setProperty('owners', $owners3);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        static::assertFalse($this->mmsService->isUserOwner($user1, $mm1));
        static::assertFalse($this->mmsService->isUserOwner($user2, $mm1));
        static::assertTrue($this->mmsService->isUserOwner($user1, $mm2));
        static::assertFalse($this->mmsService->isUserOwner($user2, $mm2));
        static::assertTrue($this->mmsService->isUserOwner($user1, $mm3));
        static::assertTrue($this->mmsService->isUserOwner($user2, $mm3));
    }

    public function testDeleteAllFromGroup()
    {
        $group = new Group();
        $group->setKey('key');
        $group->setName('group');
        $this->dm->persist($group);
        $this->dm->flush();

        static::assertCount(0, $this->repo->findWithGroup($group)->toArray());

        $mm1 = new MultimediaObject();
        $mm1->setNumericalID(1);
        $mm1->setTitle('mm1');
        $mm1->addGroup($group);

        $mm2 = new MultimediaObject();
        $mm2->setNumericalID(2);
        $mm2->setTitle('mm2');
        $mm2->addGroup($group);

        $mm3 = new MultimediaObject();
        $mm3->setNumericalID(3);
        $mm3->setTitle('mm3');
        $mm3->addGroup($group);

        $this->dm->persist($mm1);
        $this->dm->persist($mm2);
        $this->dm->persist($mm3);
        $this->dm->flush();

        static::assertCount(3, $this->repo->findWithGroup($group)->toArray());

        $this->mmsService->deleteAllFromGroup($group);
        static::assertCount(0, $this->repo->findWithGroup($group)->toArray());
    }

    private function createTags()
    {
        $rootTag = new Tag();
        $rootTag->setCod('ROOT');
        $rootTag->setTitle('ROOT');
        $rootTag->setDisplay(false);
        $rootTag->setMetatag(true);

        $this->dm->persist($rootTag);
        $this->dm->flush();

        $pubChannelTag = new Tag();
        $pubChannelTag->setCod('PUBLICATIONCHANNELS');
        $pubChannelTag->setTitle('Publication Channels');
        $pubChannelTag->setDisplay(true);
        $pubChannelTag->setMetatag(true);
        $pubChannelTag->setParent($rootTag);

        $this->dm->persist($pubChannelTag);
        $this->dm->flush();

        $webTVTag = new Tag();
        $webTVTag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $webTVTag->setTitle('WebTV Publication Channel');
        $webTVTag->setDisplay(true);
        $webTVTag->setMetatag(false);
        $webTVTag->setParent($pubChannelTag);

        $this->dm->persist($webTVTag);
        $this->dm->flush();
    }

    private function generateTrackMedia(array $tags): MediaInterface
    {
        $originalName = 'originalName'.random_int(0, mt_getrandmax());
        $description = i18nText::create($this->i18nService->generateI18nText('18nDescription'));
        $language = 'en';
        $tags = Tags::create($tags);
        $views = 0;
        $url = Url::create('');
        $path = Path::create($this->projectDir.'/tests/files/pumukit.mp4');
        $storage = Storage::create($url, $path);
        $mediaMetadata = VideoAudio::create('{"streams":[{"index":0,"codec_name":"h264","codec_long_name":"H.264 \\/ AVC \\/ MPEG-4 AVC \\/ MPEG-4 part 10","profile":"High 4:4:4 Predictive","codec_type":"video","codec_tag_string":"avc1","codec_tag":"0x31637661","width":1920,"height":1080,"coded_width":1920,"coded_height":1080,"closed_captions":0,"film_grain":0,"has_b_frames":2,"pix_fmt":"yuv444p","level":40,"chroma_location":"left","field_order":"progressive","refs":1,"is_avc":"true","nal_length_size":"4","id":"0x1","r_frame_rate":"30\\/1","avg_frame_rate":"30\\/1","time_base":"1\\/15360","start_pts":0,"start_time":"0.000000","duration_ts":153600,"duration":"10.000000","bit_rate":"89370","bits_per_raw_sample":"8","nb_frames":"300","extradata_size":47,"disposition":{"default":1,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0,"timed_thumbnails":0,"captions":0,"descriptions":0,"metadata":0,"dependent":0,"still_image":0},"tags":{"language":"und","handler_name":"VideoHandler","vendor_id":"[0][0][0][0]"}}],"format":{"filename":"\\/srv\\/pumukit\\/public\\/storage\\/masters\\/662608a27328d054160eaf83\\/6626097a07ef8d6b000d4f44.mp4","nb_streams":1,"nb_programs":0,"format_name":"mov,mp4,m4a,3gp,3g2,mj2","format_long_name":"QuickTime \\/ MOV","start_time":"0.000000","duration":"10.000000","size":"116169","bit_rate":"92935","probe_score":100,"tags":{"major_brand":"isom","minor_version":"512","compatible_brands":"isomiso2avc1mp41","encoder":"Lavf58.76.100"}}}');

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
