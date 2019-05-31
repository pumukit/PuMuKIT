<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;

/**
 * Class PlayerController.
 */
class PlayerController extends Controller implements WebTVControllerInterface
{
    /**
     * @param MultimediaObject $multimediaObject
     */
    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     */
    protected function getChapterMarks(MultimediaObject $multimediaObject)
    {
        //Get editor chapters for the editor template.
        //Once the chapter marks player plugin is created, this part won't be needed.
        $marks = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/marks')
            ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
            ->getQuery()->getSingleResult();

        $trimming = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Annotation::class)
            ->createQueryBuilder()
            ->field('type')->equals('paella/trimming')
            ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
            ->getQuery()->getSingleResult();

        $editorChapters = [];

        if ($marks) {
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
        }

        return $editorChapters;
    }
}
