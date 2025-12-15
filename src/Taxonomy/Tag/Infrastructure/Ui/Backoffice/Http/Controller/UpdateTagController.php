<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Shared\Domain\TranslatorInterface;
use App\Taxonomy\Tag\Application\List\ListTagsRequest;
use App\Taxonomy\Tag\Application\List\ListTagsService;
use App\Taxonomy\Tag\Application\Update\UpdateTagRequest;
use App\Taxonomy\Tag\Application\Update\UpdateTagService;
use App\Taxonomy\Tag\Application\View\ViewTagRequest;
use App\Taxonomy\Tag\Application\View\ViewTagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateTagController extends AbstractController
{
    public function __construct(
        private ViewTagService $viewTagService,
        private UpdateTagService $updateTagService,
        private ListTagsService $listTagsService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(string $id, Request $request): Response
    {
        $viewTagRequest = new ViewTagRequest($id);
        $tagResponse = ($this->viewTagService)($viewTagRequest);
        $tag = $tagResponse->tag;

        if ($request->isMethod('POST')) {
            try {
                $title = json_decode((string) $request->request->get('title', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $description = json_decode((string) $request->request->get('description', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $properties = json_decode((string) $request->request->get('properties', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];

                $updateTagRequest = new UpdateTagRequest(
                    id: $id,
                    title: $title,
                    description: $description,
                    slug: $request->request->get('slug') ?: null,
                    metatag: $request->request->getBoolean('metatag'),
                    display: $request->request->getBoolean('display'),
                    properties: $properties
                );

                ($this->updateTagService)($updateTagRequest);

                $this->addFlash('success', $this->translator->trans(
                    'taxonomy.flash.updated',
                    ['%cod%' => $tag->getCod()],
                    'taxonomy'
                ));

                return $this->redirectToRoute('taxonomy_tag_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans(
                    'taxonomy.error.update_failed',
                    ['%message%' => $e->getMessage()],
                    'taxonomy'
                ));
            }
        }

        $listTagsRequest = new ListTagsRequest(onlyRoots: false);
        $tagsResponse = ($this->listTagsService)($listTagsRequest);

        return $this->render('@Tag/Views/update.html.twig', [
            'tag' => $tag,
            'tags' => $tagsResponse->tags,
        ]);
    }
}
