<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\CoversNothing;
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
use Pumukit\StatsBundle\Document\ViewsDailyStats;
use Pumukit\StatsBundle\Document\ViewsDailyTotals;
use Pumukit\StatsBundle\Document\ViewsDailyVisitor;
use Pumukit\StatsBundle\Document\ViewsLog;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversNothing]
class PumukitTestCase extends WebTestCase
{
    protected DocumentManager $dm;
    private static bool $indexesEnsured = false;

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
        self::ensureTestDatabase($this->dm);

        if (!self::$indexesEnsured) {
            $this->dm->getSchemaManager()->ensureIndexes();
            self::$indexesEnsured = true;
        }

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
        $this->dm->getDocumentCollection(ViewsDailyStats::class)->deleteMany([]);
        $this->dm->getDocumentCollection(ViewsDailyVisitor::class)->deleteMany([]);
        $this->dm->getDocumentCollection(ViewsDailyTotals::class)->deleteMany([]);
        $this->dm->flush();
    }

    public static function ensureTestDatabase(DocumentManager $dm): void
    {
        $database = (string) $dm->getConfiguration()->getDefaultDB();
        if (!str_ends_with($database, '_test')) {
            throw new \RuntimeException(sprintf('Refusing to clear the "%s" database: tests only run against a database whose name ends with "_test".', $database));
        }
    }
}
