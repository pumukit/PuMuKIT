<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Tus\Server;

class TUSUploadController extends AbstractController
{
    private $kernelProjectDir;

    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @Route("/tus/", name="tus_post")
     * @Route("/tus/{token}", name="tus_post_token", requirements={"token"=".+"})
     * @Route("/files/{token}", name="tus_files", requirements={"token"=".+"})
     */
    public function server(Server $server)
    {
        $server->setUploadDir($this->kernelProjectDir.'/web/storage/inbox');
        $server->setApiPath('/files');

        return $server->serve();
    }
}
