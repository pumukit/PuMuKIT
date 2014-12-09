<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Services\FactoryService;

class FactoryServiceTest extends WebTestCase
{
    private $dm;
    private $translator;
    private $locales;
    private $factory;

    public function setUp()
    {
      $options = array('environment' => 'test');
      $kernel = static::createKernel($options);
      $kernel->boot();
      $this->dm = $kernel->getContainer()
	  ->get('doctrine_mongodb')->getManager();
      $this->translator = $kernel->getContainer()
	  ->get('translator');
      $this->locales = $kernel->getContainer()
          ->get('pumukitschema.schema.locale');
      $this->factory = $kernel->getContainer()
	  ->get('pumukitschema.factory');

      $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
	  ->remove(array());
      $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
	  ->remove(array());
      $this->dm->flush();
    }

    public function testCreateSeries()
    {
      $this->factory->createSeries();

      $this->assertEquals(1, count($this->dm->getRepository('PumukitSchemaBundle:Series')));
      $this->assertEquals(1, count($this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')));
    }
}