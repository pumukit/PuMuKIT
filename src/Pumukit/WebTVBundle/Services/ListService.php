<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;

class ListService
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EmbeddedEventSessionService
     */
    private $embeddedEventSessionService;

    private $advanceLiveEvents;
    private $wallTag;

    /**
     * ListService constructor.
     *
     * @param DocumentManager             $documentManager
     * @param EmbeddedEventSessionService $embeddedEventSessionService
     * @param string                      $advanceLiveEvents
     * @param string                      $wallTag
     */
    public function __construct(DocumentManager $documentManager, EmbeddedEventSessionService $embeddedEventSessionService, $advanceLiveEvents, $wallTag)
    {
        $this->documentManager = $documentManager;
        $this->embeddedEventSessionService = $embeddedEventSessionService;
        $this->advanceLiveEvents = $advanceLiveEvents;
        $this->wallTag = $wallTag;
    }

    /**
     * @param $limit
     *
     * @return array
     */
    public function getLives($limit)
    {
        if (!$this->advanceLiveEvents) {
            return [];
        }

        return $this->embeddedEventSessionService->findCurrentSessions([], $limit);
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function getWallVideos()
    {
        $criteria = [
            'tags.cod' => $this->wallTag,
        ];

        return $this->documentManager->getRepository(MultimediaObject::class)->findStandardBy($criteria);
    }

    /**
     * @param array  $criteria
     * @param string $sort
     * @param string $locale
     * @param null   $parentTag
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return array
     */
    public function getMediaLibrary(array $criteria = [], $sort = 'date', $locale = 'en', $parentTag = null)
    {
        $result = [];
        $seriesRepository = $this->documentManager->getRepository(Series::class);
        $aggregatedNumMmobjs = $this->documentManager->getRepository(MultimediaObject::class)->countMmobjsBySeries();

        switch ($sort) {
            case 'alphabetically':
                $sortField = 'title.'.$locale;
                $series = $seriesRepository->findBy($criteria, [$sortField => 1]);

                foreach ($series as $serie) {
                    if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                        continue;
                    }

                    $key = mb_substr(trim($serie->getTitle()), 0, 1, 'UTF-8');
                    if (!isset($result[$key])) {
                        $result[$key] = [];
                    }
                    $result[$key][] = $serie;
                }

                break;
            case 'date':
                $sortField = 'public_date';
                $series = $seriesRepository->findBy($criteria, [$sortField => -1]);

                foreach ($series as $serie) {
                    if (!isset($aggregatedNumMmobjs[$serie->getId()])) {
                        continue;
                    }

                    $key = $serie->getPublicDate()->format('m/Y');
                    if (!isset($result[$key])) {
                        $result[$key] = [];
                    }

                    $title = $serie->getTitle();
                    if (!isset($result[$key][$title])) {
                        $result[$key][$title] = $serie;
                    } else {
                        $result[$key][$title.rand()] = $serie;
                    }
                }

                array_walk(
                    $result,
                    function (&$e, $key) {
                        ksort($e);

                        return array_values($e);
                    }
                );

                break;
            case 'tags':
                $p_cod = $parentTag;
                $parentTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $p_cod]);
                if (!isset($parentTag)) {
                    break;
                }

                $tags = $parentTag->getChildren();

                foreach ($tags as $tag) {
                    if ($tag->getNumberMultimediaObjects() < 1) {
                        continue;
                    }
                    $key = $tag->getTitle();

                    $sortField = 'title.'.$locale;
                    $seriesQB = $seriesRepository->createBuilderWithTag($tag, [$sortField => 1]);
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

                        if (!isset($result[$key])) {
                            $result[$key] = [];
                        }
                        $result[$key][] = $serie;
                    }
                }

                break;
        }

        return [
            $result,
            $aggregatedNumMmobjs,
        ];
    }

    /**
     * @param Builder   $qb
     * @param \DateTime $date
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return array
     */
    public function getNextElementsByQueryBuilder(Builder $qb, \DateTime $date)
    {
        $counter = 0;
        $dateStart = clone $date;
        $dateStart->modify('first day of next month');
        $dateEnd = clone $date;
        $dateEnd->modify('last day of next month');
        $dateEnd->setTime(23, 59, 59);
        do {
            ++$counter;
            $dateStart->modify('first day of last month');
            $dateEnd->modify('last day of last month');
            $last = $this->getNextElementsByDates($qb, $dateStart, $dateEnd);
        } while (empty($last) && $counter < 24);

        return [$dateEnd, $last];
    }

    public function getVideosByTag($tagCod = null, $limit = 0)
    {
        if (!$tagCod) {
            throw new \Exception('You must send tagCod');
        }

        $tag = $this->documentManager->getRepository(Tag::class)->findOneBy([
            'cod' => $tagCod,
        ]);

        if (!$tag) {
            throw new \Exception('Tag not found');
        }

        return $this->documentManager->getRepository(MultimediaObject::class)->findBy([
            'tags.cod' => $tagCod,
        ], [], $limit);
    }

    /**
     * @param null $tagCod
     *
     * @throws \Exception
     *
     * @return null|object|Tag
     */
    public function getEmbedVideoBlock($tagCod = null)
    {
        if (!$tagCod) {
            throw new \Exception('Tag code not found');
        }

        $tag = $this->documentManager->getRepository(Tag::class)->findOneBy([
            'cod' => $tagCod,
        ]);

        if (!$tag) {
            throw new \Exception('Tag code not exist');
        }

        return $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'tags.cod' => $tagCod,
        ]);
    }

    /**
     * @param Builder   $qb
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return mixed
     */
    private function getNextElementsByDates(Builder $qb, \DateTime $dateStart, \DateTime $dateEnd)
    {
        $qb->field('public_date')->range($dateStart, $dateEnd);

        return $qb->sort(['public_date' => -1])->getQuery()->execute()->toArray();
    }
}
