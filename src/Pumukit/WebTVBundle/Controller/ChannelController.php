<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * Class ChannelController.
 */
class ChannelController extends Controller implements WebTVControllerInterface
{
    private $titles = [
        1 => 'University',
        2 => 'Business',
        3 => 'Natural Sciences',
        5 => 'Humanities',
        6 => 'Health & Medicine',
        4 => 'Law',
        7 => 'Social Matters & Education',
    ];

    private $tags = [
        1 => ['PUCHWEBTV'], //"University",
        2 => ['100'], //"Business",
        3 => ['101', '108', '109'], //"Natural Sciences",
        5 => ['102', '104', '106', '107', '114', '115'], //"Humanities",
        6 => ['103'], //"Health & Medicine",
        4 => ['116'], //"Law",
        7 => ['110', '111', '112', '113'], //"Social Matters & Education",
    ];

    /**
     * @Route("/series/channel/{channelNumber}.html", name="pumukit_webtv_channel_series")
     * @Template("PumukitWebTVBundle:Channel:template.html.twig")
     *
     * @param $channelNumber
     *
     * @return array
     */
    public function seriesAction($channelNumber)
    {
        $numberCols = $this->container->getParameter('columns_objs_bytag');
        $limit = $this->container->getParameter('limit_objs_bytag');

        $repoSeries = $this->get('doctrine_mongodb')->getRepository(Series::class);
        $repoMmobj = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);

        $channelTitle = $this->getChannelTitle($channelNumber);
        $channelTags = $this->getTagsForChannel($channelNumber);
        $results = [];

        foreach ($channelTags as $tag) {
            $series = $repoSeries->createBuilderWithTag($tag, ['record_date' => -1]);
            $series = $series->getQuery()->execute();
            $numMmobjs = $repoMmobj
                ->createBuilderWithTag($tag, ['record_date' => -1])
                ->count()
                ->getQuery()
                ->execute();

            $results[] = [
                'tag' => $tag,
                'objects' => $series,
                'numMmobjs' => $numMmobjs,
                'limit' => $limit,
            ];
        }

        $this->updateBreadcrumbs($channelTitle, 'pumukit_webtv_channel_series', ['channelNumber' => $channelNumber]);

        return [
            'title' => $channelTitle,
            'results' => $results,
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => true,
        ];
    }

    /**
     * @param $channelNumber
     *
     * @return mixed|string
     */
    public function getChannelTitle($channelNumber)
    {
        $title = isset($this->titles[$channelNumber]) ? $this->titles[$channelNumber] : 'No title';
        $title = $this->get('translator')->trans($title);

        return $title;
    }

    /**
     * @param $channelNumber
     *
     * @return array
     */
    public function getTagsForChannel($channelNumber)
    {
        $tagCods = isset($this->tags[$channelNumber]) ? $this->tags[$channelNumber] : [];
        $tags = [];
        $repoTags = $this->get('doctrine_mongodb')->getRepository(Tag::class);
        foreach ($tagCods as $tagCod) {
            $tags[] = $repoTags->findOneByCod($tagCod);
        }

        return $tags;
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    private function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }
}
