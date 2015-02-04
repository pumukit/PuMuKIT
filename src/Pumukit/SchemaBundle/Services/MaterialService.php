<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Material;
use Doctrine\ODM\MongoDB\DocumentManager;

class MaterialService
{
    private $dm;
    private $targetPath;
    private $targetUrl;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl)
    {
        $this->dm = $documentManager;
        $this->targetPath = $targetPath;
        $this->targetUrl = $targetUrl;
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
    public function addMaterialUrl(MultimediaObject $multimediaObject, $url, $formData)
    {
        $material = new Material();
        $material = $this->saveFormData($material, $formData);

        $material->setUrl($url);

        $multimediaObject->addMaterial($material);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Add a material from a file into the multimediaObject
     */
    public function addMaterialFile(MultimediaObject $multimediaObject, UploadedFile $materialFile, $formData)
    {
        $material = new Material();
        $material = $this->saveFormData($material, $formData);

        $path = $materialFile->move($this->targetPath."/".$multimediaObject->getId(), $materialFile->getClientOriginalName());

        $material->setPath($path);
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

    /**
     * Save form data of Material
     *
     * @return Material $material
     */
    private function saveFormData(Material $material, $formData)
    {
        if (array_key_exists('i18n_name', $formData)) {
            $material->setI18nName($formData['i18n_name']);
        }
        if (array_key_exists('hide', $formData)) {
            $material->setHide($formData['hide']);
        }
        if (array_key_exists('mime_type', $formData)) {
            $material->setMimeType($formData['mime_type']);
        }

        return $material;
    }
}
