<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\AnnounceService;
use Pumukit\StatsBundle\Services\StatsService;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\ListService;
use Pumukit\WebTVBundle\Services\MenuService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModulesController extends AbstractController implements WebTVControllerInterface
{
    public static $menuResponse;
    protected $menuTemplate = '@PumukitWebTV/Modules/widget_menu.html.twig';

    protected $translator;
    protected $statService;
    protected $pumukitSchemaAnnounce;
    protected $menuService;
    protected $listService;
    protected $documentManager;
    protected $requestStack;
    protected $breadcrumbService;

    protected $byTagBlockObjectsByCol;
    protected $limitObjsMostViewed;
    protected $showMostViewedLastMonth;
    protected $mostViewedObjectsByCol;
    protected $showLatestWithPudeNew;
    protected $limitObjsHightlight;
    protected $hightlightObjectsByCol;
    protected $menuHomeTitle;
    protected $menuAnnouncesTitle;
    protected $menuSearchTitle;
    protected $menuMediatecaTitle;
    protected $menuCategoriesTitle;
    protected $limitObjsLiveBlock;
    protected $liveBlockObjectsByCol;
    protected $locales;
    protected $limitObjsRecentlyAdded;
    protected $recentlyAddedObjectsByCol;

    public function __construct(
        TranslatorInterface $translator,
        StatsService $statService,
        AnnounceService $pumukitSchemaAnnounce,
        MenuService $menuService,
        ListService $listService,
        DocumentManager $documentManager,
        RequestStack $requestStack,
        BreadcrumbsService $breadcrumbService,
        $byTagBlockObjectsByCol,
        $limitObjsMostViewed,
        $showMostViewedLastMonth,
        $mostViewedObjectsByCol,
        $showLatestWithPudeNew,
        $limitObjsHightlight,
        $hightlightObjectsByCol,
        $menuHomeTitle,
        $menuAnnouncesTitle,
        $menuSearchTitle,
        $menuMediatecaTitle,
        $menuCategoriesTitle,
        $limitObjsLiveBlock,
        $liveBlockObjectsByCol,
        $locales,
        $limitObjsRecentlyAdded,
        $recentlyAddedObjectsByCol
    ) {
        $this->translator = $translator;
        $this->statService = $statService;
        $this->pumukitSchemaAnnounce = $pumukitSchemaAnnounce;
        $this->menuService = $menuService;
        $this->listService = $listService;
        $this->documentManager = $documentManager;
        $this->requestStack = $requestStack;
        $this->breadcrumbService = $breadcrumbService;
        $this->byTagBlockObjectsByCol = $byTagBlockObjectsByCol;
        $this->limitObjsMostViewed = $limitObjsMostViewed;
        $this->showMostViewedLastMonth = $showMostViewedLastMonth;
        $this->mostViewedObjectsByCol = $mostViewedObjectsByCol;
        $this->showLatestWithPudeNew = $showLatestWithPudeNew;
        $this->limitObjsHightlight = $limitObjsHightlight;
        $this->hightlightObjectsByCol = $hightlightObjectsByCol;
        $this->menuHomeTitle = $menuHomeTitle;
        $this->menuAnnouncesTitle = $menuAnnouncesTitle;
        $this->menuSearchTitle = $menuSearchTitle;
        $this->menuMediatecaTitle = $menuMediatecaTitle;
        $this->menuCategoriesTitle = $menuCategoriesTitle;
        $this->limitObjsLiveBlock = $limitObjsLiveBlock;
        $this->liveBlockObjectsByCol = $liveBlockObjectsByCol;
        $this->locales = $locales;
        $this->limitObjsRecentlyAdded = $limitObjsRecentlyAdded;
        $this->recentlyAddedObjectsByCol = $recentlyAddedObjectsByCol;
    }

    public function mostViewedAction(string $design = 'horizontal'): Response
    {
        if ($this->showMostViewedLastMonth) {
            $objects = $this->statService->getMostViewedUsingFilters(30, $this->limitObjsMostViewed);
            $title = $this->translator->trans('Most viewed on the last month');
        } else {
            $objects = $this->documentManager->getRepository(MultimediaObject::class)->findStandardBy(
                [],
                ['numview' => -1],
                $this->limitObjsMostViewed,
                0
            );
            $title = $this->translator->trans('Most viewed');
        }

        return $this->render('@PumukitWebTV/Modules/widget_media.html.twig', [
            'design' => $design,
            'objects' => $objects,
            'objectByCol' => $this->mostViewedObjectsByCol,
            'title' => $title,
            'class' => 'mostviewed',
            'show_info' => true,
            'show_more' => false,
            'show_more_path' => false,
        ]);
    }

    /**
     * Returns all videos with PUDENEW tag.
     */
    public function highlightAction(): Response
    {
        if (!$this->showLatestWithPudeNew) {
            throw new \Exception('Show latest with pudenew parameters must be true to use this module');
        }

        $title = $this->translator->trans('Hightlight');

        $last = $this->pumukitSchemaAnnounce->getLast($this->limitObjsHightlight);

        return $this->render('@PumukitWebTV/Modules/widget_media.html.twig', [
            'objects' => $last,
            'objectByCol' => $this->hightlightObjectsByCol,
            'class' => 'highlight',
            'title' => $title,
            'show_info' => true,
            'show_more' => false,
            'show_more_path' => 'pumukit_webtv_announces_latestuploads',
        ]);
    }

    /**
     * Returns all videos without PUDENEW tag.
     */
    public function recentlyAddedWithoutHighlightAction(string $design = 'horizontal'): Response
    {
        $last = $this->documentManager->getRepository(MultimediaObject::class)->findStandardBy(
            ['tags.cod' => ['$ne' => 'PUDENEW']],
            [
                'public_date' => -1,
            ],
            $this->limitObjsRecentlyAdded,
            0
        );

        return $this->render('@PumukitWebTV/Modules/widget_media.html.twig', [
            'design' => $design,
            'objects' => $last,
            'objectByCol' => $this->recentlyAddedObjectsByCol,
            'title' => $this->translator->trans('Recently added'),
            'class' => 'recently',
            'show_info' => true,
            'show_more' => false,
            'show_more_path' => 'pumukit_webtv_announces_latestuploads',
        ]);
    }

    /**
     * Returns all videos without PUDENEW tag.
     */
    public function recentlyAddedAllAction(string $design = 'horizontal'): Response
    {
        $last = $this->documentManager->getRepository(MultimediaObject::class)->findStandardBy(
            [],
            [
                'public_date' => -1,
            ],
            $this->limitObjsRecentlyAdded,
            0
        );

        return $this->render('@PumukitWebTV/Modules/widget_media.html.twig', [
            'design' => $design,
            'objects' => $last,
            'objectByCol' => $this->recentlyAddedObjectsByCol,
            'title' => $this->translator->trans('Recently added'),
            'class' => 'recently',
            'show_info' => true,
            'show_more' => false,
            'show_more_path' => 'pumukit_webtv_announces_latestuploads',
        ]);
    }

    public function statsAction(): Response
    {
        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);
        $seriesRepo = $this->documentManager->getRepository(Series::class);

        $counts = $this->render('@PumukitWebTV/Modules/widget_stats.html.twig', [
            'series' => $seriesRepo->countPublic(),
            'mms' => $mmRepo->count(),
            'hours' => $mmRepo->countDuration(),
        ]);

        return ['counts' => $counts];
    }

    public function breadcrumbsAction(): Response
    {
        return $this->render('@PumukitWebTV/Modules/widget_breadcrumb.html.twig', ['breadcrumbs' => $this->breadcrumbService->getBreadcrumbs()]);
    }

    public function languageAction(): Response
    {
        if ((is_countable($this->locales) ? count($this->locales) : 0) <= 1) {
            return new Response('');
        }

        return $this->render('@PumukitWebTV/Modules/widget_language.html.twig', ['languages' => $this->locales]);
    }

    public function categoriesAction(Request $request, string $title, string $class, $categories, int $cols = 6, bool $sort = true): Response
    {
        if (!$categories) {
            throw new NotFoundHttpException('Categories not found');
        }

        if ($sort) {
            if (is_array($categories)) {
                $tags = $this->documentManager->createQueryBuilder(Tag::class)
                    ->field('cod')->in($categories)
                    ->field('display')->equals(true)
                    ->sort('title.'.$request->getLocale())
                    ->getQuery()
                    ->execute()
                ;
            } else {
                $tag = $this->documentManager->getRepository(Tag::class)->findOneBy([
                    'cod' => $categories,
                ]);
                if (!$tag) {
                    throw new NotFoundHttpException('Category not found');
                }
                $tags = $tag->getChildren();
            }
        } else {
            $tags = [];
            foreach ($categories as $categoryCod) {
                $tags[] = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $categoryCod, 'display' => true]);
            }
        }

        return $this->render('@PumukitWebTV/Modules/widget_categories.html.twig', [
            'objectByCol' => $cols,
            'objects' => $tags,
            'objectsData' => $categories,
            'title' => $this->translator->trans($title),
            'class' => $class,
        ]);
    }

    /**
     * This module was create to keep BC. Uses vertical design by default.
     * Returns:
     * - showPudenew = true => Only videos with PUDENEW tag and announce property true
     * - showPudenew = false => Returns all videos.
     */
    public function legacyRecentlyAdded(string $design = 'vertical')
    {
        $last = $this->pumukitSchemaAnnounce->getLast($this->limitObjsRecentlyAdded, $this->showLatestWithPudeNew);

        return $this->render('@PumukitWebTV/Modules/widget_media.html.twig', [
            'design' => $design,
            'objects' => $last,
            'objectByCol' => $this->recentlyAddedObjectsByCol,
            'title' => $this->translator->trans('Recently added'),
            'class' => 'recently',
            'show_info' => true,
            'show_more' => false,
            'show_more_path' => 'pumukit_webtv_announces_latestuploads',
        ]);
    }

    /**
     * This module represents old categories block of PuMuKIT. Remember fix responsive design ( depends of height of images ).
     */
    public function legacyCategoriesAction(): Response
    {
        return $this->render('@PumukitWebTV/Modules/widget_block_categories.html.twig', []);
    }

    /**
     * This module represents old menu block of PuMuKIT ( vertical menu ). This design is just bootstrap panel example.
     */
    public function legacyMenuAction(): Response
    {
        if (self::$menuResponse) {
            return self::$menuResponse;
        }
        $params = $this->getLegacyMenuElements();
        self::$menuResponse = $this->render($this->menuTemplate, $params);

        return self::$menuResponse;
    }

    public function liveBlockAction(): Response
    {
        $objects = $this->listService->getLives($this->limitObjsLiveBlock);

        return $this->render('@PumukitWebTV/Modules/widget_event.html.twig', [
            'objects' => $objects,
            'objectByCol' => $this->liveBlockObjectsByCol,
            'title' => $this->translator->trans('Live events'),
            'class' => 'live_events',
            'show_info' => false,
            'show_more' => false,
            'show_more_path' => 'pumukit_webtv_events',
        ]);
    }

    public function wallBlockAction(): Response
    {
        $objects = $this->listService->getWallVideos();

        return $this->render('@PumukitWebTV/Modules/widget_wall.html.twig', [
            'objects' => $objects,
            'objectByCol' => 1,
            'title' => $this->translator->trans('Wall'),
            'class' => 'wall_block',
            'show_info' => false,
            'show_more' => false,
        ]);
    }

    public function searchBlockAction(): Response
    {
        return $this->render('@PumukitWebTV/Modules/widget_search.html.twig', []);
    }

    public function byTagBlockAction(string $tagCod, string $title): Response
    {
        $objects = $this->listService->getVideosByTag($tagCod, $this->byTagBlockObjectsByCol);

        return $this->render('@PumukitWebTV/Modules/widget_media.html.twig', [
            'objects' => $objects,
            'objectByCol' => $this->byTagBlockObjectsByCol,
            'title' => $this->translator->trans($title),
            'class' => 'by-tag-block',
            'show_info' => true,
            'show_more' => false,
            'show_more_path' => false,
        ]);
    }

    public function embedVideoBlockAction(string $tagCod): Response
    {
        $object = $this->listService->getEmbedVideoBlock($tagCod);

        return $this->render('@PumukitWebTV/Modules/widget_player.html.twig', [
            'object' => $object,
            'autostart' => false,
        ]);
    }

    private function getLegacyMenuElements(): array
    {
        [$events, $channels, $liveEventTypeSession] = $this->menuService->getMenuEventsElement();

        return [
            'events' => $events,
            'channels' => $channels,
            'type' => $liveEventTypeSession,
            'menu_selected' => $this->requestStack->getMasterRequest()->get('_route'),
            'home_title' => $this->menuHomeTitle,
            'announces_title' => $this->menuAnnouncesTitle,
            'search_title' => $this->menuSearchTitle,
            'catalogue_title' => $this->menuMediatecaTitle,
            'categories_title' => $this->menuCategoriesTitle,
        ];
    }
}
