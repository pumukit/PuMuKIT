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

define("URL_VIDEOS", "http://static.campusdomar.es/pumukit_videos.zip");

class PumukitInitExampleDataCommand extends ContainerAwareCommand
{
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
        $newFile = 'tmp_file.zip';

        if (!copy(URL_VIDEOS, $newFile)) {
            echo "Failed to copy $file...\n";
        }

        $zip = new ZipArchive();
        if ($zip->open($newFile, ZIPARCHIVE::CREATE)==TRUE) {
            $zip->extractTo(realpath(dirname(__FILE__) . '/../Resources/public/'));
            $zip->close();
        }

        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");

        if ($input->getOption('force')) {
            
            if ($input->getOption('append') != 1){
               $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array()); 
               $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
               $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
            }

            //Series example 1

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Series example 1';
            $subtitle = 'Series';
            $description = 'This is a description of series example 1';
            $header = 'Header of series example 1';
            $footer = 'Footer of series example 1';
            $copyright = 'Copyright';
            $keyword = 'Keyword';
            $line2 = 'Line2';
            $locale = 'en';

            $series_example_1 = new Series();
            $series_example_1->setAnnounce($announce);
            $series_example_1->setPublicDate($publicDate);
            $series_example_1->setTitle($title);
            $series_example_1->setSubtitle($subtitle);
            $series_example_1->setDescription($description);
            $series_example_1->setHeader($header);
            $series_example_1->setFooter($footer);
            $series_example_1->setCopyright($copyright);
            $series_example_1->setKeyword($keyword);
            $series_example_1->setLine2($line2);
            $series_example_1->setLocale($locale);

            $titleEs = 'Serie de ejemplo 1';
            $subtitleEs = 'Serie';
            $descriptionEs = 'Esta es la descripción de la serie de ejemplo 1';
            $headerEs = 'Cabecera de la serie de ejemplo 1';
            $footerEs = 'Pie de la serie de ejemplo 1';
            $copyrightEs = 'Derechos de copia';
            $keywordEs = 'Palabra clave';
            $line2Es = 'Línea 2';
            $localeEs = 'es';

