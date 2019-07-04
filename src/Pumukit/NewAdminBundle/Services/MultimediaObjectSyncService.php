<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;

class MultimediaObjectSyncService
{
    private $dm;

    private $publishingDecisionCode = 'PUBDECISIONS';

    private $syncFields = [
        'publishingdecisions' => 'Publishing Decisions',
        'description' => 'Description',
        'comments' => 'Comments',
        'keywords' => 'Keywords',
        'copyright' => 'Copyright',
        'license' => 'License',
        'publicdate' => 'Publication Date',
        'recorddate' => 'Record Date',
        'headline' => 'Headline',
        'subseries' => 'Subseries',
        'owners' => 'Owners',
        'groups' => 'Groups',
    ];

    private $syncMethods = [
        'comments' => 'syncComments',
        'copyright' => 'syncCopyright',
        'description' => 'syncDescription',
        'groups' => 'syncGroups',
        'headline' => 'syncHeadline',
        'keywords' => 'syncKeywords',
        'license' => 'syncLicense',
        'owners' => 'syncOwners',
        'publicdate' => 'syncPublicDate',
        'publishingdecisions' => 'syncPublishingDecisions',
        'recorddate' => 'syncRecordDate',
        'subseries' => 'syncSubSeries',
    ];

    private $notCallUserFunc = [
        'tag' => 'syncTags',
        'role' => 'syncRoles',
    ];

