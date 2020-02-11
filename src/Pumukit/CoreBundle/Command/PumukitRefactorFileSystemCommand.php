<?php

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class PumukitRefactorFileSystemCommand extends Command
{
    private $dm;
    private $output;
    private $input;
    private $finder;
    private $pics;
    private $materials;
    private $force;
    private $id;
    private $regex = '/^[0-9a-z]{24}$/';
    private $allowedTypes = ['pics', 'materials'];
    private $pumukitPublicDir;

    public function __construct(DocumentManager $documentManager, string $pumukitPublicDir)
    {
        $this->dm = $documentManager;
        $this->pumukitPublicDir = $pumukitPublicDir;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:files:refactor:path')
            ->setDescription('Pumukit refactor wrongs path for images and materials')
            ->addOption('pics', null, InputOption::VALUE_NONE, 'Refactor pics')
            ->addOption('materials', null, InputOption::VALUE_NONE, 'Refactor materials')
            ->addOption('id', null, InputOption::VALUE_NONE, 'Filter by id')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this to execute command')
            ->setHelp(
                <<<'EOT'

                Command to refactor path of pics and materials on pumukit. The command will change element path from old versions
                to the new element path on pumukit new versions.

                Old Path:
                    element.path = "element/uid/file.ext"
                New path:
                    element.path = "element/uidseries/video/uidvideo/file.ext"

                Show change on: https://github.com/pumukit/PuMuKIT/commit/bd63851ce2a9d44be90017a0db0d5e073b55dec5#diff-2cb454b02139985bdcb5f15387a4be64

                Examples:

                1. List the refactor pics
                    php app/console pumukit:files:refactor:path --pics
                2. List the refactor materials
                    php app/console pumukit:files:refactor:path --materials
                3. List both
                    php app/console pumukit:files:refactor:path --pics --materials

                Example to execute:

                1. Refactor pics
                    php app/console pumukit:files:refactor:path --pics --force
                2. Refactor materials
                    php app/console pumukit:files:refactor:path --materials --force
                3. Refactor both
                    php app/console pumukit:files:refactor:path --pics --materials --force

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->pics = $this->input->getOption('pics');
        $this->materials = $this->input->getOption('materials');
        $this->id = $this->input->getOption('id');
        $this->force = (true === $input->getOption('force'));
        $this->finder = new Finder();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkInputs();

        $this->repairMongoDBPicsAndMaterial();

        if ($this->pics) {
            $message = '***** List pics paths to refactor *****';
            if ($this->force) {
                $message = '***** Refactor pics paths *****';
            }

            $this->showMessage($output, $message);
            $this->refactorPicsPath($output);
            $this->showMessage($output, '----- Refactor pics done -----');
        }

        if ($this->materials) {
            $output->writeln('Trying to refactor materials paths ...');

            try {
                $this->refactorMaterialsPath($output);
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }

            $this->output->writeln('Refactor materials done');
        }

        return 0;
    }

    private function checkInputs(): void
    {
        if (!$this->pics && !$this->materials) {
            throw new \Exception('Please select one type ( pics or materials )');
        }

        if ($this->id && !preg_match($this->regex, $this->id)) {
            throw new \Exception('Param ID is not a valid objectID');
        }
    }

    private function repairMongoDBPicsAndMaterial(): void
    {
        if ($this->pics) {
            $this->repairMongoDB('pics');
        }

        if ($this->materials) {
            $this->repairMongoDB('materials');
        }
    }

    private function repairMongoDB(string $type): bool
    {
        if ('pics' === $type) {
            $multimediaObjects = $this->findPicsWithoutPaths();
        } else {
            $multimediaObjects = $this->findMaterialsWithoutPaths();
        }

        if (!$multimediaObjects) {
            $this->showMessage($this->output, 'No multimedia objects found');

            return true;
        }

        foreach ($multimediaObjects as $multimediaObject) {
            $this->fixPathMultimediaObject($multimediaObject, $type);
        }

        return true;
    }

    private function showMessage(OutputInterface $output, $message): void
    {
        $output->writeln($message);
    }

    private function fixPathMultimediaObject(MultimediaObject $multimediaObject, $type): void
    {
        if (!in_array($type, $this->allowedTypes)) {
            throw new \Exception('Types cant be distinct of '.implode(' or ', $this->allowedTypes));
        }

        if ('pics' === $type) {
            $elements = $multimediaObject->getPics();
        } else {
            $elements = $multimediaObject->getMaterials();
        }

        $this->fixPath($elements, $type);
    }

    private function fixPath($elements, $type)
    {
        $haveChanges = false;
        foreach ($elements as $elem) {
            $path = $elem->getPath();
            if (!isset($path) && false !== stripos($elem->getUrl(), '/uploads/pic/')) {
                $path = realpath($this->pumukitPublicDir.$elem->getUrl());
                if (!$path) {
                    throw new \Exception('Error reading: '.$this->pumukitPublicDir.$elem->getUrl());
                }
                $checkFile = $this->checkFileExists($path);
                if ($checkFile && $this->force) {
                    $message = 'Adding path '.$path.' form pic with ID - '.$elem->getId();
                    $elem->setPath($path);
                    $haveChanges = true;
                } elseif (!$checkFile) {
                    $message = "{$type} (".$elem->getId().') - URL <comment>('.$elem->getUrl().'</comment>) doesnt exists on filesystem.';
                } else {
                    $message = "<info>{$type} (".$elem->getId().') - URL ('.$elem->getUrl().') </info>';
                }
                $this->showMessage($this->output, $message);
            }
        }

        if ($haveChanges && $this->force) {
            $this->dm->flush();
        }
    }

    private function refactorPicsPath(OutputInterface $output)
    {
        $multimediaObjects = $this->findWrongPathPics();

        foreach ($multimediaObjects as $multimediaObject) {
            $haveChanges = false;
            foreach ($multimediaObject['pics'] as $pic) {
                if (!isset($pic['path'])) {
                    continue;
                }

                $multimediaObjectId = $multimediaObject['_id']->{'$id'};
                if (false === stripos($pic['url'], '/pic/series/')) {
                    $oldDirname = $pic['path'];
                    $seriesID = $multimediaObject['series'][0]->{'$id'};

                    if (!$seriesID) {
                        $this->showMessage($output, "<error>There aren't series ID for multimediaObject ".$multimediaObjectId.'</error>');

                        continue;
                    }

                    $replaceString = '/pic/series/'.$seriesID.'/video/';
                    $newPath = str_replace('/pic/', $replaceString, (string) $pic['path']);
                    $newPath = str_replace(' ', '_', (string) $newPath);
                    $newUrl = str_replace('/pic/', $replaceString, (string) $pic['url']);
                    $newUrl = str_replace(' ', '_', (string) $newUrl);

                    if ($this->checkFileExists($pic['path'])) {
                        try {
                            $this->moveElement($pic['path'], $newPath);
                        } catch (\Exception $exception) {
                            $this->showMessage($output, '<warning> Pic ('.$pic['id'].') not exists '.$pic['path']);

                            continue;
                        }
                    }

                    try {
                        $this->updateMultimediaObjectPic($multimediaObjectId, $pic['path'], $newPath, $newUrl);
                    } catch (\Exception $exception) {
                        $this->showMessage($output, 'Cant update mmobj '.$multimediaObjectId.' with the new path of the pic '.$pic['path']);

                        continue;
                    }

                    $haveChanges = true;
                }
            }

            if ($haveChanges && isset($oldDirname)) {
                $this->deleteDirectory($output, $haveChanges, $oldDirname);
            }
        }
    }

    private function refactorMaterialsPath(OutputInterface $output)
    {
        $multimediaObjects = $this->findWrongPathMaterials();

        foreach ($multimediaObjects as $multimediaObject) {
            $haveChanges = false;
            foreach ($multimediaObject['materials'] as $material) {
                if (!isset($material['path'])) {
                    continue;
                }

                $multimediaObjectId = $multimediaObject['_id']->{'$id'};
                if (false === stripos($material['url'], '/material/series/')) {
                    $oldDirname = $material['path'];
                    $seriesID = $multimediaObject['series'][0]->{'$id'};

                    if (!$seriesID) {
                        $this->showMessage($output, "<error>There aren't series ID for multimediaObject ".$multimediaObjectId.'</error>');

                        continue;
                    }

                    $replaceString = '/material/series/'.$seriesID.'/video/';
                    $newPath = str_replace('/material/', $replaceString, (string) $material['path']);
                    $newPath = str_replace(' ', '_', (string) $newPath);
                    $newUrl = str_replace('/material/', $replaceString, (string) $material['url']);
                    $newUrl = str_replace(' ', '_', (string) $newUrl);

                    if ($this->checkFileExists($material['path'])) {
                        try {
                            $this->moveElement($material['path'], $newPath);
                        } catch (\Exception $exception) {
                            $this->showMessage($output, '<warning> Material ('.$material['id'].') not exists '.$material['path']);

                            continue;
                        }
                    }

                    try {
                        $this->updateMultimediaObjectMaterial($multimediaObjectId, $material['path'], $newPath, $newUrl);
                    } catch (\Exception $exception) {
                        $this->showMessage($output, 'Cant update mmobj '.$multimediaObjectId.' with the new path of the material '.$material['path']);

                        continue;
                    }

                    $haveChanges = true;
                }
            }

            if ($haveChanges && isset($oldDirname)) {
                $this->deleteDirectory($output, $haveChanges, $oldDirname);
            }
        }
    }

    private function findPicsWithoutPaths()
    {
        return $this->dm->getRepository(MultimediaObject::class)->findBy(
            [
                'pics.url' => new Regex('/uploads/pic/'),
                'pics.path' => ['$exists' => false],
            ]
        );
    }

    private function findMaterialsWithoutPaths()
    {
        return $this->dm->getRepository(MultimediaObject::class)->findBy(
            [
                'materials.url' => new Regex('/uploads/material/'),
                'materials.path' => ['$exists' => false],
            ]
        );
    }

    private function findWrongPathPics()
    {
        $collection = $this->dm->getDocumentCollection(MultimediaObject::class);

        $pipeline = [
            [
                '$match' => [
                    'pics.path' => ['$regex' => '/uploads/pic/', '$options' => 'i'],
                ],
            ],
        ];
        $pipeline[] = ['$unwind' => '$pics'];

        $group = [
            '$group' => [
                '_id' => '$_id',
                'series' => ['$addToSet' => '$series'],
                'pics' => ['$addToSet' => '$pics'],
            ],
        ];

        $pipeline[] = $group;

        return $collection->aggregate($pipeline, ['cursor' => []]);
    }

    private function findWrongPathMaterials()
    {
        $collection = $this->dm->getDocumentCollection(MultimediaObject::class);

        $pipeline = [
            [
                '$match' => [
                    'materials' => ['$exists' => true],
                    'materials.path' => ['$regex' => 'uploads/material/', '$options' => 'i'],
                ],
            ],
        ];
        $pipeline[] = ['$unwind' => '$materials'];

        $group = [
            '$group' => [
                '_id' => '$_id',
                'series' => ['$addToSet' => '$series'],
                'materials' => ['$addToSet' => '$materials'],
            ],
        ];

        $pipeline[] = $group;

        return $collection->aggregate($pipeline, ['cursor' => []]);
    }

    private function checkFileExists(string $path): bool
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($path)) {
            return true;
        }

        return false;
    }

    private function moveElement(string $oldPath, string $newPath)
    {
        $dirName = dirname($newPath);

        if (!$this->checkFileExists($dirName)) {
            if (mkdir($dirName, 0755, true) || is_dir($dirName)) {
                $this->createProcessToMove($oldPath, $newPath);
            } else {
                throw new \Exception('Error trying to create folders - '.$dirName);
            }
        }

        $this->createProcessToMove($oldPath, $newPath);
    }

    private function createProcessToMove(string $oldPath, string $newPath)
    {
        $parameters = [
            'mv',
            $oldPath,
            $newPath,
        ];

        $process = new Process($parameters);

        $process->setTimeout(3600);

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
        } catch (ProcessFailedException $exception) {
            echo $exception->getMessage();
        }

        return null;
    }

    private function updateMultimediaObjectPic(string $multimediaObjectId, string $oldPath, string $newPath, string $newUrl): void
    {
        $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->findOneBy(
            ['_id' => new ObjectId($multimediaObjectId)]
        );

        foreach ($multimediaObject->getPics() as $pic) {
            if ($pic->getPath() && $pic->getPath() === $oldPath) {
                $pic->setPath($newPath);
                $pic->setUrl($newUrl);
            }
        }

        $this->dm->flush();
    }

    private function updateMultimediaObjectMaterial(string $multimediaObjectId, string $oldPath, string $newPath, string $newUrl): void
    {
        /** @var MultimediaObject */
        $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->findOneBy(
            ['_id' => new ObjectId($multimediaObjectId)]
        );

        foreach ($multimediaObject->getMaterials() as $material) {
            if ($material->getPath() && $material->getPath() === $oldPath) {
                $material->setPath($newPath);
                $material->setUrl($newUrl);
            }
        }

        $this->dm->flush();
    }

    private function deleteDirectory(OutputInterface $output, bool $haveChanges, string $oldDirName): bool
    {
        if ($haveChanges && $oldDirName) {
            if ($this->checkFileExists(dirname($oldDirName))) {
                rmdir(dirname($oldDirName));

                return true;
            }
            $this->showMessage($output, 'File or directory ( '.$oldDirName.' ) doesnt exists');
        }

        return false;
    }
}
