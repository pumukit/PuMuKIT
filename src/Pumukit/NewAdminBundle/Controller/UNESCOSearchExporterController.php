<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\Track;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UNESCOSearchExporterController extends AbstractController implements NewAdminControllerInterface
{
    /** @var SessionInterface */
    private $session;

    /** @var TagCatalogueService */
    private $tagCatalogueService;

    public function __construct(
        SessionInterface $session,
        TagCatalogueService $tagCatalogueService
    ) {
        $this->session = $session;
        $this->tagCatalogueService = $tagCatalogueService;
    }

    /**
     * @Route("/export-unesco-search", name="export_unesco_search")
     */
    public function exportUNESCOSearchAction(Request $request): StreamedResponse
    {
        $results = $this->session->get('paginated_results', []);
        $totalViews = 0;

        if (!$this->session->has('admin/unesco/selected_fields')) {
            $defaultSelectedFields = $this->tagCatalogueService->getDefaultListFields();
            $this->session->set('admin/unesco/selected_fields', $defaultSelectedFields);
        }

        $totalViews = array_sum(array_map(function ($result) {
            return $result->getNumview();
        }, $results));

        $response = new StreamedResponse(function () use ($results, $totalViews) {
            $handle = fopen('php://output', 'w+');

            $fields = $this->tagCatalogueService->getAllCustomListFields();
            $columns = [];

            foreach ($fields as $field) {
                $columns[] = $field['label'];
            }

            fputcsv($handle, $columns, ';');

            foreach ($results as $result) {
                $data = [];
                foreach ($fields as $key => $field) {
                    $render = $fields[$key]['render'];

                    switch ($key) {
                        case 'series.id':
                            $data[] = trim($result->getSeries()->getId());

                            break;

                        case 'seriesTitle':
                            $data[] = trim($result->getSeriesTitle());

                            break;

                        case 'tracks.name':
                            $tracks = $result->getTracks()->toArray();
                            if (empty($tracks)) {
                                $tracks = $result->getTracks()->getMongoData();
                            }
                            $trackName = '';
                            foreach ($tracks as $track) {
                                if ($track instanceof Track) {
                                    if ($track->getOriginalName()) {
                                        $trackName = $track->getOriginalName();
                                    }
                                } else {
                                    if (isset($track['originalName'])) {
                                        $trackName = $track['originalName'];
                                    }
                                }
                            }
                            $data[] = $trackName;

                            break;

                        case 'groups':
                            $groups = $result->getGroups()->toArray();
                            if (empty($groups)) {
                                $groups = $result->getGroups()->getMongoData();
                            }
                            $groupName = implode(',', $groups);

                            $data[] = $groupName;

                            break;

                        default:
                            if ('role' == $render) {
                                $roles = $result->getRoles()->toArray();
                                if (empty($roles)) {
                                    $roles = $result->getRoles()->getMongoData();
                                }
                                $text = '';
                                foreach ($roles as $role) {
                                    $roleOM = explode('.', $key);
                                    $roleCod = $roleOM[1] ?? $key;
                                    $code = '';
                                    if ($role instanceof EmbeddedRole) {
                                        $code = $role->getCod();
                                    } else {
                                        $code = $role['cod'];
                                    }
                                    if ($code === $roleCod) {
                                        if ($role instanceof EmbeddedRole) {
                                            $people = $role->getPeople();
                                        } else {
                                            $people = $role['people'];
                                        }
                                        foreach ($people as $embeddedPerson) {
                                            if ($embeddedPerson instanceof EmbeddedPerson) {
                                                $text .= $embeddedPerson->getName()."\n";
                                            } else {
                                                $text .= $embeddedPerson['name']."\n";
                                            }
                                        }
                                    }
                                }
                                $data[] = trim($text);
                            } else {
                                $data[] = $this->tagCatalogueService->renderField($result, $this->session, $key);
                            }
                    }
                }
                fputcsv($handle, $data, ';');
            }

            fputcsv($handle, ['', 'Total Views:', $totalViews]);

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="resultados.csv"');

        return $response;
    }
}
