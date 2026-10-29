<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
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

    public function testTheSeriesHeaderAndFooterKeepTheirFormattingWithoutScripts(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $documentManager = static::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $series = new Series();
        $series->setNumericalID(987654);
        $series->setHeader('<p><b>Course</b> <a href="https://example.org/course">info</a></p><script>alert(1)</script><img src="https://example.org/x.png" onerror="alert(2)">', 'en');
        $series->setFooter('<a href="javascript:alert(3)">contact</a><iframe src="https://attacker.example"></iframe>', 'en');
        $documentManager->persist($series);
        $documentManager->flush();
        $id = $series->getId();

        try {
            $client->request('GET', '/series/'.$id);
            $content = $client->getResponse()->getContent();

            static::assertSame(200, $client->getResponse()->getStatusCode());
            static::assertStringContainsString('<b>Course</b>', $content);
            static::assertStringContainsString('<a href="https://example.org/course">info</a>', $content);
            static::assertStringContainsString('<img src="https://example.org/x.png"', $content);
            static::assertStringNotContainsString('alert(1)', $content);
            static::assertStringNotContainsString('alert(2)', $content);
            static::assertStringNotContainsString('javascript:alert(3)', $content);
            static::assertStringNotContainsString('attacker.example', $content);
        } finally {
            $documentManager = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
            $documentManager->remove($documentManager->find(Series::class, $id));
            $documentManager->flush();
        }
    }

    public function testTheSubseriesTitleKeepsItsLineBreaksWithoutScripts(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $documentManager = static::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $documentManager->persist($tag);

        $series = new Series();
        $series->setNumericalID(987655);
        $series->setProperty('template', 'subserial');
        $documentManager->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(987655);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->addTag($tag);
        $multimediaObject->setSeries($series);
        $multimediaObject->setProperty('opencast', 'subseries');
        $multimediaObject->setProperty('subseriestitle', ['en' => '<b>Unit 1</b><img src="x" onerror="alert(4)">']);
        $documentManager->persist($multimediaObject);
        $documentManager->flush();
        $ids = [$tag->getId(), $series->getId(), $multimediaObject->getId()];

        try {
            $client->request('GET', '/series/'.$series->getId());
            $content = $client->getResponse()->getContent();

            static::assertSame(200, $client->getResponse()->getStatusCode());
            static::assertStringContainsString('<b>Unit 1</b>', $content);
            static::assertStringNotContainsString('alert(4)', $content);
        } finally {
            $documentManager = static::getContainer()->get('doctrine_mongodb.odm.document_manager');
            $documentManager->remove($documentManager->find(MultimediaObject::class, $ids[2]));
            $documentManager->remove($documentManager->find(Series::class, $ids[1]));
            $documentManager->remove($documentManager->find(Tag::class, $ids[0]));
            $documentManager->flush();
        }
    }
}
