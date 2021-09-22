<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Tus\Server;

class TUSUploadController extends AbstractController
{
    /**
     * @Route("/tus", name="tus_post")
     * @Route("/tus/{token}", name="tus_post_token", requirements={"token"=".+"})
     * @Route("/files/{token}", name="tus_files", requirements={"token"=".+"})
     */
    public function server(Server $server)
    {
        return $server->serve();
    }
}
