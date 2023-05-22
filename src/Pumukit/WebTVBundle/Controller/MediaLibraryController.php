<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\ListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaLibraryController extends AbstractController implements WebTVControllerInterface
{
    protected $documentManager;
    protected $breadcrumbsService;
    protected $translator;
    protected $listService;

    protected $menuMediatecaTitle;
    protected $pumukitWebTVMediaLibraryFilterTags;
    protected $catalogueThumbnails;
    protected $columnsObjsCatalogue;

    public function __construct(
        DocumentManager $documentManager,
        BreadcrumbsService $breadcrumbsService,
        TranslatorInterface $translator,
        ListService $listService,
        $menuMediatecaTitle,
        $pumukitWebTVMediaLibraryFilterTags,
        $catalogueThumbnails,
        $columnsObjsCatalogue
    ) {
        $this->documentManager = $documentManager;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->translator = $translator;
        $this->listService = $listService;
        $this->menuMediatecaTitle = $menuMediatecaTitle;
        $this->pumukitWebTVMediaLibraryFilterTags = $pumukitWebTVMediaLibraryFilterTags;
        $this->catalogueThumbnails = $catalogueThumbnails;
        $this->columnsObjsCatalogue = $columnsObjsCatalogue;
    }

    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_medialibrary_index")
     */
    public function indexAction(Request $request, string $sort): Response
    {
        $templateTitle = $this->translator->trans($this->menuMediatecaTitle);
        $this->breadcrumbsService->addList($templateTitle, 'pumukit_webtv_medialibrary_index', ['sort' => $sort]);

        $selectionTags = $this->documentManager->getRepository(Tag::class)->findBy(['cod' => ['$in' => $this->pumukitWebTVMediaLibraryFilterTags]]);

        [$objects, $aggregatedNumMmobjs] = $this->listService->getMediaLibrary([], $sort, $request->getLocale(), $request->query->get('p_tag'));

        return $this->render('@PumukitWebTV/MediaLibrary/template.html.twig', [
            'objects' => $objects,
            'sort' => $sort,
            'tags' => $selectionTags,
            'objectByCol' => $this->columnsObjsCatalogue,
            'show_info' => false,
            'show_more' => false,
            'catalogue_thumbnails' => $this->catalogueThumbnails,
            'aggregated_num_mmobjs' => $aggregatedNumMmobjs,
        ]);
    }
}
