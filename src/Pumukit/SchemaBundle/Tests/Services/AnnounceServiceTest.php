<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;

class AnnounceServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $announceService;
    private $factoryService;
    private $tagService;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->seriesRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:Series');
        $this->mmobjRepo = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $this->announceService = $kernel->getContainer()
          ->get('pumukitschema.announce');
        $this->factoryService = $kernel->getContainer()
          ->get('pumukitschema.factory');
        $this->tagService = $kernel->getContainer()
          ->get('pumukitschema.tag');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')
          ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
          ->remove(array());
        $this->dm->flush();
    }

    public function testGetLast()
    {
        $series1 = $this->factoryService->createSeries();
        $series2 = $this->factoryService->createSeries();

        $this->dm->persist($series1);
        $this->dm->persist($series2);
        $this->dm->flush();

        $mm11 = $this->factoryService->createMultimediaObject($series1);
        $mm12 = $this->factoryService->createMultimediaObject($series1);

        $mm21 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);

        $this->dm->persist($mm11);
        $this->dm->persist($mm12);
        $this->dm->persist($mm21);
        $this->dm->persist($mm22);
        $this->dm->flush();

        $tag = new Tag();
        $tag->setCod('PUDENEW');
        $tag->setTitle('PUDENEW');
        $this->dm->persist($tag);
        $this->dm->flush();

        $this->tagService->addTagToMultimediaObject($mm11, $tag->getId());

        $this->assertEquals(array(), $this->announceService->getLast());
    }
    public function testNextLatestUploads()
    {
        $tagPudenew = new Tag();
        $tagPudenew->setCod("PUDENEW");//This tag must be added to mmobjs in order for them to appear on 'Latests Uploads'

        //We create a serie to hold our mmobjs
        $series2 = $this->factoryService->createSeries();
        $series2->setPublicDate(\DateTime::createFromFormat('d/m/Y', "31/05/1999"));
        $series2->setAnnounce(true);
        $this->dm->persist($series2);
        $this->dm->flush();

        //We create two mmobjs to run test with
        $mm11 = $this->factoryService->createMultimediaObject($series2);
        $mm22 = $this->factoryService->createMultimediaObject($series2);
        $mm11->setPublicDate(\DateTime::createFromFormat('d/m/Y', "01/05/1999"));
        $mm22->setPublicDate(\DateTime::createFromFormat('d/m/Y', "05/04/1999"));
        $mm11->addTag($tagPudenew);
        $mm22->addTag($tagPudenew);
        $this->dm->persist($mm11);
        $this->dm->persist($mm22);
        $this->dm->flush();

        //We create initial date to request
        $date = \DateTime::createFromFormat('d/m/Y', "01/08/1999");

        //We check the response is correct (returns objects within the same month)
        list($dateEnd, $last) = $this->announceService->getNextLatestUploads($date);
        $this->assertEquals( "05/1999", $dateEnd->format("m/Y"));
        $this->assertEquals( array($series2, $mm11), $last);

        //We reuse the series and change the date
        $series2->setPublicDate(\DateTime::createFromFormat('d/m/Y', "05/04/1999"));
        $series2->setAnnounce(false);
        $this->dm->persist($series2);
        $this->dm->flush();

        //Now we take the returned date and decrease it by one month (as in the AJAX request)
        $dateEnd->modify('first day of last month');
        //We check again for a correct answer (the series shouldn't be here at all)
        list($dateEnd, $last) = $this->announceService->getNextLatestUploads($dateEnd);
        $this->assertEquals( array($mm22), $last);

        //Finally, we check the answer is empty after searching for 24 months. (calling it two times)
        $dateEnd->modify('first day of last month');
        list($dateEnd, $last) = $this->announceService->getNextLatestUploads($dateEnd);
        $this->assertEquals( array(), $last);
        $this->assertEquals( "04/1997", $dateEnd->format("m/Y"));
        $dateEnd->modify('first day of last month');
        list($dateEnd, $last) = $this->announceService->getNextLatestUploads($dateEnd);
        $this->assertEquals( array(), $last);
        $this->assertEquals( "04/1995", $dateEnd->format("m/Y"));

    }
}
