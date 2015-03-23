<?php

namespace Pumukit\ExampleDataBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
    //const $URL_VIDEO = "http://static.campusdomar.es/pumukit_videos.zip";
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
               $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array()); 
               $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
               $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
            }

            //Unzipping videos in folder
            $newFile = 'tmp_file.zip';
            if (!copy(self::PATH_VIDEO, $newFile)) {
                  echo "Failed to copy $file...\n";
            }
            $zip = new ZipArchive();
            if ($zip->open($newFile, ZIPARCHIVE::CREATE)==TRUE) {
                  $zip->extractTo(realpath(dirname(__FILE__) . '/../Resources/public/'));
                  $zip->close();
                  //unlink('tmp_file.zip');
            }

            //Series Access grid----------------------------------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Access grid';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Access grid';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/39.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/8/24.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/8/24.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 31000;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/17.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/17.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Access Grid';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //----------------------------------------------------------------------------

            //Series Uvigo----------------------------------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Uvigo';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Uvigo';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/7.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/9/26.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/9/26.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/19.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/19.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Uvigo';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //----------------------------------------------------------------------------

            //Series Robots----------------------------------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Robots';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Robots';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/22.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/10/38.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/10/38.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/21.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/21.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'AIBO';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();


            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/10/36.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/10/36.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/22.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/22.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Movil';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/10/28.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/10/28.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/23.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/23.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Fanuc';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/10/30.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/10/30.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/27.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/27.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Concurso';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/10/35.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/10/35.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/20.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/20.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Robonova';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //----------------------------------------------------------------------------

            //Series Polimedia----------------------------------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Polimedia';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Polimedia';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/37.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/11/34.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/11/34.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/38.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/38.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Armesto';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //------------------------------------------------------------------

            //Serie Energia de materiales y medio ambiente  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Energy materials and environment';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Energía de materiales y medio ambiente';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/32.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/12/40.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/12/40.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/28.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/28.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Energy materials and environment';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            //Serie Marine sciences  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Marine science';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Ciencias del mar';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/28.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/13/45.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/13/45.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/29.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/29.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Toralla';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            //Serie NOS register  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'NOS register';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Registro de NOS';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/41.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/14/46.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/14/46.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/31.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/31.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Isaac Díaz Pardo';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/14/47.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/14/47.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/30.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/30.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Promo';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            //Serie Zigzag  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Zigzag';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Zigzag';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/40.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/15/48.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/15/48.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/40.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/40.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Episode I';
            $subtitle = 'Zigzag';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            //Serie Quijote  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Quijote';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Quijote';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/35.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/16/53.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/16/53.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/33.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/33.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'First';
            $subtitle = 'Quijote';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/16/50.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/16/50.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/34.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/34.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Second';
            $subtitle = 'Quijote';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            //Serie autonomic  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Financing economic autonomy statutes';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'Financiación económica en los estatutos de autonomía';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/33.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/17/54.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/17/54.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/35.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/35.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Conference';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            //Serie HS  ------------------------------

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'HD';
            $subtitle = '';
            $description = '';
            $header = '';
            $footer = '';
            $copyright = 'UdN-TV';
            $keyword = '';
            $line2 = '';
            $locale = 'en';

            $series = $factoryService->createSeries();
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

            $titleEs = 'HD';
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
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $url = '/bundles/pumukitexampledata/images/36.jpg';
            $pic = new Pic();
            $pic->setUrl($url);
            $series->addPic($pic);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/18/56.mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/18/56.mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 64100;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/36.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/36.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic = new Pic();
            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Presentation';
            $subtitle = '';
            $description = "";

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);
            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

            //--------------------------------------------------------------------------------

            unlink('tmp_file.zip');

        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }
    }
}
