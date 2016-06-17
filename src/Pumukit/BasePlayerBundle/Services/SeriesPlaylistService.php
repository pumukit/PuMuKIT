<?php

namespace Pumukit\BasePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\Common\Collections\Criteria;

class SeriesPlaylistService
{
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    public function getPlaylistMmobjs(Series $series)
    {
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
                   ->field('series')->references($series);
        $seriesMmobjs = $qb->getQuery()->execute();

        $iterable = new \AppendIterator();
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
