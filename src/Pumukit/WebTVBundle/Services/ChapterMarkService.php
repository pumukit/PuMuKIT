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
            ->getQuery()->getSingleResult();

        $trimming = $this->documentManager
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/trimming')
            ->field('multimediaObject')->equals(new ObjectId($multimediaObject->getId()))
            ->getQuery()->getSingleResult();

        $editorChapters = [];

        if ($marks) {
            $marks = json_decode($marks->getValue(), true);
            if ($trimming) {
                $trimming = json_decode($trimming->getValue(), true);
                if (isset($trimming['trimming'])) {
                    $trimming = $trimming['trimming'];
                }

                foreach ($marks['marks'] as $chapter) {
                    $time = $chapter['s'];
                    if ($trimming['start'] <= $time && $trimming['end'] >= $time) {
                        $editorChapters[] = [
                            'title' => $chapter['name'],
                            'real_time' => $time,
                            'time_to_show' => $time - $trimming['start'],
                        ];
                    }
                }
            }

            usort($editorChapters, static function ($a, $b) {
                return $a['real_time'] > $b['real_time'];
            });
        }

        return $editorChapters;
    }
}
