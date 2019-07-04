<?php

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class VersionController extends Controller implements AdminControllerInterface
{
    /**
     * @Route("/admin/versions", name="pumukit_stats_versions")
     * @Template
     */
    public function indexAction(Request $request)
    {
        $composerLockFile = realpath($this->container->getParameter('kernel.root_dir').'/../composer.lock');
        if (!$composerLockFile) {
            throw new \Exception('Error reading composer.lock');
        }

        $composerLock = json_decode(file_get_contents($composerLockFile));

        $pumukit = [];
        $other = [];
        foreach ($composerLock->packages as $package) {
            if ((false !== strpos($package->name, '/pmk2-')) || false !== strpos(strtolower($package->name), 'pumukit') || (isset($package->keywords) && in_array('pumukit', $package->keywords))) {
                $pumukit[] = $package;
            } else {
                $other[] = $package;
            }
        }

        return ['pumukitPackages' => $pumukit, 'otherPackages' => $other];
    }

    /**
     * @Route("/admin/versions/info.json")
     */
    public function infoAction(Request $request)
    {
        $composerLockFile = realpath($this->container->getParameter('kernel.root_dir').'/../composer.lock');
        if (!$composerLockFile) {
            throw new \Exception('Error reading composer.lock');
        }

        $composerLock = file_get_contents($composerLockFile);

        if (false === $composerLock) {
            return new JsonResponse(['error' => 'Error reading composer lock file'], 500);
        }

        return new Response($composerLock, 200, ['Content-Type' => 'application/json']);
    }
}
