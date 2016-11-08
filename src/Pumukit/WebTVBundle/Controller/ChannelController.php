<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;

class ChannelController extends Controller implements WebTVController
{
    private $titles = array( 1 => 'University',
                             2 => 'Business',
                             3 => 'Natural Sciences',
                             5 => 'Humanities',
                             6 => 'Health & Medicine',
                             4 => 'Law',
                             7 => 'Social Matters & Education', );

    private $tags = array( 1 => array('PUDEUNI'), //"University",
                           2 => array('100'), //"Business",
                           3 => array('101', '108', '109'), //"Natural Sciences",
                           5 => array('102', '104', '106', '107', '114', '115'), //"Humanities",
                           6 => array('103'), //"Health & Medicine",
                           4 => array('116'), //"Law",
                           7 => array('110', '111', '112', '113')//"Social Matters & Education",
    );
    /**
     * @Route("/series/channel/{channelNumber}.html", name="pumukit_webtv_channel_series")
     * @Template("PumukitWebTVBundle:Channel:index.html.twig")
     */
    public function seriesAction(Request $request, $channelNumber)
    {
        $numberCols = $this->container->getParameter('columns_objs_bytag');
        $limit = $this->container->getParameter('limit_objs_bytag');

        $repoSeries = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $repoMmobj = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        $channelTitle = $this->getChannelTitle($channelNumber);
        $channelTags = $this->getTagsForChannel($channelNumber);
        $results = array();

        foreach ($channelTags as $tag) {
            $series = $repoSeries->createBuilderWithTag($tag, array('record_date' => -1));
            $series = $series->getQuery()->execute();
            $numMmobjs = $repoMmobj->createBuilderWithTag($tag, array('record_date' => -1))
                                   ->count()->getQuery()->execute();

            $results[] = array('tag' => $tag,
                               'objects' => $series,
                               'numMmobjs' => $numMmobjs, );
        }

        $this->updateBreadcrumbs($channelTitle, 'pumukit_webtv_channel_series', array('channelNumber' => $channelNumber));

        return array('title' => $channelTitle,
                     'results' => $results,
                     'number_cols' => $numberCols, );
    }

    public function getChannelTitle($channelNumber)
    {
        $title = isset($this->titles[$channelNumber])?$this->titles[$channelNumber]:'No title';
        $title = $this->get('translator')->trans($title);

        return $title;
    }

    public function getTagsForChannel($channelNumber)
    {
        $tagCods = isset($this->tags[$channelNumber])?$this->tags[$channelNumber]:array();
        $tags = array();
        $repoTags = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        foreach ($tagCods as $tagCod) {
            $tags[] = $repoTags->findOneByCod($tagCod);
        }

        return $tags;
    }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }
}
