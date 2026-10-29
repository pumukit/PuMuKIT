<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Smoke;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use MongoDB\Database;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
#[CoversNothing]
#[Group('smoke')]
final class RouteSmokeTest extends PumukitTestCase
{
    private const SKIPPED_PATHS = '#^/(_|admin/logout$|logout$|login$)#';

    private const MODIFY_DATA_ON_GET = [
        'pumukit_live_chat_post' => 'H36',
        'pumukit_live_chat_basic_post' => 'H36',
        'pumukit_new_admin_live_event_create' => 'H41',
        'pumukit_new_admin_live_event_options' => 'H41',
        'pumukit_new_admin_live_event_session_delete' => 'H41',
        'pumukit_new_admin_live_event_clone_session' => 'H41',
        'pumukit_new_admin_autocomplete_series_with_event_data' => 'H41',
        'pumukit_multimedia_object_reject_co_owner' => 'H41',
        'pumukit_newadmin_series_styles_create' => 'H41',
        'pumukit_newadmin_series_styles_delete' => 'H41',
        'pumukitnewadmin_places_delete' => 'H41',
        'pumukitnewadmin_unesco_delete_tag' => 'H41',
        'pumukitnewadmin_unesco_delete' => 'H41',
        'pumukitnewadmin_unesco_clone' => 'H41',
        'pumukitnewadmin_person_link' => 'H41',
        'pumukitnewadmin_series_create' => 'H41',
        'pumukitnewadmin_track_retranscode' => 'H41',
    ];

    private const NOT_REQUESTED = [
        'tus_post_token' => 'TUS upload server',
        'tus_files' => 'TUS upload server',
        'pumukit_schema_annotationsapi_getbyid' => 'needs an annotation',
        'api_genid' => 'API Platform internal route',
        'api_validation_errors' => 'API Platform internal route',
        '_api_validation_errors_problem' => 'API Platform internal route',
        '_api_validation_errors_hydra' => 'API Platform internal route',
        '_api_validation_errors_jsonapi' => 'API Platform internal route',
        '_api_validation_errors_xml' => 'API Platform internal route',
    ];

    private const CONTROLLER_RESOURCES = [
        'SeriesPicController' => 'series',
        'SeriesController' => 'series',
        'SeriesStylesController' => 'seriesStyle',
        'SeriesTypeController' => 'seriesType',
        'PlaylistPicController' => 'playlist',
        'PlaylistMultimediaObjectController' => 'playlist',
        'PlaylistController' => 'playlist',
        'BasePlaylistController' => 'playlist',
        'MultimediaObjectPicController' => 'mm',
        'MultimediaObjectController' => 'mm',
        'TrackController' => 'track',
        'MaterialController' => 'material',
        'LinkController' => 'link',
        'PersonController' => 'person',
        'RoleController' => 'role',
        'GroupController' => 'group',
        'PermissionProfileController' => 'permissionProfile',
        'UserController' => 'user',
        'TagController' => 'tag',
        'PlaceController' => 'place',
        'LiveController' => 'live',
        'LegacyEventController' => 'legacyEvent',
        'LegacyEventPicController' => 'legacyEvent',
        'EventsController' => 'eventMm',
        'InfoController' => 'job',
        'JobController' => 'job',
        'UNESCOController' => 'tag',
        'PlayerController' => 'mm',
        'BasePlayerController' => 'mm',
        'TrackFileController' => 'track',
        'SecurityController' => 'mm',
        'DefaultController' => 'live',
        'EventController' => 'eventMm',
        'MultimediaObjectArchiveController' => 'mm',
        'PaellaRepositoryController' => 'mm',
        'BaseLivePlayerController' => 'live',
    ];

    private KernelBrowser $client;
    private Database $database;
    private array $ids;
    private array $snapshot = [];
    private string $projectDir;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->client->followRedirects(false);
        $container = self::getContainer();
        $this->dm = $container->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        $this->projectDir = $container->getParameter('kernel.project_dir');
        $this->database = $this->dm->getClient()->selectDatabase($this->dm->getConfiguration()->getDefaultDB());

        $command = new CommandTester((new Application(self::$kernel))->find('pumukit:init:repo'));
        $command->execute(['repo' => 'all', '--force' => true]);

        $fixtures = new RouteSmokeFixtures(
            $this->dm,
            $container->get(FactoryService::class),
            $container->get(TagService::class),
            $this->projectDir,
        );
        $this->ids = $fixtures->load();

