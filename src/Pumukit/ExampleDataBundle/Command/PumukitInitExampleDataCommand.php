<?php

namespace Pumukit\ExampleDataBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\StatsBundle\Document\ViewsLog;

class PumukitInitExampleDataCommand extends ContainerAwareCommand
{
    const PATH_VIDEO = 'http://static.campusdomar.es/pumukit_videos.zip';

    private $dm = null;
    private $repo = null;
    private $roleRepo;

    protected function configure()
    {
        $this
            ->setName('pumukit:init:example')
            ->setDescription('Load Pumukit example data fixtures to your database')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->addOption('noviewlogs', null, InputOption::VALUE_NONE, 'Does not add viewlog dummy views')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Add examples without deleting')
            ->addOption('reusezip', null, InputOption::VALUE_NONE, 'Set this parameter to not delete zip file with videos to reuse in the future')
            ->setHelp(<<<EOT

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
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $this->roleRepo = $this->dm->getRepository('PumukitSchemaBundle:Role');
        $this->seriesRepo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->pmk2AllLocales = $this->getContainer()->getParameter('pumukit2.locales');

        $factoryService = $this->getContainer()->get('pumukitschema.factory');

        if ($input->getOption('force')) {
            $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
            $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
            $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
            $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
            $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog')->remove(array());
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
            if ($zip->open($newFile, ZIPARCHIVE::CREATE) == true) {
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
            $this->load_series($series, 'Access grid');
            $this->load_pic_series($series, '39');
            $series->setProperty('dataexample', 'Access grid');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Access grid');
            $this->load_track_multimediaobject($multimediaObject, '8', '24', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUBCHANNELS', 'Dscience', 'Dhealth'));
            $this->load_people_multimediaobject($multimediaObject, 'Will', $actorRole);
            $this->load_pic_multimediaobject($multimediaObject, '17');
        }
        $progress->advance();

        //Series Uvigo
        if (!$this->checkSeriesExists('Uvigo')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Uvigo');
            $this->load_pic_series($series, '7');
            $series->setProperty('dataexample', 'Uvigo');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Uvigo');
            $this->load_track_multimediaobject($multimediaObject, '9', '26', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD3', 'DIRECTRIZ', 'Dhealth'));
            $this->load_pic_multimediaobject($multimediaObject, '19');
        }
        $progress->advance();

        //Series Robots
        if (!$this->checkSeriesExists('Robots')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Robots');
            $this->load_pic_series($series, '22');
            $series->setProperty('dataexample', 'Robots');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'AIBO');
            $this->load_track_multimediaobject($multimediaObject, '10', '38', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'Dscience', 'Dtechnical'));
            $this->load_pic_multimediaobject($multimediaObject, '21');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Movil');
            $this->load_track_multimediaobject($multimediaObject, '10', '36', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'Dscience', 'Dhumanities'));
            $this->load_people_multimediaobject($multimediaObject, 'Laura', $presenterRole);
            $this->load_pic_multimediaobject($multimediaObject, '22');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Fanuc');
            $this->load_track_multimediaobject($multimediaObject, '10', '28', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'DIRECTRIZ', 'Dhealth', 'Dtechnical'));
            $this->load_pic_multimediaobject($multimediaObject, '23');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Concurso');
            $this->load_track_multimediaobject($multimediaObject, '10', '30', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD3', 'Dsocial', 'Dhumanities'));
            $this->load_pic_multimediaobject($multimediaObject, '27');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Robonova');
            $this->load_track_multimediaobject($multimediaObject, '10', '35', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'DIRECTRIZ', 'Dscience', 'Dhumanities'));
            $this->load_pic_multimediaobject($multimediaObject, '20');
        }
        $progress->advance();

        //Series Polimedia
        if (!$this->checkSeriesExists('Polimedia')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Polimedia');
            $this->load_pic_series($series, '37');
            $series->setProperty('dataexample', 'Polimedia');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Armesto');
            $this->load_track_multimediaobject($multimediaObject, '11', '34', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'PUBDECISIONS', 'Dsocial', 'Dhumanities'));
            $this->load_pic_multimediaobject($multimediaObject, '38');
        }
        $progress->advance();

