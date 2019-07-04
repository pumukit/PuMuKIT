<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;

class SearchService
{
    const MULTIMEDIA_OBJECT = 0;
    const SERIES = 1;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    private $parentTagCod;
    private $parentTagCodOptional;

    public function __construct(DocumentManager $documentManager, $parentTagCod, $parentTagCodOptional)
    {
        $this->documentManager = $documentManager;
        $this->parentTagCod = $parentTagCod;
        $this->parentTagCodOptional = $parentTagCodOptional;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function getSearchTags()
    {
        return [
            $this->getParentTag(),
            $this->getOptionalParentTag(),
        ];
    }

    /**
     * @throws \Exception
     *
     * @return null|object|Tag
     */
    public function getParentTag()
    {
        $parentTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $this->parentTagCod]);
        if (!isset($parentTag)) {
            throw new \Exception(
                sprintf(
                    'The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)',
                    $this->parentTagCod
                )
            );
        }

        return $parentTag;
    }

    /**
     * @return null|object|Tag
     */
    public function getOptionalParentTag()
    {
        $parentTagOptional = null;
        if ($this->parentTagCodOptional) {
            $parentTagOptional = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $this->parentTagCodOptional]);
        }

        return $parentTagOptional;
    }

    /**
     * @param int $type
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return array
     */
    public function getYears($type = self::MULTIMEDIA_OBJECT)
    {
        if (self::MULTIMEDIA_OBJECT === $type) {
            $pipeline = [
                ['$group' => ['_id' => ['$year' => '$public_date']]],
                ['$sort' => ['_id' => 1]],
            ];
            $class = Series::class;
        } else {
            $pipeline = [
                ['$match' => ['status' => MultimediaObject::STATUS_PUBLISHED]],
                ['$group' => ['_id' => ['$year' => '$record_date']]],
                ['$sort' => ['_id' => 1]],
            ];
            $class = MultimediaObject::class;
        }

        $yearResults = $this->documentManager->getDocumentCollection($class)->aggregate($pipeline, ['cursor' => []]);
        $years = [];

        foreach ($yearResults as $year) {
            $years[] = $year['_id'];
        }

        return $years;
    }

    /**
     * @return mixed
     */
    public function getLanguages()
    {
        return $this->documentManager->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->distinct('tracks.language')
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param Builder $queryBuilder
     * @param string  $typeFound
     *
     * @return Builder
     */
    public function addTypeQueryBuilder(Builder $queryBuilder, $typeFound)
    {
        if ('' != $typeFound) {
            $queryBuilder->field('type')->equals(
                ('audio' == $typeFound) ? Multimediaobject::TYPE_AUDIO : Multimediaobject::TYPE_VIDEO
            );
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param string  $durationFound
     *
     * @return Builder
     */
    public function addDurationQueryBuilder(Builder $queryBuilder, $durationFound)
    {
        if ('' != $durationFound) {
            if ('-5' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(300);
            }
            if ('-10' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(600);
            }
            if ('-30' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(1800);
            }
            if ('-60' == $durationFound) {
                $queryBuilder->field('tracks.duration')->lte(3600);
            }
            if ('+60' == $durationFound) {
                $queryBuilder->field('tracks.duration')->gt(3600);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param string  $locale
     * @param string  $searchFound
     *
     * @throws \MongoException
     *
     * @return Builder
     */
    public function addSearchQueryBuilder(Builder $queryBuilder, $locale, $searchFound)
    {
        $searchFound = trim($searchFound);

        if ((false !== strpos($searchFound, '*')) && (false === strpos($searchFound, ' '))) {
            $searchFound = str_replace('*', '.*', $searchFound);
            $mRegex = new \MongoRegex("/{$searchFound}/i");
            $queryBuilder->addOr($queryBuilder->expr()->field('title.'.$locale)->equals($mRegex));
            $queryBuilder->addOr($queryBuilder->expr()->field('people.people.name')->equals($mRegex));
        } elseif ('' != $searchFound) {
            $queryBuilder->field('$text')->equals([
                '$search' => TextIndexUtils::cleanTextIndex($searchFound),
                '$language' => TextIndexUtils::getCloseLanguage($locale),
            ]);
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param string  $startFound
     * @param string  $endFound
     * @param string  $yearFound
     * @param string  $dateField
     *
     * @return mixed
     */
    public function addDateQueryBuilder(Builder $queryBuilder, $startFound, $endFound, $yearFound, $dateField = 'record_date')
    {
        if (null !== $yearFound && '' !== $yearFound) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:00', $yearFound));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:00', ($yearFound) + 1));
            $queryBuilder->field($dateField)->gte($start);
            $queryBuilder->field($dateField)->lt($end);
        } else {
            if ('' != $startFound) {
                $start = \DateTime::createFromFormat('!Y-m-d', $startFound);
                $queryBuilder->field($dateField)->gt($start);
            }
            if ('' != $endFound) {
                $end = \DateTime::createFromFormat('!Y-m-d', $endFound);
                $end->modify('+1 day');
                $queryBuilder->field($dateField)->lt($end);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     * @param string  $languageFound
     *
     * @return Builder
     */
    public function addLanguageQueryBuilder(Builder $queryBuilder, $languageFound)
    {
        if ('' != $languageFound) {
            $queryBuilder->field('tracks.language')->equals($languageFound);
        }

        return $queryBuilder;
    }

    /**
     * @param Builder    $queryBuilder
     * @param null|array $tagsFound
     * @param null|Tag   $blockedTag
     * @param bool       $useTagAsGeneral
     *
     * @throws \MongoException
     *
     * @return Builder
     */
    public function addTagsQueryBuilder(Builder $queryBuilder, array $tagsFound = null, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        if (null !== $blockedTag) {
            $tagsFound[] = $blockedTag->getCod();
        }
        if (null !== $tagsFound) {
            $tagsFound = array_values(array_diff($tagsFound, ['All', '']));
        }

        if ($tagsFound && count($tagsFound) > 0) {
            $queryBuilder->field('tags.cod')->all($tagsFound);
        }

        if ($useTagAsGeneral && null !== $blockedTag) {
            $queryBuilder->field('tags.path')->notIn([new \MongoRegex('/'.preg_quote($blockedTag->getPath()).'.*\|/')]);
        }

        return $queryBuilder;
    }

    /**
     * @param Builder $queryBuilder
     *
     * @return Builder
     */
    public function addValidSeriesQueryBuilder(Builder $queryBuilder)
    {
        $validSeries = $this->documentManager->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->distinct('series')
            ->getQuery()
            ->execute()
            ->toArray()
        ;

        return $queryBuilder->field('_id')->in($validSeries);
    }
}
