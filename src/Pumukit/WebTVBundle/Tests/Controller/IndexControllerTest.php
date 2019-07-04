<?php

namespace Pumukit\WebTVBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class IndexControllerTest.
 *
 * @internal
 * @coversNothing
 */
class IndexControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($crawler->filter('html')->count() > 0);
        $this->assertTrue($crawler->filter('title')->count() > 0);
        $this->assertTrue($crawler->filter('body')->count() > 0);
    }
}
