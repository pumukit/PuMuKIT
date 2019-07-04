<?php

namespace Pumukit\EncoderBundle\Services;

use Pumukit\SchemaBundle\Document\Pic;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder\Finder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PicService
{
    private $dm;
    private $fileSystem;
    private $finder;
    private $mmsPicService;
    private $max_width = 1920;
    private $max_height = 1080;

    /**
     * PicService constructor.
     *
     * @param DocumentManager            $documentManager
     * @param MultimediaObjectPicService $mmsPicService
     */
    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService)
    {
        $this->dm = $documentManager;
        $this->mmsPicService = $mmsPicService;

        $this->fileSystem = new Filesystem\Filesystem();
        $this->finder = new Finder();
    }

    /**
     * @param string|null $id
     * @param string|null $size
     * @param string|null $path
     * @param string|null $extension
     * @param string|null $tags
     * @param string|null $exists
     * @param string|null $type
     *
     * @return \Doctrine\MongoDB\Iterator|mixed|null
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findPicsByOptions($id = null, $size = null, $path = null, $extension = null, $tags = null, $exists = null, $type = null)
    {
        if ('series' == $type) {
            $collection = $this->dm->getDocumentCollection(Series::class);
        } else {
            $collection = $this->dm->getDocumentCollection(MultimediaObject::class);
        }

        $pipeline = [['$match' => ['pics' => ['$exists' => true]]]];
        array_push($pipeline, ['$unwind' => '$pics']);

        $match = [
            '$match' => ['pics.path' => ['$exists' => true]],
        ];

        array_push($pipeline, $match);

        if ($id) {
            $match = [
                '$match' => ['_id' => new \MongoId($id)],
            ];

            array_push($pipeline, $match);
        }
        if ($path) {
            $match = [
                '$match' => ['pics.path' => ['$regex' => $path, '$options' => 'i']],
            ];

            array_push($pipeline, $match);
        }

        if ($tags) {
            $match = [
                '$match' => ['pics.tags' => ['$in' => $tags]],
            ];

            array_push($pipeline, $match);
        }

        if ($extension) {
            $orCondition = [];
            foreach ($extension as $ext) {
                if (false !== strpos($ext, '.')) {
                    $orCondition[] = ['pics.path' => ['$regex' => $ext, '$options' => 'i']];
                } else {
                    $orCondition[] = ['pics.path' => ['$regex' => '.'.$ext, '$options' => 'i']];
                }
            }

            $match = ['$match' => ['$or' => $orCondition]];

            array_push($pipeline, $match);
        }

        $group = ['$group' => [
            '_id' => null,
            'pics' => ['$addToSet' => '$pics'],
        ]];

        array_push($pipeline, $group);

        $pics = $collection->aggregate($pipeline, ['cursor' => []]);
        $data = $pics->toArray();
        $pics = reset($data);

        if ($pics) {
            if (isset($exists)) {
                $pics = $this->checkExistsFiles($pics, $exists);
            }

            if (isset($size)) {
                $pics = $this->checkSizeFiles($pics, $size);
            }
        }

        return $pics;
    }

    /**
     * @param string $data
     * @param string $exists
     *
     * @return array $data
     */
    public function checkExistsFiles($data, $exists)
    {
        $filterResult = [];

        foreach ($data['pics'] as $pic) {
            if ('true' === $exists || '1' === $exists) {
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
     * @param $data
     * @param $size
     *
     * @return array $data
     */
    public function checkSizeFiles($data, $size)
    {
        $filterResult = [];

        foreach ($data['pics'] as $pic) {
            $this->finder = new Finder();
            if (!$this->fileSystem->exists($pic['path'])) {
                $filterResult[] = 'File not found '.$pic['path'];
            } else {
                $files = $this->finder->files()->name(basename($pic['path']))->size('> '.$size.'K')->in(dirname($pic['path']));
                foreach ($files as $file) {
                    if ($file->getPathName() === $pic['path']) {
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

        return [$id, $size, $path, $extension, $tags, $exists, $type];
    }

    /**
     * @param       $data
     * @param array $params
     * @param bool  $no_replace
     *
     * @return array
     *
     * @throws \Exception
     */
    public function convertImage($data, array $params, $no_replace = false)
    {
        if (!isset($data['pics']) || empty($data['pics'])) {
            throw new \Exception('No pics found');
        }

        $output = [];

        foreach ($data['pics'] as $pic) {
            $ext = pathinfo($pic['path'], PATHINFO_EXTENSION);
            $picPath = $this->createFromPic($pic, $params, $no_replace, $ext);

            $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->findOneBy(
                ['pics.path' => $pic['path']]
            );

            if (!$multimediaObject) {
                $output[] = 'Multimedia Object not found by path '.$pic['path'];
                continue;
            }

            if ($no_replace) {
                try {
                    $newPic = $this->generateNewPic($multimediaObject, $picPath, $pic);
                    $this->hideOriginalImage($multimediaObject, $pic);

                    $multimediaObject->addPic($newPic);

                    $output[] = 'Create new image - Multimedia object '.$multimediaObject->getId().' and image path '.$picPath;
                } catch (\Exception $exception) {
                    $output[] = 'Create new image - Multimedia object '.$multimediaObject->getId().' error trying to add new pic';
                    continue;
                }
            } else {
                if ($picPath !== $pic['path']) {
                    try {
                        $this->updateOriginalImage($multimediaObject, $picPath, $pic);
                    } catch (\Exception $exception) {
                        $output[] = 'Override - Multimedia object '.$multimediaObject->getId().' error trying to update original image '.$picPath;
                        continue;
                    }
                    $output[] = 'Override - Updated path for multimedia object '.$picPath;
                } else {
                    $output[] = 'Override - Multimedia object have the same path for the image '.$picPath;
                }
            }

            $this->dm->flush();
        }

        return $output;
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
            $aExtensions = [$extension];
        }

        array_map('trim', $aExtensions);
        array_filter($aExtensions, function ($value) {
            return '' !== $value;
        });

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
            $aTags = [$tags];
        }

        array_map('trim', $aTags);
        array_filter($aTags, function ($value) {
            return '' !== $value;
        });

        return $aTags;
    }

    /**
     * @param $pic
     * @param $params
     * @param $no_replace
     * @param $ext
     *
     * @return mixed|string
     */
    private function createFromPic($pic, $params, $no_replace, $ext)
    {
        [$originalWidth, $originalHeight] = getimagesize($pic['path']);

        $width = $params['max_width'] ?? 0;
        $height = $params['max_height'] ?? 0;

        [$width, $height] = $this->preserveAspectRatio($width, $height, $originalWidth, $originalHeight);

        $image_p = \imagecreatetruecolor($width, $height);
        if ('png' === $ext) {
            $image = \imagecreatefrompng($pic['path']);
        } else {
            $image = \imagecreatefromjpeg($pic['path']);
        }

        \imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

        if ($no_replace) {
            $name = dirname($pic['path']).'/'.rand().'.jpg';
            \imagejpeg($image_p, $name, $params['quality']);
        } else {
            $name = $pic['path'];
            $name = str_replace($ext, 'jpg', $name);
            \imagejpeg($image_p, $name, $params['quality']);
        }

        return $name;
    }

    /**
     * @param $width
     * @param $height
     * @param $originalWidth
     * @param $originalHeight
     *
     * @return array
     */
    private function preserveAspectRatio($width, $height, $originalWidth, $originalHeight)
    {
        if (0 == $width && 0 == $height) {
            $width = $this->max_width;
        }

        $exceededRatio = 0;
        if ($height <= $width && $originalWidth > $width) {
            $exceededRatio = $originalWidth / $width;
        } elseif ($height > $width && $originalHeight > $height) {
            $exceededRatio = $originalHeight / $height;
        }

        if ($exceededRatio > 0) {
            $width = $originalWidth / $exceededRatio;
            $height = $originalHeight / $exceededRatio;
        } else {
            $width = $originalWidth;
            $height = $originalHeight;
        }

        return [$width, $height];
    }

    /**
     * @param $multimediaObject
     * @param $picPath
     * @param $pic
     *
     * @return Pic
     */
    private function generateNewPic($multimediaObject, $picPath, $pic)
    {
        $url = $this->mmsPicService->getTargetUrl($multimediaObject);

        $newPic = new Pic();
        $newPic->setPath($picPath);
        $newPic->setUrl($url.'/'.basename($picPath));

        $newPic->setSize(filesize($picPath));
        $newPic->setHide(false);
        $newPic->addTag('refactor_image');

        [$width, $height, $type, $attributes] = \getimagesize($picPath);

        $newPic->setWidth($width);
        $newPic->setHeight($height);
        $newPic->setMimeType(\image_type_to_mime_type($type));

        $newPic->setProperty('referer', $pic['path']);

        $this->dm->persist($newPic);

        return $newPic;
    }

    /**
     * @param $multimediaObject
     * @param $pic
     */
    private function hideOriginalImage($multimediaObject, $pic)
    {
        foreach ($multimediaObject->getPics() as $mmsPic) {
            if ($mmsPic->getPath() === $pic['path']) {
                $mmsPic->setHide(true);
                break;
            }
        }
    }

    /**
     * @param $multimediaObject
     * @param $picPath
     * @param $pic
     */
    private function updateOriginalImage($multimediaObject, $picPath, $pic)
    {
        $url = $this->mmsPicService->getTargetUrl($multimediaObject);
        $url .= '/'.basename($picPath);

        [$width, $height, $type, $attributes] = \getimagesize($picPath);

        foreach ($multimediaObject->getPics() as $mmsPic) {
            if ($mmsPic->getPath() === $pic['path']) {
                $mmsPic->setPath($picPath);
                $mmsPic->setUrl($url);
                $mmsPic->setSize(filesize($picPath));
                $mmsPic->setWidth($width);
                $mmsPic->setHeight($height);
                $mmsPic->setMimeType(\image_type_to_mime_type($type));
                $mmsPic->addTag('overrided');
                break;
            }
        }
    }
}
