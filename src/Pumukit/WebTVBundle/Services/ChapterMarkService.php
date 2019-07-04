<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * Class LinkService.
 */
class ChapterMarkService
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @throws \MongoException
     *
     * @return array
     */
    public function getChapterMarks(MultimediaObject $multimediaObject)
    {
        //Get editor chapters for the editor template.
        //Once the chapter marks player plugin is created, this part won't be needed.
        $marks = $this->documentManager
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/marks')
            ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
            ->getQuery()->getSingleResult();

        $trimming = $this->documentManager
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/trimming')
            ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
            ->getQuery()->getSingleResult();

        $editorChapters = [];

        if (!$marks) {
            return $editorChapters;
        }

        $marks = json_decode($marks->getValue(), true);
        if ($trimming) {
            $trimming = json_decode($trimming->getValue(), true);
            if (isset($trimming['trimming'])) {
                $trimming = $trimming['trimming'];
            }

            foreach ($marks['marks'] as $chapt) {
                $time = $chapt['s'];
                if ($trimming['start'] <= $time && $trimming['end'] >= $time) {
                    $editorChapters[] = [
                        'title' => $chapt['name'],
                        'real_time' => $time,
                        'time_to_show' => $time - $trimming['start'],
                    ];
                }
            }
        }

        usort(
            $editorChapters,
            function ($a, $b) {
                return $a['real_time'] > $b['real_time'];
            }
        );

        return $editorChapters;
    }
}
