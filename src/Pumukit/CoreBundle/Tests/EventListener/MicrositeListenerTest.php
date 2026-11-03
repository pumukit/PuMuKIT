<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\EventListener;

use Pumukit\CoreBundle\DependencyInjection\Configuration;
use Pumukit\CoreBundle\EventListener\MicrositeListener;
use Pumukit\CoreBundle\Services\MicrositeService;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\CoreBundle\Twig\MicrositeExtension;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class MicrositeListenerTest extends PumukitTestCase
{
    private const MICROSITES = [
        'library' => [
            'hosts' => ['library.tv.example.org'],
            'tag' => 'MICROSITE01000',
            'layout' => 'microsites/library/layout.html.twig',
            'css' => 'microsites/library.css',
            'home_template' => null,
        ],
    ];

    public function tearDown(): void
    {
        if ($this->dm->getFilterCollection()->isEnabled('microsite')) {
            $this->dm->getFilterCollection()->disable('microsite');
        }
        parent::tearDown();
    }

    public function testTheMicrositeHostOnlyShowsTheObjectsWithItsTag(): void
    {
        $tag = new Tag();
        $tag->setCod('MICROSITE01000');
        $this->dm->persist($tag);
        $this->dm->flush();
        $factory = self::getContainer()->get(FactoryService::class);
        $series = $factory->createSeries();
        $tagged = $factory->createMultimediaObject($series);
        $tagged->addTag($tag);
        $factory->createMultimediaObject($series);
        $this->dm->flush();
        $this->dm->clear();

        $request = $this->dispatch('https://LIBRARY.tv.example.org/');

        $this->assertSame('library', $request->attributes->get('microsite')['name']);
        $this->assertSame('MICROSITE01000', $request->attributes->get('microsite_custom_tag'));
        $this->assertSame('microsites/library.css', $request->attributes->get('microsite_custom_css'));
        $this->assertSame('microsites/library/layout.html.twig', $request->attributes->get('microsite_custom_base'));
        $this->assertFalse($request->attributes->has('microsite_home_template'));
        $ids = array_map(static fn (MultimediaObject $multimediaObject): string => $multimediaObject->getId(), $this->dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder()->getQuery()->execute()->toArray());
        $this->assertSame([$tagged->getId()], array_values($ids));
    }

    public function testTheOtherHostsAreNotMicrosites(): void
    {
        $request = $this->dispatch('https://tv.example.org/');

        $this->assertFalse($request->attributes->has('microsite'));
        $this->assertFalse($this->dm->getFilterCollection()->isEnabled('microsite'));
    }

    public function testTheBackOfficeOfAMicrositeGoesToTheMainHost(): void
    {
        $event = new RequestEvent(self::$kernel, Request::create('https://library.tv.example.org/admin/series/?page=2'), HttpKernelInterface::MAIN_REQUEST);

        $this->listener()($event);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        $this->assertSame('https://tv.example.org/admin/series/?page=2', $event->getResponse()->getTargetUrl());
        $this->assertFalse($this->dm->getFilterCollection()->isEnabled('microsite'));
    }

    public function testTheTwigFunctionsGiveTheCurrentMicrosite(): void
    {
        $tag = new Tag();
        $tag->setCod('MICROSITE01000');
        $this->dm->persist($tag);
        $this->dm->flush();
        $requestStack = new RequestStack();
        $extension = new MicrositeExtension($requestStack, $this->dm);

        $requestStack->push(Request::create('https://tv.example.org/'));
        $this->assertNull($extension->microsite());
        $this->assertNull($extension->micrositeTag());

        $requestStack->pop();
        $requestStack->push($this->dispatch('https://library.tv.example.org/'));
        $this->assertSame('library', $extension->microsite()['name']);
        $this->assertSame('MICROSITE01000', $extension->micrositeTag()?->getCod());
    }

    public function testTheMicrositesNeedHostsAndATag(): void
    {
        $processor = new Processor();
        $this->assertSame([], $processor->processConfiguration(new Configuration(), [[]])['microsites']);

        $this->expectException(InvalidConfigurationException::class);
        $processor->processConfiguration(new Configuration(), [['microsites' => ['library' => ['tag' => 'MICROSITE01000']]]]);
    }

    private function dispatch(string $uri): Request
    {
        $request = Request::create($uri);
        $this->listener()(new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        return $request;
    }

    private function listener(): MicrositeListener
    {
        return new MicrositeListener(new MicrositeService(self::MICROSITES), $this->dm, 'tv.example.org');
    }
}
