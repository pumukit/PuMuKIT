<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\EventListener\DisabledFeatureListener;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Live;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversNothing]
final class DisabledFeatureListenerTest extends PumukitTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
    }

    public function testTheRoutesOfADisabledFeatureAnswerNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new DisabledFeatureListener(false, true))->onKernelRequest($this->event('pumukitnewadmin_playlistmms_list'));
    }

    public function testTheChatRoutesAnswerNotFoundWhenTheChatIsDisabled(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new DisabledFeatureListener(true, false))->onKernelRequest($this->event('pumukit_live_chat_list'));
    }

    public function testTheOtherRoutesAreNotAffected(): void
    {
        $this->expectNotToPerformAssertions();

        $listener = new DisabledFeatureListener(false, false);
        $listener->onKernelRequest($this->event('pumukit_playlistplayer_index'));
        $listener->onKernelRequest($this->event('pumukitnewadmin_series_index'));
        $listener->onKernelRequest($this->event(null));
    }

    public function testTheEnabledFeaturesAreNotAffected(): void
    {
        $this->expectNotToPerformAssertions();

        $listener = new DisabledFeatureListener(true, true);
        $listener->onKernelRequest($this->event('pumukitnewadmin_playlist_index'));
        $listener->onKernelRequest($this->event('pumukit_live_chat_show'));
    }

    public function testAnEmbeddedSubRequestIsNotCut(): void
    {
        $this->expectNotToPerformAssertions();

        (new DisabledFeatureListener(false, false))->onKernelRequest($this->event('pumukit_live_chat_show', HttpKernelInterface::SUB_REQUEST));
    }

    public function testEveryPlaylistAndChatRouteHasItsPrefix(): void
    {
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection();
        $checked = 0;
        foreach ($routes as $name => $route) {
            if (str_starts_with($route->getPath(), '/admin/playlist')) {
                self::assertStringStartsWith(DisabledFeatureListener::PLAYLIST_ROUTES, $name);
                ++$checked;
            }
            if (str_starts_with($route->getPath(), '/chat/')) {
                self::assertStringStartsWith(DisabledFeatureListener::CHAT_ROUTES, $name);
                ++$checked;
            }
        }

        self::assertGreaterThan(6, $checked);
    }

    public function testAPlaylistRouteAsksForTheLoginWhenTheFeatureIsEnabled(): void
    {
        $this->client->request('GET', '/admin/playlist/');

        $this->assertResponseRedirects();
    }

    public function testAPlaylistRouteDoesNotExistWhenTheFeatureIsDisabled(): void
    {
        $listener = self::getContainer()->get(DisabledFeatureListener::class);
        (new \ReflectionProperty($listener, 'enablePlaylist'))->setValue($listener, false);

        $this->client->request('GET', '/admin/playlist/');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheLivePageWorksWithTheChatDisabled(): void
    {
        $live = new Live();
        $live->setUrl('https://example.org/live');
        $live->setSourceName('stream');
        $live->setLocale('en');
        $live->setName('Channel', 'en');
        $this->dm->persist($live);
        $this->dm->flush();

        $this->client->request('GET', '/chat/basic/show/'.$live->getId());
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/live/'.$live->getId());
        $this->assertResponseIsSuccessful();
    }

    private function event(?string $route, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        return new RequestEvent(self::getContainer()->get('kernel'), $request, $type);
    }
}
