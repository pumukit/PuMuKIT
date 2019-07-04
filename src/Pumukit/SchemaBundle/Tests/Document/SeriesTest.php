<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Pic;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $series_type = new SeriesType();
        $announce = true;
        $publicDate = new \DateTime('now');
        $title = 'title';
        $subtitle = 'subtitle';
        $description = 'description';
        $header = 'header';
        $footer = 'footer';
        $keyword = 'keyword';
        $line2 = 'line2';
        $locale = 'en';
        $properties = ['property1', 'property2'];

        $series = new Series();

        $series->setSeriesType($series_type);
        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setHeader($header);
        $series->setFooter($footer);
        $series->setKeyword($keyword);
        $series->setLine2($line2);
        $series->setLocale($locale);
        $series->setProperties($properties);

        $this->assertEquals($series_type, $series->getSeriesType());
        $this->assertEquals($announce, $series->getAnnounce());
        $this->assertEquals($publicDate, $series->getPublicDate());
        $this->assertEquals($title, $series->getTitle());
        $this->assertEquals($subtitle, $series->getSubtitle());
        $this->assertEquals($description, $series->getDescription());
        $this->assertEquals($header, $series->getHeader());
        $this->assertEquals($footer, $series->getFooter());
        $this->assertEquals($keyword, $series->getKeyword());
        $this->assertEquals($line2, $series->getLine2());
        $this->assertEquals($locale, $series->getLocale());
        $this->assertEquals($properties, $series->getProperties());

        $titleEs = 'título';
        $subtitleEs = 'subtítulo';
        $descriptionEs = 'descripción';
        $headerEs = 'cabecera';
        $footerEs = 'pie';
        $keywordEs = 'palabra clave';
        $line2Es = 'línea 2';
        $localeEs = 'es';

        $titleI18n = [$locale => $title, $localeEs => $titleEs];
        $subtitleI18n = [$locale => $subtitle, $localeEs => $subtitleEs];
        $descriptionI18n = [$locale => $description, $localeEs => $descriptionEs];
        $headerI18n = [$locale => $header, $localeEs => $headerEs];
        $footerI18n = [$locale => $footer, $localeEs => $footerEs];
        $keywordI18n = [$locale => $keyword, $localeEs => $keywordEs];
        $line2I18n = [$locale => $line2, $localeEs => $line2Es];

        $series->setI18nTitle($titleI18n);
        $series->setI18nSubtitle($subtitleI18n);
        $series->setI18nDescription($descriptionI18n);
        $series->setI18nHeader($headerI18n);
        $series->setI18nFooter($footerI18n);
        $series->setI18nKeyword($keywordI18n);
        $series->setI18nLine2($line2I18n);

        $this->assertEquals($titleI18n, $series->getI18nTitle());
        $this->assertEquals($subtitleI18n, $series->getI18nSubtitle());
        $this->assertEquals($descriptionI18n, $series->getI18nDescription());
        $this->assertEquals($headerI18n, $series->getI18nHeader());
        $this->assertEquals($footerI18n, $series->getI18nFooter());
        $this->assertEquals($keywordI18n, $series->getI18nKeyword());
        $this->assertEquals($line2I18n, $series->getI18nLine2());

        $title = null;
        $subtitle = null;
        $description = null;
        $header = null;
        $footer = null;
        $keyword = null;
        $line2 = null;

        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setHeader($header);
        $series->setFooter($footer);
        $series->setKeyword($keyword);
        $series->setLine2($line2);

        $this->assertEquals(null, $series->getTitle());
        $this->assertEquals(null, $series->getSubtitle());
        $this->assertEquals(null, $series->getDescription());
        $this->assertEquals(null, $series->getHeader());
        $this->assertEquals(null, $series->getFooter());
        $this->assertEquals(null, $series->getKeyword());
        $this->assertEquals(null, $series->getLine2());
    }

    public function testToString()
    {
        $series = new Series();
        $this->assertEquals($series->getTitle(), $series->__toString());
    }

    public function testPicsInSeries()
    {
        $url = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'logo.png';
        $pic = new Pic();
        $pic->setUrl($url);

        $series = new Series();

        $this->assertEquals(0, count($series->getPics()));

        $series->addPic($pic);

        $this->assertEquals(1, count($series->getPics()));
        $this->assertTrue($series->containsPic($pic));

        $series->removePic($pic);

        $this->assertEquals(0, count($series->getPics()));
        $this->assertFalse($series->containsPic($pic));

        $picWithoutUrl = new Pic();

        $series->addPic($picWithoutUrl);
        $series->addPic($pic);

        $this->assertEquals(2, count($series->getPics()));
        $this->assertEquals($url, $series->getFirstUrlPic());
    }

    public function testIsCollection()
    {
        $series = new Series();
        $this->assertEquals(true, $series->isCollection());
    }
}
