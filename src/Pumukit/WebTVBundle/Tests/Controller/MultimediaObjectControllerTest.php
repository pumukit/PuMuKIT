<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Controller\MultimediaObjectController;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[CoversNothing]
class MultimediaObjectControllerTest extends PumukitTestCase
{
    private Series $series;
    private MultimediaObject $published;
    private MultimediaObject $hidden;
    private MultimediaObjectController $controller;

    public function setUp(): void
    {
        parent::setUp();

        $tag = new Tag();
        $tag->setCod(PumukitWebTVBundle::WEB_TV_TAG);
        $this->dm->persist($tag);

        $this->series = new Series();
        $this->series->setNumericalID(1);
        $this->dm->persist($this->series);

        $this->published = $this->createMultimediaObject(1, MultimediaObject::STATUS_PUBLISHED, $tag);
        $this->hidden = $this->createMultimediaObject(2, MultimediaObject::STATUS_HIDDEN, $tag);
        $this->dm->flush();

        $mainRequest = new Request();
        $mainRequest->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($mainRequest);

        $this->controller = static::getContainer()->get(MultimediaObjectController::class);
        (new \ReflectionProperty($this->controller, 'pumukitFullMagicUrl'))->setValue($this->controller, true);
    }

    public function testTheSeriesBlockDoesNotListHiddenVideosWhenOnlyTheRefererMentionsMagic(): void
    {
        $request = Request::create('/video/'.$this->published->getId(), server: ['HTTP_REFERER' => 'https://attacker.example/magic']);

        $content = $this->controller->seriesAction($request, $this->published)->getContent();

        $this->assertStringNotContainsString($this->hidden->getSecret(), $content);
    }

    public function testTheSeriesBlockListsHiddenVideosOnTheMagicPageOfAVideo(): void
    {
        $request = Request::create('/video/magic/'.$this->published->getSecret());
        $request->attributes->set('magicUrl', true);

        $content = $this->controller->seriesAction($request, $this->published)->getContent();

        $this->assertStringContainsString($this->hidden->getSecret(), $content);
    }

    public function testTheSeriesBlockListsHiddenVideosWhenComingFromTheMagicPageOfTheSeries(): void
    {
        $seriesMagicUrl = static::getContainer()->get('router')->generate('pumukit_webtv_series_magicindex', ['secret' => $this->series->getSecret()], UrlGeneratorInterface::ABSOLUTE_URL);
        $request = Request::create('/video/'.$this->published->getId(), server: ['HTTP_REFERER' => $seriesMagicUrl]);

        $content = $this->controller->seriesAction($request, $this->published)->getContent();

        $this->assertStringContainsString($this->hidden->getSecret(), $content);
    }

    private function createMultimediaObject(int $numericalId, int $status, Tag $tag): MultimediaObject
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID($numericalId);
        $multimediaObject->setStatus($status);
        $multimediaObject->addTag($tag);
        $multimediaObject->setSeries($this->series);
        $this->dm->persist($multimediaObject);

        return $multimediaObject;
    }
}
