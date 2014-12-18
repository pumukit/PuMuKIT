<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Material;
use Doctrine\ODM\MongoDB\DocumentManager;

class MaterialService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * Add Material to Multimedia Object
     */
    public function addMaterialToMultimediaObject(MultimediaObject $multimediaObject, Material $material)
    {
        $multimediaObject->addMaterial($material);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Update Material in Multimedia Object
     */
    public function updateMaterialInMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Set a material from an url into the multimediaObject
     */
    public function addMaterialUrl(MultimediaObject $multimediaObject, $materialUrl)
    {
        //TODO check URL is valid and a image.
        $material = new Material();
        $material->setUrl($materialUrl);

        $multimediaObject->addMaterial($material);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Add a material from a file into the multimediaObject
     */
    public function addMaterialFile(MultimediaObject $multimediaObject, File $materialFile)
    {
        //TODO check file is mimetype
        //TODO delete double slash "//"
        $path = $materialFile->move($this->targetPath."/".$multimediaObject->getId(), $materialFile->getClientOriginalName());

        $material = new Material();
        $material->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));

        $multimediaObject->addMaterial($material);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Remove Material from Multimedia Object
     */
    public function removeMaterialFromMultimediaObject(MultimediaObject $multimediaObject, $materialId)
    {
        $multimediaObject->removeMaterialById($materialId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Up Material in Multimedia Object
     */
    public function upMaterialInMultimediaObject(MultimediaObject $multimediaObject, $materialId)
    {
        $multimediaObject->upMaterialById($materialId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Down Material in Multimedia Object
     */
    public function downMaterialInMultimediaObject(MultimediaObject $multimediaObject, $materialId)
    {
        $multimediaObject->downMaterialById($materialId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }
}
