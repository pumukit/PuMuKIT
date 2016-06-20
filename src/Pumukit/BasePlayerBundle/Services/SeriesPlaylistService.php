<?php

namespace Pumukit\BasePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Common\Collections\Criteria;
use Pumukit\BasePlayerBundle\Utils\CountableAppendIterator;

class SeriesPlaylistService
{
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    /**
     * Returns an iterator with all mmobjs belonging to the playlist
     *
     * This function returns an iterator with the 'series' mmobjs (mmobj whose series ref is this Collection)
     * followed by the 'playlist' mmobjs (mmobjs whose ids are on the playlist embedded document on this Collection)
     *
     * @param Series $series The series to return mmobjs from.
     * @return CountableAppendIterator
     */
    public function getPlaylistMmobjs(Series $series)
    {
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
                   ->field('series')->references($series);
        $seriesMmobjs = $qb->getQuery()->execute();

        $iterable = new CountableAppendIterator();
        $iterable->append($seriesMmobjs);

        //Is there a better way to get the ORDERED FILTERED objects from the embed mmobjs?
        $playlistMmobjs = $series->getPlaylist()->getMultimediaObjectsIdList();
        $playlistMmobjsFiltered = $this->mmobjRepo->createQueryBuilder()->field('id')->in($playlistMmobjs)->getQuery()->execute();
        $playlist = array();
        foreach($playlistMmobjs as $playMmobj){
            foreach($playlistMmobjsFiltered as $mmobj) {
                if($playMmobj == $mmobj->getId())
                    $playlist[] = $mmobj;
            }
        }

        if(!$playlist)
            return $iterable;

        $iterable->append(new \ArrayIterator($playlist));
        return $iterable;
    }

    /**
     * Returns the 'first' mmobj on the playlist
     *
     * This function returns the first mmobj on the playlist. If there are any multimedia
     * objects which references this Collection, the first of those (using the rank criteria)
     * will be returned. Otherwise, the first valid mmobj belonging to the playlist embed
     * document will be returned.
     *
     * @param Series $series The series to return the first mmobj from.
     * @return MultimediaObject
     */
    public function getPlaylistFirstMmobj(Series $series)
    {
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
                   ->field('series')->references($series);
        $mmobj = $qb->getQuery()->getSingleResult();

        if(!$mmobj) {
            //Is there a better way to get the first FILTERED object from the embed mmobjs?
            $playlistMmobjs = $series->getPlaylist()->getMultimediaObjectsIdList();
            $mmobjs = $this->mmobjRepo->createQueryBuilder()->field('id')->in($playlistMmobjs)->getQuery()->execute();
            foreach($playlistMmobjs as $playMmobj){
                foreach($mmobjs as $mmobj) {
                    if($playMmobj == $mmobj->getId())
                        return $mmobj;
                }
            }
        }

        return $mmobj;
    }

    /**
     * Returns the mmobj with the given id, if belongs to the series. Otherwise it returns null.
     *
     * This function is used to check whether the given mmobj id is valid and belongs to the given series.
     *
     * @param string $mmobjId The id of the multimedia object.
     * @param Series $series The series to return the first mmobj from.
     * @return MultimediaObject
     */
    public function getMmobjFromIdAndPlaylist($mmobjId, Series $series)
    {
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
                   ->field('series')->references($series)
                   ->field('id')->equals(new \MongoId($mmobjId));
        $mmobj = $qb->getQuery()->getSingleResult();

        if(!$mmobj) {
            $mmobj = $series->getPlaylist()->getMultimediaObjects()->filter(
                function($mmobj) use($mmobjId) {
                    return $mmobj->getId() == $mmobjId;
                }
            );
            $mmobj = $mmobj->first();
            $mmobj = $this->mmobjRepo->createQueryBuilder()->field('id')->equals(new \MongoId($mmobj->getId()))->getQuery()->getSingleResult();
        }

        return $mmobj;
    }
}
