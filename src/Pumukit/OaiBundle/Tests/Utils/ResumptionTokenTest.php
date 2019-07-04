<?php

namespace Pumukit\OaiBundle\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Pumukit\OaiBundle\Utils\ResumptionToken;

/**
 * @internal
 * @coversNothing
 */
class ResumptionTokenTest extends TestCase
{
    public function testConstructAndGetter()
    {
        $token = new ResumptionToken();
        $this->assertSame(0, $token->getOffset());
        $this->assertSame(null, $token->getFrom());
        $this->assertSame(null, $token->getUntil());
        $this->assertSame(null, $token->getMetadataPrefix());
        $this->assertSame(null, $token->getSet());

        $offset = 10;
        $from = new \DateTime('yesterday');
        $until = new \DateTime('tomorrow');
        $metadataPrefix = 'oai_dc';
        $set = 'castillo';
        $token = new ResumptionToken($offset, $from, $until, $metadataPrefix, $set);
        $this->assertSame($offset, $token->getOffset());
        $this->assertSame($from, $token->getFrom());
        $this->assertSame($until, $token->getUntil());
        $this->assertSame($metadataPrefix, $token->getMetadataPrefix());
        $this->assertSame($set, $token->getSet());

        $this->assertTrue(strlen($token->encode()) > 0);
    }

    /**
     * @expectedException \Pumukit\OaiBundle\Utils\ResumptionTokenException
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

        $this->assertSame($offset, $token->getOffset());
        $this->assertSame($metadataPrefix, $token->getMetadataPrefix());
        $this->assertSame($set, $token->getSet());
    }
}
