<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\ChannelService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ChannelController extends AbstractController implements WebTVControllerInterface
{
    private $breadcrumbService;
    private $channelService;
    private $columnsObjsByTag;

    public function __construct(
        BreadcrumbsService $breadcrumbService,
        ChannelService $channelService,
        $columnsObjsByTag
    ) {
        $this->breadcrumbService = $breadcrumbService;
        $this->channelService = $channelService;
        $this->columnsObjsByTag = $columnsObjsByTag;
    }

    /**
     * @Route("/series/channel/{channelNumber}.html", name="pumukit_webtv_channel_series")
     * @Template("@PumukitWebTV/Channel/template.html.twig")
     */
    public function seriesAction(string $channelNumber)
    {
        $channelTitle = $this->channelService->getChannelTitle($channelNumber);
        $channelTags = $this->channelService->getTagsForChannel($channelNumber);
        $results = $this->channelService->getChannelSeriesByTags($channelTags);

        $this->breadcrumbService->add($channelTitle, 'pumukit_webtv_channel_series', ['channelNumber' => $channelNumber]);

        return [
            'title' => $channelTitle,
            'results' => $results,
            'objectByCol' => $this->columnsObjsByTag,
            'show_info' => true,
            'show_description' => true,
        ];
    }
}
