<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class ChapterMarkService
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getChapterMarks(MultimediaObject $multimediaObject): array
    {
        $marks = $this->documentManager
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/marks')
            ->field('multimediaObject')->equals(new ObjectId($multimediaObject->getId()))
            ->getQuery()->getSingleResult()
        ;

        $trimming = $this->documentManager
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/trimming')
            ->field('multimediaObject')->equals(new ObjectId($multimediaObject->getId()))
            ->getQuery()->getSingleResult()
        ;

        if (!$marks instanceof Annotation) {
            return [];
        }

        $marks = json_decode($marks->getValue(), true, 512, JSON_THROW_ON_ERROR);
        $trimming = $trimming instanceof Annotation ? json_decode($trimming->getValue(), true, 512, JSON_THROW_ON_ERROR) : null;
        $trimming = $trimming['trimming'] ?? $trimming;
        $start = $trimming['start'] ?? null;
        $end = $trimming['end'] ?? null;
        $isTrimmed = null !== $start && null !== $end;

        $editorChapters = [];
        foreach ($marks['marks'] ?? [] as $chapter) {
            $time = $chapter['s'];
            if ($isTrimmed && ($time < $start || $time > $end)) {
                continue;
            }
            $editorChapters[] = [
                'title' => $chapter['name'],
                'real_time' => $time,
                'time_to_show' => $isTrimmed ? $time - $start : $time,
            ];
        }

        usort($editorChapters, static fn (array $a, array $b) => $a['real_time'] <=> $b['real_time']);

        return $editorChapters;
    }
}
