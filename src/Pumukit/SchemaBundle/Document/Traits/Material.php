<?php

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Pumukit\SchemaBundle\Document\Material as DocumentMaterial;

trait Material
{
    /*
        Warning - To use trait MATERIAL you must do:

        use Traits\Material {
            Traits\Material::__construct as private __MaterialConstruct;
        }

        and on class __construct():
        public function __construct()
        {
            ...
            $this->__MaterialConstruct();
            ...
        }
    */

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedMany(targetDocument="Material")
     */
    private $materials;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
    }

    /**
     * Add material.
     *
     * @param DocumentMaterial $material
     */
    public function addMaterial(DocumentMaterial $material)
    {
        $this->materials->add($material);
    }

    /**
     * Remove material.
     *
     * @param DocumentMaterial $material
     */
    public function removeMaterial(DocumentMaterial $material)
    {
        $this->materials->removeElement($material);
    }

    /**
     * Remove material by id.
     *
     * @param string $materialId
     */
    public function removeMaterialById($materialId)
    {
        $this->materials = $this->materials->filter(function ($material) use ($materialId) {
            return $material->getId() !== $materialId;
        });
    }

    /**
     * Up material by id.
     *
     * @param string $materialId
     */
    public function upMaterialById($materialId)
    {
        $this->reorderMaterialById($materialId, true);
    }

    /**
     * Down material by id.
     *
     * @param string $materialId
     */
    public function downMaterialById($materialId)
    {
        $this->reorderMaterialById($materialId, false);
    }

    /**
     * Contains material.
     *
     * @param DocumentMaterial $material
     *
     * @return bool
     */
    public function containsMaterial(DocumentMaterial $material)
    {
        return $this->materials->contains($material);
    }

    /**
     * Get materials.
     *
     * @return ArrayCollection
     */
    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * Get material by id.
     *
     * @param \MongoId|string $materialId
     *
     * @return null|DocumentMaterial
     */
    public function getMaterialById($materialId)
    {
        foreach ($this->materials as $material) {
            if ($material->getId() == $materialId) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get materials with tag.
     *
     * @param string $tag
     *
     * @return array
     */
    public function getMaterialsWithTag($tag)
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($material->containsTag($tag)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    /**
     * Get material with tag.
     *
     * @param string $tag
     *
     * @return null|DocumentMaterial
     */
    public function getMaterialWithTag($tag)
    {
        foreach ($this->materials as $material) {
            if ($material->containsTag($tag)) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get materials with all tags.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getMaterialsWithAllTags(array $tags)
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($material->containsAllTags($tags)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    /**
     * Get material with all tags.
     *
     * @param array $tags
     *
     * @return null|DocumentMaterial
     */
    public function getMaterialWithAllTags(array $tags)
    {
        foreach ($this->materials as $material) {
            if ($material->containsAllTags($tags)) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get materials with any tag.
     *
     * @param array $tags
     *
     * @return array
     */
    public function getMaterialsWithAnyTag(array $tags)
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($material->containsAnyTag($tags)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    /**
     * Get material with any tag.
     *
     * @param array $tags
     *
     * @return null|DocumentMaterial
     */
    public function getMaterialWithAnyTag(array $tags)
    {
        foreach ($this->materials as $material) {
            if ($material->containsAnyTag($tags)) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get filtered materials with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return array
     */
    public function getFilteredMaterialsWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = [])
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($any_tags && !$material->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$material->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $material->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $material->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $material;
        }

        return $r;
    }

    /**
     * Reorder material by id.
     *
     * @param string $materialId
     * @param bool   $up
     */
    private function reorderMaterialById($materialId, $up = true)
    {
        $snapshot = array_values($this->materials->toArray());
        $this->materials->clear();

        $out = [];
        foreach ($snapshot as $key => $material) {
            if ($material->getId() === $materialId) {
                $out[($key * 10) + ($up ? -11 : 11)] = $material;
            } else {
                $out[$key * 10] = $material;
            }
        }

        ksort($out);
        foreach ($out as $material) {
            $this->materials->add($material);
        }
    }
}
