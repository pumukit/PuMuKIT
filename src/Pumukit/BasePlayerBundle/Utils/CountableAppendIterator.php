<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Utils;

/**
 * Extends the AppendIterator class to implement the Countable interface.
 *
 * This class allows the count() function to be used on the instances of this class, thus
 * eliminating the need for remembering which results should be counted with 'count()' and
 * which ones with 'iterator_count()'.
 */
class CountableAppendIterator extends \AppendIterator implements \Countable
{
    public function count()
    {
        return iterator_count($this);
    }
}
