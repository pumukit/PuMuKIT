<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

class ChannelService
{
    private $titles = array(
        1 => 'University',
        2 => 'Business',
        3 => 'Natural Sciences',
        5 => 'Humanities',
        6 => 'Health & Medicine',
        4 => 'Law',
        7 => 'Social Matters & Education',
    );

    private $tags = array(
        1 => array('PUDEUNI'), //"University",
        2 => array('100'), //"Business",
        3 => array('101', '108', '109'), //"Natural Sciences",
        5 => array('102', '104', '106', '107', '114', '115'), //"Humanities",
        6 => array('103'), //"Health & Medicine",
        4 => array('116'), //"Law",
        7 => array('110', '111', '112', '113'), //"Social Matters & Education",
    );

    private $dm;
    private $translatorService;
    private $repoTags;
    private $repoSeries;
    private $repoMmobjs;

    public function __construct(DocumentManager $dm, $translatorService)
    {
        $this->dm = $dm;
        $this->translatorService = $translatorService;
        $this->repoTags = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $this->repoSeries = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->repoMmobjs = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    public function getChannelTitle($channelNumber)
    {
        $title = isset($this->titles[$channelNumber]) ? $this->titles[$channelNumber] : 'No title';
        $transTitle = $this->translatorService->trans($title);

        return $transTitle;
    }

    public function getTagsForChannel($channelNumber)
    {
        $tagCods = isset($this->tags[$channelNumber]) ? $this->tags[$channelNumber] : array();
        $tags = array();
        foreach ($tagCods as $tagCod) {
            $tags[] = $this->repoTags->findOneByCod($tagCod);
        }

        return $tags;
    }

    public function getChannelSeriesByTags($channelTags)
    {
        $results = array();

        foreach ($channelTags as $tag) {
            $series = $this->repoSeries->createBuilderWithTag($tag, array('record_date' => -1))
                    ->getQuery()->execute();

            $numMmobjs = $this->repoMmobjs->createBuilderWithTag($tag, array('record_date' => -1))
                       ->count()->getQuery()->execute();

            $results[] = array(
                'tag' => $tag,
                'objects' => $series,
                'numMmobjs' => $numMmobjs,
            );
        }

        return $results;
    }
}
