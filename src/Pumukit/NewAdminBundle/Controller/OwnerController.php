<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Services\OwnerService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TemplatingEngine;

class OwnerController extends AbstractController implements NewAdminControllerInterface
{
    protected const CO_OWNER_ERROR_TEMPLATE = '@PumukitNewAdmin/MultimediaObject/Owner/error.html.twig';
    private $translator;
    private $multimediaObjectService;
    private $ownerService;
    private $templating;

    public function __construct(
        TranslatorInterface $translator,
        MultimediaObjectService $multimediaObjectService,
        OwnerService $ownerService,
        TemplatingEngine $templating
    ) {
        $this->translator = $translator;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->ownerService = $ownerService;
        $this->templating = $templating;
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     * @Route("/reject/{id}/owner/{owner}/coowner/{coOwner}", name="pumukit_multimedia_object_reject_co_owner")
     * @ParamConverter("multimediaObject", options={"id"="id"})
     * @ParamConverter("owner", options={"id"="owner"})
     * @ParamConverter("coOwner", options={"id"="coOwner"})
     * @Template("@PumukitNewAdmin/MultimediaObject/Owner/reject.html.twig")
     */
    public function rejectCoOwnerAction(MultimediaObject $multimediaObject, User $owner, User $coOwner)
    {
        $user = $this->getUser();
        $errorMessage = '';

        if (!$user) {
            $errorMessage = $this->translator->trans("You're not allowed to access this link. You're not the holder user.");
        }

        if ($this->wasUsedRejectedLink($multimediaObject, $coOwner)) {
            $errorMessage = $this->translator->trans('This link was already used by co-owner.');
        }

        if (!$this->multimediaObjectService->isUserOwner($user, $multimediaObject)) {
            $errorMessage = $this->translator->trans("You're not allowed to access this link. You're not the owner user of this multimedia object.");
        }

        if (!$user || $this->wasUsedRejectedLink($multimediaObject, $coOwner) || !$this->multimediaObjectService->isUserOwner($user, $multimediaObject)) {
            return $this->renderErrorTemplate($errorMessage);
        }

        $multimediaObject = $this->ownerService->rejectCoOwnerFromMultimediaObject($multimediaObject, $user, $coOwner);

        return [
            'multimedia_object_id' => $multimediaObject->getId(),
            'multimedia_object_title' => $multimediaObject->getTitle(),
            'series_title' => $multimediaObject->getSeries()->getTitle(),
        ];
    }

    protected function renderErrorTemplate(string $message = ''): Response
    {
        $renderedView = $this->templating->render(self::CO_OWNER_ERROR_TEMPLATE, ['message' => $message]);

        return new Response($renderedView, Response::HTTP_FORBIDDEN);
    }

    protected function wasUsedRejectedLink(MultimediaObject $multimediaObject, User $coOwner): bool
    {
        $rejectedCoOwners = $multimediaObject->getProperty(OwnerService::MULTIMEDIA_OBJECT_CO_OWNERS_PROPERTY);
        $owners = $multimediaObject->getProperty(OwnerService::MULTIMEDIA_OBJECT_OWNERS_PROPERTY);

        return is_array($rejectedCoOwners) && in_array($coOwner->getId(), $rejectedCoOwners) && !in_array($coOwner->getId(), $owners);
    }
}
