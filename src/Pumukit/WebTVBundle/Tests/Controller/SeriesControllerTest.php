<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversNothing]
class SeriesControllerTest extends WebTestCase
{
    public function testStyleIsServedAsStylesheet(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $documentManager = static::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $seriesStyle = new SeriesStyle();
        $seriesStyle->setName('test');
        $seriesStyle->setText('.title { color: red; }');
        $documentManager->persist($seriesStyle);
        $documentManager->flush();
        $id = $seriesStyle->getId();

        try {
            $client->request('GET', '/series/style/'.$id.'.css');
            $response = $client->getResponse();

            static::assertSame(200, $response->getStatusCode());
            static::assertStringStartsWith('text/css', $response->headers->get('Content-Type'));
            static::assertSame('.title { color: red; }', $response->getContent());

            $client->request('GET', '/series/style/'.$id.'.css', [], [], ['HTTP_IF_NONE_MATCH' => $response->getEtag()]);
            static::assertSame(304, $client->getResponse()->getStatusCode());
        } finally {
            $documentManager = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
            $documentManager->remove($documentManager->find(SeriesStyle::class, $id));
            $documentManager->flush();
        }
    }
}
