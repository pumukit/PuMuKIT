<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\Controller;

use App\Taxonomy\Application\DeleteTag\DeleteTagRequest;
use App\Taxonomy\Application\DeleteTag\DeleteTagService;
use App\Taxonomy\Application\ViewTag\ViewTagRequest;
use App\Taxonomy\Application\ViewTag\ViewTagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteTagController extends AbstractController
{
    public function __construct(
        private ViewTagService $viewTagService,
        private DeleteTagService $deleteTagService
    ) {}

    public function __invoke(string $id): Response
    {
        try {
            $viewTagRequest = new ViewTagRequest($id);
            $tagResponse = ($this->viewTagService)($viewTagRequest);
            $cod = $tagResponse->tag->cod();

            $deleteTagRequest = new DeleteTagRequest($id);
            ($this->deleteTagService)($deleteTagRequest);

            $this->addFlash('success', sprintf('Tag "%s" deleted successfully.', $cod));
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Error deleting tag: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('taxonomy_tag_index');
    }
}

