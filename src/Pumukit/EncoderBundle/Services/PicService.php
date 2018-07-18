<?php

namespace Pumukit\EncoderBundle\Services;

use Symfony\Component\Filesystem;
use Symfony\Component\Finder\Finder;
use Doctrine\ODM\MongoDB\DocumentManager;

class PicService
{
    private $dm;
    private $fileSystem;
    private $finder;

    /**
     * PicService constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;

        $this->fileSystem = new Filesystem\Filesystem();
        $this->finder = new Finder();
    }

    /**
     * @param null $id
     * @param null $size
     * @param null $path
     * @param null $extension
     * @param null $tags
     * @param null $exists
     * @param null $type
     *
     * @return \Doctrine\MongoDB\Iterator|mixed|null
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findPicsByOptions($id = null, $size = null, $path = null, $extension = null, $tags = null, $exists = null, $type = null)
    {
        if ('series' == $type) {
            $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:Series');
        } else {
            $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        }

        $pipeline = array(array('$match' => array('pics' => array('$exists' => true))));
        array_push($pipeline, array('$unwind' => '$pics'));

        if ($id) {
            $match = array(
                '$match' => array('_id' => new \MongoId($id)),
            );

            array_push($pipeline, $match);
        }
        if ($path) {
            $match = array(
                '$match' => array('pics.path' => array('$regex' => $path, '$options' => 'i')),
            );

            array_push($pipeline, $match);
        }

        if ($tags) {
            $match = array(
                '$match' => array('pics.tags' => array('$in' => $tags)),
            );

            array_push($pipeline, $match);
        }

        if ($extension) {
            $orCondition = array();
            foreach ($extension as $ext) {
                if (false !== strpos($ext, '.')) {
                    $orCondition[] = array('pics.path' => array('$regex' => $ext, '$options' => 'i'));
                } else {
                    $orCondition[] = array('pics.path' => array('$regex' => '.'.$ext, '$options' => 'i'));
                }
            }

            $match = array('$match' => array('$or' => $orCondition));

            array_push($pipeline, $match);
        }

        $group = array('$group' => array(
            '_id' => null,
            'pics' => array('$addToSet' => '$pics'),
        ));

        array_push($pipeline, $group);

        $pics = $collection->aggregate($pipeline);
        $data = $pics->toArray();
        $pics = reset($data);

        if (isset($this->exists)) {
            $pics = $this->checkExistsFiles($pics, $exists);
        }

        if (isset($this->size)) {
            $pics = $this->checkSizeFiles($pics, $size);
        }

        return $pics;
    }

    /**
     * @param null $data
     * @param      $exists
     *
     * @return array $data
     */
    public function checkExistsFiles($data = null, $exists)
    {
        $filterResult = array();

        foreach ($data['pics'] as $pic) {
            if ('true' === $exists or '1' === $exists) {
                if ($this->fileSystem->exists($pic['path'])) {
                    $filterResult[] = $pic;
                }
            } else {
                if (!$this->fileSystem->exists($pic['path'])) {
                    $filterResult[] = $pic;
                }
            }
        }

        $data['pics'] = $filterResult;

        return $data;
    }

    /**
     * @param null $data
     * @param      $size
     *
     * @return array $data
     */
    public function checkSizeFiles($data = null, $size)
    {
        $filterResult = array();

        foreach ($data['pics'] as $pic) {
            if (!$this->fileSystem->exists($pic['path'])) {
                $filterResult[] = 'File not found '.$pic['path'];
            } else {
                $files = $this->finder->files()->name(basename($pic['path']))->size('< '.$size.'K')->in(dirname($pic['path']));
                foreach ($files as $file) {
                    if ($file->getPathName() == $pic['path']) {
                        $filterResult[] = $pic;
                    }
                }
            }
        }

        $data['pics'] = $filterResult;

        return $data;
    }

    /**
     * @param $id
     * @param $size
     * @param $path
     * @param $extension
     * @param $tags
     * @param $exists
     * @param $type
     *
     * @return array
     *
     * @throws \Exception
     */
    public function formatInputs($id, $size, $path, $extension, $tags, $exists, $type)
    {
        if ($extension) {
            $extension = $this->getAllInputExtensions($extension);
            if (empty($extension)) {
                throw new \Exception('Please check extensions input');
            }
        }

        if ($path) {
            $pathExists = $this->checkPath($path);
            if (!$pathExists) {
                throw new \Exception("Path doesn't exists");
            }
        }

        if ($tags) {
            $tags = $this->getAllInputTags($tags);
            if (empty($tags)) {
                throw new \Exception('Please check tags input');
            }
        }

        return array($id, $size, $path, $extension, $tags, $exists, $type);
    }

    /**
     * @param $extension
     *
     * @return array
     */
    private function getAllInputExtensions($extension)
    {
        $extension = trim($extension);
        if (false !== strpos($extension, ',')) {
            $aExtensions = explode(',', $extension);
        } else {
            $aExtensions = array($extension);
        }

        array_map('trim', $aExtensions);
        array_filter($aExtensions, function ($value) { return '' !== $value; });

        return $aExtensions;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    private function checkPath($path)
    {
        return $this->fileSystem->exists($path);
    }

    /**
     * @param $tags
     *
     * @return array
     */
    private function getAllInputTags($tags)
    {
        $tags = trim($tags);
        if (false !== strpos($tags, ',')) {
            $aTags = explode(',', $tags);
        } else {
            $aTags = array($tags);
        }

        array_map('trim', $aTags);
        array_filter($aTags, function ($value) { return '' !== $value; });

        return $aTags;
    }
}
