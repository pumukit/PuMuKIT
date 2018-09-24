<?php

namespace Pumukit\EncoderBundle\Command;

use UnexpectedValueException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;

class PumukitRefactorFileSystemCommand extends ContainerAwareCommand
{
    private $dm;
    private $output;
    private $input;
    private $finder;
    private $fileSystem;
    private $pics;
    private $materials;
    private $logger;

    protected function configure()
    {
        $this
            ->setName('pumukit:refactor:files:path')
            ->setDescription('Pumukit refactor wrongs path for images and materials')
            ->addOption('pics', null, InputArgument::OPTIONAL, 'Refactor pics')
            ->addOption('materials', null, InputArgument::OPTIONAL, 'Refactor materials')
            ->setHelp(<<<'EOT'
                
                Pumukit refactor wrongs path for images and materials
                
                Example to use:
                
                1. Refactor pics
                    php app/console pumukit:refactor:files:path --pics=true
                2. Refactor materials
                    php app/console pumukit:refactor:files:path --materials=true
                3. Refactor both
                    php app/console pumukit:refactor:files:path --pics=true --materials=true            
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
        $this->logger = $this->getContainer()->get('logger');
        $this->output = $output;
        $this->input = $input;

        $this->pics = $this->input->getOption('pics');
        $this->materials = $this->input->getOption('materials');
        $this->finder = new Finder();
        $this->fileSystem = new Filesystem();
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
        if (!$this->pics && !$this->materials) {
            throw new \Exception('Please select one type');
        }

        if ($this->pics && in_array(strtolower($this->pics), array('false', 'true', '1', '0'))) {
            $output->writeln('Trying to refactor pics paths ...');
            try {
                $this->refactorPicsPath();
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }

            $output->writeln('Refactor pics done');
        }

        if ($this->materials && in_array(strtolower($this->materials), array('false', 'true', '1', '0'))) {
            $output->writeln('Trying to refactor materials paths ...');
            try {
                $this->refactorMaterialsPath();
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }

            $output->writeln('Refactor materials done');
        }
    }

    /**
     * @throws \Exception
     */
    private function refactorPicsPath()
    {
        $multimediaObjects = $this->findWrongPathPics();

        foreach ($multimediaObjects as $multimediaObject) {
            $haveChanges = false;
            foreach ($multimediaObject['pics'] as $pic) {
                $multimediaObjectId = $multimediaObject['_id']->{'$id'};
                if (false === stripos($pic['url'], '/pic/series/')) {
                    $oldDirname = $pic['path'];
                    $seriesID = $multimediaObject['series'][0]->{'$id'};

                    if (!$seriesID) {
                        throw new \Exception("There aren't series ID for multimediaObject ".$multimediaObjectId);
                    }

                    $replaceString = '/pic/series/'.$seriesID.'/video/';
                    $newPath = str_replace('/pic/', $replaceString, $pic['path']);
                    $newPath = str_replace(' ', '_', $newPath);
                    $newUrl = str_replace('/pic/', $replaceString, $pic['url']);
                    $newUrl = str_replace(' ', '_', $newUrl);

                    if ($this->checkFileExists($pic['path'])) {
                        try {
                            $this->moveElement($pic['path'], $newPath);
                        } catch (\Exception $exception) {
                            $this->logger->error('pic not exists '.$pic['path']);
                            continue;
                        }
                    }

                    try {
                        $this->updateMultimediaObjectPic($multimediaObjectId, $pic['path'], $newPath, $newUrl);
                    } catch (\Exception $exception) {
                        $this->logger->error('Cant update mmobj '.$multimediaObjectId.' with the new path of the pic '.$pic['path']);
                        continue;
                    }

                    $haveChanges = true;
                }
            }

            if ($haveChanges && isset($oldDirname)) {
                if (!rmdir(dirname($oldDirname))) {
                    $this->logger->error('Cannot delete directory '.$oldDirname.' because is not empty');
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function refactorMaterialsPath()
    {
        $multimediaObjects = $this->findWrongPathMaterials();

        foreach ($multimediaObjects as $multimediaObject) {
            $haveChanges = false;
            foreach ($multimediaObject['materials'] as $material) {
                $multimediaObjectId = $multimediaObject['_id']->{'$id'};
                if (false === stripos($material['url'], '/material/series/')) {
                    $oldDirname = $material['path'];
                    $seriesID = $multimediaObject['series'][0]->{'$id'};

                    if (!$seriesID) {
                        throw new \Exception("There aren't series ID for multimediaObject ".$multimediaObjectId);
                    }

                    $replaceString = '/material/series/'.$seriesID.'/video/';
                    $newPath = str_replace('/material/', $replaceString, $material['path']);
                    $newPath = str_replace(' ', '_', $newPath);
                    $newUrl = str_replace('/material/', $replaceString, $material['url']);
                    $newUrl = str_replace(' ', '_', $newUrl);

                    if ($this->checkFileExists($material['path'])) {
                        try {
                            $this->moveElement($material['path'], $newPath);
                        } catch (\Exception $exception) {
                            $this->logger->error('material not exists '.$material['path']);
                            continue;
                        }
                    }

                    try {
                        $this->updateMultimediaObjectPic($multimediaObjectId, $material['path'], $newPath, $newUrl);
                    } catch (\Exception $exception) {
                        $this->logger->error('Cant update mmobj '.$multimediaObjectId.' with the new path of the material '.$material['path']);
                        continue;
                    }

                    $haveChanges = true;
                }
            }

            if ($haveChanges && isset($oldDirname)) {
                if (!rmdir(dirname($oldDirname))) {
                    $this->logger->error('Cannot delete directory '.$oldDirname.' because is not empty');
                }
            }
        }
    }

    /**
     * @return mixed
     */
    private function findWrongPathPics()
    {
        $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');

        $pipeline = array(
            array(
                '$match' => array(
                    'pics' => array('$exists' => true),
                ),
            ),
        );
        array_push($pipeline, array('$unwind' => '$pics'));

        $group = array(
            '$group' => array(
                '_id' => '$_id',
                'series' => array('$addToSet' => '$series'),
                'pics' => array('$addToSet' => '$pics'),
            ),
        );

        array_push($pipeline, $group);
        $pics = $collection->aggregate($pipeline);

        return $pics;
    }

    /**
     * @return mixed
     */
    private function findWrongPathMaterials()
    {
        $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');

        $pipeline = array(
            array(
                '$match' => array(
                    'materials' => array('$exists' => true),
                ),
            ),
        );
        array_push($pipeline, array('$unwind' => '$materials'));

        $group = array(
            '$group' => array(
                '_id' => '$_id',
                'series' => array('$addToSet' => '$series'),
                'materials' => array('$addToSet' => '$materials'),
            ),
        );

        array_push($pipeline, $group);
        $materials = $collection->aggregate($pipeline);

        return $materials;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    private function checkFileExists($path)
    {
        if ($this->fileSystem->exists($path)) {
            return true;
        }

        return false;
    }

    /**
     * @param $oldPath
     * @param $newPath
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function moveElement($oldPath, $newPath)
    {
        $dirName = dirname($newPath);

        if (!file_exists($dirName)) {
            if (mkdir($dirName, 0755, true)) {
                $this->createProcessToMove($oldPath, $newPath);
                return true;
            } else {
                throw new \Exception('Error trying to create folders of '.$dirName);
            }
        }

        $this->createProcessToMove($oldPath, $newPath);
    }

    /**
     * @param $oldPath
     * @param $newPath
     *
     * @return mixed
     */
    private function createProcessToMove($oldPath, $newPath)
    {
        $parameters = array(
            $oldPath,
            $newPath,
        );

        $builder = new ProcessBuilder();
        $builder->setPrefix('mv');
        $builder->setArguments($parameters);

        $builder->setTimeout(3600);
        $process = $builder->getProcess();
        try {
            $process->mustRun();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $aResult = json_decode($process->getOutput(), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new UnexpectedValueException(json_last_error_msg());
            }

            return $aResult;
        } catch (ProcessFailedException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $multimediaObjectId
     * @param $oldPath
     * @param $newPath
     * @param $newUrl
     *
     * @return bool
     */
    private function updateMultimediaObjectPic($multimediaObjectId, $oldPath, $newPath, $newUrl)
    {
        $multimediaObject = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
            array('_id' => new \MongoId($multimediaObjectId))
        );

        foreach ($multimediaObject->getPics() as $pic) {
            if ($pic->getPath() && $pic->getPath() === $oldPath) {
                $pic->setPath($newPath);
                $pic->setUrl($newUrl);
            }
        }

        $this->dm->flush();

        return true;
    }
}
