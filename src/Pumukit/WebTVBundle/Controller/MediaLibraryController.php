<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Tag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MediaLibraryController.
 */
class MediaLibraryController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_medialibrary_index")
     * @Template("PumukitWebTVBundle:MediaLibrary:template.html.twig")
     *
     * @param string $sort
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return array
     */
    public function indexAction(Request $request, $sort)
    {
        $templateTitle = $this->container->getParameter('menu.mediateca_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_medialibrary_index', ['sort' => $sort]);

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $array_tags = $this->container->getParameter('pumukit_web_tv.media_library.filter_tags');
        $tagRepository = $dm->getRepository(Tag::class);
        $selectionTags = $tagRepository->findBy(['cod' => ['$in' => $array_tags]]);

        $hasCatalogueThumbnails = $this->container->getParameter('catalogue_thumbnails');

        [$objects, $aggregatedNumMmobjs] = $this->get('pumukit_web_tv.list_service')->getMediaLibrary([], $sort, $request->getLocale(), $request->query->get('p_tag'));

        return [
            'objects' => $objects,
            'sort' => $sort,
            'tags' => $selectionTags,
            'objectByCol' => $this->container->getParameter('columns_objs_catalogue'),
            'show_info' => false,
            'show_more' => false,
            'catalogue_thumbnails' => $hasCatalogueThumbnails,
            'aggregated_num_mmobjs' => $aggregatedNumMmobjs,
        ];
    }
}
