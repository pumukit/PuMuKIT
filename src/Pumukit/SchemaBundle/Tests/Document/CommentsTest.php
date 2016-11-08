<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Comments;

class CommentsTest extends \PHPUnit_Framework_TestCase
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

        $this->assertEquals($date, $comment->getDate());
        $this->assertEquals($text, $comment->getText());
        $this->assertEquals($multimedia_object_id, $comment->getMultimediaObjectId());
    }
}
