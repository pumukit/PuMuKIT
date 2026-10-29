<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Smoke;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;

final class RouteSmokeFixtures
{
    public const USERNAME = 'smoke-admin';
    public const MULTIMEDIA_OBJECT_NUMERICAL_ID = 4242;
    public const SERIES_NUMERICAL_ID = 4343;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly FactoryService $factoryService,
        private readonly TagService $tagService,
        private readonly string $projectDir,
    ) {}

    public function load(): array
    {
        $dm = $this->documentManager;

        $user = new User();
        $user->setUsername(self::USERNAME);
        $user->setEmail('smoke-admin@pumukit.test');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $dm->persist($user);

        $person = new Person();
        $person->setName('Smoke Person');
        $person->setEmail('smoke-admin@pumukit.test');
        $person->setUser($user);
        $dm->persist($person);
        $user->setPerson($person);
        $dm->flush();

        $role = $dm->getRepository(Role::class)->findOneBy(['cod' => 'owner']) ?? $dm->getRepository(Role::class)->findOneBy([]);

        $series = $this->factoryService->createSeries($user, ['en' => 'Smoke series']);
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $user);
        $multimediaObject->setTitle('Smoke video', 'en');
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        $multimediaObject->setNumericalID(self::MULTIMEDIA_OBJECT_NUMERICAL_ID);
        $multimediaObject->setProperty('pumukit1id', (string) self::MULTIMEDIA_OBJECT_NUMERICAL_ID);
        $series->setNumericalID(self::SERIES_NUMERICAL_ID);
        $series->setProperty('pumukit1id', (string) self::SERIES_NUMERICAL_ID);

        $track = Track::create(
            'pumukit.mp4',
            i18nText::create(['en' => 'Track']),
            'en',
            Tags::create(['display', 'master', 'html5']),
            false,
            true,
            0,
            Storage::create(StorageUrl::create(''), Path::create($this->projectDir.'/tests/files/pumukit.mp4')),
            VideoAudio::create('{"format":{"duration":"10.000000"},"streams":[{"codec_type":"video","codec_name":"h264","width":640,"height":360}]}')
        );
        $multimediaObject->addTrack($track);

        $pic = new Pic();
        $pic->setUrl('/uploads/pic/smoke.png');
        $multimediaObject->addPic($pic);

        $seriesPic = new Pic();
        $seriesPic->setUrl('/uploads/pic/smoke-series.png');
        $series->addPic($seriesPic);

        $material = new Material();
        $material->setName('Smoke material');
        $material->setUrl('/uploads/material/smoke.pdf');
        $multimediaObject->addMaterial($material);

        $link = new Link();
        $link->setName('Smoke link');
        $link->setUrl('https://example.org');
        $multimediaObject->addLink($link);

        if ($role) {
            $multimediaObject->addPersonWithRole($person, $role);
        }
        $dm->flush();

        foreach (['PUCHWEBTV', 'PUDENEW'] as $cod) {
            if ($tag = $dm->getRepository(Tag::class)->findOneBy(['cod' => $cod])) {
                $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
            }
        }

        $tag = $dm->getRepository(Tag::class)->findOneBy(['cod' => 'U310000']);
        if ($tag) {
            $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        $place = new Tag();
        $place->setCod('PLACE1');
        $place->setTitle('Smoke place', 'en');
        $place->setParent($dm->getRepository(Tag::class)->findOneBy(['cod' => 'PLACES']));
        $dm->persist($place);

        $live = new Live();
        $live->setName('Smoke channel', 'en');
        $live->setUrl('https://example.org/live');
        $live->setLiveType(Live::LIVE_TYPE_WOWZA);
        $dm->persist($live);
        $dm->flush();

        $eventSeries = $this->factoryService->createSeries($user, ['en' => 'Smoke event series']);
        $eventMultimediaObject = $this->factoryService->createMultimediaObject($eventSeries, true, $user);
        $eventMultimediaObject->setTitle('Smoke event', 'en');
        $eventMultimediaObject->setType(MultimediaObject::TYPE_LIVE);
        $eventMultimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $event = new EmbeddedEvent();
        $event->setName('Smoke event', 'en');
        $event->setDate(new \DateTime('+1 day'));
        $event->setDuration(60);
        $event->setLive($live);
        $session = new EmbeddedEventSession();
        $session->setStart(new \DateTime('+1 day'));
        $session->setEnds(new \DateTime('+1 day +1 hour'));
        $session->setDuration(3600);
        $event->addEmbeddedEventSession($session);
        $eventMultimediaObject->setEmbeddedEvent($event);
        $dm->flush();

        $legacyEvent = new Event();
        $legacyEvent->setName('Smoke legacy event', 'en');
        $legacyEvent->setLive($live);
        $legacyEvent->setDate(new \DateTime('+2 days'));
        $legacyEvent->setDuration(60);
        $legacyEvent->setDisplay(true);
        $dm->persist($legacyEvent);

        $playlist = $this->factoryService->createPlaylist($user, ['en' => 'Smoke playlist']);
        $playlist->getPlaylist()->addMultimediaObject($multimediaObject);

        $group = new Group();
        $group->setKey('SMOKE');
        $group->setName('Smoke group');
        $dm->persist($group);
        $user->addGroup($group);

        $seriesType = new SeriesType();
        $seriesType->setName('Smoke type', 'en');
        $seriesType->setCod('SMOKE');
        $dm->persist($seriesType);

        $seriesStyle = new SeriesStyle();
        $seriesStyle->setName('Smoke style');
        $seriesStyle->setText('.smoke{}');
        $dm->persist($seriesStyle);

        $job = new Job();
        $job->setMmId($multimediaObject->getId());
        $job->setProfile('master_copy');
        $job->setStatus(Job::STATUS_PAUSED);
        $job->setName('Smoke job', 'en');
        $job->setPathIni($this->projectDir.'/tests/files/pumukit.mp4');
        $job->setPathEnd('/tmp/smoke.mp4');
        $job->setCpu('local');
        $dm->persist($job);
        $dm->flush();

        $tag ??= $dm->getRepository(Tag::class)->findOneBy(['cod' => 'UNESCO']);

        return [
            'documents' => [
                Series::class => $series->getId(),
                MultimediaObject::class => $multimediaObject->getId(),
                User::class => $user->getId(),
                Person::class => $person->getId(),
                Role::class => $role?->getId(),
                Tag::class => $tag?->getId(),
                Live::class => $live->getId(),
                Event::class => $legacyEvent->getId(),
                Group::class => $group->getId(),
                SeriesType::class => $seriesType->getId(),
                SeriesStyle::class => $seriesStyle->getId(),
                PermissionProfile::class => $dm->getRepository(PermissionProfile::class)->findOneBy([])?->getId(),
                Job::class => $job->getId(),
            ],
            'series' => $series->getId(),
            'mm' => $multimediaObject->getId(),
            'track' => $track->id(),
            'material' => $material->getId(),
            'link' => $link->getId(),
            'person' => $person->getId(),
            'role' => $role?->getId(),
            'user' => $user->getId(),
            'tag' => $tag?->getId(),
            'tagCod' => $tag?->getCod(),
            'place' => $place->getId(),
            'live' => $live->getId(),
            'eventMm' => $eventMultimediaObject->getId(),
            'eventSeries' => $eventSeries->getId(),
            'session' => $session->getId(),
            'legacyEvent' => $legacyEvent->getId(),
            'playlist' => $playlist->getId(),
            'group' => $group->getId(),
            'seriesType' => $seriesType->getId(),
            'seriesStyle' => $seriesStyle->getId(),
            'permissionProfile' => $dm->getRepository(PermissionProfile::class)->findOneBy([])?->getId(),
            'job' => $job->getId(),
            'mmSecret' => $multimediaObject->getSecret(),
            'seriesSecret' => $series->getSecret(),
            'playlistSecret' => $playlist->getSecret(),
        ];
    }
}
