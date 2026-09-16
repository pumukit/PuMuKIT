<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/slider")
 */
class SliderController extends AbstractController
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @Route("", name="pumukit_newadmin_slider", methods={"GET"})
     */
    public function index(): Response
    {
        $this->denyAccessToSlider();

        $multimediaObjects = $this->documentManager
            ->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->field('tags.cod')
            ->all([
                'PUDEWALL',
                PumukitWebTVBundle::WEB_TV_TAG,
            ])
            ->field('status')
            ->equals(MultimediaObject::STATUS_PUBLISHED)
            ->sort('properties.slider_order', 'asc')
            ->getQuery()
            ->execute()
        ;

        return $this->render('@PumukitNewAdmin/Slider/index.html.twig', [
            'multimediaObjects' => $multimediaObjects,
        ]);
    }

    /**
     * @Route("/order", name="pumukit_newadmin_slider_order", methods={"POST"})
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $this->denyAccessToSlider();

        if (!$this->isCsrfTokenValid('slider_order', $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token',
            ], Response::HTTP_FORBIDDEN);
        }

        $order = $request->request->all('order');

        foreach ($order as $index => $id) {
            $multimediaObject = $this->documentManager
                ->getRepository(MultimediaObject::class)
                ->createQueryBuilder()
                ->field('_id')
                ->equals($id)
                ->field('tags.cod')
                ->all([
                    'PUDEWALL',
                    PumukitWebTVBundle::WEB_TV_TAG,
                ])
                ->field('status')
                ->equals(MultimediaObject::STATUS_PUBLISHED)
                ->getQuery()
                ->getSingleResult()
            ;

            if (!$multimediaObject) {
                continue;
            }

            $multimediaObject->setProperty('slider_order', $index + 1);
        }

        $this->documentManager->flush();

        return $this->json([
            'success' => true,
        ]);
    }

    private function denyAccessToSlider(): void
    {
        if ($this->isGranted('ROLE_TAG_DISABLE_PUDEWALL')) {
            throw $this->createAccessDeniedException();
        }
    }
}
