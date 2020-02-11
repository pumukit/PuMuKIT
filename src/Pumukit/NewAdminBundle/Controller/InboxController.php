<?php

namespace Pumukit\NewAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_ACCESS_INBOX')")
 */
class InboxController extends AbstractController implements NewAdminControllerInterface
{
    private $pumukitInbox;
    private $pumukitInboxDepth;

    public function __construct($pumukitInbox, $pumukitInboxDepth)
    {
        $this->pumukitInbox = $pumukitInbox;
        $this->pumukitInboxDepth = $pumukitInboxDepth;
    }

    /**
     * @Route("/inbox", defaults={"_format"="json"})
     */
    public function dirAction(Request $request)
    {
        $dir = $request->query->get('dir', '');
        $type = $request->query->get('type', 'file');

        $finder = new Finder();

        $res = [];

        if ('file' === $type) {
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
                if (0 !== (count(glob("{$f}/*")))) {
                    $contentFinder = new Finder();
                    if (!$this->pumukitInboxDepth) {
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
     * @Template("@PumukitNewAdmin/Inbox/form.html.twig")
     */
    public function formAction(bool $onlyDir = false)
    {
        if (!$this->pumukitInbox) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noconf.html.twig');
        }

        $dir = realpath($this->pumukitInbox);

        if (!file_exists($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_nofile.html.twig', ['dir' => $dir]);
        }

        if (!is_readable($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noperm.html.twig', ['dir' => $dir]);
        }

        return $this->render('@PumukitNewAdmin/Inbox/form.html.twig', ['dir' => $dir, 'onlyDir' => $onlyDir]);
    }
}
