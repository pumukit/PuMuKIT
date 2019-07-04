<?php

namespace Pumukit\ExampleDataBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\SchemaBundle\Document\Tag;

class PumukitInitExampleDataCommand extends ContainerAwareCommand
{
    const PATH_VIDEO = 'http://static.campusdomar.es/pumukit_videos.zip';

    private $dm;
    private $roleRepo;
    private $pmk2AllLocales;
    private $seriesRepo;

    protected function configure()
    {
        $this
            ->setName('pumukit:init:example')
            ->setDescription('Load Pumukit example data fixtures to your database')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->addOption('noviewlogs', null, InputOption::VALUE_NONE, 'Does not add viewlog dummy views')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Add examples without deleting')
            ->addOption('reusezip', null, InputOption::VALUE_NONE, 'Set this parameter to not delete zip file with videos to reuse in the future')
            ->setHelp(<<<'EOT'

            Command to load a data set of data into a database. Useful for init a demo Pumukit environment.

            The --force parameter has to be used to actually drop the database.

            The --append parameter has to be used to add examples to database without deleting.

            The --reusezip parameter has to be used to undelete files.

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newFile = $this->getContainer()->getParameter('kernel.cache_dir').'/tmp_file.zip';
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->roleRepo = $this->dm->getRepository(Role::class);
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->pmk2AllLocales = $this->getContainer()->getParameter('pumukit.locales');

        $factoryService = $this->getContainer()->get('pumukitschema.factory');

        if ($input->getOption('force')) {
            $this->dm->getDocumentCollection(Job::class)->remove([]);
            $this->dm->getDocumentCollection(Person::class)->remove([]);
            $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
            $this->dm->getDocumentCollection(Series::class)->remove([]);
            $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog')->remove([]);
        } elseif (!$input->getOption('append')) {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }

        if (!$input->getOption('reusezip')) {
            if (!$this->download(self::PATH_VIDEO, $newFile, $output)) {
                echo "Failed to copy $newFile...\n";
            }

            $zip = new ZipArchive();
            if (true === $zip->open($newFile, ZIPARCHIVE::CREATE)) {
                $zip->extractTo(realpath(dirname(__FILE__).'/../Resources/public/'));
                $zip->close();
            }
        }
        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output, 13);
        $output->writeln("\nCreating resources...");
        $progress->setFormat('verbose');
        $progress->start();

        // Roles
        $actorRole = $this->getRoleWithCode('actor');
        $presenterRole = $this->getRoleWithCode('presenter');
        $progress->advance();
        //Series Access grid
        if (!$this->checkSeriesExists('Access grid')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Access grid');
            $this->loadPicSeries($series, '39');
            $series->setProperty('dataexample', 'Access grid');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Access grid');
            $this->loadTrackMultimediaObject($multimediaObject, '8', '24', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUBCHANNELS', 'Dscience', 'Dhealth']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Will', $actorRole);
            $this->loadPicMultimediaObject($multimediaObject, '17');
        }
        $progress->advance();

        //Series Uvigo
        if (!$this->checkSeriesExists('Uvigo')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Uvigo');
            $this->loadPicSeries($series, '7');
            $series->setProperty('dataexample', 'Uvigo');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Uvigo');
            $this->loadTrackMultimediaObject($multimediaObject, '9', '26', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD3', 'DIRECTRIZ', 'Dhealth']);
            $this->loadPicMultimediaObject($multimediaObject, '19');
        }
        $progress->advance();

        //Series Robots
        if (!$this->checkSeriesExists('Robots')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Robots');
            $this->loadPicSeries($series, '22');
            $series->setProperty('dataexample', 'Robots');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'AIBO');
            $this->loadTrackMultimediaObject($multimediaObject, '10', '38', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'Dscience', 'Dtechnical']);
            $this->loadPicMultimediaObject($multimediaObject, '21');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Movil');
            $this->loadTrackMultimediaObject($multimediaObject, '10', '36', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'Dscience', 'Dhumanities']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Laura', $presenterRole);
            $this->loadPicMultimediaObject($multimediaObject, '22');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Fanuc');
            $this->loadTrackMultimediaObject($multimediaObject, '10', '28', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'DIRECTRIZ', 'Dhealth', 'Dtechnical']);
            $this->loadPicMultimediaObject($multimediaObject, '23');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Concurso');
            $this->loadTrackMultimediaObject($multimediaObject, '10', '30', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD3', 'Dsocial', 'Dhumanities']);
            $this->loadPicMultimediaObject($multimediaObject, '27');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Robonova');
            $this->loadTrackMultimediaObject($multimediaObject, '10', '35', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'DIRECTRIZ', 'Dscience', 'Dhumanities']);
            $this->loadPicMultimediaObject($multimediaObject, '20');
        }
        $progress->advance();

        //Series Polimedia
        if (!$this->checkSeriesExists('Polimedia')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Polimedia');
            $this->loadPicSeries($series, '37');
            $series->setProperty('dataexample', 'Polimedia');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Armesto');
            $this->loadTrackMultimediaObject($multimediaObject, '11', '34', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'PUBDECISIONS', 'Dsocial', 'Dhumanities']);
            $this->loadPicMultimediaObject($multimediaObject, '38');
        }
        $progress->advance();

        //Serie Energia de materiales y medio ambiente
        if (!$this->checkSeriesExists('Energy materials and environment')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Energy materials and environment');
            $this->loadPicSeries($series, '32');
            $series->setProperty('dataexample', 'Energy materials and environment');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Energy materials and environment');
            $this->loadTrackMultimediaObject($multimediaObject, '12', '40', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'Dhealth', 'Dtechnical']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Marcos', $presenterRole);
            $this->loadPicMultimediaObject($multimediaObject, '28');
        }
        $progress->advance();

        //Serie Marine sciences
        if (!$this->checkSeriesExists('Marine sciences')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Marine sciences');
            $this->loadPicSeries($series, '28');
            $series->setProperty('dataexample', 'Marine sciences');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Toralla');
            $this->loadTrackMultimediaObject($multimediaObject, '13', '45', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD2', 'PUDEPD3', 'Dscience', 'Dsocial']);
            $this->loadPicMultimediaObject($multimediaObject, '29');
        }
        $progress->advance();

        //Serie NOS register
        if (!$this->checkSeriesExists('NOS register')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'NOS register');
            $this->loadPicSeries($series, '41');
            $series->setProperty('dataexample', 'NOS register');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Isaac Díaz Pardo');
            $this->loadTrackMultimediaObject($multimediaObject, '14', '46', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'Dsocial', 'Dhumanities']);
            $this->loadPicMultimediaObject($multimediaObject, '31');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Promo');
            $this->loadTrackMultimediaObject($multimediaObject, '14', '47', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'Dscience', 'Dtechnical']);
            $this->loadPicMultimediaObject($multimediaObject, '30');
        }
        $progress->advance();

        //Serie Zigzag
        if (!$this->checkSeriesExists('ZigZag')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'ZigZag');
            $this->loadPicSeries($series, '40');
            $series->setProperty('dataexample', 'ZigZag');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Episode I');
            $this->loadTrackMultimediaObject($multimediaObject, '15', '48', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD1', 'PUBCHANNELS', 'DIRECTRIZ', 'Dsocial', 'Dhealth']);
            $this->loadPicMultimediaObject($multimediaObject, '40');
        }
        $progress->advance();

        //Serie Quijote
        if (!$this->checkSeriesExists('Quijote')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Quijote');
            $this->loadPicSeries($series, '35');
            $series->setProperty('dataexample', 'Quijote');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'First');
            $this->loadTrackMultimediaObject($multimediaObject, '16', '53', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUDEPD3', 'PUCHWEBTV', 'Dtechnical', 'Dhumanities']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Ana', $actorRole);
            $this->loadPicMultimediaObject($multimediaObject, '33');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Second');
            $this->loadTrackMultimediaObject($multimediaObject, '16', '50', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEPD2', 'Dsocial', 'Dtechnical']);
            $this->loadPicMultimediaObject($multimediaObject, '34');
        }
        $progress->advance();

        //Serie autonomic
        if (!$this->checkSeriesExists('Financing economic autonomy statutes')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Financing economic autonomy statutes');
            $this->loadPicSeries($series, '33');
            $series->setProperty('dataexample', 'Financing economic autonomy statutes');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Conference');
            $this->loadTrackMultimediaObject($multimediaObject, '17', '54', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD3', 'Dscience', 'Dhumanities']);
            $this->loadPicMultimediaObject($multimediaObject, '35');
        }
        $progress->advance();

        //Serie HD
        if (!$this->checkSeriesExists('HD')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'HD');
            $this->loadPicSeries($series, '36');
            $series->setProperty('dataexample', 'HD');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Presentation');
            $this->loadTrackMultimediaObject($multimediaObject, '18', '56', false);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUDEPD1', 'DIRECTRIZ', 'Dsocial', 'Dtechnical']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Sara', $presenterRole);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Carlos', $actorRole);
            $this->loadPicMultimediaObject($multimediaObject, '36');
        }
        $progress->advance();

        //Serie AUDIOS
        if (!$this->checkSeriesExists('Audios')) {
            $series = $factoryService->createSeries();
            $this->loadSeries($series, 'Audios');
            $this->loadPicSeries($series, 'audio');
            $series->setProperty('dataexample', 'Audios');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Audio1');
            $this->loadTrackMultimediaObject($multimediaObject, '20', 'Audio1', true);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUDEPD1', 'DIRECTRIZ', 'Dsocial', 'Dtechnical']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Sara', $presenterRole);
            $this->loadPicMultimediaObject($multimediaObject, 'audio');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->loadMultimediaObject($multimediaObject, $series, 'Audio2');
            $this->loadTrackMultimediaObject($multimediaObject, '20', 'Audio2', true);
            $this->loadTagsMultimediaObject($multimediaObject, ['PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUDEPD1', 'DIRECTRIZ', 'Dsocial', 'Dtechnical']);
            $this->loadPeopleMultimediaObject($multimediaObject, 'Sara', $presenterRole);
            $this->loadPicMultimediaObject($multimediaObject, 'audio');
        }
        $progress->advance();
        $progress->finish();
        if (!$input->getOption('noviewlogs')) {
            $this->loadViewsLog($output);
        }
        if (!$input->getOption('reusezip')) {
            unlink($newFile);
        }
        $output->writeln('');
        $output->writeln('<info>Example data load successful</info>');
    }

    private function loadSeries($series, $title)
    {
        $announce = true;
        $publicDate = new \DateTime('now');
        $title = $title;
        $subtitle = '';
        $description = '';
        $header = '';
        $footer = '';
        $copyright = 'UdN-TV';
        $keyword = '';
        $line2 = '';
        $locale = 'en';

        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        foreach ($this->pmk2AllLocales as $locale) {
            $series->setTitle($title, $locale);
            $series->setSubtitle($subtitle);
            $series->setDescription($description);
            $series->setHeader($header);
            $series->setFooter($footer);
            $series->setKeyword($keyword);
            $series->setLine2($line2);
        }
        $series->setCopyright($copyright);
    }

    private function loadPicSeries($series, $pic)
    {
        $seriesPicService = $this->getContainer()->get('pumukitschema.seriespic');
        $originalPicPath = realpath(dirname(__FILE__).'/../Resources/public/images/'.$pic.'.jpg');
        $picPath = realpath(dirname(__FILE__).'/../Resources/public/images').'/pic'.$pic.'.jpg';
        if (copy($originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic'.$pic.'.png', null, null, null, true);
            $series = $seriesPicService->addPicFile($series, $picFile);
        }
        $this->dm->persist($series);
        $this->dm->flush();
    }

    private function loadMultimediaobject($multimediaObject, $series, $title)
    {
        $rank = 1;
        $status = MultimediaObject::STATUS_PUBLISHED;
        $record_date = new \DateTime();
        $public_date = new \DateTime();
        $title = $title;
        $subtitle = 'subtitle lorem ipsum subtitle lorem ipsum subtitle lorem ipsum subtitle lorem ipsum';
        $description = "description dolor sit amet description dolor sit amet description dolor sit amet description dolor sit amet.\nDescription dolor sit amet description dolor sit amet description dolor sit amet";

        $multimediaObject->setRank($rank);
        $multimediaObject->setStatus($status);
        $multimediaObject->setSeries($series);
        $multimediaObject->setRecordDate($record_date);
        $multimediaObject->setPublicDate($public_date);
        foreach ($this->pmk2AllLocales as $locale) {
            $multimediaObject->setTitle($title, $locale);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
        }
    }

    private function loadTrackMultimediaObject($multimediaObject, $folder, $track, $audio)
    {
        $jobService = $this->getContainer()->get('pumukitencoder.job');
        $language = 'es';
        $description = [];
        if (true === $audio) {
            $path = realpath(dirname(__FILE__).'/../Resources/public/videos/'.$folder.'/'.$track.'.m4a');
            $jobService->createTrackWithFile($path, 'master_copy', $multimediaObject, $language, $description);
            $jobService->createTrackWithFile($path, 'audio_aac', $multimediaObject, $language, $description);
        } else {
            $path = realpath(dirname(__FILE__).'/../Resources/public/videos/'.$folder.'/'.$track.'.mp4');
            $jobService->createTrackWithFile($path, 'master_copy', $multimediaObject, $language, $description);
            $jobService->createTrackWithFile($path, 'video_h264', $multimediaObject, $language, $description);
        }
    }

    private function loadTagsMultimediaObject($multimediaObject, $tags)
    {
        $tags_repository = $this->getContainer()->get('doctrine_mongodb')->getRepository(Tag::class);
        $tagService = $this->getContainer()->get('pumukitschema.tag');
        $limit = count($tags);
        for ($i = 0; $i < $limit; ++$i) {
            $tag = $tags_repository->findOneBy(['cod' => $tags[$i]]);
            $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }
    }

    private function loadPeopleMultimediaObject($multimediaObject, $name, $role)
    {
        $personService = $this->getContainer()->get('pumukitschema.person');
        $person = new Person();
        $person->setName($name);
        $personService->savePerson($person);

        $personService->createRelationPerson($person, $role, $multimediaObject);
    }

    private function loadPicMultimediaObject($multimediaObject, $pic)
    {
        $mmsPicService = $this->getContainer()->get('pumukitschema.mmspic');
        $originalPicPath = realpath(dirname(__FILE__).'/../Resources/public/images/'.$pic.'.jpg');
        $picPath = realpath(dirname(__FILE__).'/../Resources/public/images').'/pic'.$pic.'.jpg';
        if (copy($originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic'.$pic.'.png', null, null, null, true);
            $multimediaObject = $mmsPicService->addPicFile($multimediaObject, $picFile);
        }
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    private function loadViewsLog($output)
    {
        $mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $allMmobjs = $mmobjRepo->findStandardBy([]);
        $useragents = ['Mozilla/5.0 PuMuKIT/2.2 (UserAgent Example Data.) Gecko/20100101 Firefox/40.1',
                             'Mozilla/5.0 PuMuKIT/2.2 (This is not the user agent you are looking for...) Gecko/20100101 Firefox/40.1',
                             'Mozilla/5.0 PuMuKIT/2.2 (The answer to everything: 42) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
                             'Mozilla/5.0 PuMuKIT/2.2 (Internet Explorer didn\'t survive) (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
        ];
        $clientips = ['123.213.231.132',
                            '0.0.0.1',
                            '12.12.12.21',
                            '74.125.224.72',
        ];

        $initTime = (new \DateTime('2 years ago'))->getTimestamp();
        $endTime = (new \DateTime())->getTimestamp();

        $clientip = $clientips[array_rand($clientips)];
        $useragent = $useragents[array_rand($useragents)];

        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output, count($allMmobjs));
        $output->writeln("\nCreating test views on ViewsLog...");
        $progress->setFormat('verbose');
        $progress->start();

        $logs = [];
        foreach ($allMmobjs as $id => $mmobj) {
            $progress->setProgress($id);
            for ($i = rand(1, 1000); $i > 0; --$i) {
                $randTimestamp = rand($initTime, $endTime);
                $logs[] = ['date' => new \MongoDate($randTimestamp),
                                'url' => 'http://localhost:8080/video/'.$mmobj->getId(),
                                'ip' => $clientip,
                                'userAgent' => $useragent,
                                'referer' => 'http://localhost:8080/series/'.$mmobj->getSeries()->getId(),
                                'multimediaObject' => new \MongoId($mmobj->getId()),
                                'series' => new \MongoId($mmobj->getSeries()->getId()), ];
                $mmobj->incNumview();
                $this->dm->persist($mmobj);
            }
        }
        $progress->setProgress(count($allMmobjs));
        $viewsLogColl->batchInsert($logs);
        $this->dm->flush();
        $progress->finish();
    }

    private function download($src, $target, $output)
    {
        $output->writeln('Downloading multimedia files to init the database:');
        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output, 100);
        $progress->start();

        $ch = curl_init($src);
        $targetFile = fopen($target, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $targetFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($c, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($progress) {
            $percentage = ($downloaded > 0 && $downloadSize > 0 ? round($downloaded / $downloadSize, 2) : 0.0);
            $progress->setProgress($percentage * 100);
        });
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        fclose($targetFile);
        curl_close($ch);
        $progress->finish();

        return 200 == $statusCode;
    }

    private function getRoleWithCode($code)
    {
        $role = $this->roleRepo->findOneByCod($code);
        if (null === $role) {
            throw new \Exception("Role with code '".$code."' not found. Please, init pumukit roles.");
        }

        return $role;
    }

    private function checkSeriesExists($seriesTitle)
    {
        $exist = $this->seriesRepo->findOneBySeriesProperty('dataexample', $seriesTitle);
        if (null !== $exist) {
            return true;
        } else {
            return false;
        }
    }
}
