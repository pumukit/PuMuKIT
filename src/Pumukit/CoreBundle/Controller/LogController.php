<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class LogController extends Controller implements AdminControllerInterface
{
    /**
     * @param string $file
     *
     * @return JsonResponse|Response
     *
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     * @Route("/admin/show/log/{file}")
     */
    public function showLogAction($file = '')
    {
        $env = $this->container->getParameter('kernel.environment');

        if (!$file) {
            $sFile = $env.'.log';
        } else {
            $sFile = $file.'_'.$env.'.log';
        }

        $pathFile = realpath($this->container->getParameter('kernel.root_dir').'/../app/logs/'.$sFile);

        if (false === $pathFile) {
            return new JsonResponse(['error' => 'Error reading log file'.$pathFile], 500);
        }

        $response = new BinaryFileResponse($pathFile);
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $sFile
        );

        return $response;
    }
}
