<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\WebTVBundle\Controller\ModulesController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[CoversNothing]
class ModulesControllerTest extends PumukitTestCase
{
    public function testTheVerticalMediaBlockRendersAnAnnouncedSeries(): void
    {
        $series = new Series();
        $series->setNumericalID(1);
        $series->setTitle('Announced series');
        $series->setAnnounce(true);
        $series->setPublicDate(new \DateTime());
        $this->dm->persist($series);

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->setSeries($series);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($request);

        $content = static::getContainer()->get(ModulesController::class)->legacyRecentlyAdded()->getContent();

        $this->assertStringContainsString('Announced series', $content);
        $this->assertStringContainsString('/series/'.$series->getId(), $content);
    }
}
