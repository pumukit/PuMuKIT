<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\HttpFoundation\Request;

class SortableAdminController extends AdminController
{
    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
    }

    public function upAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $new_rank = $resource->getRank() + 1;
        $resource->setRank($new_rank);
        $this->update($resource);

        return $this->redirectToIndex();
    }

    public function downAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $new_rank = $resource->getRank() - 1;
        $resource->setRank($new_rank);
        $this->update($resource);

        return $this->redirectToIndex();
    }

    public function topAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $new_rank = -1;
        $resource->setRank($new_rank);
        $this->update($resource);

        return $this->redirectToIndex();
    }

    public function bottomAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $new_rank = 0;
        $resource->setRank($new_rank);
        $this->update($resource);

        return $this->redirectToIndex();
    }
}
