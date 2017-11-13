<?php

namespace Pumukit\CoreBundle\Command;

use Assetic\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CreateMMOCommand extends ContainerAwareCommand
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $jobService;
    private $profileService;
    private $inspectionService;
    private $factoryService;
    private $tagService;

    protected function configure()
    {
        $this
            ->setName('import:inbox')
            ->setDescription('This command create a multimedia object from a file')
            ->addArgument('file', InputArgument::REQUIRED, 'multimedia file path')
            ->addArgument('inotify_event', InputArgument::OPTIONAL, 'inotify event, only works with IN_CLOSE_WRITE', 'IN_CLOSE_WRITE')
            ->setHelp(<<<'EOT'
This command create a multimedia object from a multimedia file path

Basic example:
<info>php app/console import:inbox /var/www/html/pumukit2/web/storage/tmp/test.mp4</info>

Complete example:
<info>php app/console import:inbox /var/www/html/pumukit2/web/storage/tmp/test.mp4 IN_CLOSE_WRITE</info>


EOT
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->seriesRepo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');
        $this->profileService = $this->getContainer()->get('pumukitencoder.profile');
        $this->inspectionService = $this->getContainer()->get('pumukit.inspection');
        $this->factoryService = $this->getContainer()->get('pumukitschema.factory');
        $this->tagService = $this->getContainer()->get('pumukitschema.tag');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ('IN_CLOSE_WRITE' != $input->getArgument('inotify_event')) {
            return false;
        }
        $locale = $this->getContainer()->getParameter('locale');

        $path = $input->getArgument('file');
        /*
        $inboxPath = realpath($this->getContainer()->getParameter('pumukit2.inbox'));
        if (strpos($path, $inboxPath) !== 0) {
            $output->writeln('<error>Path file ('.$path.') in not in the inbox ('.$inboxPath.')</error>');
            return false;
        }
        */

        // hotfix to work with FTP
        if ((($pos = strpos($path, '.filepart')) !== false) || (($pos = strpos($path, '.part')) !== false)) {
            $path = substr($path, 0, $pos);
            sleep(2);
        }

        if (strpos($path, 'INBOX_MASTER_COPY') !== false) {
            $profile = 'master_copy';
        } elseif (strpos($path, 'INBOX_MASTER_H264') !== false) {
            $profile = 'master_video_h264';
        } else {
            $profile = $this->getDefaultMasterProfile();
        }

        $seriesTitle = basename(dirname($path));
        $title = substr(basename($path), 0, -4);

        try {
            //exception if is not a mediafile (video or audio)
            $duration = $this->inspectionService->getDuration($path);
        } catch (\Exception $e) {
            throw new \Exception('The file  ('.$path.') is not a valid video or audio file');
        }

        if (0 == $duration) {
            throw new \Exception('The file ('.$path.') is not a valid video or audio file (duration is zero)');
        }

        $series = $this->seriesRepo->findOneBy(array('title.'.$locale => $seriesTitle));
        if (!$series) {
            $series = $this->factoryService->createSeries(null, array($locale => $seriesTitle));
        }

        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setTitle($title);
        $this->tagService->addTagByCodToMultimediaObject($multimediaObject, 'PUCHWEBTV');

        $track = $this->jobService->createTrackFromInboxOnServer($multimediaObject, $path, $profile, 2, $locale, array());
    }

    private function getDefaultMasterProfile()
    {
        if ($this->getContainer()->hasParameter('pumukit_wizard.simple_default_master_profile')) {
            return $this->getContainer()->getParameter('pumukit_wizard.simple_default_master_profile');
        }

        return $this->getContainer()->get('pumukitencoder.profile')->getDefaultMasterProfile();
    }
}
