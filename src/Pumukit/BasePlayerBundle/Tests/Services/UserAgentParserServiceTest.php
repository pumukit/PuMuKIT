<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserAgentParserServiceTest.
 *
 * @internal
 * @coversNothing
 */
class UserAgentParserServiceTest extends WebTestCase
{
    private $agentStrings;
    private $agentService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->agentService = static::$kernel->getContainer()->get('pumukit_baseplayer.useragent_parser');
        //Setting up user agent strings taken from random browsers:
        $this->agentStrings = [
            ['string' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20130401 Firefox/31.0', 'old' => true],
            ['string' => 'Mozilla/5.0 (X11; U; Linux i686; ru-RU; rv:1.9.2a1pre) Gecko/20090405 Ubuntu/9.04 (jaunty) Firefox/3.6a1pre', 'old' => true],
            ['string' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/534.55.3 (KHTML, like Gecko) Version/5.1.3 Safari/534.53.10', 'old' => false],
            ['string' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_7; de-de) AppleWebKit/525.28.3 (KHTML, like Gecko) Version/3.2.3 Safari/525.28.3', 'old' => true],
            ['string' => 'Mozilla/5.0 (X11; Linux i686; rv:9.0.1) Gecko/20120127 SeaMonkey/2.6.1', 'old' => false],
            ['string' => 'Mozilla/2.0 (compatible; MSIE 3.03; Windows 3.1)', 'old' => true],
            ['string' => 'Mozilla/4.0 (compatible; MSIE 4.5; Windows NT 5.1; .NET CLR 2.0.40607)', 'old' => true],
            ['string' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko', 'old' => false],
            ['string' => 'Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16', 'old' => true],
            ['string' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera', 'old' => true],
        ];
    }

    public function tearDown()
    {
        $this->agentService = null;
        $this->agentStrings = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testIsOldBrowser()
    {
        foreach ($this->agentStrings as $userAgent) {
            $this->assertEquals($userAgent['old'], $this->agentService->isOldBrowser($userAgent['string']));
        }
    }
}
