<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;

class ChannelService
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
        1 => ['PUDEUNI'], //"University",
        2 => ['100'], //"Business",
        3 => ['101', '108', '109'], //"Natural Sciences",
        5 => ['102', '104', '106', '107', '114', '115'], //"Humanities",
        6 => ['103'], //"Health & Medicine",
        4 => ['116'], //"Law",
        7 => ['110', '111', '112', '113'], //"Social Matters & Education",
    ];

    private $dm;
    private $translatorService;
    private $repoTags;
    private $repoSeries;
    private $repoMmobjs;

    public function __construct(DocumentManager $dm, $translatorService)
    {
        $this->dm = $dm;
        $this->translatorService = $translatorService;
        $this->repoTags = $this->dm->getRepository(Tag::class);
        $this->repoSeries = $this->dm->getRepository(Series::class);
        $this->repoMmobjs = $this->dm->getRepository(MultimediaObject::class);
    }

    public function getChannelTitle($channelNumber)
    {
        $title = $this->titles[$channelNumber] ?? 'No title';

        return $this->translatorService->trans($title);
    }

    public function getTagsForChannel($channelNumber)
    {
        $tagCods = $this->tags[$channelNumber] ?? [];
        $tags = [];
        foreach ($tagCods as $tagCod) {
            $tags[] = $this->repoTags->findOneByCod($tagCod);
        }

        return $tags;
    }

    public function getChannelSeriesByTags($channelTags)
    {
        $results = [];

        foreach ($channelTags as $tag) {
            $series = $this->repoSeries->createBuilderWithTag($tag, ['record_date' => -1])
                ->getQuery()->execute();

            $numMmobjs = $this->repoMmobjs->createBuilderWithTag($tag, ['record_date' => -1])
                ->count()->getQuery()->execute();

            $results[] = [
                'tag' => $tag,
                'objects' => $series,
                'numMmobjs' => $numMmobjs,
            ];
        }

        return $results;
    }
}
