<?php

namespace Pumukit\CoreBundle\Controller;

use Pumukit\CoreBundle\Services\InboxService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Tus\Server;

class TUSUploadController extends AbstractController
{
    private $inboxService;

    public function __construct(InboxService $inboxService)
    {
        $this->inboxService = $inboxService;
    }

    /**
     * @Route("/tus", name="tus_post")
     * @Route("/tus/{token}", name="tus_post_token", requirements={"token"=".+"})
     * @Route("/files/{token}", name="tus_files", requirements={"token"=".+"})
     */
    public function server(Request $request, Server $server)
    {
        if ('tus_post' === $request->attributes->get('_route') && !empty($request->get('series'))) {
            $path = $this->inboxService->inboxPath().'/'.$request->get('series');
            $server->setUploadDir($path);
        }

        return $server->serve();
    }
}
