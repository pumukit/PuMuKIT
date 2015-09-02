<?php

namespace Pumukit\Cmar\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;

/**
 * @Route("/library")
 */
class MediaLibraryController extends Controller
{
    /**
     * @Route("/", name="pumukit_webtv_medialibrary_index")
     * @Route("/", name="pumukit_cmar_web_tv_library_index")
     */
    public function indexAction(Request $request)
    {
        return $this->redirect($this->generateUrl('pumukit_cmar_web_tv_library_mainconferences'));
    }

    /**
     * @Route("/gc")
     * @Route("/mainconferences", name="pumukit_cmar_web_tv_library_mainconferences")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:display.html.twig")
     */
    public function mainConferencesAction(Request $request)
    {
        $tagName = 'PUDEMAINCONF';

        return $this->action(null, $tagName, "pumukit_cmar_web_tv_library_mainconferences", $request);
    }


    /**
     * @Route("/pc")
     * @Route("/promotional", name="pumukit_cmar_web_tv_library_promotional")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function promotionalAction(Request $request)
    {
        $tagName = 'PUDEPROMO';

        return $this->action(null, $tagName, "pumukit_cmar_web_tv_library_promotional", $request);
    }


    /**
     * @Route("/ap")
     * @Route("/pressarea", name="pumukit_cmar_web_tv_library_pressarea")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function pressAreaAction(Request $request)
    {
        $tagName = 'PUDEPRESS';

        return $this->action(null, $tagName, "pumukit_cmar_web_tv_library_pressarea", $request);
    }


    /**
     * @Route("/ps")
     * @Route("/projectsupport", name="pumukit_cmar_web_tv_library_projectsupport")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function projectSupportAction(Request $request)
    {
        $tagName = 'PUDESUPPORT';

        return $this->action(null, $tagName, "pumukit_cmar_web_tv_library_projectsupport", $request);
    }

    /**
     * @Route("/c")
     * @Route("/congresses", name="pumukit_cmar_web_tv_library_congresses")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function congressesAction(Request $request)
    {
        $tagName = 'PUDECONGRESSES';
        
        return $this->action(null, $tagName, "pumukit_cmar_web_tv_library_congresses", $request);
    }

    /**
     * @Route("/librarymh")
     * @Route("/lectures", name="pumukit_cmar_web_tv_library_lectures")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:opencastindex.html.twig")
     */
    public function lecturesAction(Request $request)
    {
        $tagName = 'TECHOPENCAST';
        
        return $this->actionOpencast("Recorded lectures", $tagName, "pumukit_cmar_web_tv_library_lectures");
    }

    /**
     * @Route("/all", name="pumukit_cmar_web_tv_library_all")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function allAction(Request $request)
    {
        $title = $this->get('translator')->trans("All videos");
        $this->get('pumukit_web_tv.breadcrumbs')->addList($title, "pumukit_cmar_web_tv_library_all");

        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:Series');
        $series = $seriesRepo->findBy(array(), array('public_date' => -1));

        return array('title' => $title, 'series' => $series);
    }


    private function action($title, $tagName, $routeName, Request $request, array $sort=array('public_date' => -1))
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($tagName);
        if (!$tag) {
          throw $this->createNotFoundException('The tag with code "'.$tagName.'" does not exist');
        }
    
        $title = $title != null ? $title : $tag->getTitle();
        
        $this->get('pumukit_web_tv.breadcrumbs')->addList($title, $routeName);

        $sort = array('public_date' => -1);
        $series = $dm->getRepository('PumukitSchemaBundle:Series')->findWithTag($tag, $sort);

        return array('title' => $title, 'series' => $series, 'tag_cod' => $tagName);
    }

    private function actionOpencast($title, $tagName, $routeName, array $sort=array('public_date' => -1))
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($tagName);
        if (!$tag) {
            throw $this->createNotFoundException('The tag with code "'.$tagName.'" does not exist');
        }

        $title = $title != null ? $title : $tag->getTitle();

        $this->get('pumukit_web_tv.breadcrumbs')->addList($title, $routeName, array(), true);

        // NOTE: Review if the number of SeriesType increases
        $allSeriesType = $dm->getRepository('PumukitSchemaBundle:SeriesType')->findBy(array(), array("cod" => 1));
        $subseries = array();
        foreach ($allSeriesType as $seriesType) {
            $series = $dm->getRepository('PumukitSchemaBundle:Series')->findWithTagAndSeriesType($tag, $seriesType, $sort);
            $subseries[$seriesType->getName()] = $series;
        }

        return array('title' => $title, 'subseries' => $subseries, 'tag_cod' => $tagName);
    }
}
