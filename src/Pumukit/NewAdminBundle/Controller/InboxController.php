<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class InboxController extends Controller
{
    /**
     * @Route("/inbox", defaults={"_format"="json"})
     */
    public function dirAction(Request $request)
    {
        $dir = $request->get("dir", "");
        $baseDir = $this->container->getParameter('pumukit2.inbox');

        if(0 !== strpos($dir, $baseDir)) {
            throw $this->createAccessDeniedException();
        }

        $finder = new Finder();
        $finder->files()->in($dir);

        $res = array();
        foreach ($finder as $f) {
            $res[] = array('path' => $f->getRealpath(),
                           'relativepath' => $f->getRelativePathname(),
                           'is_file' => $f->isFile());
        }

        return new JsonResponse($res);
    }
}