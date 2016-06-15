<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SeriesPlaylistServiceTest extends WebTestCase
{
    private $dm;
    private $seriesPlaylistService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $container = static::$kernel->getContainer();
        $this->dm = $container->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->seriesPlaylistService = $container->get('pumukit_baseplayer.seriesplaylist');
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->seriesPlaylistService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetPlaylistMmobjs()
    {
        $track = new Track();
        $series = new Series();
        $mmobjs = array(
            'normal' => new MultimediaObject(),
            'blocked' => new MultimediaObject(),
            'hidden' => new MultimediaObject(),
        );
        $track->setUrl('funnyurl.mp4');
        foreach($mmobjs as $mmobj) {
            $mmobj->setSeries($series);
            $mmobj->addTrack($track);
            $this->dm->persist($mmobj);
        }
        $this->dm->persist($series);
        $this->dm->flush();

        $playlistMmobjs = $this->seriesPlaylistService->getPlaylistMmobjs($series);
        $this->assertEquals(3, $playlistMmobjs->count());
        //TODO: Add tests when service includes more functionality.
    }
}
