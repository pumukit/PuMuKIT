<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Taxonomy\Controller;

use App\Shared\Domain\TranslatorInterface;
use App\ContentManagement\Taxonomy\Application\DeleteTag\DeleteTagRequest;
use App\ContentManagement\Taxonomy\Application\DeleteTag\DeleteTagService;
use App\ContentManagement\Taxonomy\Application\ViewTag\ViewTagRequest;
use App\ContentManagement\Taxonomy\Application\ViewTag\ViewTagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DeleteTagController extends AbstractController
{
    public function __construct(
        private ViewTagService $viewTagService,
        private DeleteTagService $deleteTagService,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(string $id): Response
    {
        try {
            $viewTagRequest = new ViewTagRequest($id);
            $tagResponse = ($this->viewTagService)($viewTagRequest);
            $cod = $tagResponse->tag->cod();

            $deleteTagRequest = new DeleteTagRequest($id);
            ($this->deleteTagService)($deleteTagRequest);

            $this->addFlash('success', $this->translator->trans(
                'taxonomy.flash.deleted',
                ['%cod%' => $cod],
                'taxonomy'
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'taxonomy.error.delete_failed',
                ['%message%' => $e->getMessage()],
                'taxonomy'
            ));
        }

        return $this->redirectToRoute('taxonomy_tag_index');
    }
}
