<?php

namespace Pumukit\CoreBundle\Command;

use Assetic\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('folder', InputArgument::OPTIONAL, 'folder')
            ->addArgument('profile', InputArgument::OPTIONAL, 'profile')
            ->addArgument('language', InputArgument::OPTIONAL, 'language')
            ->addArgument('description', InputArgument::OPTIONAL, 'description')
            ->setHelp(<<<'EOT'
This command import file like a track on a multimedia object

Example complete: 
<info>php app/console import:multimedia:file %idmultimediaobject% %pathfile% %profile% %language% %description%</info>

Example with multimediaobjectid and folder:
<info>php app/console import:multimedia:file 58a31ce08381165d008b456a /var/www/html/pumukit2/web/storage/tmp/test.mp4</info>

Example with multimediaobjectid:
<info>php app/console import:multimedia:file 58a31ce08381165d008b456a</info>

By default params folder, profile, language and description are:

<info>
    pathfile: /mnt/pumukit/storage/masters/test.mp4
    profile: video_h264
    language: en
    description: 2017 opencast community summit
</info>
EOT
            );
    }

    private function initParameters()
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');

        $this->folderFiles = $this->getContainer()->getParameter('pumukit_microsites_opencast2017.path_files');
        $this->profile = $this->getContainer()->getParameter('pumukit_microsites_opencast2017.profile');
        $this->language = $this->getContainer()->getParameter('pumukit_microsites_opencast2017.language');
        $this->aDescription = array($this->getContainer()->getParameter('pumukit_microsites_opencast2017.description'));
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
            echo $exception->getMessage();
        }

        $sPath = ($input->getArgument('folder')) ? $input->getArgument('folder') : $this->folderFiles;
        if (is_file($sPath)) {
            $sProfile = ($input->getArgument('profile')) ? $input->getArgument('profile') : $this->profile;
            $sLanguage = ($input->getArgument('language')) ? $input->getArgument('language') : $this->language;
            $sDescription = ($input->getArgument('description')) ? array($input->getArgument('description')) : $this->aDescription;

            try {
                $oTrack = $this->jobService->createTrack($oMultimedia, $sPath, $sProfile, $sLanguage, $sDescription);
                $output->writeln('<info> Track '.$oTrack->getId().' was imported succesfully on '.$oMultimedia->getId().'</info>');
            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        } else {
            $output->writeln('<error> Path is not a directory: '.$sPath.'</error>');
        }
    }
}
