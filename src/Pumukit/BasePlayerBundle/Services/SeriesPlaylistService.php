<?php

namespace Pumukit\BasePlayerBundle\Services;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Utils\CountableAppendIterator;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class SeriesPlaylistService
{
    private $dm;
    private $mmobjRepo;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
    }

    /**
     * Returns an iterator with all mmobjs belonging to the playlist.
     *
     * This function returns an iterator with the 'series' mmobjs (mmobj whose series ref is this Collection)
     * followed by the 'playlist' mmobjs (mmobjs whose ids are on the playlist embedded document on this Collection)
     *
     * @param Series $series   The series to return mmobjs from
     * @param array  $criteria (optional) The criteria to filter the mmobj with. In case personalized requirements are needed
     *
     * @return CountableAppendIterator
     */
    public function getPlaylistMmobjs(Series $series, $criteria = [])
    {
        $qb = $this->createSortedQuerySeriesMmobjs($series, $criteria);
        $seriesMmobjs = $qb->getQuery()->execute();

        $iterable = new CountableAppendIterator();
        $iterable->append($seriesMmobjs);

        $playlist = $this->retrieveSortedPlaylistMmobjs($series, $criteria);
        $iterable->append(new \ArrayIterator($playlist));

        return $iterable;
    }

    /**
     * Returns the 'first' mmobj on the playlist.
     *
     * This function returns the first mmobj on the playlist. If there are any multimedia
     * objects which references this Collection, the first of those (using the rank criteria)
     * will be returned. Otherwise, the first valid mmobj belonging to the playlist embed
     * document will be returned.
     *
     * @param Series $series   The series to return the first mmobj from
     * @param array  $criteria (optional) The criteria to filter the mmobj with. In case personalized requirements are needed
     *
     * @return MultimediaObject
     */
    public function getPlaylistFirstMmobj(Series $series, $criteria = [])
    {
        $qb = $this->createSortedQuerySeriesMmobjs($series, $criteria);
        $mmobj = $qb->getQuery()->getSingleResult();

        if (!$mmobj) {
            $playlist = $this->retrieveSortedPlaylistMmobjs($series, $criteria);
            if ($playlist) {
                return reset($playlist);
            }
        }

        return $mmobj;
    }

    /**
     * Returns the mmobj with the given id, if belongs to the series. Otherwise it returns null.
     *
     * This function is used to check whether the given mmobj id is valid and belongs to the given series.
     *
     * @param string $mmobjId  The id of the multimedia object
     * @param Series $series   The series to return the first mmobj from
     * @param array  $criteria (optional) The criteria to filter the mmobj with. In case personalized requirements are needed
     *
     * @return MultimediaObject
     */
    public function getMmobjFromIdAndPlaylist($mmobjId, Series $series, $criteria = [])
    {
        $qb = $this->createSortedQuerySeriesMmobjs($series, $criteria)
            ->field('id')->equals(new \MongoId($mmobjId));
        $mmobj = $qb->getQuery()->getSingleResult();

        if (!$mmobj) {
            $playlistMmobjs = $this->retrieveSortedPlaylistMmobjs($series, $criteria);
            foreach ($playlistMmobjs as $playMmobj) {
                if ($playMmobj->getId() == $mmobjId) {
                    return $playMmobj;
                }
            }
        }

        return $mmobj;
    }

    /**
     * Returns a query builder for the mmobjs of a series playlist embed document.
     *
     * @param array $playlistMmobjIds List of MongoIds to find
     * @param array $criteria         (optional) The criteria to filter the mmobj with. In case personalized requirements are needed
     *
     * @return \Doctrine\MongoDB\Query\Builder
     */
    protected function createQueryPlaylistMmobjs($playlistMmobjIds, $criteria = [])
    {
        $qb = $this->mmobjRepo->createQueryBuilder()->field('id')->in($playlistMmobjIds);
        if ($criteria) {
            $qb->addAnd($criteria);
        }

        return $qb;
    }

    /**
     * Returns a query builder for the mmobjs of a series.
     *
     * @param Series $series   The series to get mmobjs from
     * @param array  $criteria (optional) The criteria to filter the mmobj with. In case personalized requirements are needed
     *
     * @return mixed
     */
    protected function createSortedQuerySeriesMmobjs($series, $criteria = [])
    {
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
            ->field('series')->references($series);
        if ($criteria) {
            $qb->addAnd($criteria);
        }
        $qb->sort('rank', 'asc');

        return $qb;
    }

    /**
     * Returns an array with all valid mmobj from the playlist embed document.
     *
     * First it gets all ids, then it makes a query for all (to reduce them, in case a filter is activated)
     * Then it sorts the result according to the original embed ids sorting.
     *
     * @param Series $series   The series to return mmobjs from
     * @param array  $criteria (optional) The criteria to filter the mmobj with. In case personalized requirements are needed
     *
     * @return array
     */
    protected function retrieveSortedPlaylistMmobjs(Series $series, $criteria = [])
    {
        $playlistMmobjs = $series->getPlaylist()->getMultimediaObjectsIdList();
        $playlistMmobjsFiltered = $this->createQueryPlaylistMmobjs($playlistMmobjs, $criteria)->getQuery()->execute();

        $playlist = [];
        //This foreach orders the $playlistMmobjsFiltered results according to the order they appear in $playlistMmobjs.
        //Ideally, mongo should return them ordered already, but I couldn't find how to achieve that.
        foreach ($playlistMmobjs as $playMmobj) {
            foreach ($playlistMmobjsFiltered as $mmobj) {
                if ($playMmobj == $mmobj->getId()) {
                    $playlist[] = $mmobj;
                }
            }
        }

        return $playlist;
    }
}
