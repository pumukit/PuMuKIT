<?php

declare(strict_types=1);

namespace Pumukit\BaseLivePlayerBundle\Tests\Views;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[CoversNothing]
class LiveEventTextEscapingTest extends KernelTestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';
    private const ESCAPED = '&lt;img src=x onerror=alert(1)&gt;';

    private MultimediaObject $multimediaObject;
    private EmbeddedEvent $event;

    public function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($request);

        $this->event = new EmbeddedEvent();
        $this->event->setAlreadyHeldMessage(self::PAYLOAD);

        $this->multimediaObject = new MultimediaObject();
        (new \ReflectionProperty($this->multimediaObject, 'id'))->setValue($this->multimediaObject, 'mm');
        $this->multimediaObject->setEmbeddedEvent($this->event);
    }

    public function testTheEventMessageOfThePortalIsEscaped(): void
    {
        $html = static::getContainer()->get('twig')->render('@PumukitBaseLivePlayer/Advance/sessions.html.twig', [
            'event' => $this->event,
            'multimediaObject' => $this->multimediaObject,
            'nextSessions' => [],
            'nowSessions' => [],
            'id' => 'event',
        ]);

        $this->assertStringNotContainsString(self::PAYLOAD, $html);
        $this->assertStringContainsString(self::ESCAPED, $html);
    }

    public function testTheSessionNotesOfTheBackofficeAreEscaped(): void
    {
        $session = new EmbeddedEventSession();
        $session->setId('session');
        $session->setStart(new \DateTime());
        $session->setEnds(new \DateTime('+1 hour'));
        $session->setDuration(3600);
        $session->setNotes(self::PAYLOAD);
        $this->event->addEmbeddedEventSession($session);

        $html = static::getContainer()->get('twig')->render('@PumukitNewAdmin/LiveEvent/sessionlist.html.twig', [
            'multimediaObject' => $this->multimediaObject,
        ]);

        $this->assertStringNotContainsString(self::PAYLOAD, $html);
        $this->assertStringContainsString(self::ESCAPED, $html);
    }
}
