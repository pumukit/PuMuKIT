<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\i18nService;
use Pumukit\CoreBundle\Utils\SemaphoreUtils;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMMOCommand extends Command
{
    private $documentManager;
    private $jobCreator;
    private $inspectionService;
    private $factoryService;
    private $tagService;
    private $profileService;
    private $locales;
    private $locale;

    private $validStatuses = [
        'published' => MultimediaObject::STATUS_PUBLISHED,
        'blocked' => MultimediaObject::STATUS_BLOCKED,
        'hidden' => MultimediaObject::STATUS_HIDDEN,
    ];
    private LoggerInterface $logger;
    private i18nService $i18nService;

    public function __construct(
        DocumentManager $documentManager,
        JobCreator $jobCreator,
        InspectionFfprobeService $inspectionService,
        FactoryService $factoryService,
        TagService $tagService,
        ProfileService $profileService,
        i18nService $i18nService,
        LoggerInterface $logger,
        array $locales,
        string $locale = 'en'
    ) {
        $this->documentManager = $documentManager;
        $this->jobCreator = $jobCreator;
        $this->inspectionService = $inspectionService;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->profileService = $profileService;
        $this->locale = $locale;
        $this->locales = array_unique(array_merge($locales, ['en']));
        parent::__construct();
        $this->logger = $logger;
        $this->i18nService = $i18nService;
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
            ->addOption('series', null, InputOption::VALUE_REQUIRED, 'Series to create multimedia object')
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

        // TODO CHECK PERFILES NEW FEATURES
        if (str_contains($path, 'INBOX_MASTER_BROADCASTABLE')) {
            $profile = 'broadcastable_master';
        } elseif (str_contains($path, 'INBOX_MASTER_COPY')) {
            $profile = 'master_copy';
        } elseif (str_contains($path, 'INBOX_MASTER_H264')) {
            $profile = 'master_video_h264';
        } else {
            $profile = $this->getDefaultMasterProfile();
        }

        $seriesTitle = basename(dirname($path));

        if (in_array($seriesTitle, ['INBOX_MASTER_COPY', 'INBOX_MASTER_H264'])) {
            $seriesTitle = 'AUTOIMPORT';
        }

        $title = substr(basename($path), 0, -4);

        //        try {
        //            // exception if is not a mediafile (video or audio)
        //            $duration = $this->inspectionService->getDuration($path);
        //        } catch (\Exception $e) {
        //            throw new \Exception('The file  ('.$path.') is not a valid video or audio file');
        //        }
        //
        //        if (0 == $duration) {
        //            throw new \Exception('The file ('.$path.') is not a valid video or audio file (duration is zero)');
        //        }

        $semaphore = SemaphoreUtils::acquire(1000001);

        //        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['title.'.$locale => $seriesTitle]);
        $seriesId = $input->getOption('series');
        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['_id' => new ObjectId($seriesId)]);
        if (!$series instanceof Series) {
            throw new \Exception('The series ('.$seriesId.') is not a valid');
            // TODO: DIGIREPO REMOVE
            //            $seriesTitleAllLocales = [$locale => $seriesTitle];
            //            foreach ($this->locales as $l) {
            //                $seriesTitleAllLocales[$l] = $seriesTitle;
            //            }
            //            $series = $this->factoryService->createSeries(null, $seriesTitleAllLocales);
        }

        $user = $this->findUser($input->getOption('user'));
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $user);
        if (!$user) {
            $this->tagService->addTagByCodToMultimediaObject($multimediaObject, PumukitWebTVBundle::WEB_TV_TAG);
        }

        $i18nTitle = $this->i18nService->generateI18nText($title);
        $multimediaObject->setI18nTitle($i18nTitle);
        //        foreach ($this->locales as $l) {
        //            $multimediaObject->setTitle($title, $l);
        //        }
        (null !== $status) ? $multimediaObject->setStatus($status) : $multimediaObject->setStatus(MultimediaObject::STATUS_BLOCKED);

        $profile = 'master_copy';
        $jobOptions = new JobOptions($profile, 2, $locale, [], []);
        $path = Path::create($path);
        $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);

        SemaphoreUtils::release($semaphore);

        return 0;
    }

    private function getDefaultMasterProfile()
    {
        // TODO: DIGIREPO REMOVE
        //        if ($this->wizardSimpleDefaultMasterProfile) {
        //            return $this->wizardSimpleDefaultMasterProfile;
        //        }

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
