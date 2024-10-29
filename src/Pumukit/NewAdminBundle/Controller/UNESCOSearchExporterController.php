<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\NewAdminBundle\Services\UNESCOService;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UNESCOSearchExporterController extends AbstractController implements NewAdminControllerInterface
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var SessionInterface */
    private $session;

    /** @var TagCatalogueService */
    private $tagCatalogueService;

    /** @var RequestStack */
    private $requestStack;

    /** @var UNESCOService */
    private $UNESCOService;

    public function __construct(
        DocumentManager $documentManager,
        SessionInterface $session,
        TagCatalogueService $tagCatalogueService,
        RequestStack $requestStack,
        UNESCOService $UNESCOService
    ) {
        $this->documentManager = $documentManager;
        $this->session = $session;
        $this->tagCatalogueService = $tagCatalogueService;
        $this->requestStack = $requestStack;
        $this->UNESCOService = $UNESCOService;
    }

    /**
     * @Route("/export-unesco-search", name="export_unesco_search")
     */
    public function exportUNESCOSearchAction(Request $request): StreamedResponse
    {
        $session = $this->session;

        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();

        $configuredTag = $this->tagCatalogueService->getConfiguredTag();
        $tag = $session->get('admin/unesco/tag');

        $tagCondition = $tag;
        if (isset($tag) && !in_array($tag, ['1', '2'])) {
            $tagCondition = 'tag';
        }

        switch ($tagCondition) {
            case '1':
                $selectedTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $configuredTag->getCod()]);
                $qb
                    ->field('tags.cod')
                    ->notEqual($selectedTag->getCod())
                ;

                break;

            case 'tag':
                $selectedTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $session->get('admin/unesco/tag')]);
                $qb
                    ->field('tags.cod')
                    ->equals($selectedTag->getCod())
                ;

                break;
        }

        if ($session->has('admin/unesco/element_sort')) {
            if ($session->get('admin/unesco/text', false)) {
                $qb->sortMeta('score', 'textScore');
            } else {
                $qb->sort($session->get('admin/unesco/element_sort'), $session->get('admin/unesco/type'));
            }
        }

        $criteria = $session->get('UNESCO/criteria');
        if (isset($criteria) && !empty($criteria)) {
            $request = $this->requestStack->getMainRequest();
            $qb = $this->UNESCOService->addCriteria($qb, $criteria, $request->getLocale());
        }

        if (!$this->session->has('admin/unesco/selected_fields')) {
            $defaultSelectedFields = $this->tagCatalogueService->getDefaultListFields();
            $this->session->set('admin/unesco/selected_fields', $defaultSelectedFields);
        }

        $response = new StreamedResponse(function () use ($qb) {
            $handle = fopen('php://output', 'w+');

            $fields = $this->tagCatalogueService->getAllCustomListFields();
            $columns = [];

            foreach ($fields as $field) {
                $columns[] = $field['label'];
            }

            fputcsv($handle, $columns, ';');

            $page = 1;
            $limit = 100;
            $totalViews = 0;

            while (true) {
                $results = $this->getPaginatedResults($qb, $page, $limit);

                if (0 === count($results)) {
                    break;
                }

                foreach ($results as $result) {
                    $data = [];
                    $numViews = $result->getNumview();
                    $totalViews += $numViews;

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

                ++$page;
            }

            fputcsv($handle, ['', 'Total Views:', $totalViews]);

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="resultados.csv"');

        return $response;
    }

    public function getPaginatedResults(Builder $qb, int $page, int $limit)
    {
        return $qb
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray()
        ;
    }
}
