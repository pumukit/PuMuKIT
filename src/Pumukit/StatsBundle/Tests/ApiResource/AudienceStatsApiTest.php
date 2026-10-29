<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Tests\ApiResource;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\StatsBundle\Services\ViewsDailyStatsService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
final class AudienceStatsApiTest extends PumukitTestCase
{
    private const DESKTOP = 'Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0';
    private const MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
    private const TABLET = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private KernelBrowser $client;
    private User $admin;
    private User $teacher;
    private MultimediaObject $own;
    private MultimediaObject $other;
    private string $today;
    private string $from;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $role = new Role();
        $role->setCod('owner');
        $this->dm->persist($role);

        $this->admin = $this->user('stats-admin', ['ROLE_SUPER_ADMIN']);
        $this->teacher = $this->user('stats-teacher', [PermissionProfile::SCOPE_PERSONAL, 'ROLE_ACCESS_STATS']);
        $person = new Person();
        $person->setName('Teacher');
        $person->setUser($this->teacher);
        $this->teacher->setPerson($person);
        $this->dm->persist($person);

        $this->own = $this->multimediaObject('Own video');
        $this->own->addPersonWithRole($person, $role);
        $this->other = $this->multimediaObject('Other video');
        $this->dm->flush();

        $today = new \DateTimeImmutable('today 12:00');
        $this->today = $today->format('Y-m-d');
        $this->from = $today->modify('-6 days')->format('Y-m-d');

        $this->view($this->own, $today, 'http://localhost/video/1', self::DESKTOP);
        $this->view($this->own, $today, 'http://localhost/iframe/1', self::MOBILE);
        $this->view($this->own, $today->modify('-1 day'), 'http://localhost/openedx/openedx/embed/1', self::TABLET);
        $this->view($this->other, $today, 'https://example.org/page', self::DESKTOP);
        $this->view($this->other, $today->modify('-2 days'), '', self::DESKTOP);
        $this->view($this->other, $today->modify('-3 days'), 'http://localhost/admin/mms/1', self::DESKTOP);
        $this->view($this->own, $today->modify('-8 days'), 'http://localhost/video/1', self::DESKTOP);
        $this->dm->flush();

