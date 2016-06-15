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
        $mmobjs = array();
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
                   ->field('series')->references($series);
        $mmobjs = $qb->getQuery()->execute();

        $playlist = $series->getPlaylist()->getMultimediaObjects()->getIterator();

        $iterable = new \AppendIterator();
        $iterable->append($mmobjs);
        $iterable->append($playlist);
        return $iterable;
    }

    public function getPlaylistFirstMmobj(Series $series)
    {
        $mmobj = array();
        $qb = $this->mmobjRepo->createStandardQueryBuilder()
                   ->field('series')->references($series);
        $mmobj = $qb->getQuery()->getSingleResult();

        if(!$mmobj)
            $mmobj = $series->getPlaylist()->getMultimediaObjects()->first();

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
        }

        return $mmobj;
    }
}