    /**
     * MultimediaObjectSyncService constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * @return array
     */
    public function getSyncFields()
    {
        return $this->syncFields;
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     */
    public function getMultimediaObjectsToSync(MultimediaObject $multimediaObject)
    {
        return $this->dm->getRepository(MultimediaObject::class)->findBy(
            [
                'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE],
                'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
                'series' => new \MongoId($multimediaObject->getSeries()->getId()),
                '_id' => ['$ne' => new \MongoId($multimediaObject->getId())],
            ]
        );
    }

    /**
     * @param array            $multimediaObjects
     * @param MultimediaObject $originData
     * @param array            $syncFieldsSelected
     *
     * @return array
     */
    public function syncMetadata(array $multimediaObjects, MultimediaObject $originData, array $syncFieldsSelected)
    {
        $sync = [];
        foreach ($multimediaObjects as $multimediaObject) {
            $sync[] = $this->doSyncMetadata($multimediaObject, $originData, $syncFieldsSelected);
        }

        $this->dm->flush();

        return $sync;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     * @param array            $syncFieldsSelected
     *
     * @return bool
     */
    public function doSyncMetadata(MultimediaObject $multimediaObject, MultimediaObject $originData, array $syncFieldsSelected)
    {
        foreach ($syncFieldsSelected as $key => $field) {
            $case = explode('_', $key);

            if (!array_key_exists($case[1], $this->syncMethods) && !array_key_exists($case[1], $this->notCallUserFunc)) {
                return false;
            }

            if (array_key_exists($case[1], $this->syncMethods)) {
                $method = $this->syncMethods[$case[1]];
                call_user_func([$this, $method], $multimediaObject, $originData);
            } else {
                $method = $this->notCallUserFunc[$case[1]];
                call_user_func([$this, $method], $multimediaObject, $originData, $case[2]);
            }
        }

        return true;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncComments(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $comments = $originData->getComments();

        $multimediaObject->setComments($comments);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncCopyright(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $copyright = $originData->getCopyright();

        $multimediaObject->setCopyright($copyright);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncDescription(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $description = $originData->getI18nDescription();

        $multimediaObject->setI18nDescription($description);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncHeadline(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $line2 = $originData->getI18nLine2();

        $multimediaObject->setI18nLine2($line2);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncKeywords(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $keywords = $originData->getI18nKeywords();

        $multimediaObject->setI18nKeywords($keywords);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncLicense(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $license = $originData->getLicense();

        $multimediaObject->setLicense($license);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncPublicDate(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $publicDate = $originData->getPublicDate();

        $multimediaObject->setPublicDate($publicDate);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncRecordDate(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $recordDate = $originData->getRecordDate();

        $multimediaObject->setRecordDate($recordDate);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncSubSeries(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $subSeriesTitle = $originData->getProperty('subseriestitle');
        $subSeries = $originData->getProperty('subseries');

        $multimediaObject->setProperty('subseriestitle', $subSeriesTitle);
        $multimediaObject->setProperty('subseries', $subSeries);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncGroups(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        foreach ($multimediaObject->getGroups() as $group) {
            $multimediaObject->removeGroup($group);
        }

        $groups = $originData->getGroups();
        foreach ($groups as $group) {
            $multimediaObject->addGroup($group);
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     */
    public function syncOwners(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $roleOwner = $this->dm->getRepository(Role::class)->findOneBy(
            ['cod' => 'owner']
        );
        $role = $originData->getEmbeddedRole($roleOwner);

        $oldRole = $multimediaObject->getEmbeddedRole($roleOwner);
        if ($oldRole) {
            foreach ($oldRole->getPeople() as $person) {
                $multimediaObject->removePersonWithRole($person, $roleOwner);
            }
        }

        foreach ($role->getPeople() as $person) {
            $multimediaObject->addPersonWithRole($person, $roleOwner);
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     *
     * @return bool
     */
    public function syncPublishingDecisions(MultimediaObject $multimediaObject, MultimediaObject $originData)
    {
        $pubDecisionTag = $this->dm->getRepository(Tag::class)->findOneBy(
            ['cod' => $this->publishingDecisionCode]
        );

        if (!$pubDecisionTag) {
            return false;
        }

        foreach ($multimediaObject->getTags() as $tag) {
            if ($tag->isChildOf($pubDecisionTag)) {
                $multimediaObject->removeTag($tag);
            } elseif ($tag->getCod() === $pubDecisionTag->getCod()) {
                $multimediaObject->removeTag($pubDecisionTag);
            }
        }

        foreach ($originData->getTags() as $tag) {
            if ($tag->isChildOf($pubDecisionTag)) {
                $multimediaObject->addTag($tag);
            } elseif ($tag->getCod() === $pubDecisionTag->getCod()) {
                $multimediaObject->addTag($pubDecisionTag);
            }
        }

        return true;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     * @param $tagId
     */
    public function syncTags(MultimediaObject $multimediaObject, MultimediaObject $originData, $tagId)
    {
        $tag = $this->dm->getRepository(Tag::class)->findOneBy(
            ['_id' => new \MongoId($tagId)]
        );

        foreach ($multimediaObject->getTags() as $embeddedTag) {
            if ($embeddedTag->isDescendantOf($tag)) {
                $multimediaObject->removeTag($embeddedTag);
            } elseif ($embeddedTag->getCod() === $tag->getCod()) {
                $multimediaObject->removeTag($tag);
            }
        }

        foreach ($originData->getTags() as $embeddedTag) {
            if ($embeddedTag->isDescendantOf($tag)) {
                $multimediaObject->addTag($embeddedTag);
            } elseif ($embeddedTag->getCod() === $tag->getCod()) {
                $multimediaObject->addTag($tag);
            }
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param MultimediaObject $originData
     * @param $roleId
     */
    public function syncRoles(MultimediaObject $multimediaObject, MultimediaObject $originData, $roleId)
    {
        $role = $this->dm->getRepository(Role::class)->findOneBy(
            ['_id' => new \MongoId($roleId)]
        );

        $embeddedRole = $multimediaObject->getEmbeddedRole($role);
        if ($embeddedRole) {
            foreach ($embeddedRole->getPeople() as $person) {
                $multimediaObject->removePersonWithRole($person, $role);
            }
        }

        $originEmbeddedRole = $originData->getEmbeddedRole($role);
        if ($originEmbeddedRole) {
            foreach ($originEmbeddedRole->getPeople() as $person) {
                $multimediaObject->addPersonWithRole($person, $role);
            }
        }
    }
}