        //Serie Energia de materiales y medio ambiente
        if (!$this->checkSeriesExists('Energy materials and environment')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Energy materials and environment');
            $this->load_pic_series($series, '32');
            $series->setProperty('dataexample', 'Energy materials and environment');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Energy materials and environment');
            $this->load_track_multimediaobject($multimediaObject, '12', '40', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'Dhealth', 'Dtechnical'));
            $this->load_people_multimediaobject($multimediaObject, 'Marcos', $presenterRole);
            $this->load_pic_multimediaobject($multimediaObject, '28');
        }
        $progress->advance();

        //Serie Marine sciences
        if (!$this->checkSeriesExists('Marine sciences')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Marine sciences');
            $this->load_pic_series($series, '28');
            $series->setProperty('dataexample', 'Marine sciences');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Toralla');
            $this->load_track_multimediaobject($multimediaObject, '13', '45', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD2', 'PUDEPD3', 'Dscience', 'Dsocial'));
            $this->load_pic_multimediaobject($multimediaObject, '29');
        }
        $progress->advance();

        //Serie NOS register
        if (!$this->checkSeriesExists('NOS register')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'NOS register');
            $this->load_pic_series($series, '41');
            $series->setProperty('dataexample', 'NOS register');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Isaac Díaz Pardo');
            $this->load_track_multimediaobject($multimediaObject, '14', '46', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD3', 'Dsocial', 'Dhumanities'));
            $this->load_pic_multimediaobject($multimediaObject, '31');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Promo');
            $this->load_track_multimediaobject($multimediaObject, '14', '47', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'Dscience', 'Dtechnical'));
            $this->load_pic_multimediaobject($multimediaObject, '30');
        }
        $progress->advance();

        //Serie Zigzag
        if (!$this->checkSeriesExists('ZigZag')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'ZigZag');
            $this->load_pic_series($series, '40');
            $series->setProperty('dataexample', 'ZigZag');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Episode I');
            $this->load_track_multimediaobject($multimediaObject, '15', '48', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD1', 'PUBCHANNELS', 'DIRECTRIZ', 'Dsocial', 'Dhealth'));
            $this->load_pic_multimediaobject($multimediaObject, '40');
        }
        $progress->advance();

        //Serie Quijote
        if (!$this->checkSeriesExists('Quijote')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Quijote');
            $this->load_pic_series($series, '35');
            $series->setProperty('dataexample', 'Quijote');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'First');
            $this->load_track_multimediaobject($multimediaObject, '16', '53', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUDEPD3', 'PUCHWEBTV', 'Dtechnical', 'Dhumanities'));
            $this->load_people_multimediaobject($multimediaObject, 'Ana', $actorRole);
            $this->load_pic_multimediaobject($multimediaObject, '33');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Second');
            $this->load_track_multimediaobject($multimediaObject, '16', '50', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEPD2', 'Dsocial', 'Dtechnical'));
            $this->load_pic_multimediaobject($multimediaObject, '34');
        }
        $progress->advance();

        //Serie autonomic
        if (!$this->checkSeriesExists('Financing economic autonomy statutes')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Financing economic autonomy statutes');
            $this->load_pic_series($series, '33');
            $series->setProperty('dataexample', 'Financing economic autonomy statutes');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Conference');
            $this->load_track_multimediaobject($multimediaObject, '17', '54', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUDEREV', 'PUDEPD3', 'Dscience', 'Dhumanities'));
            $this->load_pic_multimediaobject($multimediaObject, '35');
        }
        $progress->advance();

        //Serie HD
        if (!$this->checkSeriesExists('HD')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'HD');
            $this->load_pic_series($series, '36');
            $series->setProperty('dataexample', 'HD');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Presentation');
            $this->load_track_multimediaobject($multimediaObject, '18', '56', false);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUDEPD1', 'DIRECTRIZ', 'Dsocial', 'Dtechnical'));
            $this->load_people_multimediaobject($multimediaObject, 'Sara', $presenterRole);
            $this->load_people_multimediaobject($multimediaObject, 'Carlos', $actorRole);
            $this->load_pic_multimediaobject($multimediaObject, '36');
        }
        $progress->advance();

        //Serie AUDIOS
        if (!$this->checkSeriesExists('Audios')) {
            $series = $factoryService->createSeries();
            $this->load_series($series, 'Audios');
            $this->load_pic_series($series, 'audio');
            $series->setProperty('dataexample', 'Audios');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Audio1');
            $this->load_track_multimediaobject($multimediaObject, '20', 'Audio1', true);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUDEPD1', 'DIRECTRIZ', 'Dsocial', 'Dtechnical'));
            $this->load_people_multimediaobject($multimediaObject, 'Sara', $presenterRole);
            $this->load_pic_multimediaobject($multimediaObject, 'audio');

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $this->load_multimediaobject($multimediaObject, $series, 'Audio2');
            $this->load_track_multimediaobject($multimediaObject, '20', 'Audio2', true);
            $this->load_tags_multimediaobject($multimediaObject, array('PUCHWEBTV', 'PUDENEW', 'PUBDECISIONS', 'PUDEPD1', 'DIRECTRIZ', 'Dsocial', 'Dtechnical'));
            $this->load_people_multimediaobject($multimediaObject, 'Sara', $presenterRole);
            $this->load_pic_multimediaobject($multimediaObject, 'audio');
        }
        $progress->advance();
        $progress->finish();
        if (!$input->getOption('noviewlogs')) {
            $this->load_viewsLog($this->dm, $output);
        }
        if (!$input->getOption('reusezip')) {
            unlink($newFile);
        }
        $output->writeln('');
        $output->writeln('<info>Example data load successful</info>');
    }

    private function load_series($series, $title)
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

    private function load_pic_series($series, $pic)
    {
        $seriesPicService = $this->getContainer()->get('pumukitschema.seriespic');
        $originalPicPath = realpath(dirname(__FILE__).'/../Resources/public/images/'.$pic.'.jpg');
        $picPath = realpath(dirname(__FILE__).'/../Resources/public/images').'/pic'.$pic.'.jpg';
        if (copy($originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic'.$pic.'.png', null, null, null, true);
            $series = $seriesPicService->addPicFile($series, $picFile);
        }
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();
    }

    private function load_multimediaobject($multimediaObject, $series, $title)
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

    private function load_track_multimediaobject($multimediaObject, $folder, $track, $audio)
    {
        $jobService = $this->getContainer()->get('pumukitencoder.job');
        $language = 'es';
        $description = array();
        if ($audio == true) {
            $path = realpath(dirname(__FILE__).'/../Resources/public/videos/'.$folder.'/'.$track.'.m4a');
            $jobService->createTrackWithFile($path, 'master_copy', $multimediaObject, $language, $description);
            $jobService->createTrackWithFile($path, 'audio_aac', $multimediaObject, $language, $description);
        } else {
            $path = realpath(dirname(__FILE__).'/../Resources/public/videos/'.$folder.'/'.$track.'.mp4');
            $jobService->createTrackWithFile($path, 'master_copy', $multimediaObject, $language, $description);
            $jobService->createTrackWithFile($path, 'video_h264', $multimediaObject, $language, $description);
        }
    }

    private function load_tags_multimediaobject($multimediaObject, $tags)
    {
        $tags_repository = $this->getContainer()->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
        for ($i = 0; $i < count($tags); ++$i) {
            $tag = $tags_repository->findOneBy(array('cod' => $tags[$i]));
            $tagService = $this->getContainer()->get('pumukitschema.tag');
            $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }
    }

    private function load_people_multimediaobject($multimediaObject, $name, $role)
    {
        $personService = $this->getContainer()->get('pumukitschema.person');
        $person = new Person();
        $person->setName($name);
        $personService->savePerson($person);

        $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
    }

    private function load_pic_multimediaobject($multimediaObject, $pic)
    {
        $mmsPicService = $this->getContainer()->get('pumukitschema.mmspic');
        $originalPicPath = realpath(dirname(__FILE__).'/../Resources/public/images/'.$pic.'.jpg');
        $picPath = realpath(dirname(__FILE__).'/../Resources/public/images').'/pic'.$pic.'.jpg';
        if (copy($originalPicPath, $picPath)) {
            $picFile = new UploadedFile($picPath, 'pic'.$pic.'.png', null, null, null, true);
            $multimediaObject = $mmsPicService->addPicFile($multimediaObject, $picFile);
        }
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();
    }

    private function load_viewsLog(DocumentManager $dm, $output)
    {
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $viewsLogColl = $dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $allMmobjs = $mmobjRepo->findStandardBy(array());
        $useragents = array('Mozilla/5.0 PuMuKIT/2.2 (UserAgent Example Data.) Gecko/20100101 Firefox/40.1',
                             'Mozilla/5.0 PuMuKIT/2.2 (This is not the user agent you are looking for...) Gecko/20100101 Firefox/40.1',
                             'Mozilla/5.0 PuMuKIT/2.2 (The answer to everything: 42) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
                             'Mozilla/5.0 PuMuKIT/2.2 (Internet Explorer didn\'t survive) (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
        );
        $clientips = array('123.213.231.132',
                            '0.0.0.1',
                            '12.12.12.21',
                            '74.125.224.72',
        );

        $initTime = (new \DateTime('2 years ago'))->getTimestamp();
        $endTime = (new \DateTime())->getTimestamp();

        $clientip = $clientips[array_rand($clientips)];
        $useragent = $useragents[array_rand($useragents)];

        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output, count($allMmobjs));
        $output->writeln("\nCreating test views on ViewsLog...");
        $progress->setFormat('verbose');
        $progress->start();

        $logs = array();
        foreach ($allMmobjs as $id => $mmobj) {
            $progress->setProgress($id);
            for ($i = rand(1, 1000); $i > 0; --$i) {
                $randTimestamp = rand($initTime, $endTime);
                $logs[] = array('date' => new \MongoDate($randTimestamp),
                                'url' => 'http://localhost:8080/video/'.$mmobj->getId(),
                                'ip' => $clientip,
                                'userAgent' => $useragent,
                                'referer' => 'http://localhost:8080/series/'.$mmobj->getSeries()->getId(),
                                'multimediaObject' => new \MongoId($mmobj->getId()),
                                'series' => new \MongoId($mmobj->getSeries()->getId()));
                $mmobj->incNumview();
                $dm->persist($mmobj);
            }
        }
        $progress->setProgress(count($allMmobjs));
        $viewsLogColl->batchInsert($logs);
        $dm->flush();
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

        return (200 == $statusCode);
    }

    private function getRoleWithCode($code)
    {
        $role = $this->roleRepo->findOneByCod($code);
        if (null == $role) {
            throw new \Exception("Role with code '".$code."' not found. Please, init pumukit roles.");
        }

        return $role;
    }

    private function checkSeriesExists($seriesTitle)
    {
        $exist = $this->seriesRepo->findOneBySeriesProperty('dataexample', $seriesTitle);
        if (null != $exist) {
            return true;
        } else {
            return false;
        }
    }
}
