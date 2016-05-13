<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Doctrine\ODM\MongoDB\DocumentManager;

class EmbeddedBroadcastService
{
    private $dm;

    /**
     * Constructor
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
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
     * Create public embedded broadcast
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
        case EmbeddedBroadcast::TYPE_LDAP:
            $embeddedBroadcast->setType(EmbeddedBroadcast::TYPE_LDAP);
            $embeddedBroadcast->setName(EmbeddedBroadcast::NAME_LDAP);
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
}