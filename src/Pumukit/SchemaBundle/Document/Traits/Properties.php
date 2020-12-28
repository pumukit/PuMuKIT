<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Properties
{
    /**
     * @MongoDB\Field(type="raw")
     */
    private $properties = [];

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties($properties): void
    {
        $this->properties = $properties;
    }

    public function getProperty($key)
    {
        return $this->properties[$key] ?? null;
    }

    public function getPropertyAsDateTime($key)
    {
        if (isset($this->properties[$key])) {
            return \DateTime::createFromFormat('Y-m-d\TH:i:sT', $this->properties[$key]);
        }

        return null;
    }

    public function setProperty($key, $value): void
    {
        $this->properties[$key] = $value;
    }

    public function setPropertyAsDateTime($key, \DateTime $value): void
    {
        $this->properties[$key] = $value->format('c');
    }

    public function removeProperty($key): void
    {
        if (isset($this->properties[$key])) {
            unset($this->properties[$key]);
        }
    }
}
