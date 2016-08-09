<?php

namespace Pumukit\OaiBundle\Test\Utils;

use Pumukit\OaiBundle\Utils\ResumptionToken;

class ResumptionTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndGetter()
    {
        $token = new ResumptionToken();
        $this->assertEquals(0, $token->getOffset());
        $this->assertEquals(null, $token->getFrom());
        $this->assertEquals(null, $token->getUntil());
        $this->assertEquals(null, $token->getMetadataPrefix());
        $this->assertEquals(null, $token->getSet());

        $offset = 10;
        $from = new \DateTime('yesterday');
        $until = new \DateTime('tomorrow');
        $metadataPrefix = 'oai_dc';
        $set = 'castillo';
        $token = new ResumptionToken($offset, $from, $until, $metadataPrefix, $set);
        $this->assertEquals($offset, $token->getOffset());
        $this->assertEquals($from, $token->getFrom());
        $this->assertEquals($until, $token->getUntil());
        $this->assertEquals($metadataPrefix, $token->getMetadataPrefix());
        $this->assertEquals($set, $token->getSet());

        $this->assertTrue(strlen($token->encode()) > 0);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDecode()
    {
        $rawToken = base64_encode('}}~~{{');
        $token = ResumptionToken::decode($rawToken);
    }

    public function testDecode()
    {
        $rawToken = 'eyJvZmZzZXQiOjEwLCJtZXRhZGF0YVByZWZpeCI6Im9haV9kYyIsInNldCI6ImNhc3RpbGxvIiwiZnJvbSI6MTQ3MDYwNzIwMCwidW50aWwiOjE0NzA3ODAwMDB9';

        $offset = 10;
        $metadataPrefix = 'oai_dc';
        $set = 'castillo';
        $token = ResumptionToken::decode($rawToken);

        $this->assertEquals($offset, $token->getOffset());
        $this->assertEquals($metadataPrefix, $token->getMetadataPrefix());
        $this->assertEquals($set, $token->getSet());
    }
}
