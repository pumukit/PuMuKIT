<?php

namespace Pumukit\CoreBundle\Command;

use Assetic\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportFileToMMOCommand extends ContainerAwareCommand
{
    private $dm = null;

    protected function configure()
    {
        $this
            ->setName('import:multimedia:file')
            ->setDescription('This command import file like a track on a multimedia object')
            ->addArgument('object', InputArgument::REQUIRED, 'object')
            ->addArgument('file', InputArgument::REQUIRED, 'file')
            ->addOption('profile', null, InputOption::VALUE_OPTIONAL, 'profile')
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'language', null)
            ->addArgument('description', InputArgument::OPTIONAL, 'description')
            ->setHelp(<<<'EOT'
This command import file like a track on a multimedia object

Example complete: 
<info>php app/console import:multimedia:file %idmultimediaobject% %pathfile% --profile=%profile% --language=%language% %description%</info>

Basic example:
<info>php app/console import:multimedia:file 58a31ce08381165d008b456a /var/www/html/pumukit2/web/storage/tmp/test.mp4</info>

EOT
            );
    }

    private function initParameters()
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');
        $this->profileService = $this->getContainer()->get('pumukitencoder.profile');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initParameters();

        $output->writeln('<info> ***** Add track to multimedia object ***** </info>');
        try {
            $oMultimedia = $this->mmobjRepo->findOneBy(
                array('id' => new \MongoId($input->getArgument('object')))
            );
        } catch (Exception $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
        }

        $sPath = $input->getArgument('file');
        if (is_file($sPath)) {
            $sProfile = ($input->getOption('profile')) ? $input->getOption('profile') : $this->profileService->getDefaultMasterProfile();
            $sLanguage = ($input->getOption('language')) ? $input->getOption('language') : null;
            $sDescription = ($input->getArgument('description')) ? array($input->getArgument('description')) : '';

            try {
                $oTrack = $this->jobService->createTrack($oMultimedia, $sPath, $sProfile, $sLanguage, $sDescription);
                $output->writeln('<info> Track '.$oTrack->getId().' was imported succesfully on '.$oMultimedia->getId().'</info>');
            } catch (Exception $exception) {
                $output->writeln('<error>'.$exception->getMessage().'</error>');
            }
        } else {
            $output->writeln('<error> Path is not a file: '.$sPath.'</error>');
        }
    }
}
