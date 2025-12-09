<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Taxonomy\Controller;

use App\Shared\Domain\TranslatorInterface;
use App\ContentManagement\Taxonomy\Application\ListTags\ListTagsRequest;
use App\ContentManagement\Taxonomy\Application\ListTags\ListTagsService;
use App\ContentManagement\Taxonomy\Application\UpdateTag\UpdateTagRequest;
use App\ContentManagement\Taxonomy\Application\UpdateTag\UpdateTagService;
use App\ContentManagement\Taxonomy\Application\ViewTag\ViewTagRequest;
use App\ContentManagement\Taxonomy\Application\ViewTag\ViewTagService;
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
                // Parse JSON fields
                $title = json_decode($request->request->get('title', '{}'), true) ?: [];
                $description = json_decode($request->request->get('description', '{}'), true) ?: [];
                $properties = json_decode($request->request->get('properties', '{}'), true) ?: [];

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

        // Get all tags for parent selection
        $listTagsRequest = new ListTagsRequest(onlyRoots: false);
        $tagsResponse = ($this->listTagsService)($listTagsRequest);

        return $this->render('@Taxonomy/Views/update.html.twig', [
            'tag' => $tag,
            'tags' => $tagsResponse->tags,
        ]);
    }
}
