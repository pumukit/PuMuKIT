<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use MongoDB\BSON\UTCDateTime;

/**
 * Class UNESCOService.
 */
class UNESCOService
{
    /** @var MultimediaObjectSearchService */
    private $multimediaObjectSearchService;

    public function __construct(
        MultimediaObjectSearchService $multimediaObjectSearchService
    ) {
        $this->multimediaObjectSearchService = $multimediaObjectSearchService;
    }

    public function addCriteria($query, $criteria, $locale)
    {
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
                        $locale
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
