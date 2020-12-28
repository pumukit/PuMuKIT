<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class SortableAdminController extends AdminController
{
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
