<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MediaLibraryController extends Controller implements WebTVController
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_medialibrary_index")
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
        $templateTitle = $this->container->getParameter('menu.mediateca_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);
        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_medialibrary_index', array('sort' => $sort));

        $series_repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $tags_repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

        $array_tags = $this->container->getParameter('pumukit_web_tv.media_library.filter_tags');
        $selectionTags = $tags_repo->findBy(array('cod' => array('$in' => $array_tags)));

        $criteria = $request->query->get('search', false) ?
                    array('title.'.$request->getLocale() => new \MongoRegex(sprintf('/%s/i', $request->query->get('search')))) :
                    array();
        $result = array();

        $numberCols = $this->container->getParameter('columns_objs_catalogue');
        $hasCatalogueThumbnails = $this->container->getParameter('catalogue_thumbnails');

        $aggregatedNumMmobjs = $series_repo->countMmobjsBySeries();

        switch ($sort) {
            case 'alphabetically':
                $sortField = 'title.'.$request->getLocale();
                $series = $series_repo->findBy($criteria, array($sortField => 1));

                foreach ($series as $serie) {
                    if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                        continue;
                    }

                    $key = mb_substr(trim($serie->getTitle()), 0, 1);
                    if (!isset($result[ $key ])) {
                        $result[$key] = array();
                    }
                    $result[$key][] = $serie;
                }
                break;
            case 'date':
                $sortField = 'public_date';
                $series = $series_repo->findBy($criteria, array($sortField => -1));

                foreach ($series as $serie) {
                    if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                        continue;
                    }

                    $key = $serie->getPublicDate()->format('m/Y');
                    if (!isset($result[ $key ])) {
                        $result[ $key ] = array();
                    }

                    $title = $serie->getTitle();
                    if (!isset($result[ $key ][ $title ])) {
                        $result[ $key ][ $title ] = $serie;
                    } else {
                        $result[ $key ][ $title.rand() ] = $serie;
                    }
                }

                array_walk($result, function (&$e, $key) {
                    ksort($e);

                    return array_values($e);
                });

                break;
            case 'tags':
                $p_cod = $request->query->get('p_tag', false);
                $parentTag = $tags_repo->findOneBy(array('cod' => $p_cod));
                if (!isset($parentTag)) {
                    break;
                }
                $tags = $parentTag->getChildren();

                foreach ($tags as $tag) {
                    if ($tag->getNumberMultimediaObjects() < 1) {
                        continue;
                    }
                    $key = $tag->getTitle();

                    $sortField = 'title.'.$request->getLocale();
                    $seriesQB = $series_repo->createBuilderWithTag($tag, array($sortField => 1));
                    if ($criteria) {
                        $seriesQB->addAnd($criteria);
                    }
                    $series = $seriesQB->getQuery()->execute();

                    if (!$series) {
                        continue;
                    }

                    foreach ($series as $serie) {
                        if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                            continue;
                        }

                        if (!isset($result[ $key ])) {
                            $result[ $key ] = array();
                        }
                        $result[ $key ][] = $serie;
                    }
                }
                break;
        }

        return array(
            'objects' => $result,
            'sort' => $sort,
            'tags' => $selectionTags,
            'number_cols' => $numberCols,
            'catalogue_thumbnails' => $hasCatalogueThumbnails,
            'aggregated_num_mmobjs' => $aggregatedNumMmobjs,
        );
    }
}
