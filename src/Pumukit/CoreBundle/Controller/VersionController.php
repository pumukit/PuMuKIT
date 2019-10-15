<?php

namespace Pumukit\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VersionController extends AbstractController implements AdminControllerInterface
{
    /**
     * @Route("/admin/versions", name="pumukit_stats_versions")
     * @Template("PumukitCoreBundle:Version:index.html.twig")
     *
     * @throws \Exception
     */
    public function indexAction(): array
    {
        $composerLockFile = $this->getComposerLockPath();

        $composerLock = $this->getContentFromFile($composerLockFile);

        $pumukit = [];
        $other = [];
        foreach ($composerLock->packages as $package) {
            if (false !== strpos(strtolower($package->name), 'pumukit') || (isset($package->keywords) && in_array('pumukit', $package->keywords))) {
                $pumukit[] = $package;
            } else {
                $other[] = $package;
            }
        }

        return [
            'pumukitPackages' => $pumukit,
            'otherPackages' => $other,
        ];
    }

    /**
     * @Route("/admin/versions/info.json")
     *
     * @throws \Exception
     */
    public function infoAction(): Response
    {
        $composerLockFile = $this->getComposerLockPath();

        $composerLock = $this->getContentFromFile($composerLockFile);

        return new Response($composerLock, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @throws \Exception
     */
    private function getComposerLockPath(): string
    {
        $composerLockFile = realpath($this->container->getParameter('kernel.root_dir').'/../composer.lock');
        if (!$composerLockFile) {
            throw new \Exception('Error reading composer.lock');
        }

        return $composerLockFile;
    }

    private function getContentFromFile(string $composerLockFile)
    {
        $composerLock = file_get_contents($composerLockFile);
        if (false === $composerLock) {
            return new JsonResponse(['error' => 'Error reading composer lock file'], 500);
        }

        return json_decode($composerLock);
    }
}
