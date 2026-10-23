<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
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
            'multimedia object links' => ['pumukitnewadmin_mms_links'],
            'multimedia object broadcast' => ['pumukitnewadmin_mms_updatebroadcast'],
            'series broadcast' => ['pumukitnewadmin_series_updatebroadcast'],
            'multimedia object tag tree' => ['pumukitnewadmin_mms_reloadtags'],
            'multimedia object tag search' => ['pumukitnewadmin_mms_searchtag'],
            'multimedia object tag children' => ['pumukitnewadmin_mms_getchildrentag'],
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
        if ('pumukitnewadmin_series_updatebroadcast' === $route) {
            $factory = static::getContainer()->get(FactoryService::class);
            $series = $factory->createSeries();
            $factory->createMultimediaObject($series, true);

            return ['id' => $series->getId()];
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
