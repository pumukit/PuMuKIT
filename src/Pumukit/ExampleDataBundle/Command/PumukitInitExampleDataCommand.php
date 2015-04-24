<?php

namespace Pumukit\ExampleDataBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;


class PumukitInitExampleDataCommand extends ContainerAwareCommand
{
      const PATH_VIDEO = "http://static.campusdomar.es/pumukit_videos.zip";

      private $dm = null;
      private $repo = null;

      private $tagsPath = "../Resources/data/tags/";

      protected function configure()
      {
            $this
            ->setName('pumukit:init:example')
            ->setDescription('Load Pumukit expample data fixtures to your database')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT

            Command to load a data set of data into a database. Useful for init a demo Pumukit environment.

            The --force parameter has to be used to actually drop the database.

            The --append paramenter has to be used to add examples to database without deleting.

EOT
          );
      }

      protected function execute(InputInterface $input, OutputInterface $output)
      {
            $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $this->repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");
            $factoryService = $this->getContainer()->get('pumukitschema.factory'); 

            if ($input->getOption('force')) {
            
                  if ($input->getOption('append') != 1){
                        $this->dm->getDocumentCollection('PumukitEncoderBundle:Job')->remove(array());
                        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
                        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
                        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
                  }

                  //Unzipping videos in folder
                  $newFile = 'tmp_file.zip';
                  if (!$this->download(self::PATH_VIDEO, $newFile, $output)) {
                        echo "Failed to copy $file...\n";
                  }
                  $zip = new ZipArchive();
                  if ($zip->open($newFile, ZIPARCHIVE::CREATE)==TRUE) {
                        $zip->extractTo(realpath(dirname(__FILE__) . '/../Resources/public/'));
                        $zip->close();
                        //unlink('tmp_file.zip');
                  }

                  //Series Access grid
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Access grid");
                  $this->load_pic_series($series, '39');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Access grid");
                  $this->load_track_multimediaobject($multimediaObject, '8', '24');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUBDECISIONS","PUBCHANNELS","PUCHARCA","Dscience","Dhealth"));
                  $this->load_pic_multimediaobject($multimediaObject, '17');

                  //Series Uvigo
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Uvigo");
                  $this->load_pic_series($series, '7');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Uvigo");
                  $this->load_track_multimediaobject($multimediaObject, '9', '26');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEREV","PUDEPD3","PUCHWEBTV","DIRECTRIZ","Dhealth"));
                  $this->load_pic_multimediaobject($multimediaObject, '19');

                  //Series Robots
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Robots");
                  $this->load_pic_series($series, '22');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "AIBO");
                  $this->load_track_multimediaobject($multimediaObject, '10', '38');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD3","Dscience","Dtechnical"));
                  $this->load_pic_multimediaobject($multimediaObject, '21');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Movil");
                  $this->load_track_multimediaobject($multimediaObject, '10', '36');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","Dscience","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '22');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Fanuc");
                  $this->load_track_multimediaobject($multimediaObject, '10', '28');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD3","PUCHWEBTV","DIRECTRIZ","Dhealth","Dtechnical"));
                  $this->load_pic_multimediaobject($multimediaObject, '23');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Concurso");
                  $this->load_track_multimediaobject($multimediaObject, '10', '30');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEREV","PUDEPD3","PUCHWEBTV","Dsocial","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '27');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Robonova");
                  $this->load_track_multimediaobject($multimediaObject, '10', '35');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD3","DIRECTRIZ","Dscience","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '20');

                  //Series Polimedia
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Polimedia");
                  $this->load_pic_series($series, '37');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Armesto");
                  $this->load_track_multimediaobject($multimediaObject, '11', '34');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD3","PUBDECISIONS","Dsocial","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '38');

                  //Serie Energia de materiales y medio ambiente
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Energy materials and environment");
                  $this->load_pic_series($series, '32');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Energy materials and environment");
                  $this->load_track_multimediaobject($multimediaObject, '12', '40');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUCHARCA","Dhealth","Dtechnical"));
                  $this->load_pic_multimediaobject($multimediaObject, '28');

                  //Serie Marine sciences
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Marine sciences");
                  $this->load_pic_series($series, '28');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Toralla");
                  $this->load_track_multimediaobject($multimediaObject, '13', '45');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEREV","PUDEPD2","PUDEPD3","PUCHWEBTV","Dscience","Dsocial"));
                  $this->load_pic_multimediaobject($multimediaObject, '29');

                  //Serie NOS register
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "NOS register");
                  $this->load_pic_series($series, '41');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Isaac Díaz Pardo");
                  $this->load_track_multimediaobject($multimediaObject, '14', '46');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD3","PUCHWEBTV","Dsocial","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '31');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Promo");
                  $this->load_track_multimediaobject($multimediaObject, '14', '47');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUCHARCA","Dscience","Dtechnical"));
                  $this->load_pic_multimediaobject($multimediaObject, '30');

                  //Serie Zigzag
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "ZigZag");
                  $this->load_pic_series($series, '40');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Episode I");
                  $this->load_track_multimediaobject($multimediaObject, '15', '48');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD1","PUBCHANNELS","DIRECTRIZ","Dsocial","Dhealth"));
                  $this->load_pic_multimediaobject($multimediaObject, '40');

                  //Serie Quijote
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Quijote");
                  $this->load_pic_series($series, '35');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "First");
                  $this->load_track_multimediaobject($multimediaObject, '16', '53');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDEPD3","PUCHARCA","Dtechnical","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '33');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Second");
                  $this->load_track_multimediaobject($multimediaObject, '16', '50');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEPD2","PUCHWEBTV","Dsocial","Dtechnical"));
                  $this->load_pic_multimediaobject($multimediaObject, '34');

                  //Serie autonomic
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "Financing economic autonomy statutes");
                  $this->load_pic_series($series, '33');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Conference");
                  $this->load_track_multimediaobject($multimediaObject, '17', '54');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUDEREV","PUDEPD3","PUCHARCA","Dscience","Dhumanities"));
                  $this->load_pic_multimediaobject($multimediaObject, '35');

                  //Serie HD
                  $series = $factoryService->createSeries();
                  $this->load_series($series, "HD");
                  $this->load_pic_series($series, '36');

                  $multimediaObject = $factoryService->createMultimediaObject($series);
                  $this->load_multimediaobject($multimediaObject, $series, "Presentation");
                  $this->load_track_multimediaobject($multimediaObject, '18', '56');
                  $this->load_tags_multimediaobject($multimediaObject, array("PUDENEW","PUBDECISIONS","PUDEPD1","DIRECTRIZ","Dsocial","Dtechnical"));
                  $this->load_pic_multimediaobject($multimediaObject, '36');

                  unlink('tmp_file.zip');
                  $output->writeln('<info>Example data load successful</info>');

            } 
            else {
                  $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
                  $output->writeln('');
                  $output->writeln('<info>Would drop the database</info>');
                  $output->writeln('Please run the operation with --force to execute');
                  $output->writeln('<error>All data will be lost!</error>');

                  return -1;
            }
      }

      private function load_series($series, $title){
            $announce = true;
            $publicDate = new \DateTime("now");
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
            $series->setTitle($title);
            $series->setSubtitle($subtitle);
            $series->setDescription($description);
            $series->setHeader($header);
            $series->setFooter($footer);
            $series->setCopyright($copyright);
            $series->setKeyword($keyword);
            $series->setLine2($line2);
            $series->setLocale($locale);

            $titleEs = $title;
            $subtitleEs = '';
            $descriptionEs = '';
            $headerEs = '';
            $footerEs = '';
            $copyrightEs = 'UdN-TV';
            $keywordEs = '';
            $line2Es = '';
            $localeEs = 'es';

            $titleI18n = array($locale => $title, $localeEs => $titleEs);
            $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
            $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
            $headerI18n = array($locale => $header, $localeEs => $headerEs);
            $footerI18n = array($locale => $footer, $localeEs => $footerEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);
      }

      private function load_pic_series($series, $pic){
            $seriesPicService = $this->getContainer()->get('pumukitschema.seriespic'); 
            $originalPicPath = realpath(dirname(__FILE__) . '/../Resources/public/images/' . $pic .'.jpg');
            $picPath = realpath(dirname(__FILE__) . '/../Resources/public/images').'/pic' . $pic . '.jpg';
            if (copy($originalPicPath, $picPath)){
                  $picFile = new UploadedFile($picPath, 'pic' . $pic . '.png', null, null, null, true);
                  $series = $seriesPicService->addPicFile($series, $picFile);
            }
            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();
      }

      private function load_multimediaobject($multimediaObject, $series, $title){
            $rank = 3;
            $status = MultimediaObject::STATUS_PUBLISHED;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = $title;
            $subtitle = '';
            $description = "";
            $numview = 3;

            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);
      }

      private function load_track_multimediaobject($multimediaObject, $folder, $track){
            $jobService = $this->getContainer()->get('pumukitencoder.job'); 
            $language = 'es';
            $description = array();
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/' . $folder . '/' . $track . '.mp4');
            $jobService->createTrackWithFile($path, 'master_copy', $multimediaObject, $language, $description, $track);
            $jobService->createTrackWithFile($path, 'video_h264', $multimediaObject, $language, $description, $track);
      }

      private function load_tags_multimediaobject($multimediaObject, $tags){
            $tags_repository = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");
            for($i=0; $i<count($tags); $i++){
                  $tag = $tags_repository->findOneBy(array("cod" => $tags[$i]));
                  $tagService = $this->getContainer()->get('pumukitschema.tag'); 
                  $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
            }
      }

      private function load_pic_multimediaobject($multimediaObject, $pic){
            $mmsPicService = $this->getContainer()->get('pumukitschema.mmspic');
            $originalPicPath = realpath(dirname(__FILE__) . '/../Resources/public/images/' . $pic .'.jpg');
            $picPath = realpath(dirname(__FILE__) . '/../Resources/public/images').'/pic' . $pic . '.jpg';
            if (copy($originalPicPath, $picPath)){
                  $picFile = new UploadedFile($picPath, 'pic' . $pic . '.png', null, null, null, true);
                  $multimediaObject = $mmsPicService->addPicFile($multimediaObject, $picFile);
            }
            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();
      }

      private function download($src, $target, $output)
      {
            if (file_exists($target)) {
                  $output->writeln("Using existed file.");
                  return true;
            }
            $output->writeln("Downloading multimedia files to init the database:");
            $progress = new \Symfony\Component\Console\Helper\ProgressBar($output, 100);
            $progress->start();

            $ch = curl_init($src);
            $targetFile = fopen($target, 'wb');        
            curl_setopt($ch, CURLOPT_FILE, $targetFile);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($c, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($progress){
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
}
