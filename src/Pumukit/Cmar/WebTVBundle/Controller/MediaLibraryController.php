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
     * @Route("/", name="pumukitcmarwebtv_library_index")
     */
    public function indexAction(Request $request)
    {
        return $this->redirect($this->generateUrl('pumukitcmarwebtv_library_mainconferences'));
    }

    /**
     * @Route("/gc")
     * @Route("/mainconferences", name="pumukitcmarwebtv_library_mainconferences")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:display.html.twig")
     */
    public function mainConferencesAction(Request $request)
    {
        $tagName = 'PUDEMAINCONF';

        return $this->action(null, $tagName, "pumukitcmarwebtv_library_mainconferences", $request);
    }


    /**
     * @Route("/pc")
     * @Route("/promotional", name="pumukitcmarwebtv_library_promotional")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function promotionalAction(Request $request)
    {
        $tagName = 'PUDEPROMO';

        return $this->action(null, $tagName, "pumukitcmarwebtv_library_promotional", $request);
    }


    /**
     * @Route("/ap")
     * @Route("/pressarea", name="pumukitcmarwebtv_library_pressarea")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function pressAreaAction(Request $request)
    {
        $tagName = 'PUDEPRESS';

        return $this->action(null, $tagName, "pumukitcmarwebtv_library_pressarea", $request);
    }


    /**
     * @Route("/ps")
     * @Route("/projectsupport", name="pumukitcmarwebtv_library_projectsupport")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function projectSupportAction(Request $request)
    {
        $tagName = 'PUDESUPPORT';

        /* TODO $serials["all"] = SerialPeer::retrieveByPKs(array(6, 9, 7)); */
        return $this->action(null, $tagName, "pumukitcmarwebtv_library_projectsupport", $request);
    }

    /**
     * @Route("/c")
     * @Route("/congresses", name="pumukitcmarwebtv_library_congresses")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function congressesAction(Request $request)
    {
        $tagName = 'PUDECONGRESSES';
        
        // TODO review: check locale, check defintion of congresses
        // $series = $seriesRepo->findBy(array('keyword.en' => 'congress'), array('public_date' => 'desc'));
        return $this->action(null, $tagName, "pumukitcmarwebtv_library_congresses", $request);
    }

    /**
     * @Route("/all", name="pumukitcmarwebtv_library_all")
     * @Template("PumukitCmarWebTVBundle:MediaLibrary:multidisplay.html.twig")
     */
    public function allAction(Request $request)
    {
        $title = "All Videos";

        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:Series');

        //TODO revew
        $series = $seriesRepo->findBy(array(), array('public_date' => -1));

        return array('title' => $title, 'series' => $series);
    }


    private function action($title, $tagName, $routeName, Request $request, array $sort=array('public_date' => -1))
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($tagName);
        if (!$tag) {
          throw $this->createNotFoundException('The tag does not exist');
        }
    
        $title = $title != null ? $title : $tag->getTitle();
        
        $this->get('pumukit_web_tv.breadcrumbs')->addList($title, $routeName);

        $series = $dm->getRepository('PumukitSchemaBundle:Series')->findWithTag($tag, $sort);

        return array('title' => $title, 'series' => $series, 'tag_cod' => $tagName);
    }
}
