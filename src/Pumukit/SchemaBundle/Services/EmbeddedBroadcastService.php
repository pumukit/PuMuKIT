<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Doctrine\ODM\MongoDB\DocumentManager;

class EmbeddedBroadcastService
{
    private $dm;
    private $dispatcher;
    private $disabledBroadcast;

    /**
     * Constructor
     */
    public function __construct(DocumentManager $documentManager, MultimediaObjectEventDispatcherService $dispatcher, $disabledBroadcast)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->disabledBroadcast = $disabledBroadcast;
    }

    /**
     * Set public embedded broadcast
     *
     * @param  MultimediaObject $multimediaObject
     * @param  string           $type
     * @param  boolean          $executeFlush
     * @return MultimediaObject
     */
    public function setByType(MultimediaObject $multimediaObject, $type = EmbeddedBroadcast::TYPE_PUBLIC, $executeFlush = true)
    {
        $embeddedBroadcast = $this->createEmbeddedBroadcastByType($type);
        $multimediaObject->setEmbeddedBroadcast($embeddedBroadcast);
        $this->dm->persist($multimediaObject);
        if ($executeFlush) {
            $this->dm->flush();
        }

        return $multimediaObject;
    }

    /**
     * Create embedded broadcast by type
     *
     * @param  string            $type
     * @return EmbeddedBroadcast
     */
    public function createEmbeddedBroadcastByType($type = null)
    {
        $embeddedBroadcast = new EmbeddedBroadcast();
        switch ($type) {
        case EmbeddedBroadcast::TYPE_PASSWORD:
            $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_PASSWORD);
            $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_PASSWORD);
            break;
        case EmbeddedBroadcast::TYPE_LOGIN:
            $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_LOGIN);
            $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_LOGIN);
            break;
        case EmbeddedBroadcast::TYPE_GROUPS:
            $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_GROUPS);
            $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_GROUPS);
            break;
        default:
            $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_PUBLIC);
            $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_PUBLIC);
            break;
        }

        return $embeddedBroadcast;
    }

    /**
     * Create public embedded broadcast
     *
     * @return EmbeddedBroadcast
     */
    public function createPublicEmbeddedBroadcast()
    {
        return $this->createEmbeddedBroadcastByType(EmbeddedBroadcast::TYPE_PUBLIC);
    }

    /**
     * Clone resource
     *
     * @param  EmbeddedBroadcast $embeddedBroadcast
     * @return EmbeddedBroadcast
     */
    public function cloneResource(EmbeddedBroadcast $embeddedBroadcast)
    {
        $new = new EmbeddedBroadcast();
        $new->setType($embeddedBroadcast->getType());
        $new->setName($embeddedBroadcast->getName());
        if ($password = $embeddedBroadcast->getPassword()) {
            $new->setPassword($password);
        }
        if ($groups = $embeddedBroadcast->getGroups()) {
            foreach ($groups as $group) {
                $new->addGroup($group);
            }
        }

        return $new;
    }

    /**
     * Get all broadcast types
     *
     * @return array
     */
    public function getAllTypes()
    {
        if ($this->disabledBroadcast) {
            return array(
                         EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                         EmbeddedBroadcast::TYPE_LOGIN => EmbeddedBroadcast::NAME_LOGIN,
                         EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS
                         );
        }

        return array(
                     EmbeddedBroadcast::TYPE_PUBLIC => EmbeddedBroadcast::NAME_PUBLIC,
                     EmbeddedBroadcast::TYPE_PASSWORD => EmbeddedBroadcast::NAME_PASSWORD,
                     EmbeddedBroadcast::TYPE_LOGIN => EmbeddedBroadcast::NAME_LOGIN,
                     EmbeddedBroadcast::TYPE_GROUPS => EmbeddedBroadcast::NAME_GROUPS
                     );
    }

    /**
     * Update type and name
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $type
     * @param boolean          $executeFlush
     */
    public function updateTypeAndName($type, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
        if (!$embeddedBroadcast) {
            $embeddedBroadcast = $this->createPublicEmbeddedBroadcast();
            $multimediaObject->setEmbeddedBroadcast($embeddedBroadcast);
        }
        $allTypes = $this->getAllTypes();
        if (($type !== $embeddedBroadcast->getType()) && array_key_exists($type, $allTypes)) {
            $embeddedBroadcast->setType($type);
            $embeddedBroadcast->setName($allTypes[$type]);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
}

    /**
     * Update password
     *
     * @param string           $password
     * @param MultimediaObject $multimediaObject
     * @param boolean          $executeFlush
     */
    public function updatePassword($password, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
        if (!$embeddedBroadcast) {
            $embeddedBroadcast = $this->createPublicEmbeddedBroadcast();
            $multimediaObject->setEmbeddedBroadcast($embeddedBroadcast);
        }
        if ($password !== $embeddedBroadcast->getPassword()) {
            $embeddedBroadcast->setPassword($password);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }

    /**
     * Add group to embeddedBroadcast
     *
     * @param Group $group
     * @param MultimediaObject $multimediaObject
     * @param boolean $executeFlush
     */
    public function addGroup(Group $group, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
        if (!$embeddedBroadcast) {
            $embeddedBroadcast = $this->createPublicEmbeddedBroadcast();
            $multimediaObject->setEmbeddedBroadcast($embeddedBroadcast);
        }
        if (!$embeddedBroadcast->containsGroup($group)) {
            $embeddedBroadcast->addGroup($group);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }

    /**
     * Delete group from embedded Broadcasr
     *
     * @param Group $group
     * @param MultimediaObject $multimediaObject
     * @param boolean $executeFlush
     */
    public function deleteGroup(Group $group, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
        if (!$embeddedBroadcast) {
            $embeddedBroadcast = $this->createPublicEmbeddedBroadcast();
            $multimediaObject->setEmbeddedBroadcast($embeddedBroadcast);
        }
        if ($embeddedBroadcast->containsGroup($group)) {
            $embeddedBroadcast->removeGroup($group);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }
}