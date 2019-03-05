<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Utils\Search\SearchUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    private $baseTagCod = 'UNESCO';
    private $configuredTag;

    private $allDefaultFields;

    /**
     * TagCatalogueService constructor.
     *
     * @param DocumentManager     $documentManager
     * @param TranslatorInterface $translator
     * @param                     $configuredTag
     */
    public function __construct(DocumentManager $documentManager, TranslatorInterface $translator, $configuredTag)
    {
        $this->dm = $documentManager;
        $this->translator = $translator;
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
            array('cod' => $tagCod)
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
        $newCriteria = array();
        $tag = array();

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
                    $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array('numerical_id' => intval($value)));
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
                } elseif (in_array($key, array('initPublicDate', 'finishPublicDate', 'initRecordDate', 'finishRecordDate'))) {
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
                } elseif (in_array($key, array('comments', 'license', 'copyright')) && !empty($value)) {
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
                $newCriteria['tags.cod'] = array('$all' => $tag);
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
        return array(
            'custom_field_1' => array(
                'label' => $this->translator->trans('Type'),
                'key' => 'type',
            ),
            'custom_field_2' => array(
                'label' => $this->translator->trans('Images'),
                'key' => 'pics',
            ),
            'custom_field_3' => array(
                'label' => $this->translator->trans('Series title'),
                'key' => 'seriesTitle',
            ),
            'custom_field_4' => array(
                'label' => $this->translator->trans('Title'),
                'key' => 'title',
            ),
            'custom_field_5' => array(
                'label' => $this->translator->trans('Duration'),
                'key' => 'duration',
            ),
            'custom_field_6' => array(
                'label' => $this->translator->trans('Record date'),
                'key' => 'record_date',
            ),
            'custom_field_7' => array(
                'label' => $this->translator->trans('Publication date'),
                'key' => 'public_date',
            ),
        );
    }

    /**
     * @param MultimediaObject $object
     * @param                  $field
     * @param                  $params
     *
     * @return string
     *
     * @throws \Exception
     */
    public function renderField(MultimediaObject $object, $field, $params)
    {
        if (!isset($this->allDefaultFields[$field]['render'])) {
            throw new \Exception('Render field key doesnt exists');
        }

        $key = $this->allDefaultFields[$field]['render'];
        switch ($key) {
            case 'text':
                $data = 'texto';
                break;
            case 'datetime':
                $data = 'datetime';
                break;
            default:
                $data = 'default';
        }

        if (isset($param['series_link'])) {
            $seriesID = $object->getSeries()->getId();
            $data = "<a href='".'a'."' title=''>".$data.'</a>';
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getAllCustomListFields()
    {
        $allFields = array(
            'id' => array(
                'label' => $this->translator->trans('Video ID'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'series.id' => array(
                'label' => $this->translator->trans('Series ID'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'title' => array(
                'label' => $this->translator->trans('Title'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'seriesTitle' => array(
                'label' => $this->translator->trans('Series title'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'subtitle' => array(
                'label' => $this->translator->trans('Subtitle'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'description' => array(
                'label' => $this->translator->trans('Description'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'comments' => array(
                'label' => $this->translator->trans('Comments'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'keywords' => array(
                'label' => $this->translator->trans('Keywords'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'copyright' => array(
                'label' => $this->translator->trans('Copyright'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'license' => array(
                'label' => $this->translator->trans('License'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'public_date' => array(
                'label' => $this->translator->trans('Publication date'),
                'render' => 'datetime',
                'render_params' => array(),
            ),
            'record_date' => array(
                'label' => $this->translator->trans('Record date'),
                'render' => 'datetime',
                'render_params' => array(),
            ),
            'tracks.name' => array(
                'label' => $this->translator->trans('Track name'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'numerical_id' => array(
                'label' => $this->translator->trans('Numerical video ID'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'series.numerical_id' => array(
                'label' => $this->translator->trans('Numerical series ID'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'type' => array(
                'label' => $this->translator->trans('Type'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'duration' => array(
                'label' => $this->translator->trans('Duration'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'year' => array(
                'label' => $this->translator->trans('Year'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'embeddedBroadcast' => array(
                'label' => $this->translator->trans('Broadcast'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'status' => array(
                'label' => $this->translator->trans('Status'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'groups' => array(
                'label' => $this->translator->trans('Groups'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'pics' => array(
                'label' => $this->translator->trans('Images'),
                'render' => 'text',
                'render_params' => array(),
            ),
            'criteria' => array(
                'label' => $this->translator->trans('Criteria'),
                'render' => 'text',
                'render_params' => array(),
            ),
        );

        $roles = $this->dm->getRepository('PumukitSchemaBundle:Role')->findAll();
        foreach ($roles as $role) {
            $allFields['role.'.$role->getCod()] = array(
                'label' => $role->getName(),
                'render' => 'role',
                'render_params' => array(),
            );
        }

        return $allFields;
    }
}
