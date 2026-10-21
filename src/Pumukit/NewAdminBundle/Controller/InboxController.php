<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function dirAction(Request $request): JsonResponse
    {
        $relativeDir = $request->query->get('dir', '');
        $type = $request->query->get('type', 'file');

        $inboxBasePath = realpath($this->pumukitInbox);
        if (!$inboxBasePath) {
            return new JsonResponse([]);
        }

        // Resolve the absolute path and validate it stays within the inbox
        $absoluteDir = '' !== $relativeDir
            ? realpath($inboxBasePath.DIRECTORY_SEPARATOR.$relativeDir)
            : $inboxBasePath;

        if (!$absoluteDir || !str_starts_with($absoluteDir.DIRECTORY_SEPARATOR, $inboxBasePath.DIRECTORY_SEPARATOR)) {
            return new JsonResponse([], 403);
        }

        $finder = new Finder();

        $res = [];

        if ('file' === $type || 'both' === $type) {
            $finder->depth('< 1')->followLinks()->in($absoluteDir);
            $finder->sortByName();

            foreach ($finder as $f) {
                $content = false;

                if ($f->isDir()) {
                    if (0 === (is_countable(glob("{$f}/*")) ? count(glob("{$f}/*")) : 0)) {
                        continue;
                    }

                    $contentFinder = new Finder();
                    if (!$this->pumukitInboxDepth) {
                        $contentFinder->depth('== 0');
                    }
                    $contentFinder->files()->in($f->getRealpath());
                    $content = $contentFinder->count();
                }

                $res[] = [
                    'path' => $this->toRelativePath($f->getRealpath(), $inboxBasePath),
                    'relativepath' => $f->getRelativePathname(),
                    'is_file' => $f->isFile(),
                    'hash' => hash('md5', $f->getRealpath()),
                    'content' => $content,
                ];
            }
        } else {
            $finder->depth('< 1')->directories()->followLinks()->in($absoluteDir);
            $finder->sortByName();
            foreach ($finder as $f) {
                if (0 !== (is_countable(glob("{$f}/*")) ? count(glob("{$f}/*")) : 0)) {
                    $contentFinder = new Finder();
                    if (!$this->pumukitInboxDepth) {
                        $contentFinder->depth('== 0');
                    }
                    $contentFinder->files()->in($f->getRealpath());
                    $res[] = ['path' => $this->toRelativePath($f->getRealpath(), $inboxBasePath),
                        'relativepath' => $f->getRelativePathname(),
                        'is_file' => $f->isFile(),
                        'hash' => hash('md5', $f->getRealpath()),
                        'content' => $contentFinder->count(), ];
                }
            }
        }

        return new JsonResponse($res);
    }

    public function formAction(Series $series, string $selection = 'file'): Response
    {
        if (!$this->pumukitInbox) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noconf.html.twig');
        }

        $dir = realpath($this->pumukitInbox);

        if (!file_exists($dir ?: $this->pumukitInbox)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_nofile.html.twig', ['dir' => basename($this->pumukitInbox), 'series' => $series]);
        }

        if (!is_readable($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noperm.html.twig', ['dir' => basename($this->pumukitInbox), 'series' => $series]);
        }

        return $this->render('@PumukitNewAdmin/Inbox/form.html.twig', [
            'dir' => '',
            'displayDir' => basename($this->pumukitInbox),
            'selection' => $selection,
            'series' => $series,
        ]);
    }

    private function toRelativePath(string $absolutePath, string $basePath): string
    {
        return ltrim(substr($absolutePath, strlen($basePath)), DIRECTORY_SEPARATOR);
    }
}
