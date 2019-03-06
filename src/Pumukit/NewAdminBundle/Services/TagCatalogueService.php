<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Utils\Search\SearchUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TagCatalogueService.
 */
class TagCatalogueService
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    private $baseTagCod = 'UNESCO';
    private $configuredTag;

    private $allDefaultFields;

    /**
     * TagCatalogueService constructor.
     *
     * @param DocumentManager     $documentManager
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     * @param                     $configuredTag
     */
    public function __construct(DocumentManager $documentManager, TranslatorInterface $translator, RouterInterface $router, $configuredTag)
    {
        $this->dm = $documentManager;
        $this->translator = $translator;
        $this->router = $router;
        $this->configuredTag = $configuredTag;

        $this->allDefaultFields = $this->getAllCustomListFields();
    }

    /**
     * @return object|Tag
     *
     * @throws \Exception
     */
    public function getConfiguredTag()
    {
        $tagCod = $this->configuredTag;
        if (null === $this->configuredTag) {
            $tagCod = $this->baseTagCod;
        }

        $tag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(
            ['cod' => $tagCod]
        );

        if (!$tag) {
            throw new \Exception('Catalogue - Tag code configured not found '.$tagCod);
        }

        return $tag;
    }

    /**
     * @param SessionInterface $session
     * @param                  $all
     */
    public function resetSessionCriteria(SessionInterface $session, $all = true)
    {
        if ($all) {
            $session->remove('UNESCO/criteria');
            $session->remove('admin/unesco/custom_fields');
            $session->remove('admin/unesco/selected_fields');
            $session->remove('UNESCO/form');
            $session->remove('UNESCO/formbasic');
            $session->set('admin/unesco/text', false);
        }

        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');
        $session->remove('admin/unesco/type');
        $session->remove('admin/unesco/element_sort');
    }

    /**
     * @param Request          $request
     * @param SessionInterface $session
     */
    public function addSessionCriteria(Request $request, SessionInterface $session)
    {
        $formBasic = false;
        $newCriteria = [];
        $tag = [];

        $criteria = $request->request->get('criteria');

        $session->set('admin/unesco/text', false);
        if ($criteria) {
            foreach ($criteria as $key => $value) {
                if (('id' === $key) && !empty($value)) {
                    $newCriteria['_id'] = $value;
                    $formBasic = true;
                } elseif (('seriesID' === $key) && !empty($value)) {
                    $newCriteria['series'] = $value;
                    $formBasic = true;
                } elseif (('series.numerical_id' === $key) && !empty($value)) {
                    $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(['numerical_id' => intval($value)]);
                    if ($series) {
                        $newCriteria['series'] = new \MongoId($series->getId());
                    } else {
                        // NOTE: Return 0 results.
                        $newCriteria['series'] = new \MongoId();
                    }
                } elseif (('mm.numerical_id' === $key) && !empty($value)) {
                    $newCriteria['numerical_id'] = intval($value);
                } elseif ((false !== strpos($key, 'properties')) && !(empty($value))) {
                    $newCriteria[$key] = $value;
                } elseif ('type' === $key && !empty($value)) {
                    if ('all' !== $value) {
                        $newCriteria['type'] = intval($value);
                        $formBasic = true;
                    }
                } elseif ('duration' === $key && !empty($value)) {
                    $newCriteria['tracks.duration'] = $value;
                    $formBasic = true;
                } elseif ('year' === $key && !empty($value)) {
                    $newCriteria['year'] = $value;
                    $formBasic = true;
                } elseif ('text' === $key && !empty($value)) {
                    $newCriteria['$text'] = $value;
                    $session->set('admin/unesco/text', true);
                    $formBasic = true;
                } elseif ('broadcast' === $key && !empty($value)) {
                    if ('all' !== $value) {
                        $newCriteria['embeddedBroadcast.type'] = $value;
                    }
                } elseif ('statusPub' === $key) {
                    if ('-1' !== $value) {
                        $newCriteria['status'] = intval($value);
                    }
                } elseif ('announce' === $key && !empty($value)) {
                    $tag[] = 'PUDENEW';
                } elseif ('puderadio' === $key && !empty($value)) {
                    $tag[] = 'PUDERADIO';
                } elseif ('pudetv' === $key && !empty($value)) {
                    $tag[] = 'PUDETV';
                } elseif ('genre' === $key && !empty($value)) {
                    $tag[] = $value;
                } elseif ('roles' === $key) {
                    foreach ($value as $key2 => $field) {
                        if (!empty($field)) {
                            $newCriteria['roles'][$key2] = new \MongoRegex('/.*'.preg_quote($field).'.*/i');
                        }
                    }
                } elseif ('group' === $key) {
                    if ('all' !== $value) {
                        $newCriteria['groups'] = new \MongoId($value);
                    }
                } elseif (in_array($key, ['initPublicDate', 'finishPublicDate', 'initRecordDate', 'finishRecordDate'])) {
                    if ('initPublicDate' === $key && !empty($value)) {
                        $newCriteria['public_date_init'] = $value;
                    } elseif ('finishPublicDate' === $key && !empty($value)) {
                        $newCriteria['public_date_finish'] = $value;
                    } elseif ('initRecordDate' === $key && !empty($value)) {
                        $newCriteria['record_date_init'] = $value;
                    } elseif ('finishRecordDate' === $key && !empty($value)) {
                        $newCriteria['record_date_finish'] = $value;
                    }
                } elseif ('originalName' === $key && !empty($value)) {
                    $newCriteria['tracks.originalName'] = SearchUtils::generateRegexExpression($value);
                } elseif (in_array($key, ['comments', 'license', 'copyright']) && !empty($value)) {
                    $newCriteria[$key] = SearchUtils::generateRegexExpression($value);
                } elseif (!empty($value)) {
                    $newCriteria[$key.'.'.$request->getLocale()] = SearchUtils::generateRegexExpression($value);
                }
            }
        }

        if (!empty($tag)) {
            if ('all' === $tag[0]) {
                array_shift($tag);
            }
            if (!empty($tag)) {
                $newCriteria['tags.cod'] = ['$all' => $tag];
            }
        }

        if ($request->request->has('sort_type')) {
            $sort_type = $request->request->get('sort_type');
            if ('title' === $request->request->get('sort_type')) {
                $sort_type = 'title.'.$request->getLocale();
            }
            if ($session->get('admin/unesco/text', false)) {
                $sort_utype = 'score';
            } else {
                $sort_utype = $request->request->get('sort');
            }
            $session->set('admin/unesco/element_sort', $sort_type);
            $session->set('admin/unesco/type', $sort_utype);

            return;
        }

        if ($session->get('admin/unesco/text', false)) {
            $session->set('admin/unesco/type', 'score');
        }

        $session->set('UNESCO/form', $criteria);
        $session->set('UNESCO/criteria', $newCriteria);
        $session->set('UNESCO/formbasic', $formBasic);

        return;
    }

    /**
     * @return array
     */
    public function getDefaultListFields()
    {
        return [
            'custom_field_1' => 'type',
            'custom_field_2' => 'pics',
            'custom_field_3' => 'seriesTitle',
            'custom_field_4' => 'title',
            'custom_field_5' => 'duration',
            'custom_field_6' => 'record_date',
            'custom_field_7' => 'public_date',
        ];
    }

    /**
     * @param MultimediaObject $object
     * @param SessionInterface $session
     * @param                  $field
     *
     * @return string
     *
     * @throws \Exception
     */
    public function renderField(MultimediaObject $object, SessionInterface $session, $field)
    {
        if (!isset($this->allDefaultFields[$field]['render'])) {
            throw new \Exception('Render field key doesnt exists');
        }

        $key = $this->allDefaultFields[$field]['render'];
        switch ($key) {
            case 'text':
                return $this->textRenderField($object, $field);
                break;
            case 'criteria':
                return $this->criteriaRenderField($session);
            case 'role':
                return $this->roleRenderField($object, $field);
            default:
                $data = '';
        }

        return $data;
    }

    /**
     * @param MultimediaObject $object
     * @param                  $field
     *
     * @return string
     */
    private function textRenderField(MultimediaObject $object, $field)
    {
        switch ($field) {
            case 'id':
                $text = $object->getId();
                break;
            case 'series.id':
                $text = $object->getSeries()->getId();
                $route = $this->router->generate('pumukitnewadmin_series_index', array('id' => $text));
                $text = "<a href='".$route."'>".(string) $text.'</a>';
                break;
            case 'title':
                $text = $object->getTitle();
                break;
            case 'seriesTitle':
                $text = $object->getSeriesTitle();
                $route = $this->router->generate('pumukitnewadmin_series_index', array('id' => $object->getSeries()->getId()));
                $text = "<a href='".$route."'>".$text.'</a>';
                break;
            case 'subtitle':
                $text = $object->getSubtitle();
                break;
            case 'description':
                $text = $object->getDescription();
                break;
            case 'comments':
                $text = $object->getComments();
                break;
            case 'keywords':
                $text = $object->getKeyword();
                break;
            case 'copyright':
                $text = $object->getCopyright();
                break;
            case 'license':
                $text = $object->getLicense();
                break;
            case 'record_date':
                $text = $object->getRecordDate()->format('Y-m-d');
                break;
            case 'public_date':
                $text = $object->getPublicDate()->format('Y-m-d');
                break;
            case 'tracks.name':
                $text = $this->getTracksName($object);
                break;
            case 'numerical_id':
                $text = $object->getNumericalID();
                break;
            case 'series.numerical_id':
                $text = $object->getSeries()->getNumericalID();
                break;
            case 'type':
                $type = $object->getType();
                $text = $this->translator->trans($this->getStringType($type));
                break;
            case 'duration':
                $text = $object->getDurationString();
                break;
            case 'year':
                $text = $object->getRecordDate();
                $text = $text->format('Y');
                break;
            case 'embeddedBroadcast':
                $text = $this->translator->trans($object->getEmbeddedBroadcast()->getName());
                break;
            case 'status':
                $text = $object->getStatus();
                break;
            case 'groups':
                $text = implode(',', $object->getGroups()->toArray());
                break;
            default:
                $text = 'No data';
        }

        return $text;
    }

    /**
     * @param SessionInterface $session
     *
     * @return string
     */
    private function criteriaRenderField(SessionInterface $session)
    {
        if (!$session->has('UNESCO/criteria')) {
            return $this->translator->trans('Without criteria');
        }

        if (count($session->get('UNESCO/criteria')) > 1) {
            return $this->translator->trans('Multiple criteria');
        }

        $criteria = $session->get('UNESCO/criteria');
        $key = array_keys($criteria);

        return $key[0];
    }

    /**
     * @param MultimediaObject $object
     * @param                  $field
     *
     * @return string
     */
    private function roleRenderField(MultimediaObject $object, $field)
    {
        $role = explode('.', $field);
        $roleCod = $role[1];

        $people = $object->getPeopleByRoleCod($roleCod);

        $text = '';
        foreach ($people as $person) {
            $text .= $person->getName()."\n";
        }

        return $text;
    }

    /**
     * @param MultimediaObject $object
     *
     * @return mixed
     */
    private function getTracksName(MultimediaObject $object)
    {
        $tracks = $object->getTracks();
        foreach ($tracks as $track) {
            if ($track->getOriginalName()) {
                return $track->getOriginalName();
            }
        }

        return '';
    }

    /**
     * @param $type
     *
     * @return string
     */
    private function getStringType($type)
    {
        if (MultimediaObject::TYPE_VIDEO === $type) {
            $text = 'Video';
        } elseif (MultimediaObject::TYPE_AUDIO === $type) {
            $text = 'Audio';
        } elseif (MultimediaObject::TYPE_EXTERNAL === $type) {
            $text = 'External';
        } else {
            $text = '';
        }

        return $text;
    }

    /**
     * @return array
     */
    public function getAllCustomListFields()
    {
        $allFields = [
            'id' => [
                'label' => $this->translator->trans('Video ID'),
                'render' => 'text',
                'render_params' => [],
            ],
            'series.id' => [
                'label' => $this->translator->trans('Series ID'),
                'render' => 'text',
                'render_params' => [],
            ],
            'title' => [
                'label' => $this->translator->trans('Title'),
                'render' => 'text',
                'render_params' => [],
            ],
            'seriesTitle' => [
                'label' => $this->translator->trans('Series title'),
                'render' => 'text',
                'render_params' => [],
            ],
            'subtitle' => [
                'label' => $this->translator->trans('Subtitle'),
                'render' => 'text',
                'render_params' => [],
            ],
            'description' => [
                'label' => $this->translator->trans('Description'),
                'render' => 'text',
                'render_params' => [],
            ],
            'comments' => [
                'label' => $this->translator->trans('Comments'),
                'render' => 'text',
                'render_params' => [],
            ],
            'keywords' => [
                'label' => $this->translator->trans('Keywords'),
                'render' => 'text',
                'render_params' => [],
            ],
            'copyright' => [
                'label' => $this->translator->trans('Copyright'),
                'render' => 'text',
                'render_params' => [],
            ],
            'license' => [
                'label' => $this->translator->trans('License'),
                'render' => 'text',
                'render_params' => [],
            ],
            'public_date' => [
                'label' => $this->translator->trans('Publication date'),
                'render' => 'text',
                'render_params' => [],
            ],
            'record_date' => [
                'label' => $this->translator->trans('Record date'),
                'render' => 'text',
                'render_params' => [],
            ],
            'tracks.name' => [
                'label' => $this->translator->trans('Track name'),
                'render' => 'text',
                'render_params' => [],
            ],
            'numerical_id' => [
                'label' => $this->translator->trans('Numerical video ID'),
                'render' => 'text',
                'render_params' => [],
            ],
            'series.numerical_id' => [
                'label' => $this->translator->trans('Numerical series ID'),
                'render' => 'text',
                'render_params' => [],
            ],
            'type' => [
                'label' => $this->translator->trans('Type'),
                'render' => 'text',
                'render_params' => [],
            ],
            'duration' => [
                'label' => $this->translator->trans('Duration'),
                'render' => 'text',
                'render_params' => [],
            ],
            'year' => [
                'label' => $this->translator->trans('Year'),
                'render' => 'text',
                'render_params' => [],
            ],
            'embeddedBroadcast' => [
                'label' => $this->translator->trans('Broadcast'),
                'render' => 'text',
                'render_params' => [],
            ],
            'status' => [
                'label' => $this->translator->trans('Status'),
                'render' => 'text',
                'render_params' => [],
            ],
            'groups' => [
                'label' => $this->translator->trans('Groups'),
                'render' => 'text',
                'render_params' => [],
            ],
            'pics' => [
                'label' => $this->translator->trans('Images'),
                'render' => 'img',
                'render_params' => [],
            ],
            'criteria' => [
                'label' => $this->translator->trans('Criteria'),
                'render' => 'criteria',
                'render_params' => [],
            ],
        ];

        $roles = $this->dm->getRepository('PumukitSchemaBundle:Role')->findAll();
        foreach ($roles as $role) {
            $allFields['role.'.$role->getCod()] = [
                'label' => $role->getName(),
                'render' => 'role',
                'render_params' => [],
            ];
        }

        return $allFields;
    }
}
