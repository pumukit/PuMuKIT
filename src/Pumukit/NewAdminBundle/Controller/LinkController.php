<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Form\Type\LinkType;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\LinkService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class LinkController extends AbstractController implements NewAdminControllerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var LinkService */
    private $linkService;

    /** @var SessionInterface */
    private $session;

    public function __construct(TranslatorInterface $translator, LinkService $linkService, SessionInterface $session)
    {
        $this->translator = $translator;
        $this->linkService = $linkService;
        $this->session = $session;
    }

    /**
     * @Template("@PumukitNewAdmin/Link/create.html.twig")
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $locale = $request->getLocale();
        $link = new Link();
        $form = $this->createForm(LinkType::class, $link, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $multimediaObject = $this->linkService->addLinkToMultimediaObject($multimediaObject, $link);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->render(
                '@PumukitNewAdmin/Link/list.html.twig',
                [
                    'links' => $multimediaObject->getLinks(),
                    'mmId' => $multimediaObject->getId(),
                ]
            );
        }

        return [
            'link' => $link,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Link/update.html.twig")
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        $locale = $request->getLocale();
        $link = $multimediaObject->getLinkById($request->get('id'));
        $form = $this->createForm(LinkType::class, $link, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $multimediaObject = $this->linkService->updateLinkInMultimediaObject($multimediaObject, $link);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->render(
                '@PumukitNewAdmin/Link/list.html.twig',
                [
                    'links' => $multimediaObject->getLinks(),
                    'mmId' => $multimediaObject->getId(),
                ]
            );
        }

        return [
            'link' => $link,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Link/list.html.twig")
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->linkService->removeLinkFromMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'links' => $multimediaObject->getLinks(),
            'mmId' => $multimediaObject->getId(),
        ];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Link/list.html.twig")
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->linkService->upLinkInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'mmId' => $multimediaObject->getId(),
            'links' => $multimediaObject->getLinks(),
        ];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     *
     * @Template("@PumukitNewAdmin/Link/list.html.twig")
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->linkService->downLinkInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'mmId' => $multimediaObject->getId(),
            'links' => $multimediaObject->getLinks(),
        ];
    }
}
