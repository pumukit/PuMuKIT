<?php

namespace Pumukit\SchemaBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

trait Properties
{
    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $properties = array();

    /**
     * Get properties, null if none.
     *
     * @return array
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
