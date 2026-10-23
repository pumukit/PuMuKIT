<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\MediaCreator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[CoversNothing]
class InlineJavascriptTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $roles = array_values(array_filter(
            (new \ReflectionClass(Permission::class))->getConstants(),
            static fn ($value): bool => is_string($value) && str_starts_with($value, 'ROLE_')
        ));
        $user = new User();
        $user->setUsername('inlinejs');
        $user->setEmail('inlinejs@pumukit.local');
        $user->setPassword('unused');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_SUPER_ADMIN', ...$roles]);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }

    #[DataProvider('migratedPages')]
    public function testThePageHasNoInlineJavascript(string $route): void
    {
        $this->client->request('GET', static::getContainer()->get('router')->generate($route, $this->parameters($route)));
        $this->dm->clear();

        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();

        preg_match_all('/<[a-z][^>]*\son[a-z]+\s*=/i', $html, $handlers);
        $this->assertSame([], $handlers[0]);

        preg_match_all('#<script\b(?![^>]*\bsrc=)(?![^>]*type="importmap")[^>]*>(.*?)</script>#is', $html, $scripts);
        $inline = array_values(array_filter(array_map('trim', $scripts[1]), static fn (string $code): bool => '' !== $code && "import 'pumukit';" !== $code));
        $this->assertSame([], $inline);
    }

    public static function migratedPages(): array
    {
        return [
            'roles' => ['pumukitnewadmin_role_index'],
            'series types' => ['pumukitnewadmin_seriestype_index'],
            'live channels' => ['pumukitnewadmin_live_index'],
            'legacy events' => ['pumukitnewadmin_event_index'],
            'users' => ['pumukitnewadmin_user_index'],
            'groups' => ['pumukitnewadmin_group_index'],
            'people' => ['pumukitnewadmin_person_index'],
            'permission profiles' => ['pumukitnewadmin_permissionprofile_index'],
            'tags' => ['pumukitnewadmin_tag_index'],
            'series' => ['pumukitnewadmin_series_index'],
            'playlists' => ['pumukitnewadmin_playlist_index'],
            'head and tail' => ['pumukit_newadmin_head_and_tail'],
            'multimedia objects' => ['pumukitnewadmin_mms_index'],
            'multimedia object editor' => ['pumukitnewadmin_mms_edit'],
            'unesco editor' => ['pumukit_new_admin_unesco_edit'],
            'live events' => ['pumukit_new_admin_live_event_index'],
            'playlist objects' => ['pumukitnewadmin_playlistmms_index'],
            'playlist add objects' => ['pumukitnewadmin_playlistmms_modal'],
            'add to playlist' => ['pumukitnewadmin_playlistmms_modal_add'],
            'live event editor' => ['pumukit_new_admin_live_event_edit'],
            'unesco list' => ['pumukitnewadmin_unesco_list'],
            'unesco preview' => ['pumukitnewadmin_unesco_show'],
            'unesco search' => ['pumukitnewadmin_unesco_advance_search_form'],
            'multimedia object links' => ['pumukitnewadmin_mms_links'],
            'multimedia object tracks' => ['pumukitnewadmin_track_list'],
            'multimedia object materials' => ['pumukitnewadmin_material_list'],
            'multimedia object broadcast' => ['pumukitnewadmin_mms_updatebroadcast'],
            'series broadcast' => ['pumukitnewadmin_series_updatebroadcast'],
            'multimedia object tag tree' => ['pumukitnewadmin_mms_reloadtags'],
            'multimedia object tag search' => ['pumukitnewadmin_mms_searchtag'],
            'multimedia object tag children' => ['pumukitnewadmin_mms_getchildrentag'],
            'places' => ['pumukitnewadmin_places_index'],
            'place list' => ['pumukitnewadmin_places_parent'],
            'precinct list' => ['pumukitnewadmin_places_children'],
            'place create' => ['pumukitnewadmin_places_create'],
            'place update' => ['pumukitnewadmin_places_update'],
        ];
    }

    private function parameters(string $route): array
    {
        if ('pumukitnewadmin_mms_index' === $route) {
            $factory = static::getContainer()->get(FactoryService::class);
            $series = $factory->createSeries();
            $factory->createMultimediaObject($series, true);

            return ['id' => $series->getId()];
        }
        if (in_array($route, ['pumukit_new_admin_live_event_index', 'pumukit_new_admin_live_event_edit'], true)) {
            $this->tag('PUBCHANNELS', 'ROOT|PUBCHANNELS|');
            $factory = static::getContainer()->get(FactoryService::class);
            $multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);
            $multimediaObject->setType(MultimediaObject::TYPE_LIVE);
            $event = new EmbeddedEvent();
            $event->setDate(new \DateTime());
            $event->setName('Event', 'en');
            $event->setDescription('', 'en');
            $session = new EmbeddedEventSession();
            $session->setStart(new \DateTime('+1 day'));
            $session->setEnds(new \DateTime('+1 day +1 hour'));
            $session->setDuration(3600);
            $event->addEmbeddedEventSession($session);
            $this->dm->persist($event);
            $multimediaObject->setEmbeddedEvent($event);
            $this->dm->flush();

            return 'pumukit_new_admin_live_event_index' === $route ? [] : ['id' => $multimediaObject->getId()];
        }
        if (str_starts_with($route, 'pumukitnewadmin_playlistmms_')) {
            $factory = static::getContainer()->get(FactoryService::class);
            $multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);
            $playlist = $factory->createPlaylist(null, ['en' => 'Playlist']);
            $playlist->getPlaylist()->addMultimediaObject($multimediaObject);
            $this->dm->flush();

            return ['id' => 'pumukitnewadmin_playlistmms_modal_add' === $route ? $multimediaObject->getId() : $playlist->getId()];
        }
        if ('pumukitnewadmin_series_updatebroadcast' === $route) {
            $factory = static::getContainer()->get(FactoryService::class);
            $series = $factory->createSeries();
            $factory->createMultimediaObject($series, true);

            return ['id' => $series->getId()];
        }
        if (in_array($route, ['pumukitnewadmin_mms_edit', 'pumukit_new_admin_unesco_edit', 'pumukitnewadmin_unesco_list', 'pumukitnewadmin_unesco_show', 'pumukitnewadmin_unesco_advance_search_form', 'pumukitnewadmin_track_list', 'pumukitnewadmin_material_list'], true)) {
            $owner = new Role();
            $owner->setCod('owner');
            $owner->setName('Owner');
            $this->dm->persist($owner);
            $root = $this->tag('ROOT', 'ROOT|');
            $unesco = $this->tag('UNESCO', 'ROOT|UNESCO|', $root);
            $this->tag('U110000', 'ROOT|UNESCO|U110000|', $unesco);
            $factory = static::getContainer()->get(FactoryService::class);
            $multimediaObject = $factory->createMultimediaObject($factory->createSeries(), true);
            $material = new Material();
            $material->setName('Notes');
            $material->setUrl('https://example.org/notes.pdf');
            $multimediaObject->addMaterial($material);
            static::getContainer()->get(MediaCreator::class)->createMediaFromExternalURL($multimediaObject, 'https://example.org/player');
            $this->dm->flush();

            return in_array($route, ['pumukitnewadmin_unesco_list', 'pumukitnewadmin_unesco_advance_search_form'], true) ? [] : ['id' => $multimediaObject->getId()];
        }
        if (str_starts_with($route, 'pumukitnewadmin_places_')) {
            $places = $this->tag('PLACES', 'ROOT|PLACES|');
            $place = $this->tag('PLACE1', 'ROOT|PLACES|PLACE1|', $places);
            $precinct = $this->tag('PLACE1PRECINCT1', 'ROOT|PLACES|PLACE1|PLACE1PRECINCT1|', $place);
            $this->dm->flush();

            return match ($route) {
                'pumukitnewadmin_places_children', 'pumukitnewadmin_places_create' => ['id' => $place->getId()],
                'pumukitnewadmin_places_update' => ['id' => $precinct->getId()],
                default => [],
            };
        }
        if (in_array($route, ['pumukitnewadmin_mms_links', 'pumukitnewadmin_mms_updatebroadcast'], true)) {
            $factory = static::getContainer()->get(FactoryService::class);

            return ['id' => $factory->createMultimediaObject($factory->createSeries(), true)->getId()];
        }
        if (!str_contains($route, 'tag')) {
            return [];
        }

        $factory = static::getContainer()->get(FactoryService::class);
        $mmId = $factory->createMultimediaObject($factory->createSeries(), true)->getId();
        $block = $this->tag('BLOCK', 'ROOT|BLOCK|');
        $branch = $this->tag('BRANCH', 'ROOT|BLOCK|BRANCH|', $block);
        $this->tag('LEAF', 'ROOT|BLOCK|BRANCH|LEAF|', $branch);
        $this->dm->flush();

        return match ($route) {
            'pumukitnewadmin_mms_reloadtags' => ['mmId' => $mmId, 'parentId' => $block->getId()],
            'pumukitnewadmin_mms_searchtag' => ['search_text' => 'Leaf', 'mmId' => $mmId, 'parent' => $block->getId()],
            default => ['id' => $branch->getId(), 'tag_id' => $block->getId(), 'mm_id' => $mmId],
        };
    }

    private function tag(string $cod, string $path, ?Tag $parent = null): Tag
    {
        $tag = new Tag();
        $tag->setCod($cod);
        $tag->setPath($path);
        $tag->setDisplay(true);
        $tag->setTitle(ucfirst(strtolower($cod)), 'en');
        $tag->setTitle(ucfirst(strtolower($cod)), 'es');
        if ($parent) {
            $tag->setParent($parent);
        }
        $this->dm->persist($tag);

        return $tag;
    }
}
