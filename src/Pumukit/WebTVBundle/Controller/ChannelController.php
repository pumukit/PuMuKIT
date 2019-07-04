<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class ChannelController.
 */
class ChannelController extends Controller implements WebTVControllerInterface
{
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

        $channelService = $this->get('pumukit_web_tv.channels');
        $channelTitle = $channelService->getChannelTitle($channelNumber);
        $channelTags = $channelService->getTagsForChannel($channelNumber);

        $results = $channelService->getChannelSeriesByTags($channelTags);

        $this->updateBreadcrumbs($channelTitle, 'pumukit_webtv_channel_series', ['channelNumber' => $channelNumber]);

        return [
            'title' => $channelTitle,
            'results' => $results,
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => true,
        ];
    }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }
}
