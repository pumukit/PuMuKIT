<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\MicrositeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(priority: 30)]
final class MicrositeListener
{
    public function __construct(
        private readonly MicrositeService $micrositeService,
        private readonly DocumentManager $documentManager,
        private readonly string $mainHost = '',
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $microsite = $this->micrositeService->byHost($request->getHost());
        if (null === $microsite) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/admin') && '' !== $this->mainHost && 0 !== strcasecmp($request->getHost(), $this->mainHost)) {
            $event->setResponse(new RedirectResponse($request->getScheme().'://'.$this->mainHost.$request->getRequestUri()));

            return;
        }

        $this->documentManager->getFilterCollection()->enable('microsite')->setParameter('microsite_tag', $microsite['tag']);

        $request->attributes->set(MicrositeService::ATTRIBUTE, $microsite);
        $request->attributes->set('microsite_custom_tag', $microsite['tag']);
        foreach (['css' => 'microsite_custom_css', 'layout' => 'microsite_custom_base', 'home_template' => 'microsite_home_template'] as $option => $attribute) {
            if (null !== $microsite[$option]) {
                $request->attributes->set($attribute, $microsite[$option]);
            }
        }
    }
}
