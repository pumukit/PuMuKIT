<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Tus\Server;

class TUSUploadController extends AbstractController
{
    /**
     * @Route("/tus", name="tus_post")
     * @Route("/tus/{token}", name="tus_post_token", requirements={"token"=".+"})
     * @Route("/files/{token}", name="tus_files", requirements={"token"=".+"})
     */
    public function server(Request $request, Server $server)
    {
        if ('tus_post' === $request->attributes->get('_route') && !empty($request->get('folder_path'))) {
            $server->setUploadDir($request->get('folder_path'));
        }

        return $server->serve();
    }
}
