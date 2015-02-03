<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class SortableAdminController extends AdminController
{
    public function upAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);

        $new_rank = $resource->getRank() + 1;
        $resource->setRank($new_rank);
        $this->domainManager->update($resource);

        $this->addFlash('success', 'up');

        return $this->redirectToRoute(
            $config->getRedirectRoute('index'),
            $config->getRedirectParameters()
        );
    }

    public function downAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);

        $new_rank = $resource->getRank() - 1;
        $resource->setRank($new_rank);
        $this->domainManager->update($resource);

        $this->addFlash('success', 'up');

        return $this->redirectToRoute(
            $config->getRedirectRoute('index'),
            $config->getRedirectParameters()
        );
    }

    public function topAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);

        $new_rank = -1;
        $resource->setRank($new_rank);
        $this->domainManager->update($resource);

        $this->addFlash('success', 'up');

        return $this->redirectToRoute(
            $config->getRedirectRoute('index'),
            $config->getRedirectParameters()
        );
    }

    public function bottomAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);

        $new_rank = 0;
        $resource->setRank($new_rank);
        $this->domainManager->update($resource);

        $this->addFlash('success', 'up');

        return $this->redirectToRoute(
            $config->getRedirectRoute('index'),
            $config->getRedirectParameters()
        );
    }
}
