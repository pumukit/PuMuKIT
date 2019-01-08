<?php

namespace Pumukit\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DeleteOrphanFilesCommand extends ContainerAwareCommand
{
    private $dm;
    private $output;
    private $input;
    private $finder;
    private $path;
    private $delete;
    private $logger;

    protected function configure()
    {
        $this
            ->setName('pumukit:files:delete:orphan')
            ->setDescription('Pumukit delete orphan files on folders')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to check', null)
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete files and folders')
            ->setHelp(<<<'EOT'
            
            Pumukit delete orphan files on specific path. This command shows if the path's file exists on:
            
            a) pics.path of multimedia object
            b) materials.path of multimedia object
            c) tracks.path of multimedia object
            d) pics.path of series
            e) materials.path of series
            
            The command will try to delete the folder if its empty.
                
                Example to use:
                
                1. List orphan files
                    php app/console pumukit:files:delete:orphan --path="/var/www/html/pumukit2/web/uploads/material"
                2. Delete orphan files
                    php app/console pumukit:files:delete:orphan --path="/var/www/html/pumukit2/web/uploads/material" --delete         
                  
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

        $this->path = $this->input->getOption('path');
        $this->delete = $this->input->getOption('delete');
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
        if (!file_exists($this->path)) {
            throw new \Exception('Path doesnt exists');
        }

        $this->findFilesOfPath($this->path);
    }

    /**
     * @param $path
     *
     * @throws \Exception
     */
    private function findFilesOfPath($path)
    {
        $files = $this->finder->files()->in($path);

        foreach ($files as $file) {
            $filePath = $file->getRelativePathName();
            $absoluteFilePath = $file->getPathName();

            $existsInMongoDB = $this->findInMongoDB($filePath);
            if (!$existsInMongoDB) {
                $this->output->writeln('No file found in MongoDB '.$filePath);
                $this->logger->info('No file found in MongoDB '.$filePath);

                if ($this->delete) {
                    $this->output->writeln('Trying to delete file....');
                    unlink($absoluteFilePath);
                    $this->output->writeln('File deleted '.$filePath);
                    $this->isEmptyDirectory($absoluteFilePath);
                }
            }
        }
    }

    /**
     * @param $filePath
     *
     * @return bool
     */
    private function findInMongoDB($filePath)
    {
        $mmobjPic = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array(
            'pics.path' => array(
                '$regex' => $filePath,
                '$options' => 'i',
            ),
        ));
        $mmobjMaterial = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array(
            'materials.path' => array(
                '$regex' => $filePath,
                '$options' => 'i',
            ),
        ));

        $mmobjTracks = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array(
            'tracks.path' => array(
                '$regex' => $filePath,
                '$options' => 'i',
            ),
        ));

        $seriesPic = $this->dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array(
            'pic.path' => array(
                '$regex' => $filePath,
                '$options' => 'i',
            ),
        ));
        $seriesMaterial = $this->dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array(
            'materials.path' => array(
                '$regex' => $filePath,
                '$options' => 'i',
            ),
        ));

        if (0 === count($mmobjPic) && 0 === count($mmobjMaterial) && 0 === count($mmobjTracks) && 0 === count($seriesPic) && 0 === count($seriesMaterial)) {
            return false;
        }

        return true;
    }

    /**
     * @param $directoryPath
     */
    private function isEmptyDirectory($directoryPath)
    {
        $dirName = pathinfo($directoryPath);
        try {
            rmdir($dirName['dirname']);
            $this->logger->info('Deleted empty directory '.$directoryPath);
        } catch (\Exception $exception) {
            $this->logger->info('Cannot delete directory because is not empty '.$directoryPath);
        }
    }
}
