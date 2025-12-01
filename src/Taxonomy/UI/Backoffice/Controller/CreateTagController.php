<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\Controller;

use App\Taxonomy\Application\CreateTag\CreateTagRequest;
use App\Taxonomy\Application\CreateTag\CreateTagService;
use App\Taxonomy\Application\ListTags\ListTagsRequest;
use App\Taxonomy\Application\ListTags\ListTagsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateTagController extends AbstractController
{
    public function __construct(
        private CreateTagService $createTagService,
        private ListTagsService $listTagsService
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                // Parse JSON fields
                $title = json_decode($request->request->get('title', '{}'), true) ?: [];
                $description = json_decode($request->request->get('description', '{}'), true) ?: [];
                $properties = json_decode($request->request->get('properties', '{}'), true) ?: [];

                $createTagRequest = new CreateTagRequest(
                    cod: $request->request->get('cod'),
                    title: $title,
                    description: $description,
                    slug: $request->request->get('slug') ?: null,
                    metatag: $request->request->getBoolean('metatag'),
                    display: $request->request->getBoolean('display'),
                    parentId: $request->request->get('parent') ?: null,
                    properties: $properties
                );

                ($this->createTagService)($createTagRequest);

                $this->addFlash('success', sprintf('Tag "%s" created successfully.', $request->request->get('cod')));

                return $this->redirectToRoute('taxonomy_tag_index');
            } catch (\Exception $e) {
                $this->addFlash('error', sprintf('Error creating tag: %s', $e->getMessage()));
            }
        }

        // Get all tags for parent selection
        $listTagsRequest = new ListTagsRequest(onlyRoots: false);
        $tagsResponse = ($this->listTagsService)($listTagsRequest);

        return $this->render('@Taxonomy/UI/Backoffice/Pages/create.html.twig', [
            'tags' => $tagsResponse->tags,
        ]);
    }
}

