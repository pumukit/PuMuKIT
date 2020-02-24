<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;

/**
 * @internal
 * @coversNothing
 */
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

        static::assertEquals($series_type, $series->getSeriesType());
        static::assertEquals($announce, $series->getAnnounce());
        static::assertEquals($publicDate, $series->getPublicDate());
        static::assertEquals($title, $series->getTitle());
        static::assertEquals($subtitle, $series->getSubtitle());
        static::assertEquals($description, $series->getDescription());
        static::assertEquals($header, $series->getHeader());
        static::assertEquals($footer, $series->getFooter());
        static::assertEquals($keyword, $series->getKeyword());
        static::assertEquals($line2, $series->getLine2());
        static::assertEquals($locale, $series->getLocale());
        static::assertEquals($properties, $series->getProperties());

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

        static::assertEquals($titleI18n, $series->getI18nTitle());
        static::assertEquals($subtitleI18n, $series->getI18nSubtitle());
        static::assertEquals($descriptionI18n, $series->getI18nDescription());
        static::assertEquals($headerI18n, $series->getI18nHeader());
        static::assertEquals($footerI18n, $series->getI18nFooter());
        static::assertEquals($keywordI18n, $series->getI18nKeyword());
        static::assertEquals($line2I18n, $series->getI18nLine2());

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

        static::assertEquals(null, $series->getTitle());
        static::assertEquals(null, $series->getSubtitle());
        static::assertEquals(null, $series->getDescription());
        static::assertEquals(null, $series->getHeader());
        static::assertEquals(null, $series->getFooter());
        static::assertEquals(null, $series->getKeyword());
        static::assertEquals(null, $series->getLine2());
    }

    public function testToString()
    {
        $series = new Series();
        static::assertEquals($series->getTitle(), $series->__toString());
    }

    public function testPicsInSeries()
    {
        $url = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'logo.png';
        $pic = new Pic();
        $pic->setUrl($url);

        $series = new Series();

        static::assertCount(0, $series->getPics());

        $series->addPic($pic);

        static::assertCount(1, $series->getPics());
        static::assertTrue($series->containsPic($pic));

        $series->removePic($pic);

        static::assertCount(0, $series->getPics());
        static::assertFalse($series->containsPic($pic));

        $picWithoutUrl = new Pic();

        $series->addPic($picWithoutUrl);
        $series->addPic($pic);

        static::assertCount(2, $series->getPics());
        static::assertEquals($url, $series->getFirstUrlPic());
    }

    public function testIsCollection()
    {
        $series = new Series();
        static::assertEquals(true, $series->isCollection());
    }
}
