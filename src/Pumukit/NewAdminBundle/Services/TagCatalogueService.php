<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
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

    private $locales;

    /**
     * TagCatalogueService constructor.
     *
     * @param DocumentManager     $documentManager
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     * @param                     $configuredTag
     * @param                     $locales
     */
    public function __construct(DocumentManager $documentManager, TranslatorInterface $translator, RouterInterface $router, $configuredTag, $locales)
    {
        $this->dm = $documentManager;
        $this->translator = $translator;
        $this->router = $router;
        $this->configuredTag = $configuredTag;
        $this->locales = $locales;
    }

    /**
     * @throws \Exception
     *
     * @return object|Tag
     */
    public function getConfiguredTag()
    {
        $tagCod = $this->configuredTag;
        if (null === $this->configuredTag) {
            $tagCod = $this->baseTagCod;
        }

        $tag = $this->dm->getRepository(Tag::class)->findOneBy(
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
        if ($criteria) {
            foreach ($criteria as $key => $value) {
                if (('id' === $key) && !empty($value)) {
                    $newCriteria['_id'] = $value;
                } elseif (('seriesID' === $key) && !empty($value)) {
                    $newCriteria['series'] = $value;
                } elseif (('series.numerical_id' === $key) && !empty($value)) {
                    $series = $this->dm->getRepository(Series::class)->findOneBy(['numerical_id' => (int) $value]);
                    if ($series) {
                        $newCriteria['series'] = new \MongoId($series->getId());
                    } else {
                        // NOTE: Return 0 results.
                        $newCriteria['series'] = new \MongoId();
                    }
                } elseif (('mm.numerical_id' === $key) && !empty($value)) {
                    $newCriteria['numerical_id'] = (int) $value;
                } elseif ((false !== strpos($key, 'properties')) && !(empty($value))) {
                    $newCriteria[$key] = $value;
                } elseif ('type' === $key && !empty($value)) {
                    if ('all' !== $value) {
                        $newCriteria['type'] = (int) $value;
                    }
                } elseif ('duration' === $key && !empty($value)) {
                    $newCriteria['tracks.duration'] = $value;
                } elseif ('year' === $key && !empty($value)) {
                    $newCriteria['year'] = $value;
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
                        $newCriteria['status'] = (int) $value;
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
            $this->checkAndSortCriteria($request, $session);

            return;
        }

        if ($session->get('admin/unesco/text', false)) {
            $session->set('admin/unesco/type', 'score');
        }

        $session->set('UNESCO/form', $criteria);
        $session->set('UNESCO/criteria', $newCriteria);
        $session->set('UNESCO/formbasic', $formBasic);
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
     * @throws \Exception
     *
     * @return string
     */
    public function renderField(MultimediaObject $object, SessionInterface $session, $field)
    {
        $allDefaultFields = $this->getAllCustomListFields();
        if (!isset($allDefaultFields[$field]['render'])) {
            throw new \Exception('Render field key doesnt exists');
        }

        $key = $allDefaultFields[$field]['render'];
        switch ($key) {
            case 'text':
                return $this->textRenderField($object, $field);

                break;
            case 'criteria':
                return $this->criteriaRenderField($object, $session);
            case 'role':
                return $this->roleRenderField($object, $field);
            default:
                $data = '';
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getAllCustomListFields()
    {
        if ($this->allDefaultFields) {
            return $this->allDefaultFields;
        }

        $allFields = [
            'id' => [
                'label' => $this->translator->trans('Video ID'),
                'render' => 'text',
                'render_params' => [
                    'sort' => false,
                    'break-word' => true,
                ],
            ],
            'series.id' => [
                'label' => $this->translator->trans('Series ID'),
                'render' => 'text',
                'render_params' => [
                    'sort' => false,
                    'break-word' => true,
                ],
            ],
            'title' => [
                'label' => $this->translator->trans('Title'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'seriesTitle' => [
                'label' => $this->translator->trans('Series title'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'subtitle' => [
                'label' => $this->translator->trans('Subtitle'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'description' => [
                'label' => $this->translator->trans('Description'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'comments' => [
                'label' => $this->translator->trans('Comments'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'keywords' => [
                'label' => $this->translator->trans('Keywords'),
                'render' => 'text',
                'render_params' => [
                    'sort' => false,
                    'break-word' => true,
                ],
            ],
            'copyright' => [
                'label' => $this->translator->trans('Copyright'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'license' => [
                'label' => $this->translator->trans('License'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'public_date' => [
                'label' => $this->translator->trans('Publication date'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'record_date' => [
                'label' => $this->translator->trans('Record Date'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'tracks.name' => [
                'label' => $this->translator->trans('Track name'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => true,
                ],
            ],
            'numerical_id' => [
                'label' => $this->translator->trans('Numerical video ID'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'series.numerical_id' => [
                'label' => $this->translator->trans('Numerical series ID'),
                'render' => 'text',
                'render_params' => [
                    'sort' => false,
                    'break-word' => false,
                ],
            ],
            'type' => [
                'label' => $this->translator->trans('Type'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'duration' => [
                'label' => $this->translator->trans('Duration'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'numview' => [
                'label' => $this->translator->trans('Views'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'year' => [
                'label' => $this->translator->trans('Year'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'embeddedBroadcast' => [
                'label' => $this->translator->trans('Broadcast'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'status' => [
                'label' => $this->translator->trans('Status'),
                'render' => 'text',
                'render_params' => [
                    'sort' => true,
                    'break-word' => false,
                ],
            ],
            'groups' => [
                'label' => $this->translator->trans('Groups'),
                'render' => 'text',
                'render_params' => [
                    'sort' => false,
                    'break-word' => false,
                ],
            ],
            'pics' => [
                'label' => $this->translator->trans('Images'),
                'render' => 'img',
                'render_params' => [
                    'sort' => false,
                    'break-word' => false,
                ],
            ],
            'criteria' => [
                'label' => $this->translator->trans('Criteria'),
                'render' => 'criteria',
                'render_params' => [
                    'sort' => false,
                    'break-word' => false,
                ],
            ],
        ];

        $roles = $this->dm->getRepository(Role::class)->findAll();
        foreach ($roles as $role) {
            $allFields['role.'.$role->getCod()] = [
                'label' => $role->getName(),
                'render' => 'role',
                'render_params' => [
                    'sort' => false,
                ],
            ];
        }

        $this->allDefaultFields = $allFields;

        return $allFields;
    }

    /**
     * @param Request          $request
     * @param SessionInterface $session
     */
    private function checkAndSortCriteria(Request $request, SessionInterface $session)
    {
        $mappingSort = [
            'year' => 'record_date',
            'tracks.name' => 'tracks.originalName',
            'embeddedBroadcast' => 'embeddedBroadcast.name',
            'series.id' => 'series',
        ];

        $sort_type = $request->request->get('sort_type');
        if (in_array($sort_type, ['title', 'subtitle', 'seriesTitle', 'description', 'keywords'])) {
            $sort_type = implode('.', [$sort_type, $request->getLocale()]);
        } elseif (array_key_exists($sort_type, $mappingSort)) {
            $sort_type = $mappingSort[$sort_type];
        }

        $session->set('admin/unesco/element_sort', $sort_type);

        if ($session->get('admin/unesco/text', false) && !$request->request->get('sort')) {
            $sort_utype = 'score';
        } else {
            $sort_utype = $request->request->get('sort');
        }

        $session->set('admin/unesco/type', $sort_utype);
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
                $route = $this->router->generate('pumukitnewadmin_mms_index', ['id' => $text]);
                $text = "<a href='".$route."'>".(string) $text.'</a>';

                break;
            case 'title':
                $text = $object->getTitle();

                break;
            case 'seriesTitle':
                $text = $object->getSeries()->getTitle();
                $route = $this->router->generate('pumukitnewadmin_mms_index', ['id' => $object->getSeries()->getId()]);
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
                $text = $this->translator->trans($object->getStringType($type));

                break;
            case 'duration':
                $text = $object->getDurationString();

                break;
            case 'numview':
                $text = $object->getNumview();

                break;
            case 'year':
                $text = $object->getRecordDate();
                $text = $text->format('Y');

                break;
            case 'embeddedBroadcast':
                $text = $this->translator->trans($object->getEmbeddedBroadcast()->getName());

                break;
            case 'status':
                $text = $this->translator->trans($object->getStringStatus($object->getStatus()));

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
     * @param MultimediaObject $object
     * @param SessionInterface $session
     *
     * @return string
     */
    private function criteriaRenderField(MultimediaObject $object, SessionInterface $session)
    {
        if (!$session->has('UNESCO/criteria') || 0 === count($session->get('UNESCO/criteria'))) {
            return $this->translator->trans('Without criteria');
        }

        if (count($session->get('UNESCO/criteria')) > 1) {
            return $this->translator->trans('Multiple criteria');
        }

        $criteria = $session->get('UNESCO/criteria');
        $key = array_keys($criteria);

        $text = '';
        if (isset($key[0])) {
            $text = $this->getTextFromCriteria($object, $session, $key[0]);
        }

        return $text;
    }

    /**
     * @param MultimediaObject $object
     * @param SessionInterface $session
     * @param                  $key
     *
     * @return string
     */
    private function getTextFromCriteria(MultimediaObject $object, SessionInterface $session, $key)
    {
        foreach ($this->locales as $locale) {
            if (false !== stripos($key, '.'.$locale)) {
                $key = str_replace('.'.$locale, '', $key);
            }
        }

        $mappingFields = [
            '_id' => 'id',
            'tracks.originalName' => 'tracks.name',
            'tracks.duration' => 'duration',
            'embeddedBroadcasType' => 'embeddedBroadcast',
        ];

        if (array_key_exists($key, $mappingFields)) {
            $key = $mappingFields[$key];
        } elseif ('roles' === $key) {
            $criteria = $session->get('UNESCO/criteria');
            $roles = $criteria['roles'];
            $kRoles = array_keys($roles);
            $key = 'role.'.$kRoles[0];
        } elseif ('series' === $key) {
            $criteria = $session->get('UNESCO/criteria');
            $series = $criteria['series'];
            if (is_int($series)) {
                $key = 'series.numerical_id';
            } else {
                $key = 'series.id';
            }
        }

        return $this->textRenderField($object, $key);
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
        $roleCod = $role[1] ?? $role;

        $people = $object->getPeopleByRoleCod($roleCod, true);

        $text = '';
        foreach ($people as $person) {
            if ($person instanceof EmbeddedPerson) {
                $text .= $person->getName()."\n";
            }
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
}
