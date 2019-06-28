<?php

namespace Pumukit\WebTVBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;

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
     * @return array
     *
     * @throws \Exception
     */
    public function getSearchTags()
    {
        return [
            $this->getParentTag(),
            $this->getOptionalParentTag(),
        ];
    }

    /**
     * @return null|object|Tag
     *
     * @throws \Exception
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
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
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

        $yearResults = $this->documentManager->getDocumentCollection($class)->aggregate($pipeline, array('cursor' => array()));
        $years = [];

        foreach ($yearResults as $year) {
            $years[] = $year['_id'];
        }

        return $years;
    }
}
