<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class AnnounceService
{
    private $seriesRepo;
    private $mmobjRepo;

    public function __construct(DocumentManager $documentManager)
    {
        $dm = $documentManager;
        $this->seriesRepo = $dm->getRepository(Series::class);
        $this->mmobjRepo = $dm->getRepository(MultimediaObject::class);
    }

    public function getLast($limit = 3, $withPudenewTag = true, $useRecordDate = false)
    {
        if ($withPudenewTag) {
            $return = $this->getLastMmobjsWithSeries($limit, $useRecordDate);
        } else {
            //Get recently added mmobjs
            $sortKey = $useRecordDate ? 'record_date' : 'public_date';
            $return = $this->mmobjRepo->findStandardBy([], [$sortKey => -1], $limit, 0);
        }

        return $return;
    }

    public function getLatestUploadsByDates($dateStart, $dateEnd, $withPudenewTag = true, $useRecordDate = false)
    {
        $sortKey = $useRecordDate ? 'record_date' : 'public_date';

        $queryBuilderMms = $this->mmobjRepo->createQueryBuilder();
        $queryBuilderMms->field($sortKey)->range($dateStart, $dateEnd);

        if (!$withPudenewTag) {
            return $queryBuilderMms->sort([$sortKey => -1])->getQuery()->execute()->toArray();
        }

        $queryBuilderSeries = $this->seriesRepo->createQueryBuilder();
        $queryBuilderSeries->field('public_date')->range($dateStart, $dateEnd);

        $queryBuilderMms->field('tags.cod')->equals('PUDENEW');
        $queryBuilderSeries->field('announce')->equals(true);

        $lastMms = $queryBuilderMms->getQuery()->execute();
        $lastSeries = $queryBuilderSeries->getQuery()->execute();

        $last = [];

        foreach ($lastSeries as $serie) {
            $haveMmojb = $this->mmobjRepo->findBy(['series' => new \MongoId($serie->getId())]);
            if (0 !== count($haveMmojb)) {
                $last[] = $serie;
            }
        }
        foreach ($lastMms as $mm) {
            $last[] = $mm;
        }

        usort($last, function ($a, $b) {
            $date_a = $a->getPublicDate();
            $date_b = $b->getPublicDate();
            if ($date_a == $date_b) {
                return 0;
            }

            return $date_a < $date_b ? 1 : -1;
        });

        return $last;
    }

    /**
     * Gets the next latest uploads month, starting with the month given and looking 24 months forward.
     *
     * An optional parameter can be added to either use PUDENEW or not.
     * If it can't find any objects, returns an empty array.
     *
     * @param mixed $date
     * @param mixed $withPudenewTag
     * @param mixed $useRecordDate
     *
     * @return array
     */
    public function getNextLatestUploads($date, $withPudenewTag = true, $useRecordDate = false)
    {
        $dateStart = clone $date;
        $dateStart->modify('first day of next month');
        $dateEnd = clone $date;
        $dateEnd->modify('last day of next month');
        $dateEnd->setTime(23, 59, 59);

        $sortKey = $useRecordDate ? 'record_date' : 'public_date';
        $queryBuilderMms = $this->mmobjRepo->createQueryBuilder();
        $queryBuilderMms->sort($sortKey, 'asc');
        if ($withPudenewTag) {
            $queryBuilderMms->field('tags.cod')->equals('PUDENEW');
        }
        $queryBuilderMms->limit(1);

        $lastMm = $queryBuilderMms->getQuery()->getSingleResult();
        if (!$lastMm) {
            return [null, []];
        }

        $lastDate = $useRecordDate ? $lastMm->getRecordDate() : $lastMm->getPublicDate();

        do {
            $dateStart->modify('first day of last month');
            $dateEnd->modify('last day of last month');
            $last = $this->getLatestUploadsByDates($dateStart, $dateEnd, $withPudenewTag, $useRecordDate);
        } while (empty($last) && $lastMm && $lastDate <= $dateEnd);

        if (empty($last)) {
            $dateEnd = null;
        }

        return [$dateEnd, $last];
    }

    /**
     * Returns the last series/mmobjs with the pudenew tag.
     *
     * @param mixed $limit
     * @param mixed $useRecordDate
     */
    protected function getLastMmobjsWithSeries($limit = 3, $useRecordDate = false)
    {
        $mmobjCriteria = ['tags.cod' => 'PUDENEW'];
        $seriesCriteria = ['announce' => true];

        $sortKey = $useRecordDate ? 'record_date' : 'public_date';
        $lastMms = $this->mmobjRepo->findStandardBy($mmobjCriteria, [$sortKey => -1], $limit, 0);
        $lastSeries = $this->seriesRepo->findBy($seriesCriteria, ['public_date' => -1], $limit, 0);

        $z = 0;
        foreach ($lastSeries as $series) {
            $isValidSeries = $this->mmobjRepo->findStandardBy(['series' => $series->getId()]);
            if (count($isValidSeries) <= 0) {
                unset($lastSeries[$z]);
            }
            ++$z;
        }

        $return = [];
        $i = 0;
        $iMms = 0;
        $iSeries = 0;

        while ($i++ < $limit) {
            if ((!isset($lastMms[$iMms])) && (!isset($lastSeries[$iSeries]))) {
                break;
            }
            if (!isset($lastMms[$iMms])) {
                $return[] = $lastSeries[$iSeries++];
            } elseif (!isset($lastSeries[$iSeries])) {
                $return[] = $lastMms[$iMms++];
            } else {
                $auxMms = $lastMms[$iMms];
                $auxSeries = $lastSeries[$iSeries];
                if ($auxMms->getPublicDate() > $auxSeries->getPublicDate()) {
                    $return[] = $auxMms;
                    ++$iMms;
                } else {
                    $return[] = $auxSeries;
                    ++$iSeries;
                }
            }
        }

        return $return;
    }
}
