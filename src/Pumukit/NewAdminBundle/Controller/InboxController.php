<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("is_granted('ROLE_ACCESS_INBOX')")
 */
class InboxController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Route("/inbox", defaults={"_format"="json"})
     */
    public function dirAction(Request $request)
    {
        $dir = $request->query->get('dir', '');
        $type = $request->query->get('type', 'file');

        /*
          if(0 !== strpos($dir, $baseDir)) {
          throw $this->createAccessDeniedException();
          }
        */

        $finder = new Finder();

        $res = [];

        if ('file' == $type) {
            $finder->depth('< 1')->followLinks()->in($dir);
            $finder->sortByName();
            foreach ($finder as $f) {
                $res[] = ['path' => $f->getRealpath(),
                               'relativepath' => $f->getRelativePathname(),
                               'is_file' => $f->isFile(),
                               'hash' => hash('md5', $f->getRealpath()),
                               'content' => false, ];
            }
        } else {
            $finder->depth('< 1')->directories()->followLinks()->in($dir);
            $finder->sortByName();
            foreach ($finder as $f) {
                if (0 !== (count(glob("$f/*")))) {
                    $contentFinder = new Finder();
                    if (!$this->getParameter('pumukit.inbox_depth')) {
                        $contentFinder->depth('== 0');
                    }
                    $contentFinder->files()->in($f->getRealpath());
                    $res[] = ['path' => $f->getRealpath(),
                                   'relativepath' => $f->getRelativePathname(),
                                   'is_file' => $f->isFile(),
                                   'hash' => hash('md5', $f->getRealpath()),
                                   'content' => $contentFinder->count(), ];
                }
            }
        }

        return new JsonResponse($res);
    }

    /**
     * @Template
     */
    public function formAction($onlyDir = false)
    {
        if (!$this->container->hasParameter('pumukit.inbox')) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noconf.html.twig');
        }

        $dir = realpath($this->container->getParameter('pumukit.inbox'));

        if (!file_exists($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_nofile.html.twig', ['dir' => $dir]);
        }

        if (!is_readable($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noperm.html.twig', ['dir' => $dir]);
        }

        return $this->render('@PumukitNewAdmin/Inbox/form.html.twig', ['dir' => $dir, 'onlyDir' => $onlyDir]);
    }
}
