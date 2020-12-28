<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Init the locale of the i18n Documents when loaded.
 * Use default locale in console commands and current request locale in web request.
 */
class LocaleListener implements EventSubscriberInterface
{
    private $requestStack;
    private $defaultLocale;
    private $locales;

    public function __construct(RequestStack $requestStack, array $locales, $defaultLocale = 'en')
    {
        $this->requestStack = $requestStack;
        $this->defaultLocale = $defaultLocale;
        $this->locales = $locales;
    }

    /**
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->fixRequestLocale($event->getRequest());
    }

    /**
     * @throws \Exception
     */
    public function fixRequestLocale(Request $request)
    {
        $requestLocale = $request->attributes->get('_locale');
        $sessionLocale = $request->getSession()->get('_locale');

        // try to see if the locale has been set as a _locale routing parameter
        if ($requestLocale && in_array($requestLocale, $this->locales)) {
            $request->getSession()->set('_locale', $requestLocale);
        } else {
            if (!$sessionLocale || !in_array($sessionLocale, $this->locales)) {
                $validLocales = array_intersect($request->getLanguages(), $this->locales);
                if ($validLocales) {
                    $request->getSession()->set('_locale', current($validLocales));
                } elseif (in_array($this->defaultLocale, $this->locales)) {
                    $request->getSession()->set('_locale', $this->defaultLocale);
                } elseif (!empty($this->locales)) {
                    $request->getSession()->set('_locale', $this->locales[0]);
                } else {
                    throw new \Exception('Pumukit.Locales is empty. You should define it in your parameters.');
                }
            }

            $request->setLocale($request->getSession()->get('_locale'));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 17]],
        ];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $document = $args->getDocument();

        if (method_exists($document, 'setLocale')) {
            if ($request = $this->requestStack->getCurrentRequest()) {
                $document->setLocale($request->getLocale());
            } else {
                $document->setLocale($this->defaultLocale);
            }
        }
    }
}
