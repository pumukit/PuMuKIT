<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\Presenter;

use Pumukit\SchemaBundle\Document\Person;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class PersonDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
    ) {}

    public function present(Person $person): array
    {
        return [
            'id' => $person->getId(),
            'name' => $person->getName(),
            'email' => $person->getEmail(),
            'phone' => $person->getPhone(),
            'web' => $person->getWeb(),
            'honorific' => $person->getHonorific('en'),
            'firm' => $person->getFirm('en'),
            'post' => $person->getPost('en'),
            'actions' => $this->renderActions($person),
        ];
    }

    private function renderActions(Person $person): string
    {
        return $this->twig->render('@Shared/Views/components/table/_datatable_actions.html.twig', [
            'actions' => [
                [
                    'type' => 'link',
                    'url' => $this->router->generate('person_view', ['id' => $person->getId()]),
                    'style' => 'info',
                    'icon' => 'eye',
                    'title' => 'View',
                ],
                [
                    'type' => 'link',
                    'url' => $this->router->generate('person_update', ['id' => $person->getId()]),
                    'style' => 'warning',
                    'icon' => 'edit',
                    'title' => 'Edit',
                ],
                [
                    'type' => 'form',
                    'url' => $this->router->generate('person_delete', ['id' => $person->getId()]),
                    'style' => 'danger',
                    'icon' => 'trash',
                    'title' => 'Delete',
                    'confirm' => 'Are you sure you want to remove this person?',
                ],
            ],
        ]);
    }
}
