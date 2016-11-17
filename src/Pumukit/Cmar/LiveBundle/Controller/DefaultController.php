<?php

namespace Pumukit\Cmar\LiveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\LiveBundle\Controller\DefaultController as Base;
use Pumukit\LiveBundle\Document\Live;

class DefaultController extends Base
{

    public function iframeAction(Live $live)
    {
        $parameters = parent::iframeAction($live);

        $parameters['enable_chat'] = $this->container->getParameter('pumukit_cmar_live.chat.enable');

        return $parameters;
    }
}
