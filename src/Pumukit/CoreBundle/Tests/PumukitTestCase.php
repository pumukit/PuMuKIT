<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\CpuStatus;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\Message;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\StatsBundle\Document\ViewsAggregation;
use Pumukit\StatsBundle\Document\ViewsLog;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class PumukitTestCase extends WebTestCase
{
    protected DocumentManager $dm;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        self::bootKernel($options);

        $this->dm = self::$kernel->getContainer()->get('doctrine_mongodb.odm.document_manager');

        $this->clearBBDD();
    }

    public function tearDown(): void
    {
        $this->clearBBDD();
    }

    public function clearBBDD(): void
    {
        $this->dm->getDocumentCollection(Annotation::class)->deleteMany([]);
        $this->dm->getDocumentCollection(CpuStatus::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Event::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Group::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Job::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Live::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Message::class)->deleteMany([]);
        $this->dm->getDocumentCollection(MultimediaObject::class)->deleteMany([]);
        $this->dm->getDocumentCollection(PermissionProfile::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Person::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Role::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Series::class)->deleteMany([]);
        $this->dm->getDocumentCollection(SeriesStyle::class)->deleteMany([]);
        $this->dm->getDocumentCollection(SeriesType::class)->deleteMany([]);
        $this->dm->getDocumentCollection(Tag::class)->deleteMany([]);
        $this->dm->getDocumentCollection(User::class)->deleteMany([]);
        $this->dm->getDocumentCollection(ViewsAggregation::class)->deleteMany([]);
        $this->dm->getDocumentCollection(ViewsLog::class)->deleteMany([]);
        $this->dm->flush();
    }
}
