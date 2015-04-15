<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BroadcastAdminController extends AdminController
{
    /**
     * Change the default broadcast type
     */
    public function defaultAction(Request $request)
    {
        $config = $this->getConfiguration();
        $repository = $this->getRepository();

        $true_resource = $this->findOr404($request);
        $resources = $this->resourceResolver->getResource($repository, 'findAll');

        foreach ($resources as $resource) {
            if (0 !== strcmp($resource->getId(), $true_resource->getId())) {
                $resource->setDefaultSel(false);
            } else {
                $resource->setDefaultSel(true);
            }
            $this->domainManager->update($resource);
        }

        $this->addFlash('success', 'default');

        return new JsonResponse(array('default' => $resource->getId()));
    }
}
