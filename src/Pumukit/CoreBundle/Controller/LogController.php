<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LogController extends Controller implements AdminController
{
    /**
     * @param $file
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
        $pathFile = @file_get_contents($pathFile);

        if (false === $pathFile) {
            return new JsonResponse(array('error' => 'Error reading log file'.$pathFile), 500);
        }

        return new Response($pathFile, 200, array('Content-Type' => 'application/json'));
    }
}
