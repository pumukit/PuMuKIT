<?php

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
    private $pumukitLocales;

    public function __construct(RequestStack $requestStack, $defaultLocale = 'en', $pumukitLocales = [])
    {
        $this->requestStack = $requestStack;
        $this->defaultLocale = $defaultLocale;
        $this->pumukitLocales = $pumukitLocales;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->fixRequestLocale($event->getRequest());
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     */
    public function fixRequestLocale(Request $request)
    {
        $requestLocale = $request->attributes->get('_locale');

        if (null === $request->getSession()) {
            return;
        }

        $sessionLocale = $request->getSession()->get('_locale');

        // try to see if the locale has been set as a _locale routing parameter
        if ($requestLocale && in_array($requestLocale, $this->pumukitLocales)) {
            $request->getSession()->set('_locale', $requestLocale);
        } else {
            if (!$sessionLocale || !in_array($sessionLocale, $this->pumukitLocales)) {
                $validLocales = array_intersect($request->getLanguages(), $this->pumukitLocales);
                if ($validLocales) {
                    $request->getSession()->set('_locale', current($validLocales));
                } elseif (in_array($this->defaultLocale, $this->pumukitLocales)) {
                    $request->getSession()->set('_locale', $this->defaultLocale);
                } elseif (!empty($this->pumukitLocales)) {
                    $request->getSession()->set('_locale', $this->pumukitLocales[0]);
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

    /**
     * @param LifecycleEventArgs $args
     */
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
