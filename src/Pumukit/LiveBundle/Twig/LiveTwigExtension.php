<?php

namespace Pumukit\LiveBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;

class LiveTwigExtension extends \Twig_Extension
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function getName()
    {
        return 'pumukit_live_twig_extension';
    }

    public function getFunctions()
    {
        return array(
                     new \Twig_SimpleFunction('future_and_not_finished_event', array($this, 'getFutureAndNotFinishedEvent')),
                     );
    }

    /**
     * Get future and not finished event
     *
     * @param integer $limit
     * @return Event $event
     */
    public function getFutureAndNotFinishedEvent($limit = null)
    {
        $eventRepo = $this->dm->getRepository('PumukitLiveBundle:Event');

        return $eventRepo->findFutureAndNotFinished($limit?$limit:null);
    }
}
