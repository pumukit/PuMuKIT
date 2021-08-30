<?php

namespace Pumukit\CoreBundle\Controller;

use TusPhp\Tus\Server;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TUSUploadController extends AbstractController
{
    /**
     * @Route("/tus/", name="tus_post")
     * @Route("/tus/{token?}", name="tus_post_token", requirements={"token"=".+"})
     * @Route("/files/{token?}", name="tus_files", requirements={"token"=".+"})
     */
    public function server(Server $server)
    {
        return $server->serve();
    }
}
