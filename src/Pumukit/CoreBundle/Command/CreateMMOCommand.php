<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Utils\SemaphoreUtils;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMMOCommand extends Command
{
    private $documentManager;
    private $jobService;
    private $inspectionService;
    private $factoryService;
    private $tagService;
    private $profileService;
    private $locales;
    private $wizardSimpleDefaultMasterProfile;
    private $locale;

    private $validStatuses = [
        'published' => MultimediaObject::STATUS_PUBLISHED,
        'blocked' => MultimediaObject::STATUS_BLOCKED,
        'hidden' => MultimediaObject::STATUS_HIDDEN,
    ];

    public function __construct(DocumentManager $documentManager, JobService $jobService, InspectionFfprobeService $inspectionService, FactoryService $factoryService, TagService $tagService, ProfileService $profileService, array $locales, string $locale = 'en', ?string $wizardSimpleDefaultMasterProfile = null)
    {
        $this->wizardSimpleDefaultMasterProfile = $wizardSimpleDefaultMasterProfile;
        $this->documentManager = $documentManager;
        $this->jobService = $jobService;
        $this->inspectionService = $inspectionService;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->profileService = $profileService;
        $this->locale = $locale;
        $this->locales = array_unique(array_merge($locales, ['en']));
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:import:inbox')
            ->setDescription('This command create a multimedia object from a file')
            ->addArgument('file', InputArgument::REQUIRED, 'multimedia file path')
            ->addArgument('inotify_event', InputArgument::OPTIONAL, 'inotify event, only works with IN_CLOSE_WRITE', 'IN_CLOSE_WRITE')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Multimedia object initial status (\'published\', \'blocked\' or \'hidden\')')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'User was upload video')
            ->setHelp(
                <<<'EOT'
This command create a multimedia object from a multimedia file path

Basic example:
<info>php bin/console pumukit:import:inbox {pathToPuMuKITStorageTempFiles}/test.mp4</info>

Complete example:
<info>php bin/console pumukit:import:inbox {pathToPuMuKITStorageTempFiles}/test.mp4 IN_CLOSE_WRITE</info>

Complete example with hidden status:
<info>php bin/console pumukit:import:inbox {pathToPuMuKITStorageTempFiles}/test.mp4 IN_CLOSE_WRITE --status=hidden</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        if ('IN_CLOSE_WRITE' !== $input->getArgument('inotify_event')) {
            return -1;
        }
        $locale = $this->locale;

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
            // exception if is not a mediafile (video or audio)
            $duration = $this->inspectionService->getDuration($path);
        } catch (\Exception $e) {
            throw new \Exception('The file  ('.$path.') is not a valid video or audio file');
        }

        if (0 == $duration) {
            throw new \Exception('The file ('.$path.') is not a valid video or audio file (duration is zero)');
        }

        $semaphore = SemaphoreUtils::acquire(1000001);

        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['title.'.$locale => $seriesTitle]);
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

        SemaphoreUtils::release($semaphore);

        return 0;
    }

    private function getDefaultMasterProfile()
    {
        if ($this->wizardSimpleDefaultMasterProfile) {
            return $this->wizardSimpleDefaultMasterProfile;
        }

        return $this->profileService->getDefaultMasterProfile();
    }

    private function findUser($username)
    {
        if (!$username) {
            return null;
        }

        return $this->documentManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }
}
