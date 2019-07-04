<?php

namespace Pumukit\StatsBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Properties
{
    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $properties = [];

    /**
     * Get properties, null if none.
     *
     * @return string
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set properties.
     *
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Get property, null if none.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getProperty($key)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }

        return null;
    }

    /**
     * Get property as DateTime, FALSE on failure or null if none.
     *
     * @param string $key
     *
     * @return \DateTime|false|null
     */
    public function getPropertyAsDateTime($key)
    {
        if (isset($this->properties[$key])) {
            return \DateTime::createFromFormat('Y-m-d\TH:i:sT', $this->properties[$key]);
        }

        return null;
    }

    /**
     * Set property.
     *
     * @param string $key
     * @param string $value
     */
    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * Set property.
     *
     * @param string    $key
     * @param \DateTime $value
     */
    public function setPropertyAsDateTime($key, \DateTime $value)
    {
        $this->properties[$key] = $value->format('c');
    }

    /**
     * Remove property.
     *
     * @param string $key
     */
    public function removeProperty($key)
    {
        if (isset($this->properties[$key])) {
            unset($this->properties[$key]);
        }
    }
}
