<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route ("/series/styles")
 *
 * @Security("is_granted('ROLE_ACCESS_SERIES_STYLE')")
 */
class SeriesStylesController extends AbstractController
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var SessionInterface */
    private $session;

    public function __construct(DocumentManager $documentManager, TranslatorInterface $translator, SessionInterface $session)
    {
        $this->documentManager = $documentManager;
        $this->translator = $translator;
        $this->session = $session;
    }

    /**
     * @Route("/", name="pumukit_newadmin_series_styles")
     *
     * @Template("@PumukitNewAdmin/SeriesStyle/crud.html.twig")
     */
    public function menuAction()
    {
        return [];
    }

    /**
     * @Route("/list", name="pumukit_newadmin_series_styles_list")
     *
     * @Template("@PumukitNewAdmin/SeriesStyle/list.html.twig")
     */
    public function listAction(): array
    {
        $styles = $this->documentManager->getRepository(SeriesStyle::class)->findAll();

        usort($styles, static function (SeriesStyle $a, SeriesStyle $b) {
            return strtolower($a->getName()) > strtolower($b->getName());
        });

        return ['styles' => $styles];
    }

    /**
     * @Route("/create", name="pumukit_newadmin_series_styles_create")
     */
    public function createAction(Request $request): JsonResponse
    {
        $style = new SeriesStyle();
        $style->setName($request->query->get('name'));
        $style->setText('');
        $this->documentManager->persist($style);
        $this->documentManager->flush();

        $session = $this->session;
        $session->set('seriesstyle/id', $style->getId());

        return new JsonResponse(['success', 'id' => $style->getId()]);
    }

    /**
     * @Route("/edit", name="pumukit_newadmin_series_styles_edit")
     */
    public function editAction(Request $request): JsonResponse
    {
        $id = $request->request->get('id');
        if (isset($id)) {
            $style = $this->documentManager->getRepository(SeriesStyle::class)->findOneBy(
                ['_id' => new ObjectId($request->request->get('id'))]
            );
        } else {
            return new JsonResponse(['error']);
        }

        $style->setText($request->request->get('style_text'));
        $this->documentManager->flush();

        $session = $this->session;
        $session->set('seriesstyle/id', $style->getId());

        return new JsonResponse(['success']);
    }

    /**
     * @Route("/delete/{id}", name="pumukit_newadmin_series_styles_delete")
     */
    public function deleteAction(string $id): JsonResponse
    {
        $session = $this->session;

        $style = $this->documentManager->getRepository(SeriesStyle::class)->findOneBy(['_id' => new ObjectId($id)]);

        if ($style) {
            $series = $this->documentManager->getRepository(Series::class)->findOneBy(
                ['series_style' => new ObjectId($style->getId())]
            );

            if (!$series) {
                $this->documentManager->remove($style);
                $this->documentManager->flush();

                $session->set('seriesstyle/id', '');

                return new JsonResponse(['success', 'msg' => $this->translator->trans('Successfully deleted series style')]);
            }

            return new JsonResponse(['error', 'msg' => $this->translator->trans('There are series with this series style')]);
        }

        return new JsonResponse(['error', 'msg' => $this->translator->trans("Series style {$id}() doesn't exists")]);
    }

    /**
     * @Route("/show/{id}", name="pumukit_newadmin_series_styles_show")
     *
     * @Template("@PumukitNewAdmin/SeriesStyle/show.html.twig")
     */
    public function showAction(?string $id = null): array
    {
        if (isset($id)) {
            $style = $this->documentManager->getRepository(SeriesStyle::class)->findOneBy(
                ['_id' => new ObjectId($id)]
            );
        } else {
            $style = '';
        }

        return ['style' => $style];
    }
}
