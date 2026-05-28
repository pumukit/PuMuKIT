<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Pumukit\NewAdminBundle\Form\Type\MaterialType;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MaterialService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
class MaterialController extends AbstractController implements NewAdminControllerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    private $requestStack;

    /** @var MaterialService */
    private $materialService;

    public function __construct(TranslatorInterface $translator, MaterialService $materialService, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->materialService = $materialService;
        $this->requestStack = $requestStack;
    }

    #[Template('@PumukitNewAdmin/Material/create.html.twig')]
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $locale = $request->getLocale();
        $material = new Material();
        $form = $this->createForm(MaterialType::class, $material, ['translator' => $this->translator, 'locale' => $locale]);

        return [
            'material' => $material,
            'form' => $form->createView(),
            'mm' => $multimediaObject,
        ];
    }

    public function updateAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $locale = $request->getLocale();
        $material = $multimediaObject->getMaterialById($request->get('id'));
        $form = $this->createForm(MaterialType::class, $material, ['translator' => $this->translator, 'locale' => $locale]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $multimediaObject = $this->materialService->updateMaterialInMultimediaObject($multimediaObject, $material);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('pumukitnewadmin_material_list', ['id' => $multimediaObject->getId()]);
        }

        return $this->render(
            '@PumukitNewAdmin/Material/update.html.twig',
            [
                'material' => $material,
                'form' => $form->createView(),
                'mmId' => $multimediaObject->getId(),
            ]
        );
    }

    #[Template('@PumukitNewAdmin/Material/upload.html.twig')]
    public function uploadAction(MultimediaObject $multimediaObject, Request $request)
    {
        $formData = $request->get('pumukitnewadmin_material', []);

        $materialService = $this->materialService;

        try {
            if (0 === $request->files->count() && 0 === $request->request->count()) {
                throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
            }
            if ($request->files->has('file') && (!$request->get('url', null))) {
                $multimediaObject = $materialService->addMaterialFile($multimediaObject, $request->files->get('file'), $formData);
            } elseif ($request->get('url', null)) {
                $multimediaObject = $materialService->addMaterialUrl($multimediaObject, $request->get('url'), $formData);
            }
        } catch (\Exception $e) {
            return [
                'mm' => $multimediaObject,
                'uploaded' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'mm' => $multimediaObject,
            'uploaded' => 'success',
            'message' => 'New Material added.',
        ];
    }

    public function deleteAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->materialService->removeMaterialFromMultimediaObject($multimediaObject, $request->get('id'));

        return $this->redirectToRoute('pumukitnewadmin_material_list', ['id' => $multimediaObject->getId()]);
    }

    public function upAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->materialService->upMaterialInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'up');

        return $this->redirectToRoute('pumukitnewadmin_material_list', ['id' => $multimediaObject->getId()]);
    }

    public function downAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->materialService->downMaterialInMultimediaObject($multimediaObject, $request->get('id'));

        $this->addFlash('success', 'down');

        return $this->redirectToRoute('pumukitnewadmin_material_list', ['id' => $multimediaObject->getId()]);
    }

    #[Template('@PumukitNewAdmin/Material/list.html.twig')]
    public function listAction(MultimediaObject $multimediaObject)
    {
        return [
            'mmId' => $multimediaObject->getId(),
            'materials' => $multimediaObject->getMaterials(),
        ];
    }
}
