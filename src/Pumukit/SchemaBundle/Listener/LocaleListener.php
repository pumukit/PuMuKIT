<?php

namespace Pumukit\SchemaBundle\Listener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Init the locale of the i18n Documents when loaded.
 * Use default locale in console commands and current request locale in web request.
 */
class LocaleListener implements EventSubscriberInterface
{
    private $requestStack;
    private $defaultLocale;

    public function __construct(RequestStack $requestStack, $defaultLocale = 'en')
    {
        $this->requestStack = $requestStack;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
      if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
          return;
      }

      $request = $event->getRequest();

      // try to see if the locale has been set as a _locale routing parameter
      if ($locale = $request->attributes->get('_locale')) {
          $request->getSession()->set('_locale', $locale);
      } else {
          $sessionLocale = $request->getSession()->get('_locale');
          if(!$sessionLocale) {
              $request->getSession()->set('_locale', $this->defaultLocale);
          }
          
          $request->setLocale($request->getSession()->get('_locale'));
      }
    }

    public static function getSubscribedEvents()
    {
      return array(
                   // must be registered before the default Locale listener
                   KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
                   );
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
