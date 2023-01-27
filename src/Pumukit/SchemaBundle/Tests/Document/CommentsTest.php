<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Comments;

/**
 * @internal
 *
 * @coversNothing
 */
class CommentsTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $date = new \DateTime('now');
        $text = 'description text';
        $multimedia_object_id = 1;

        $comment = new Comments();

        $comment->setDate($date);
        $comment->setText($text);
        $comment->setMultimediaObjectId($multimedia_object_id);

        static::assertEquals($date, $comment->getDate());
        static::assertEquals($text, $comment->getText());
        static::assertEquals($multimedia_object_id, $comment->getMultimediaObjectId());
    }
}
