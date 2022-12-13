<?php

namespace Pumukit\CoreBundle\Command;

use Assetic\Exception\Exception;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMMOCommand extends ContainerAwareCommand
{
    private $dm;
    private $seriesRepo;
    private $jobService;
    private $inspectionService;
    private $factoryService;
    private $tagService;
    private $locales;

    private $validStatuses = [
        'published' => MultimediaObject::STATUS_PUBLISHED,
        'blocked' => MultimediaObject::STATUS_BLOCKED,
        'hidden' => MultimediaObject::STATUS_HIDDEN,
    ];

    protected function configure()
    {
        $this
            ->setName('import:inbox')
            ->setDescription('This command create a multimedia object from a file')
            ->addArgument('file', InputArgument::REQUIRED, 'multimedia file path')
            ->addArgument('inotify_event', InputArgument::OPTIONAL, 'inotify event, only works with IN_CLOSE_WRITE', 'IN_CLOSE_WRITE')
            ->addArgument('inotify_event', InputArgument::OPTIONAL, 'inotify event, only works with IN_CLOSE_WRITE', 'IN_CLOSE_WRITE')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Multimedia object initial status (\'published\', \'blocked\' or \'hidden\')')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'User was upload video')
            ->setHelp(
                <<<'EOT'
This command create a multimedia object from a multimedia file path

Basic example:
<info>php app/console import:inbox /var/www/html/pumukit/web/storage/tmp/test.mp4</info>

Complete example:
<info>php app/console import:inbox /var/www/html/pumukit/web/storage/tmp/test.mp4 IN_CLOSE_WRITE</info>

Complete example with hidden status:
<info>php app/console import:inbox /var/www/html/pumukit/web/storage/tmp/test.mp4 IN_CLOSE_WRITE --status=hidden</info>

Complete example with user:
<info>php app/console import:inbox /var/www/html/pumukit/web/storage/tmp/test.mp4 IN_CLOSE_WRITE --user=username</info>

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');
        $this->inspectionService = $this->getContainer()->get('pumukit.inspection');
        $this->factoryService = $this->getContainer()->get('pumukitschema.factory');
        $this->tagService = $this->getContainer()->get('pumukitschema.tag');
        $this->locales = array_unique(array_merge($this->getContainer()->getParameter('pumukit.locales'), ['en']));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $status = null;
        if ($input->getOption('status')) {
            $statusText = $input->getOption('status');
            if (!is_string($statusText)) {
                throw new \Exception('Status option must be an string');
            }

            if (!array_key_exists($statusText, $this->validStatuses)) {
                throw new \Exception('The status  ('.$statusText.') is not a valid. Use \'published\', \'blocked\' or \'hidden\'');
            }

            $status = $this->validStatuses[$statusText];
        }

        if ('IN_CLOSE_WRITE' != $input->getArgument('inotify_event')) {
            return -1;
        }
        $locale = $this->getContainer()->getParameter('locale');

        $path = $input->getArgument('file');
        if (!is_string($path)) {
            throw new \Exception('File argument must be string');
        }

        // hotfix to work with FTP
        if ((false !== ($pos = strpos($path, '.filepart'))) || (false !== ($pos = strpos($path, '.part')))) {
            $path = substr($path, 0, $pos);
            sleep(2);
        }

        if (false !== strpos($path, 'INBOX_MASTER_BROADCASTABLE')) {
            $profile = 'broadcastable_master';
        } elseif (false !== strpos($path, 'INBOX_MASTER_COPY')) {
            $profile = 'master_copy';
        } elseif (false !== strpos($path, 'INBOX_MASTER_H264')) {
            $profile = 'master_video_h264';
        } else {
            $profile = $this->getDefaultMasterProfile();
        }

        $seriesTitle = basename(dirname($path));

        if (in_array($seriesTitle, ['INBOX_MASTER_COPY', 'INBOX_MASTER_H264'])) {
            $seriesTitle = 'AUTOIMPORT';
        }

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

        $SEMKey = 123456999;
        $seg = sem_get($SEMKey, 1, 0666, -1);
        sem_acquire($seg);

        $series = $this->seriesRepo->findOneBy(['title.'.$locale => $seriesTitle]);
        if (!$series) {
            $seriesTitleAllLocales = [$locale => $seriesTitle];
            foreach ($this->locales as $l) {
                $seriesTitleAllLocales[$l] = $seriesTitle;
            }
            $series = $this->factoryService->createSeries(null, $seriesTitleAllLocales);
        }

        $user = $this->findUser($input->getOption('user'));
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $user);
        if (!$user) {
            $this->tagService->addTagByCodToMultimediaObject($multimediaObject, 'PUCHWEBTV');
        }
        foreach ($this->locales as $l) {
            $multimediaObject->setTitle($title, $l);
        }
        if (null !== $status) {
            $multimediaObject->setStatus($status);
        }

        $this->jobService->createTrackFromInboxOnServer($multimediaObject, $path, $profile, 2, $locale, []);

        sem_release($seg);
    }

    private function getDefaultMasterProfile()
    {
        if ($this->getContainer()->hasParameter('pumukit_wizard.simple_default_master_profile')) {
            return $this->getContainer()->getParameter('pumukit_wizard.simple_default_master_profile');
        }

        /** @var ProfileService */
        $profileService = $this->getContainer()->get('pumukitencoder.profile');

        return $profileService->getDefaultMasterProfile();
    }

    private function findUser($username)
    {
        if (!$username) {
            return null;
        }

        return $this->dm->getRepository(User::class)->findOneBy(['username' => $username]);
    }
}