        $dailyStats = self::getContainer()->get(ViewsDailyStatsService::class);
        for ($day = $today->modify('-10 days')->setTime(0, 0); $day <= $today; $day = $day->modify('+1 day')) {
            $dailyStats->rebuildDay($day);
        }
    }

    public function testTheSummaryOfAGlobalUserCountsTheWholeCatalogue(): void
    {
        $summary = $this->get($this->admin, 'summary');

        $this->assertSame(6, $summary['views']);
        $this->assertSame(1, $summary['previousViews']);
        $this->assertSame(2, $summary['viewedObjects']);
        $this->assertSame(2, $summary['viewedSeries']);
        $this->assertSame(2, $summary['publishedObjects']);
        $this->assertSame(0, $summary['unviewedObjects']);
        $this->assertSame(6, $summary['uniqueViews']);
        $this->assertSame(1, $summary['previousUniqueViews']);
    }

    public function testATeacherOnlySeesTheirOwnMultimediaObjects(): void
    {
        $summary = $this->get($this->teacher, 'summary');
        $this->assertSame(3, $summary['views']);
        $this->assertSame(1, $summary['publishedObjects']);

        $top = $this->get($this->teacher, 'top-videos');
        $this->assertSame([(string) $this->own->getId()], array_column($top['items'], 'id'));

        $this->assertSame(3, $this->get($this->teacher, 'summary', ['multimediaObject' => (string) $this->own->getId()])['views']);

        $this->request($this->teacher, 'summary', ['multimediaObject' => (string) $this->other->getId()]);
        $this->assertResponseStatusCodeSame(404);

        $this->request($this->teacher, 'summary', ['series' => (string) $this->other->getSeries()->getId()]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheViewsAreClassifiedBySourceAndDevice(): void
    {
        $sources = array_column($this->get($this->admin, 'sources')['items'], 'views', 'key');
        $this->assertSame(['portal' => 1, 'embed' => 1, 'lms' => 1, 'backoffice' => 1, 'external' => 1, 'direct' => 1], $sources);

        $devices = array_column($this->get($this->admin, 'devices')['items'], 'views', 'key');
        $this->assertSame(['desktop' => 4, 'mobile' => 1, 'tablet' => 1, 'unknown' => 0], $devices);
    }

    public function testTheTimelineAndTheHeatmapAddUpToTheViews(): void
    {
        $timeline = $this->get($this->admin, 'timeline');
        $this->assertSame('day', $timeline['interval']);
        $this->assertCount(7, $timeline['points']);
        $this->assertSame(6, array_sum(array_column($timeline['points'], 'views')));
        $this->assertSame(1, array_sum(array_column($timeline['points'], 'previousViews')));
        $this->assertSame(3, end($timeline['points'])['views']);

        $heatmap = $this->get($this->admin, 'heatmap');
        $this->assertSame(6, array_sum(array_map('array_sum', $heatmap['cells'])));
    }

    public function testASingleDayIsShownByHour(): void
    {
        $timeline = $this->get($this->admin, 'timeline', ['from' => $this->today, 'to' => $this->today]);

        $this->assertSame('hour', $timeline['interval']);
        $this->assertCount(24, $timeline['points']);
        $this->assertSame(3, $timeline['points'][12]['views']);
        $this->assertSame(1, $timeline['points'][12]['previousViews']);
    }

    public function testADayWithViewsFromMidnightIsReadByHour(): void
    {
        $day = new \DateTimeImmutable('-20 days 00:30');
        $this->view($this->own, $day, '', self::DESKTOP);
        $this->dm->flush();
        self::getContainer()->get(ViewsDailyStatsService::class)->rebuildDay($day->setTime(0, 0));

        $timeline = $this->get($this->admin, 'timeline', ['from' => $day->format('Y-m-d'), 'to' => $day->format('Y-m-d')]);
        $this->assertSame(1, $timeline['points'][0]['views']);

        $heatmap = $this->get($this->admin, 'heatmap', ['from' => $day->format('Y-m-d'), 'to' => $day->format('Y-m-d')]);
        $this->assertSame(1, $heatmap['cells'][(int) $day->format('N') - 1][0]);
    }

    public function testTheTopListsAreSortedByViews(): void
    {
        $top = $this->get($this->admin, 'top-videos');
        $this->assertSame(6, $top['total']);
        $views = array_column($top['items'], 'views');
        $this->assertSame([3, 3], $views);
        $this->assertEqualsCanonicalizing(['Own video', 'Other video'], array_column($top['items'], 'title'));
        $this->assertSame([0.5, 0.5], array_column($top['items'], 'share'));

        $series = $this->get($this->admin, 'top-series');
        $this->assertCount(2, $series['items']);
    }

    public function testInvalidParametersAreRejected(): void
    {
        $this->request($this->admin, 'summary', ['from' => '2026-13-01']);
        $this->assertResponseStatusCodeSame(400);

        $this->request($this->admin, 'summary', ['from' => $this->today, 'to' => $this->from]);
        $this->assertResponseStatusCodeSame(400);

        $this->request($this->admin, 'summary', ['series' => 'x']);
        $this->assertResponseStatusCodeSame(400);

        $this->request($this->admin, 'summary', ['from' => (new \DateTimeImmutable('-3 years'))->format('Y-m-d')]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testAnonymousUsersCannotReadTheStats(): void
    {
        $this->client->request('GET', '/api/v1/stats/summary', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseStatusCodeSame(401);
    }

    private function get(User $user, string $report, array $parameters = []): array
    {
        $this->request($user, $report, $parameters);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function request(User $user, string $report, array $parameters): void
    {
        $this->client->loginUser($user, 'pumukit');
        $this->client->request('GET', '/api/v1/stats/'.$report, $parameters + ['from' => $this->from, 'to' => $this->today], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    private function user(string $username, array $roles): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles($roles);
        $this->dm->persist($user);

        return $user;
    }

    private function multimediaObject(string $title): MultimediaObject
    {
        $factory = self::getContainer()->get(FactoryService::class);
        $series = $factory->createSeries(null, ['en' => $title.' series']);
        $multimediaObject = $factory->createMultimediaObject($series);
        $multimediaObject->setTitle($title, 'en');
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);

        return $multimediaObject;
    }

    private function view(MultimediaObject $multimediaObject, \DateTimeImmutable $date, string $referer, string $userAgent): void
    {
        $log = new ViewsLog('http://localhost/trackfile/1', '203.0.113.0', $userAgent, $referer, $multimediaObject->getId(), $multimediaObject->getSeries()->getId(), null, null, md5($userAgent.$date->format('Y-m-d')));
        $log->setDate(\DateTime::createFromImmutable($date));
        $this->dm->persist($log);
    }
}
