<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Services\AnnounceService;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnnouncesController extends AbstractController implements WebTVControllerInterface
{
    private $translator;
    private $breadcrumbsService;
    private $columnsObjsAnnounces;
    private $menuAnnouncesTitle;
    private $announcesService;
    private $showLatestWithPudeNew;
    private $useRecordDateAnnounce;

    public function __construct(
        TranslatorInterface $translator,
        BreadcrumbsService $breadcrumbsService,
        AnnounceService $announcesService,
        int $columnsObjsAnnounces,
        string $menuAnnouncesTitle,
        $showLatestWithPudeNew,
        $useRecordDateAnnounce
    ) {
        $this->translator = $translator;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->columnsObjsAnnounces = $columnsObjsAnnounces;
        $this->menuAnnouncesTitle = $menuAnnouncesTitle;
        $this->announcesService = $announcesService;
        $this->showLatestWithPudeNew = $showLatestWithPudeNew;
        $this->useRecordDateAnnounce = $useRecordDateAnnounce;
    }

    /**
     * @Route("/latestuploads", name="pumukit_webtv_announces_latestuploads")
     * @Template("@PumukitWebTV/Announces/template.html.twig")
     */
    public function latestUploadsAction()
    {
        $templateTitle = $this->translator->trans($this->menuAnnouncesTitle);
        $this->breadcrumbsService->addList($templateTitle, 'pumukit_webtv_announces_latestuploads');

        return [
            'template_title' => $templateTitle,
            'objectByCol' => $this->columnsObjsAnnounces,
            'show_info' => false,
            'show_more' => false,
        ];
    }

    /**
     * @Route("/latestuploads/pager", name="pumukit_webtv_announces_latestuploads_pager")
     *
     * @return Response
     */
    public function latestUploadsPagerAction(Request $request)
    {
        $dateRequest = $request->query->get('date', 0); //Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/{$dateRequest} 00:00:00");
        if (!$date) {
            throw $this->createNotFoundException();
        }
        [$date, $last] = $this->announcesService->getNextLatestUploads($date, $this->showLatestWithPudeNew, $this->useRecordDateAnnounce);

        $response = new Response();
        $dateHeader = '---';

        if (!empty($last)) {
            $response = new Response(
                $this->renderView(
                    $this->getLatestUploadsPagerTemplate(),
                    [
                        'last' => $last,
                        'date' => $date,
                        'objectByCol' => $this->columnsObjsAnnounces,
                        'show_info' => false,
                        'show_more' => false,
                    ]
                ),
                200
            );
            $dateHeader = $date->format('m/Y');
            $response->headers->set('X-Date-Month', $date->format('m'));
            $response->headers->set('X-Date-Year', $date->format('Y'));
        }

        $response->headers->set('X-Date', $dateHeader);

        return $response;
    }

    protected function getLatestUploadsPagerTemplate(): string
    {
        return '@PumukitWebTV/Announces/template_pager.html.twig';
    }
}
