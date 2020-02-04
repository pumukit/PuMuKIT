<?php

namespace Pumukit\CoreBundle\Tests;

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
 * @coversNothing
 */
class PumukitTestCase extends WebTestCase
{
    private $dm;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        self::bootKernel($options);

        $this->dm = self::$kernel->getContainer()->get('doctrine_mongodb')->getManager();

        $this->clearBBDD();

        return $this->dm;
    }

    public function tearDown()
    {
        $this->clearBBDD();

        return $this->dm;
    }

    public function clearBBDD()
    {
        $this->dm->getDocumentCollection(Annotation::class)->remove([]);
        $this->dm->getDocumentCollection(CpuStatus::class)->remove([]);
        $this->dm->getDocumentCollection(Event::class)->remove([]);
        $this->dm->getDocumentCollection(Group::class)->remove([]);
        $this->dm->getDocumentCollection(Job::class)->remove([]);
        $this->dm->getDocumentCollection(Live::class)->remove([]);
        $this->dm->getDocumentCollection(Message::class)->remove([]);
        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(PermissionProfile::class)->remove([]);
        $this->dm->getDocumentCollection(Person::class)->remove([]);
        $this->dm->getDocumentCollection(Role::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->getDocumentCollection(SeriesStyle::class)->remove([]);
        $this->dm->getDocumentCollection(SeriesType::class)->remove([]);
        $this->dm->getDocumentCollection(Tag::class)->remove([]);
        $this->dm->getDocumentCollection(User::class)->remove([]);
        $this->dm->getDocumentCollection(ViewsAggregation::class)->remove([]);
        $this->dm->getDocumentCollection(ViewsLog::class)->remove([]);
        $this->dm->flush();
    }
}
