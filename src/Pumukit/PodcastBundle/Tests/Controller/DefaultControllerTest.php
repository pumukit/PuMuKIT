<?php

namespace Pumukit\PodcastBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    private $dm;
    private $client;
    private $router;
    private $factory;
    private $skipTests = false;

    public function __construct()
    {
        $this->client = static::createClient();
        $options = array('environment'=>'test');
        static::bootKernel($options);

        $this->container = static::$kernel->getContainer();
        $this->dm = $this->container->get('doctrine_mongodb.odm.document_manager');
        $this->router = $this->container->get('router');
        $this->factory = $this->container->get('pumukitschema.factory');
    }

    protected function setUp()
    {
        if (!array_key_exists("PumukitPodcastBundle", $this->container->getParameter('kernel.bundles'))) {
            $this->markTestSkipped('PodcastBundle is not installed');
        }

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
            ->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
            ->remove(array());
    }

    public function testVideo()
    {

        $route = $this->router->generate('pumukit_podcast_video', array());
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        //$this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        //$this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        //$this->assertCount(1, $crawler->filter('itunes:category'));
        //$this->assertCount(1, $crawler->filter('itunes:summary'));
        //$this->assertCount(1, $crawler->filter('itunes:subtitle'));
        //$this->assertCount(1, $crawler->filter('itunes:author'));
        //$this->assertCount(1, $crawler->filter('itunes:owner'));
        //$this->assertCount(1, $crawler->filter('itunes:name'));
        //$this->assertCount(1, $crawler->filter('itunes:email'));
        //$this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testAudio()
    {

        $route = $this->router->generate('pumukit_podcast_audio', array());
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        //$this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        //$this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        //$this->assertCount(1, $crawler->filter('itunes:category'));
        //$this->assertCount(1, $crawler->filter('itunes:summary'));
        //$this->assertCount(1, $crawler->filter('itunes:subtitle'));
        //$this->assertCount(1, $crawler->filter('itunes:author'));
        //$this->assertCount(1, $crawler->filter('itunes:owner'));
        //$this->assertCount(1, $crawler->filter('itunes:name'));
        //$this->assertCount(1, $crawler->filter('itunes:email'));
        //$this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testSeriesVideo()
    {

        $series = $this->factory->createSeries();
        $route = $this->router->generate('pumukit_podcast_series_video', array('id' => $series->getId()));
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        //$this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        //$this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        //$this->assertCount(1, $crawler->filter('itunes:category'));
        //$this->assertCount(1, $crawler->filter('itunes:summary'));
        //$this->assertCount(1, $crawler->filter('itunes:subtitle'));
        //$this->assertCount(1, $crawler->filter('itunes:author'));
        //$this->assertCount(1, $crawler->filter('itunes:owner'));
        //$this->assertCount(1, $crawler->filter('itunes:name'));
        //$this->assertCount(1, $crawler->filter('itunes:email'));
        //$this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testSeriesAudio()
    {

        $series = $this->factory->createSeries();
        $route = $this->router->generate('pumukit_podcast_series_audio', array('id' => $series->getId()));
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        //$this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        //$this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        //$this->assertCount(1, $crawler->filter('itunes:category'));
        //$this->assertCount(1, $crawler->filter('itunes:summary'));
        //$this->assertCount(1, $crawler->filter('itunes:subtitle'));
        //$this->assertCount(1, $crawler->filter('itunes:author'));
        //$this->assertCount(1, $crawler->filter('itunes:owner'));
        //$this->assertCount(1, $crawler->filter('itunes:name'));
        //$this->assertCount(1, $crawler->filter('itunes:email'));
        //$this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testSeriesCollection()
    {
        $series = $this->factory->createSeries();
        $route = $this->router->generate('pumukit_podcast_series_collection', array('id' => $series->getId()));
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        //$this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        //$this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        //$this->assertCount(1, $crawler->filter('itunes:category'));
        //$this->assertCount(1, $crawler->filter('itunes:summary'));
        //$this->assertCount(1, $crawler->filter('itunes:subtitle'));
        //$this->assertCount(1, $crawler->filter('itunes:author'));
        //$this->assertCount(1, $crawler->filter('itunes:owner'));
        //$this->assertCount(1, $crawler->filter('itunes:name'));
        //$this->assertCount(1, $crawler->filter('itunes:email'));
        //$this->assertCount(1, $crawler->filter('itunes:explicit'));
    }
}
