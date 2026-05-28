<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Pumukit\NewAdminBundle\Form\Type\LinkType;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\LinkService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
class LinkController extends AbstractController implements NewAdminControllerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var LinkService */
    private $linkService;

    private $requestStack;

    public function __construct(TranslatorInterface $translator, LinkService $linkService, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->linkService = $linkService;
        $this->requestStack = $requestStack;
    }

    #[Template('@PumukitNewAdmin/Link/create.html.twig')]
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

    #[Template('@PumukitNewAdmin/Link/update.html.twig')]
    public function updateAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
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

    #[Template('@PumukitNewAdmin/Link/list.html.twig')]
    public function deleteAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->linkService->removeLinkFromMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'links' => $multimediaObject->getLinks(),
            'mmId' => $multimediaObject->getId(),
        ];
    }

    #[Template('@PumukitNewAdmin/Link/list.html.twig')]
    public function upAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->linkService->upLinkInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'mmId' => $multimediaObject->getId(),
            'links' => $multimediaObject->getLinks(),
        ];
    }

    #[Template('@PumukitNewAdmin/Link/list.html.twig')]
    public function downAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->linkService->downLinkInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'delete');

        return [
            'mmId' => $multimediaObject->getId(),
            'links' => $multimediaObject->getLinks(),
        ];
    }
}
