<?php

namespace Pumukit\SchemaBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class ArcaFilter extends BsonFilter
{

  public function addFilterCriteria(ClassMetadata $targetDocument)
  {
    if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
      return array("status" => 0, "tags.cod" => $this->getParameter("pub_channel_tag"));
    }
  }
}