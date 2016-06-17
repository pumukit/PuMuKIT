<?php
namespace Pumukit\BasePlayerBundle\Utils;

class CountableAppendIterator extends \AppendIterator implements \Countable {
    public function count() {
        return iterator_count($this);
    }
}
