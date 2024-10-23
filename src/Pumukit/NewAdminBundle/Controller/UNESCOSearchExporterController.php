<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use Pumukit\NewAdminBundle\Services\MultimediaObjectSearchService;
use Pumukit\NewAdminBundle\Services\TagCatalogueService;
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

    /** @var MultimediaObjectSearchService */
    private $multimediaObjectSearchService;

    public function __construct(
        DocumentManager $documentManager,
        SessionInterface $session,
        TagCatalogueService $tagCatalogueService,
        RequestStack $requestStack,
        MultimediaObjectSearchService $multimediaObjectSearchService
    ) {
        $this->documentManager = $documentManager;
        $this->session = $session;
        $this->tagCatalogueService = $tagCatalogueService;
        $this->requestStack = $requestStack;
        $this->multimediaObjectSearchService = $multimediaObjectSearchService;
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
            $qb = $this->addCriteria($qb, $criteria);
        }

        $results = $qb->getQuery()->execute()->toArray();
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

    private function addCriteria($query, $criteria)
    {
        $request = $this->requestStack->getMainRequest();

        foreach ($criteria as $key => $field) {
            if ('roles' === $key && (is_countable($field) ? count($field) : 0) >= 1) {
                foreach ($field as $key2 => $value) {
                    $query->field('people')->elemMatch($query->expr()->field('cod')->equals($key2)->field('people.name')->equals($value));
                }
            } elseif ('public_date_init' === $key && !empty($field)) {
                $public_date_init = $field;
            } elseif ('public_date_finish' === $key && !empty($field)) {
                $public_date_finish = $field;
            } elseif ('record_date_init' === $key && !empty($field)) {
                $record_date_init = $field;
            } elseif ('record_date_finish' === $key && !empty($field)) {
                $record_date_finish = $field;
            } elseif ('$text' === $key && !empty($field)) {
                if (preg_match('/^[0-9a-z]{24}$/', $field)) {
                    $query->field('_id')->equals($field);
                } else {
                    $this->multimediaObjectSearchService->completeSearchQueryBuilder(
                        $field,
                        $query,
                        $request->getLocale()
                    );
                }
            } elseif ('type' === $key && !empty($field)) {
                if ('all' !== $field) {
                    $query->field('type')->equals($field);
                }
            } elseif ('tracks.duration' == $key && !empty($field)) {
                $query = $this->findDuration($query, $key, $field);
            } elseif ('year' === $key && !empty($field)) {
                $query = $this->findDuration($query, 'year', $field);
            } else {
                $query->field($key)->equals($field);
            }
        }

        if (isset($public_date_init, $public_date_finish)) {
            $query->field('public_date')->range(
                new UTCDateTime(strtotime($public_date_init) * 1000),
                new UTCDateTime(strtotime($public_date_finish) * 1000)
            );
        } elseif (isset($public_date_init)) {
            $date = date($public_date_init.'T23:59:59');
            $query->field('public_date')->range(
                new UTCDateTime(strtotime($public_date_init) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        } elseif (isset($public_date_finish)) {
            $date = date($public_date_finish.'T23:59:59');
            $query->field('public_date')->range(
                new UTCDateTime(strtotime($public_date_finish) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        }

        if (isset($record_date_init, $record_date_finish)) {
            $query->field('record_date')->range(
                new UTCDateTime(strtotime($record_date_init) * 1000),
                new UTCDateTime(strtotime($record_date_finish) * 1000)
            );
        } elseif (isset($record_date_init)) {
            $date = date($record_date_init.'T23:59:59');
            $query->field('record_date')->range(
                new UTCDateTime(strtotime($record_date_init) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        } elseif (isset($record_date_finish)) {
            $date = date($record_date_finish.'T23:59:59');
            $query->field('record_date')->range(
                new UTCDateTime(strtotime($record_date_finish) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        }

        return $query;
    }

    private function findDuration($query, $key, $field)
    {
        if ('tracks.duration' === $key) {
            if ('-5' == $field) {
                $query->field($key)->lte(300);
            }
            if ('-10' == $field) {
                $query->field($key)->lte(600);
            }
            if ('-30' == $field) {
                $query->field($key)->lte(1800);
            }
            if ('-60' == $field) {
                $query->field($key)->lte(3600);
            }
            if ('+60' == $field) {
                $query->field($key)->gt(3600);
            }
        } elseif ('year' === $key) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', $field));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', ((int) $field) + 1));
            $query->field('record_date')->gte($start);
            $query->field('record_date')->lt($end);
        }

        return $query;
    }
}
