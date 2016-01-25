<?php

namespace Pumukit\VideoEditorBundle\EventListener;

use Pumukit\SchemaBundle\Event\AnnotationsAPIEvents;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class AnnotationsAPIListener implements EventSubscriberInterface
{
    public function __construct()
    {
        
    }

    public static function getSubscribedEvents()
    {
        return array(
            AnnotationsAPIEvents::API_UPDATE => array(
                array('onAnnotationsAPIUpdate', 0),
            )
        );
    }

    public function onAnnotationsAPIUpdate(GetResponseEvent $event) {
        echo "CACA";
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