        foreach ($this->database->listCollectionNames() as $name) {
            $this->snapshot[$name] = $this->database->selectCollection($name)->find([], ['typeMap' => ['root' => 'bson', 'document' => 'bson', 'array' => 'bson']])->toArray();
        }
    }

    public function testEveryGetRouteAnswersWithoutServerErrorsOrDataChanges(): void
    {
        $routes = self::getContainer()->get('router')->getRouteCollection();
        $failures = [];
        $requested = 0;

        foreach (array_merge(self::MODIFY_DATA_ON_GET, self::NOT_REQUESTED) as $name => $reason) {
            if (null === $routes->get($name)) {
                $failures[] = "{$name}: excluded ({$reason}) but the route no longer exists, remove it from the list";
            }
        }

        foreach ($routes as $name => $route) {
            if (!$this->isGetRoute($route) || preg_match(self::SKIPPED_PATHS, $route->getPath()) || isset(self::MODIFY_DATA_ON_GET[$name]) || isset(self::NOT_REQUESTED[$name])) {
                continue;
            }

            $url = $this->url($name, $route, $failures);
            if (null === $url) {
                continue;
            }

            $public = !str_starts_with($route->getPath(), '/admin') && !str_starts_with($route->getPath(), '/api');
            foreach ($public ? [true, false] : [true] as $asAdmin) {
                ++$requested;
                $failure = $this->request($url, $asAdmin);
                if ($failure) {
                    $failures[] = sprintf('%s %s (%s): %s', $asAdmin ? 'admin' : 'anonymous', $url, $name, $failure);
                }
            }
        }

        $this->assertGreaterThan(400, $requested);
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    private function request(string $url, bool $asAdmin): ?string
    {
        $this->restore();
        $this->client->restart();
        if ($asAdmin) {
            $this->client->loginUser($this->dm->getRepository(User::class)->find($this->ids['user']), 'pumukit');
        }

        $before = $this->fingerprint();
        $this->client->request('GET', $url);
        $code = $this->client->getResponse()->getStatusCode();
        $changed = array_keys(array_diff_assoc($this->fingerprint(), $before));

        if ($code >= 500) {
            $headers = $this->client->getResponse()->headers;

            return sprintf('%d %s at %s', $code, rawurldecode((string) $headers->get('X-Debug-Exception')), str_replace($this->projectDir.'/', '', rawurldecode((string) $headers->get('X-Debug-Exception-File'))));
        }

        return $changed ? 'GET modified the collections '.implode(', ', $changed) : null;
    }

    private function restore(): void
    {
        foreach ($this->database->listCollectionNames() as $name) {
            if (!isset($this->snapshot[$name])) {
                $this->database->selectCollection($name)->drop();
            }
        }
        foreach ($this->snapshot as $name => $documents) {
            $collection = $this->database->selectCollection($name);
            $collection->deleteMany([]);
            if ($documents) {
                $collection->insertMany($documents);
            }
        }
        $this->dm->clear();
    }

    private function fingerprint(): array
    {
        $fingerprint = [];
        foreach ($this->database->listCollectionNames() as $name) {
            $documents = $this->database->selectCollection($name)->find([], ['sort' => ['_id' => 1], 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']])->toArray();
            $fingerprint[$name] = md5(serialize($documents));
        }

        return $fingerprint;
    }

    private function isGetRoute(Route $route): bool
    {
        return !$route->getMethods() || in_array('GET', $route->getMethods(), true);
    }

    private function url(string $name, Route $route, array &$failures): ?string
    {
        $controller = (string) $route->getDefault('_controller');
        $isApiPlatform = str_starts_with($controller, 'api_platform');
        [$class, $method] = str_contains($controller, '::') ? explode('::', $controller) : [null, null];

        $url = $route->getPath();
        foreach ($route->compile()->getPathVariables() as $variable) {
            $value = $isApiPlatform ? $this->apiPlatformValue($variable, $route) : $this->value($variable, $route, $class, $method);
            $value ??= $route->hasDefault($variable) ? (string) $route->getDefault($variable) : null;
            if (null === $value || '' === $value) {
                $failures[] = "{$name}: no value for {{$variable}} in {$route->getPath()}, add it to the smoke test resolver";

                return null;
            }
            $url = str_replace(['{'.$variable.'}', '{!'.$variable.'}'], rawurlencode($value), $url);
        }

        return $url;
    }

    private function apiPlatformValue(string $variable, Route $route): ?string
    {
        $path = $route->getPath();

        return match ($variable) {
            'id' => match (true) {
                str_contains($path, 'multimedia_objects') => $this->ids['mm'],
                str_contains($path, 'people') => $this->ids['person'],
                str_contains($path, 'series') => $this->ids['series'],
                str_contains($path, 'tags') => $this->ids['tag'],
                default => null,
            },
            '_format' => 'jsonld',
            'index' => 'index',
            'shortName' => 'MultimediaObject',
            'status' => '404',
            default => null,
        };
    }

    private function value(string $variable, Route $route, ?string $class, ?string $method): ?string
    {
        $path = $route->getPath();
        $isEvent = (bool) preg_match('#liveevent|/live/event|/event/#', $path);

        return match ($variable) {
            'id' => $class ? $this->documentId($class, (string) $method, $path, $isEvent) : null,
            'mmId', 'multimediaObject', 'multimediaObjectId', 'element' => $isEvent ? $this->ids['eventMm'] : $this->ids['mm'],
            'series' => $this->ids['series'],
            'roleId' => $this->ids['role'],
            'session_id' => $this->ids['session'],
            'username' => RouteSmokeFixtures::USERNAME,
            'secret' => match (true) {
                str_contains($path, 'playlist') => $this->ids['playlistSecret'],
                str_contains($path, 'series') => $this->ids['seriesSecret'],
                default => $this->ids['mmSecret'],
            },
            'hash' => $this->ids['seriesSecret'],
            'pumukit1id' => (string) (str_contains($path, 'serial') ? RouteSmokeFixtures::SERIES_NUMERICAL_ID : RouteSmokeFixtures::MULTIMEDIA_OBJECT_NUMERICAL_ID),
            'tagCod', 'tag' => $this->ids['tagCod'],
            'parent' => $this->ids['tag'],
            'jobId' => $this->ids['job'],
            'owner', 'coOwner' => $this->ids['user'],
            'profile' => 'master_copy',
            '_format' => $this->firstRequirement($route, $variable) ?? 'json',
            '_locale', 'locale' => 'es',
            'cpuName' => 'local',
            'activateMaintenance' => 'deactivate',
            'useTagAsGeneral' => '0',
            'channelNumber' => '1',
            'ext' => 'mp4',
            'option' => 'delete_selected',
            'type' => match (true) {
                str_contains($path, 'liveevent/list/event') => 'now',
                str_contains($path, 'liveevent/list/options') => 'clone',
                str_contains($path, 'headandtail') => 'head',
                default => $this->firstRequirement($route, $variable) ?? 'multimediaobject',
            },
            default => $this->firstRequirement($route, $variable),
        };
    }

    private function documentId(string $class, string $method, string $path, bool $isEvent): ?string
    {
        $documents = $this->ids['documents'];
        if ($isEvent) {
            $documents[MultimediaObject::class] = $this->ids['eventMm'];
            $documents[Series::class] = $this->ids['eventSeries'];
        }

        if (method_exists($class, $method)) {
            $parameters = (new \ReflectionMethod($class, $method))->getParameters();
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                $document = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                if (!$document || !isset($documents[$document])) {
                    continue;
                }
                foreach ($parameter->getAttributes(MapDocument::class) as $attribute) {
                    $arguments = $attribute->getArguments();
                    if ('id' === ($arguments['id'] ?? null) || array_key_exists('id', $arguments['mapping'] ?? [])) {
                        return $documents[$document];
                    }
                }
                if ('id' === $parameter->getName()) {
                    return $documents[$document];
                }
            }
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                $document = $type instanceof \ReflectionNamedType ? $type->getName() : null;
                if ($document && isset($documents[$document]) && !$parameter->getAttributes(MapDocument::class)) {
                    return $documents[$document];
                }
            }
        }

        $short = (new \ReflectionClass($class))->getShortName();
        if ('StatsController' === $short) {
            return str_contains($path, 'series') ? $this->ids['series'] : $this->ids['mm'];
        }
        if ('PaellaRepositoryController' === $short && str_contains($path, 'playlist')) {
            return $this->ids['playlist'];
        }

        return isset(self::CONTROLLER_RESOURCES[$short]) ? $this->ids[self::CONTROLLER_RESOURCES[$short]] : null;
    }

    private function firstRequirement(Route $route, string $variable): ?string
    {
        $requirement = $route->getRequirement($variable);
        if (null === $requirement || !preg_match('/^[\w|.-]+$/', $requirement)) {
            return null;
        }

        return explode('|', $requirement)[0];
    }
}
