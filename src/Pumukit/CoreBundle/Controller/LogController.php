<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController implements AdminControllerInterface
{
    /**
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     * @Route("/admin/show/log/{file}")
     */
    public function showLogAction(?string $file, string $kernelEnvironment, string $kernelProjectDir)
    {
        if (!$file) {
            $sFile = $kernelEnvironment.'.log';
        } else {
            $sFile = $file.'_'.$kernelEnvironment.'.log';
        }

        $pathFile = realpath($kernelProjectDir.'/var/log/'.$sFile);

        if (false === $pathFile) {
            return new JsonResponse(['error' => 'Error reading log file'.$pathFile], Response::HTTP_NOT_FOUND);
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
