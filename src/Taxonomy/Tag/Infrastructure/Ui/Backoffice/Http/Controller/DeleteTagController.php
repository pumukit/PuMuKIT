<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Taxonomy\Tag\Application\Delete\DeleteTagRequest;
use App\Taxonomy\Tag\Application\Delete\DeleteTagService;
use App\Taxonomy\Tag\Application\View\ViewTagRequest;
use App\Taxonomy\Tag\Application\View\ViewTagService;
use App\Shared\Domain\TranslatorInterface;
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
            $cod = $tagResponse->tag->getCod();

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
