<?php

namespace Pumukit\Responsive\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MediaLibraryController extends Controller
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_responsive_webtv_medialibrary_index")
     * @Template()
     */
    public function indexAction($sort, Request $request)
    {
        $this->get('pumukit_responsive_web_tv.breadcrumbs')->addList('Full catalogue', 'pumukit_responsive_webtv_medialibrary_index');
        $series_repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $tags_repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

        $array_tags = $this->container->getParameter('responsivewebtv.media_library.filter_tags');
        $selectionTags = array();
        foreach ($array_tags as $cod) {
            $selectionTags[] = $tags_repo->findOneBy(array('cod' => $cod));
        }

        $criteria = $request->query->get('search', false) ?
                    array('title.'.$request->getLocale() => new \MongoRegex(sprintf('/%s/i', $request->query->get('search')))) :
                    array();
        $result = array();

        switch ($sort) {
            case 'alphabetically':
            $sortField = 'title.'.$request->getLocale();
            $series = $series_repo->findBy($criteria, array($sortField => 1));
            foreach ($series as $serie) {
                $num_mm = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject')->countInSeries($serie);
                if ($num_mm < 1) {
                    continue;
                }
                $key = substr($serie->getTitle(), 0, 1);
                if (!isset($result[ $key ])) {
                    $result[$key] = array();
                }
                $result[$key][] = $serie;
            }
            break;
            case 'date':
            $sortField = 'public_date';
            $series = $series_repo->findBy($criteria, array($sortField => 1));
            foreach ($series as $serie) {
                $num_mm = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject')->countInSeries($serie);
                if ($num_mm < 1) {
                    continue;
                }
                $key = $serie->getPublicDate()->format('d/m/Y');
                if (!isset($result[ $key ])) {
                    $result[ $key ] = array();
                }
                $result[ $key ][] = $serie;
            }
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
                if (!isset($result[ $key ])) {
                    $result[ $key ] = array();
                }

                $series = $series_repo->findWithTag($tag, array('public_date' => +1));
                foreach ($series as $serie) {
                    $num_mm = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject')->countInSeries($serie);
                    if ($num_mm < 1) {
                        continue;
                    }
                    $result[ $key ][] = $serie;
                }
            }
            break;
        }

        return array('objects' => $result, 'sort' => $sort, 'tags' => $selectionTags);
    }
}
