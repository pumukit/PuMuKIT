<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PumukitListPicsCommand extends ContainerAwareCommand
{
    private $dm;
    private $output;
    private $input;
    private $picCompressor;
    private $size = 100;
    private $path;
    private $extension;
    private $tags;
    private $exists;
    private $fileSystem;
    private $finder;
    private $type;

    protected function configure()
    {
        $this
            ->setName('pumukit:pics:list')
            ->setDescription('Pumukit list pics')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'List pics by path.')
            ->addOption('extension', null, InputOption::VALUE_OPTIONAL, 'List pics by extension.')
            ->addOption('tags', null, InputOption::VALUE_OPTIONAL, 'List pics by tag.')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type can be series or mmobj', 'mm')
            ->addOption('size', null, InputOption::VALUE_OPTIONAL, 'List pics greater than selected size in KB.')
            ->addOption('exists', null, InputOption::VALUE_OPTIONAL, 'List exists or not exists file pics.')
            ->setHelp(<<<'EOT'
            
Command to get all pics like selected filters.

Path example: --path="/mnt/storage/" ...
Extension examples: --extension=".jpg" or --extension="jpg" or --extension=".jpg,.png" or --extension="jpg,png". Can be all myme_types...
Tags examples: --tags="pumukit" or --tags="pumukit,auto,frame_0" ...
Size examples: --size=1 or --size=10 or --size=100 ...
Exists:
      If you defined exists option, the command will return exists images or not exists images.
      If you don't defined this option, the command will return all images
      Example:
              --exists="1" or --exists="0" or --exists="true" or --exists="false" ..
              
              
Example commands:

php app/console pumukit:pics:list --tags="master,youtube,hello" --extension=".png,.jpg" --exists=true --type="mm"
php app/console pumukit:pics:list --tags="master,youtube,hello" --extension=".png,.jpg" --exists=true --type="series"
php app/console pumukit:pics:list --tags="master,youtube" --extension=".png,.jpg" --exists=true
php app/console pumukit:pics:list --size=10000
php app/console pumukit:pics:list --tags="master" --size=10000

EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->picCompressor = $this->getContainer()->get('pumukitencoder.piccompressor');
        $this->output = $output;
        $this->input = $input;
        $this->size = $this->input->getOption('size');
        $this->path = $this->input->getOption('path');
        $this->extension = $this->input->getOption('extension');
        $this->tags = $this->input->getOption('tags');
        $this->exists = $this->input->getOption('exists');
        $this->type = $this->input->getOption('type');

        $this->fileSystem = new Filesystem();
        $this->finder = new Finder();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool|int|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validInput = $this->checkInputOptions();
        if (!$validInput['success']) {
            throw new \Exception($validInput['message']);
        }

        $this->formatInputs();
        $pics = $this->findByOptions();

        if ($pics) {
            if (isset($this->exists)) {
                $pics = $this->checkExistsFiles($pics);
            }

            if (isset($this->size)) {
                $pics = $this->checkSizeFiles($pics);
            }

            $this->showData($pics);
        } else {
            $this->output->writeln('No pics found');
        }

        return true;
    }

    /**
     * @return array
     */
    private function checkInputOptions()
    {
        $isValidInput = array('success' => true);
        if ($this->size && !is_string($this->size)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Size must be string, then will be converted';
        }

        if ($this->extension && !is_string($this->extension)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Extension must be string';
        }

        if ($this->path && !is_string($this->path)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Path must be string';
        }

        if ($this->tags && !is_string($this->tags)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Tags must be string';
        }

        if ($this->exists && !in_array(strtolower($this->exists), array('false', 'true', '1', '0'))) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Exists must be boolean';
        }

        if (!in_array($this->type, array('mm', 'series'))) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Type must be have the value series or mm';
        }

        return $isValidInput;
    }

    /**
     * @throws \Exception
     */
    private function formatInputs()
    {
        if ($this->extension) {
            $this->extension = $this->getAllInputExtensions();
            if (empty($this->extension)) {
                throw new \Exception('Please check extensions input');
            }
        }

        if ($this->path) {
            $pathExists = $this->checkPath();
            if (!$pathExists) {
                throw new \Exception("Path doesn't exists");
            }
        }

        if ($this->tags) {
            $this->tags = $this->getAllInputTags();
            if (empty($this->tags)) {
                throw new \Exception('Please check tags input');
            }
        }
    }

    /**
     * @return array
     */
    private function getAllInputExtensions()
    {
        $this->extension = trim($this->extension);
        if (false !== strpos($this->extension, ',')) {
            $aExtensions = explode(',', $this->extension);
        } else {
            $aExtensions = array($this->extension);
        }

        array_map('trim', $aExtensions);
        array_filter($aExtensions, function ($value) { return '' !== $value; });

        return $aExtensions;
    }

    /**
     * @return mixed
     */
    private function checkPath()
    {
        return $this->fileSystem->exists($this->path);
    }

    /**
     * @return array
     */
    private function getAllInputTags()
    {
        $this->tags = trim($this->tags);
        if (false !== strpos($this->tags, ',')) {
            $aTags = explode(',', $this->tags);
        } else {
            $aTags = array($this->tags);
        }

        array_map('trim', $aTags);
        array_filter($aTags, function ($value) { return '' !== $value; });

        return $aTags;
    }

    /**
     * @return mixed
     */
    private function findByOptions()
    {
        if ('series' == $this->type) {
            $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:Series');
        } else {
            $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        }

        $pipeline = array(array('$match' => array('pics' => array('$exists' => true))));
        array_push($pipeline, array('$unwind' => '$pics'));

        if ($this->path) {
            $match = array(
                '$match' => array('pics.path' => array('$regex' => $this->path, '$options' => 'i')),
            );

            array_push($pipeline, $match);
        }

        if ($this->tags) {
            $match = array(
                '$match' => array('pics.tags' => array('$in' => $this->tags)),
            );

            array_push($pipeline, $match);
        }

        if ($this->extension) {
            $orCondition = array();
            foreach ($this->extension as $ext) {
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

        return reset($data);
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function checkExistsFiles($data)
    {
        $filterResult = array();

        foreach ($data['pics'] as $pic) {
            if ('true' === $this->exists or '1' === $this->exists) {
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
     *
     * @return mixed
     */
    private function checkSizeFiles($data)
    {
        $filterResult = array();

        foreach ($data['pics'] as $pic) {
            if (!$this->fileSystem->exists($pic['path'])) {
                $this->output->writeln('File not found '.$pic['path']);
            } else {
                $files = $this->finder->files()->name(basename($pic['path']))->size('< '.$this->size.'K')->in(dirname($pic['path']));
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
     * @param $data
     *
     * @return bool
     */
    private function showData($data)
    {
        if (empty($data['pics'])) {
            $this->output->writeln('No pics found');
        }

        foreach ($data['pics'] as $pic) {
            $message = $pic['path'].' - MongoDB: ';
            if ('series' == $this->type) {
                $message .= "<info>db.Series.find({'pics.path': '".$pic['path']."'}).pretty();</info>";
            } else {
                $message .= "<info>db.MultimediaObject.find({'pics.path': '".$pic['path']."' }).pretty();</info>";
            }

            $this->output->writeln($message);
        }

        return true;
    }
}
