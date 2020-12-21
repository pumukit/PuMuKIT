<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Services\OwnerService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OwnerController extends Controller implements NewAdminControllerInterface
{
    protected const CO_OWNER_ERROR_TEMPLATE = 'PumukitNewAdminBundle:MultimediaObject/Owner:error.html.twig';

    /**
     * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     * @Route("/reject/{id}/owner/{owner}/coowner/{coOwner}", name="pumukit_multimedia_object_reject_co_owner")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id"="id"})
     * @ParamConverter("owner", class="PumukitSchemaBundle:User", options={"id"="owner"})
     * @ParamConverter("coOwner", class="PumukitSchemaBundle:User", options={"id"="coOwner"})
     * @Template("PumukitNewAdminBundle:MultimediaObject:Owner/reject.html.twig")
     */
    public function rejectCoOwnerAction(MultimediaObject $multimediaObject, User $owner, User $coOwner)
    {
        $translator = $this->get('translator');
        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        $ownerService = $this->get('pumukitnewadmin.owner');
        $user = $this->getUser();
        $errorMessage = '';

        if (!$user) {
            $errorMessage = $translator->trans("You're not allowed to access this link. You're not the holder user.");
        }

        if ($this->wasUsedRejectedLink($multimediaObject, $coOwner)) {
            $errorMessage = $translator->trans('This link was already used by co-owner.');
        }

        if (!$multimediaObjectService->isUserOwner($user, $multimediaObject)) {
            $errorMessage = $translator->trans("You're not allowed to access this link. You're not the owner user of this multimedia object.");
        }

        if (!$user || $this->wasUsedRejectedLink($multimediaObject, $coOwner) || !$multimediaObjectService->isUserOwner($user, $multimediaObject)) {
            return $this->renderErrorTemplate($errorMessage);
        }

        $multimediaObject = $ownerService->rejectCoOwnerFromMultimediaObject($multimediaObject, $user, $coOwner);

        return [
            'multimedia_object_id' => $multimediaObject->getId(),
            'multimedia_object_title' => $multimediaObject->getTitle(),
            'series_title' => $multimediaObject->getSeries()->getTitle(),
        ];
    }

    protected function renderErrorTemplate(string $message = ''): Response
    {
        $templating = $this->get('templating');
        $renderedView = $templating->render(self::CO_OWNER_ERROR_TEMPLATE, ['message' => $message]);

        return new Response($renderedView, Response::HTTP_FORBIDDEN);
    }

    protected function wasUsedRejectedLink(MultimediaObject $multimediaObject, User $coOwner): bool
    {
        $rejectedCoOwners = $multimediaObject->getProperty(OwnerService::MULTIMEDIA_OBJECT_CO_OWNERS_PROPERTY);

        return is_array($rejectedCoOwners) && in_array($coOwner->getId(), $rejectedCoOwners);
    }
}
