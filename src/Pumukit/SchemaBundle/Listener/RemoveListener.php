<?php

namespace Pumukit\SchemaBundle\Listener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;


class RemoveListener
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $document = $args->getDocument();

        if ($document instanceof MultimediaObject) {
            $service = $this->container->get("pumukitschema.tag");
            foreach($document->getTags() as $tag) {
                $service->removeTagFromMultimediaObject($document, $tag->getId());
            }
        }
    }
}
