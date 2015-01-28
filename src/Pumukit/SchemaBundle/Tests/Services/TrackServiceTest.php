<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\File\File;

class TrackServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $trackService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
          ->getRepository('PumukitSchemaBundle:Track');
        $this->trackService = $kernel->getContainer()
          ->get('pumukitschema.track');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Track')->remove(array());
        $this->dm->flush();
    }

    public function testAddTrackToMultimediaObject()
    {
        $multimediaObject = new MultimediaObject();
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        /*
        $file = new File('');

        $formData = $this->createFormData(1);

        $track = $this->trackService->addTrackToMultimediaObject($multimediaObject, $file, $formData);

        $this->assertEquals(1, count($this->repo->findAll()));

        $file2 = new File('http://www.boe.es/boe/dias/2014/11/26/pdfs/BOE-A-2014-12286.pdf');

        $formData2 = $this->createFormData(2);

        $track2 = $this->trackService->addTrackToMultimediaObject($multimediaObject, $file2, $formData2);

        $this->assertEquals(2, count($this->repo->findAll()));
        */
    }

    private function createFormData($number)
    {
        $formData = array(
                          'i18n_description' => array(
                                                      'en' => 'track description '.$number,
                                                      'es' => 'descripción del archivo '.$number,
                                                      ),
                          );

        return $formData;
    }
}
