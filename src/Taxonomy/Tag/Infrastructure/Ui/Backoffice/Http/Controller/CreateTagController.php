<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Taxonomy\Tag\Application\Create\CreateTagRequest;
use App\Taxonomy\Tag\Application\Create\CreateTagService;
use App\Taxonomy\Tag\Application\List\ListTagsRequest;
use App\Taxonomy\Tag\Application\List\ListTagsService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateTagController extends AbstractController
{
    public function __construct(
        private CreateTagService $createTagService,
        private ListTagsService $listTagsService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $title = json_decode((string) $request->request->get('title', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $description = json_decode((string) $request->request->get('description', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];
                $properties = json_decode((string) $request->request->get('properties', '{}'), true, 512, JSON_THROW_ON_ERROR) ?: [];

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

                $this->addFlash('success', $this->translator->trans(
                    'taxonomy.flash.created',
                    ['%cod%' => $request->request->get('cod')],
                    'taxonomy'
                ));

                return $this->redirectToRoute('taxonomy_tag_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans(
                    'taxonomy.error.create_failed',
                    ['%message%' => $e->getMessage()],
                    'taxonomy'
                ));
            }
        }

        $listTagsRequest = new ListTagsRequest(onlyRoots: false);
        $tagsResponse = ($this->listTagsService)($listTagsRequest);

        return $this->render('@Tag/Views/create.html.twig', [
            'tags' => $tagsResponse->tags,
        ]);
    }
}
