<?php

declare(strict_types=1);

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
     * @MongoDB\EmbedMany(targetDocument=Material::class)
     */
    private $materials;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
    }

    public function addMaterial(DocumentMaterial $material): void
    {
        $this->materials->add($material);
    }

    public function removeMaterial(DocumentMaterial $material): void
    {
        $this->materials->removeElement($material);
    }

    public function removeMaterialById($materialId): void
    {
        $this->materials = $this->materials->filter(function ($material) use ($materialId) {
            return $material->getId() !== $materialId;
        });
    }

    public function upMaterialById($materialId): void
    {
        $this->reorderMaterialById($materialId);
    }

    public function downMaterialById($materialId): void
    {
        $this->reorderMaterialById($materialId, false);
    }

    public function containsMaterial(DocumentMaterial $material): bool
    {
        return $this->materials->contains($material);
    }

    public function getMaterials(): ArrayCollection
    {
        return $this->materials;
    }

    public function getMaterialById($materialId)
    {
        foreach ($this->materials as $material) {
            if ($material->getId() === $materialId) {
                return $material;
            }
        }

        return null;
    }

    public function getMaterialsWithTag($tag): array
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($material->containsTag($tag)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    public function getMaterialWithTag($tag)
    {
        foreach ($this->materials as $material) {
            if ($material->containsTag($tag)) {
                return $material;
            }
        }

        return null;
    }

    public function getMaterialsWithAllTags(array $tags): array
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($material->containsAllTags($tags)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    public function getMaterialWithAllTags(array $tags)
    {
        foreach ($this->materials as $material) {
            if ($material->containsAllTags($tags)) {
                return $material;
            }
        }

        return null;
    }

    public function getMaterialsWithAnyTag(array $tags): array
    {
        $r = [];

        foreach ($this->materials as $material) {
            if ($material->containsAnyTag($tags)) {
                $r[] = $material;
            }
        }

        return $r;
    }

    public function getMaterialWithAnyTag(array $tags)
    {
        foreach ($this->materials as $material) {
            if ($material->containsAnyTag($tags)) {
                return $material;
            }
        }

        return null;
    }

    public function getFilteredMaterialsWithTags(array $any_tags = [], array $all_tags = [], array $not_any_tags = [], array $not_all_tags = []): array
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

    private function reorderMaterialById($materialId, $up = true): void
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