            $titleI18n = array($locale => $title, $localeEs => $titleEs);
            $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
            $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
            $headerI18n = array($locale => $header, $localeEs => $headerEs);
            $footerI18n = array($locale => $footer, $localeEs => $footerEs);
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series_example_1->setI18nTitle($titleI18n);
            $series_example_1->setI18nSubtitle($subtitleI18n);
            $series_example_1->setI18nDescription($descriptionI18n);
            $series_example_1->setI18nHeader($headerI18n);
            $series_example_1->setI18nFooter($footerI18n);
            $series_example_1->setI18nCopyright($copyrightI18n);
            $series_example_1->setI18nKeyword($keywordI18n);
            $series_example_1->setI18nLine2($line2I18n);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series_example_1);
            $dm->flush();

            //Track example 1

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/74638.flv';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/74638.flv');
            $mime = 'video/flv';
            $duration = 5000;
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

            $track_example_1 = new Track();
            $track_example_1->setTags($tags);
            $track_example_1->setLanguage($language);
            $track_example_1->setUrl($url);
            $track_example_1->setPath($path);
            $track_example_1->setMimeType($mime);
            $track_example_1->setDuration($duration);
            $track_example_1->setAcodec($acodec);
            $track_example_1->setVcodec($vcodec);
            $track_example_1->setBitrate($bitrate);
            $track_example_1->setFramerate($framerate);
            $track_example_1->setOnlyAudio($only_audio);
            $track_example_1->setChannels($channels);
            $track_example_1->setDuration($duration);
            $track_example_1->setWidth($width);
            $track_example_1->setHeight($height);
            $track_example_1->setHide($hide);
            $track_example_1->setNumview($numview);

            //Pic example 1

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/74638.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/74638.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic_example_1 = new Pic();
            $pic_example_1->setTags($tags);
            $pic_example_1->setUrl($url);
            $pic_example_1->setPath($path);
            $pic_example_1->setMimeType($mime);
            $pic_example_1->setSize($size);
            $pic_example_1->setWidth($width);
            $pic_example_1->setHeight($height);
            $pic_example_1->setHide($hide);

            //Multimedia object example 1

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Introduction';
            $subtitle = 'Intro';
            $description = "Introduction to video of Vigo university";
            $numview = 2;
            $tag1 = new Tag();
            $tag1->setCod('tag1');
            $tag2 = new Tag();
            $tag2->setCod('tag2');
            $tag3 = new Tag();
            $tag3->setCod('tag3');
            $mm_tags = array($tag1, $tag2, $tag3);

            $multimediaObject_example_1 = new MultimediaObject();
            $multimediaObject_example_1->setRank($rank);
            $multimediaObject_example_1->setStatus($status);
            $multimediaObject_example_1->setSeries($series_example_1);
            $multimediaObject_example_1->setRecordDate($record_date);
            $multimediaObject_example_1->setPublicDate($public_date);
            $multimediaObject_example_1->setTitle($title);
            $multimediaObject_example_1->setSubtitle($subtitle);
            $multimediaObject_example_1->setDescription($description);
            $multimediaObject_example_1->addTag($tag1);
            $multimediaObject_example_1->addTag($tag2);
            $multimediaObject_example_1->addTag($tag3);
            $multimediaObject_example_1->setNumview($numview);

            $multimediaObject_example_1->addPic($pic_example_1);
            $multimediaObject_example_1->addTrack($track_example_1);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject_example_1);
            $dm->flush();

            
            //Series example 2

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Series example 2';
            $subtitle = 'Series';
            $description = 'This is a description of series example 2';
            $header = 'Header of series example 2';
            $footer = 'Footer of series example 2';
            $copyright = 'Copyright';
            $keyword = 'Keyword';
            $line2 = 'Line2';
            $locale = 'en';

            $series_example_2 = new Series();
            $series_example_2->setAnnounce($announce);
            $series_example_2->setPublicDate($publicDate);
            $series_example_2->setTitle($title);
            $series_example_2->setSubtitle($subtitle);
            $series_example_2->setDescription($description);
            $series_example_2->setHeader($header);
            $series_example_2->setFooter($footer);
            $series_example_2->setCopyright($copyright);
            $series_example_2->setKeyword($keyword);
            $series_example_2->setLine2($line2);
            $series_example_2->setLocale($locale);

            $titleEs = 'Serie de ejemplo 2';
            $subtitleEs = 'Serie';
            $descriptionEs = 'Esta es la descripción de la serie de ejemplo 2';
            $headerEs = 'Cabecera de la serie de ejmeplo 2';
            $footerEs = 'Pie de la serie de ejemplo 2';
            $copyrightEs = 'Derechos de copia';
            $keywordEs = 'Palabra clave';
            $line2Es = 'Línea 2';
            $localeEs = 'es';

            $titleI18n = array($locale => $title, $localeEs => $titleEs);
            $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
            $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
            $headerI18n = array($locale => $header, $localeEs => $headerEs);
            $footerI18n = array($locale => $footer, $localeEs => $footerEs);
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series_example_2->setI18nTitle($titleI18n);
            $series_example_2->setI18nSubtitle($subtitleI18n);
            $series_example_2->setI18nDescription($descriptionI18n);
            $series_example_2->setI18nHeader($headerI18n);
            $series_example_2->setI18nFooter($footerI18n);
            $series_example_2->setI18nCopyright($copyrightI18n);
            $series_example_2->setI18nKeyword($keywordI18n);
            $series_example_2->setI18nLine2($line2I18n);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series_example_2);
            $dm->flush();

            //Track example 2

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/Invasiones Biológicas. Mejillón cebra. (Dreissena polymorpha).mp4';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/Invasiones Biológicas. Mejillón cebra. (Dreissena polymorpha).mp4');
            $mime = 'video/mpeg4-HP';
            $duration = 5000;
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

            $track_example_2 = new Track();
            $track_example_2->setTags($tags);
            $track_example_2->setLanguage($language);
            $track_example_2->setUrl($url);
            $track_example_2->setPath($path);
            $track_example_2->setMimeType($mime);
            $track_example_2->setDuration($duration);
            $track_example_2->setAcodec($acodec);
            $track_example_2->setVcodec($vcodec);
            $track_example_2->setBitrate($bitrate);
            $track_example_2->setFramerate($framerate);
            $track_example_2->setOnlyAudio($only_audio);
            $track_example_2->setChannels($channels);
            $track_example_2->setDuration($duration);
            $track_example_2->setWidth($width);
            $track_example_2->setHeight($height);
            $track_example_2->setHide($hide);
            $track_example_2->setNumview($numview);

            //Pic example 2

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/Invasiones Biológicas. Mejillón cebra. (Dreissena polymorpha).jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/Invasiones Biológicas. Mejillón cebra. (Dreissena polymorpha).jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic_example_2 = new Pic();
            $pic_example_2->setTags($tags);
            $pic_example_2->setUrl($url);
            $pic_example_2->setPath($path);
            $pic_example_2->setMimeType($mime);
            $pic_example_2->setSize($size);
            $pic_example_2->setWidth($width);
            $pic_example_2->setHeight($height);
            $pic_example_2->setHide($hide);

            //Multimedia object example 2

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Mejillón cebra';
            $subtitle = 'Invasiones biológicas';
            $description = "Description";
            $numview = 2;
            $tag1 = new Tag();
            $tag1->setCod('tag1');
            $tag2 = new Tag();
            $tag2->setCod('tag2');
            $tag3 = new Tag();
            $tag3->setCod('tag3');
            $mm_tags = array($tag1, $tag2, $tag3);

            $multimediaObject_example_2 = new MultimediaObject();
            $multimediaObject_example_2->setRank($rank);
            $multimediaObject_example_2->setStatus($status);
            $multimediaObject_example_2->setSeries($series_example_2);
            $multimediaObject_example_2->setRecordDate($record_date);
            $multimediaObject_example_2->setPublicDate($public_date);
            $multimediaObject_example_2->setTitle($title);
            $multimediaObject_example_2->setSubtitle($subtitle);
            $multimediaObject_example_2->setDescription($description);
            $multimediaObject_example_2->addTag($tag1);
            $multimediaObject_example_2->addTag($tag2);
            $multimediaObject_example_2->addTag($tag3);
            $multimediaObject_example_2->setNumview($numview);

            $multimediaObject_example_2->addPic($pic_example_2);
            $multimediaObject_example_2->addTrack($track_example_2);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject_example_2);
            $dm->flush();

            
            //Series example 3

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Series example 3';
            $subtitle = 'Series';
            $description = 'This is a description of series example 3';
            $header = 'Header of series example 3';
            $footer = 'Footer of series example 3';
            $copyright = 'Copyright';
            $keyword = 'Keyword';
            $line2 = 'Line2';
            $locale = 'en';

            $series_example_3 = new Series();
            $series_example_3->setAnnounce($announce);
            $series_example_3->setPublicDate($publicDate);
            $series_example_3->setTitle($title);
            $series_example_3->setSubtitle($subtitle);
            $series_example_3->setDescription($description);
            $series_example_3->setHeader($header);
            $series_example_3->setFooter($footer);
            $series_example_3->setCopyright($copyright);
            $series_example_3->setKeyword($keyword);
            $series_example_3->setLine2($line2);
            $series_example_3->setLocale($locale);

            $titleEs = 'Serie de ejemplo 3';
            $subtitleEs = 'Serie';
            $descriptionEs = 'Esta es la descripción de la serie de ejemplo 3';
            $headerEs = 'Cabecera de la serie de ejmplo 3';
            $footerEs = 'Pie de la serie de ejemplo 3';
            $copyrightEs = 'Derechos de copia';
            $keywordEs = 'Palabra clave';
            $line2Es = 'Línea 2';
            $localeEs = 'es';

            $titleI18n = array($locale => $title, $localeEs => $titleEs);
            $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
            $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
            $headerI18n = array($locale => $header, $localeEs => $headerEs);
            $footerI18n = array($locale => $footer, $localeEs => $footerEs);
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series_example_3->setI18nTitle($titleI18n);
            $series_example_3->setI18nSubtitle($subtitleI18n);
            $series_example_3->setI18nDescription($descriptionI18n);
            $series_example_3->setI18nHeader($headerI18n);
            $series_example_3->setI18nFooter($footerI18n);
            $series_example_3->setI18nCopyright($copyrightI18n);
            $series_example_3->setI18nKeyword($keywordI18n);
            $series_example_3->setI18nLine2($line2I18n);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series_example_3);
            $dm->flush();

            //Track example 3

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/The introduction of cats in islands ecosystems.flv';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/The introduction of cats in islands ecosystems.flv');
            $mime = 'video/mpeg4-HP';
            $duration = 5000;
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

            $track_example_3 = new Track();
            $track_example_3->setTags($tags);
            $track_example_3->setLanguage($language);
            $track_example_3->setUrl($url);
            $track_example_3->setPath($path);
            $track_example_3->setMimeType($mime);
            $track_example_3->setDuration($duration);
            $track_example_3->setAcodec($acodec);
            $track_example_3->setVcodec($vcodec);
            $track_example_3->setBitrate($bitrate);
            $track_example_3->setFramerate($framerate);
            $track_example_3->setOnlyAudio($only_audio);
            $track_example_3->setChannels($channels);
            $track_example_3->setDuration($duration);
            $track_example_3->setWidth($width);
            $track_example_3->setHeight($height);
            $track_example_3->setHide($hide);
            $track_example_3->setNumview($numview);

            //Pic example 3

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/The introduction of cats in islands ecosystems.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/The introduction of cats in islands ecosystems.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic_example_3 = new Pic();
            $pic_example_3->setTags($tags);
            $pic_example_3->setUrl($url);
            $pic_example_3->setPath($path);
            $pic_example_3->setMimeType($mime);
            $pic_example_3->setSize($size);
            $pic_example_3->setWidth($width);
            $pic_example_3->setHeight($height);
            $pic_example_3->setHide($hide);

            //Multimedia object example 3

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'The introduction of cats in islands ecosystems';
            $subtitle = 'Protection in islands ecosystems';
            $description = "Description";
            $numview = 2;
            $tag1 = new Tag();
            $tag1->setCod('tag1');
            $tag2 = new Tag();
            $tag2->setCod('tag2');
            $tag3 = new Tag();
            $tag3->setCod('tag3');
            $mm_tags = array($tag1, $tag2, $tag3);

            $multimediaObject_example_3 = new MultimediaObject();
            $multimediaObject_example_3->setRank($rank);
            $multimediaObject_example_3->setStatus($status);
            $multimediaObject_example_3->setSeries($series_example_3);
            $multimediaObject_example_3->setRecordDate($record_date);
            $multimediaObject_example_3->setPublicDate($public_date);
            $multimediaObject_example_3->setTitle($title);
            $multimediaObject_example_3->setSubtitle($subtitle);
            $multimediaObject_example_3->setDescription($description);
            $multimediaObject_example_3->addTag($tag1);
            $multimediaObject_example_3->addTag($tag2);
            $multimediaObject_example_3->addTag($tag3);
            $multimediaObject_example_3->setNumview($numview);

            $multimediaObject_example_3->addPic($pic_example_3);
            $multimediaObject_example_3->addTrack($track_example_3);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject_example_3);
            $dm->flush();


            //Series example 4

            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Series example 4';
            $subtitle = 'Series';
            $description = 'This is a description of series example 4';
            $header = 'Header of series example 4';
            $footer = 'Footer of series example 4';
            $copyright = 'Copyright';
            $keyword = 'Keyword';
            $line2 = 'Line2';
            $locale = 'en';

            $series_example_4 = new Series();
            $series_example_4->setAnnounce($announce);
            $series_example_4->setPublicDate($publicDate);
            $series_example_4->setTitle($title);
            $series_example_4->setSubtitle($subtitle);
            $series_example_4->setDescription($description);
            $series_example_4->setHeader($header);
            $series_example_4->setFooter($footer);
            $series_example_4->setCopyright($copyright);
            $series_example_4->setKeyword($keyword);
            $series_example_4->setLine2($line2);
            $series_example_4->setLocale($locale);

            $titleEs = 'Serie de ejemplo 4';
            $subtitleEs = 'Serie';
            $descriptionEs = 'Esta es la descripción de la serie de ejemplo 4';
            $headerEs = 'Cabecera de la serie de ejmplo 4';
            $footerEs = 'Pie de la serie de ejemplo 4';
            $copyrightEs = 'Derechos de copia';
            $keywordEs = 'Palabra clave';
            $line2Es = 'Línea 2';
            $localeEs = 'es';

            $titleI18n = array($locale => $title, $localeEs => $titleEs);
            $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
            $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
            $headerI18n = array($locale => $header, $localeEs => $headerEs);
            $footerI18n = array($locale => $footer, $localeEs => $footerEs);
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series_example_4->setI18nTitle($titleI18n);
            $series_example_4->setI18nSubtitle($subtitleI18n);
            $series_example_4->setI18nDescription($descriptionI18n);
            $series_example_4->setI18nHeader($headerI18n);
            $series_example_4->setI18nFooter($footerI18n);
            $series_example_4->setI18nCopyright($copyrightI18n);
            $series_example_4->setI18nKeyword($keywordI18n);
            $series_example_4->setI18nLine2($line2I18n);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series_example_4);
            $dm->flush();

            //Track example 4

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/What is the Campus do Mar_.webm';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/What is the Campus do Mar_.webm');
            $mime = 'video/mpeg4-HP';
            $duration = 5000;
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

            $track_example_4 = new Track();
            $track_example_4->setTags($tags);
            $track_example_4->setLanguage($language);
            $track_example_4->setUrl($url);
            $track_example_4->setPath($path);
            $track_example_4->setMimeType($mime);
            $track_example_4->setDuration($duration);
            $track_example_4->setAcodec($acodec);
            $track_example_4->setVcodec($vcodec);
            $track_example_4->setBitrate($bitrate);
            $track_example_4->setFramerate($framerate);
            $track_example_4->setOnlyAudio($only_audio);
            $track_example_4->setChannels($channels);
            $track_example_4->setDuration($duration);
            $track_example_4->setWidth($width);
            $track_example_4->setHeight($height);
            $track_example_4->setHide($hide);
            $track_example_4->setNumview($numview);

            //Pic example 4

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/What is the Campus do Mar_.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/What is the Campus do Mar_.jpg');
            $mime = 'image/jpg';
            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true;

            $pic_example_4 = new Pic();
            $pic_example_4->setTags($tags);
            $pic_example_4->setUrl($url);
            $pic_example_4->setPath($path);
            $pic_example_4->setMimeType($mime);
            $pic_example_4->setSize($size);
            $pic_example_4->setWidth($width);
            $pic_example_4->setHeight($height);
            $pic_example_4->setHide($hide);

            //Multimedia object example 4

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'The introduction of cats in islands ecosystems';
            $subtitle = 'Protection in islands ecosystems';
            $description = "Description";
            $numview = 2;
            $tag1 = new Tag();
            $tag1->setCod('tag1');
            $tag2 = new Tag();
            $tag2->setCod('tag2');
            $tag3 = new Tag();
            $tag3->setCod('tag3');
            $mm_tags = array($tag1, $tag2, $tag3);

            $multimediaObject_example_4 = new MultimediaObject();
            $multimediaObject_example_4->setRank($rank);
            $multimediaObject_example_4->setStatus($status);
            $multimediaObject_example_4->setSeries($series_example_4);
            $multimediaObject_example_4->setRecordDate($record_date);
            $multimediaObject_example_4->setPublicDate($public_date);
            $multimediaObject_example_4->setTitle($title);
            $multimediaObject_example_4->setSubtitle($subtitle);
            $multimediaObject_example_4->setDescription($description);
            $multimediaObject_example_4->addTag($tag1);
            $multimediaObject_example_4->addTag($tag2);
            $multimediaObject_example_4->addTag($tag3);
            $multimediaObject_example_4->setNumview($numview);

            $multimediaObject_example_4->addPic($pic_example_4);
            $multimediaObject_example_4->addTrack($track_example_4);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject_example_4);
            $dm->flush();

            //Person example

            $email = 'email@email.com';
            $name = 'Peter';
            $web = 'web';
            $phone = 'phone';
            $honorific = 'Mr';
            $firm = 'firm';
            $post = 'post';
            $bio = 'Biography of this person';

            $person = new Person();

            $person->setEmail($email);
            $person->setName($name);
            $person->setWeb($web);
            $person->setPhone($phone);
            $person->setHonorific($honorific);
            $person->setFirm($firm);
            $person->setPost($post);
            $person->setBio($bio);

            $honorificEs = 'Don';
            $firmEs = 'Firma de esta persona';
            $postEs = 'Post de esta persona';
            $bioEs = 'Biografía de esta persona';

            $i18nHonorific = array('en' => $honorific, 'es' => $honorificEs);
            $i18nFirm = array('en' => $firm, 'es' => $firmEs);
            $i18nPost = array('en' => $post, 'es' => $postEs);
            $i18nBio = array('en' => $bio, 'es' => $bioEs);

            $person->setI18nHonorific($i18nHonorific);
            $person->setI18nFirm($i18nFirm);
            $person->setI18nPost($i18nPost);
            $person->setI18nBio($i18nBio);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($person);
            $dm->flush();

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
