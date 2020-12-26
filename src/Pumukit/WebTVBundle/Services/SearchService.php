<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\TagInterface;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;

class SearchService
{
    public const MULTIMEDIA_OBJECT = 0;
    public const SERIES = 1;

    /** @var DocumentManager */
    private $documentManager;
    private $parentTagCod;
    private $parentTagCodOptional;

    public function __construct(DocumentManager $documentManager, string $parentTagCod, ?string $parentTagCodOptional)
    {
        $this->documentManager = $documentManager;
        $this->parentTagCod = $parentTagCod;
        $this->parentTagCodOptional = $parentTagCodOptional;
    }

    public function getSearchTags(): array
    {
        return [
            $this->getParentTag(),
            $this->getOptionalParentTag(),
        ];
    }

    public function getParentTag(): TagInterface
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

    public function getOptionalParentTag(): ?TagInterface
    {
        $parentTagOptional = null;
        if ($this->parentTagCodOptional) {
            $parentTagOptional = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $this->parentTagCodOptional]);
        }

        return $parentTagOptional;
    }

    public function getYears(int $type = self::MULTIMEDIA_OBJECT): array
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

    public function getLanguages()
    {
        return $this->documentManager->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->distinct('tracks.language')
            ->getQuery()
            ->execute()
        ;
    }

    public function addTypeQueryBuilder(Builder $queryBuilder, ?string $typeFound): Builder
    {
        $type = '';
        switch ($typeFound) {
            case 'audio':
                $type = MultimediaObject::TYPE_AUDIO;

                break;
            case 'video':
                $type = MultimediaObject::TYPE_VIDEO;

                break;
            case 'external':
                $type = MultimediaObject::TYPE_EXTERNAL;

                break;
            default:
        }
        if ('' !== $type) {
            $queryBuilder->field('type')->equals($type);
        }

        return $queryBuilder;
    }

    public function addDurationQueryBuilder(Builder $queryBuilder, ?string $durationFound): Builder
    {
        if ('' === $durationFound) {
            return $queryBuilder;
        }

        if ('-5' === $durationFound) {
            $queryBuilder->field('tracks.duration')->lte(300);
        }
        if ('-10' === $durationFound) {
            $queryBuilder->field('tracks.duration')->lte(600);
        }
        if ('-30' === $durationFound) {
            $queryBuilder->field('tracks.duration')->lte(1800);
        }
        if ('-60' === $durationFound) {
            $queryBuilder->field('tracks.duration')->lte(3600);
        }
        if ('+60' === $durationFound) {
            $queryBuilder->field('tracks.duration')->gt(3600);
        }

        return $queryBuilder;
    }

    public function addSearchQueryBuilder(Builder $queryBuilder, string $locale, ?string $searchFound): Builder
    {
        $searchFound = trim($searchFound);

        if ((false !== strpos($searchFound, '*')) && (false === strpos($searchFound, ' '))) {
            $searchFound = str_replace('*', '.*', $searchFound);
            $mRegex = new Regex($searchFound, 'i');
            $queryBuilder->addOr($queryBuilder->expr()->field('title.'.$locale)->equals($mRegex));
            $queryBuilder->addOr($queryBuilder->expr()->field('people.people.name')->equals($mRegex));
        } elseif ('' !== $searchFound) {
            $queryBuilder->text(TextIndexUtils::cleanTextIndex($searchFound));
            $queryBuilder->language(TextIndexUtils::getCloseLanguage($locale));
        }

        return $queryBuilder;
    }

    public function addDateQueryBuilder(Builder $queryBuilder, ?string $startFound, ?string $endFound, ?string $yearFound, string $dateField = 'record_date'): Builder
    {
        if (null !== $yearFound && '' !== $yearFound) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:00', $yearFound));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:00', (int) $yearFound + 1));
            $queryBuilder->field($dateField)->gte($start);
            $queryBuilder->field($dateField)->lt($end);

            return $queryBuilder;
        }

        if (null !== $startFound && '' !== $startFound) {
            $start = \DateTime::createFromFormat('!Y-m-d', $startFound);
            $queryBuilder->field($dateField)->gt($start);
        }

        if (null !== $endFound && '' !== $endFound) {
            $end = \DateTime::createFromFormat('!Y-m-d', $endFound);
            $end->modify('+1 day');
            $queryBuilder->field($dateField)->lt($end);
        }

        return $queryBuilder;
    }

    public function addLanguageQueryBuilder(Builder $queryBuilder, ?string $languageFound): Builder
    {
        if (null !== $languageFound && '' !== $languageFound) {
            $queryBuilder->field('tracks.language')->equals($languageFound);
        }

        return $queryBuilder;
    }

    public function addTagsQueryBuilder(Builder $queryBuilder, array $tagsFound = null, TagInterface $blockedTag = null, bool $useTagAsGeneral = false): Builder
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
            $queryBuilder->field('tags.path')->notIn([new Regex($blockedTag->getPath().'.*\|/')]);
        }

        return $queryBuilder;
    }

    public function addValidSeriesQueryBuilder(Builder $queryBuilder): Builder
    {
        $validSeries = $this->documentManager->getRepository(MultimediaObject::class)
            ->createStandardQueryBuilder()
            ->distinct('series')
            ->getQuery()
            ->execute()
        ;

        return $queryBuilder->field('_id')->in($validSeries);
    }

    public function addLicenseQueryBuilder(Builder $queryBuilder, ?string $license): Builder
    {
        if (null === $license || '' === $license) {
            return $queryBuilder;
        }

        return $queryBuilder->field('license')->equals($license);
    }
}
