<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\SeriesRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(name="text_index", keys={"textindex.text"="text", "secondarytextindex.text"="text"}, options={"language_override"="indexlanguage", "default_language"="none", "weights"={"textindex.text"=10, "secondarytextindex.text"=1}})
 * })
 */
class Series
{
    use Traits\Keywords;
    use Traits\Properties;
    use Traits\Pic {
        Traits\Pic::__construct as private __PicConstruct;
    }

    public const TYPE_SERIES = 0;
    public const TYPE_PLAYLIST = 1;

    public const SORT_MANUAL = 0;
    public const SORT_PUB_ASC = 1;
    public const SORT_PUB_DES = 2;
    public const SORT_REC_DES = 3;
    public const SORT_REC_ASC = 4;
    public const SORT_ALPHAB = 5;

    public static $sortCriteria = [
        self::SORT_MANUAL => ['rank' => 'asc'],
        self::SORT_PUB_ASC => ['public_date' => 'asc'],
        self::SORT_PUB_DES => ['public_date' => 'des'],
        self::SORT_REC_DES => ['record_date' => 'des'],
        self::SORT_REC_ASC => ['record_date' => 'asc'],
        self::SORT_ALPHAB => ['title.es' => 'asc'],
    ];

    public static $sortText = [
        self::SORT_MANUAL => 'manual',
        self::SORT_PUB_ASC => 'publication date ascending',
        self::SORT_PUB_DES => 'publication date descending',
        self::SORT_REC_DES => 'recording date descending',
        self::SORT_REC_ASC => 'recording date ascending',
        self::SORT_ALPHAB => 'title',
    ];

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\UniqueIndex()
     */
    private $numerical_id;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $secret;

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\Index
     */
    private $type;

    /**
     * @MongoDB\Field(type="int")
     */
    private $sorting = self::SORT_MANUAL;

    /**
     * @MongoDB\ReferenceOne(targetDocument=SeriesType::class, inversedBy="series", storeAs="id", cascade={"persist"})
     */
    private $series_type;

    /**
     * @MongoDB\ReferenceOne(targetDocument=SeriesStyle::class, inversedBy="series", storeAs="id", cascade={"persist"})
     */
    private $series_style;

    /**
     * @MongoDB\EmbedOne(targetDocument=Playlist::class)
     * @Serializer\Exclude
     */
    private $playlist;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $announce = false;

    /**
     * When series is hide and we access to the series with the mmobj->getSeries(),
     * it creates a pseudo series with default values (the WebTV filter dont permit to access hide series),
     * and we want to force that the series will be hide.
     *
     * @MongoDB\Field(type="bool")
     * @MongoDB\Index
     */
    private $hide = false;

    /**
     * @MongoDB\Field(type="date")
     * @MongoDB\Index
     */
    private $public_date;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $title = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $subtitle = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $comments;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $header = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $footer = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $line2 = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $textindex = [];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $secondarytextindex = [];

    /**
     * Used locale to override Translation listener`s locale this is not a mapped field of entity metadata, just a simple property.
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->secret = base_convert(sha1(uniqid((string) mt_rand(), true)), 16, 36);
        $this->hide = false;
        $this->playlist = new Playlist();
        $this->__PicConstruct();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function isCollection(): bool
    {
        return true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNumericalID()
    {
        return $this->numerical_id;
    }

    public function setNumericalID($numericalID)
    {
        return $this->numerical_id = $numericalID;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function resetSecret(): string
    {
        $this->secret = base_convert(sha1(uniqid((string) mt_rand(), true)), 16, 36);

        return $this->secret;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        return $this->type = $type;
    }

    public function isPlaylist(): bool
    {
        return self::TYPE_PLAYLIST === $this->type;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function getSortingCriteria(): array
    {
        return self::$sortCriteria[$this->sorting] ?? self::$sortCriteria[0];
    }

    public function setSorting($sorting)
    {
        return $this->sorting = $sorting;
    }

    public function setSeriesType(SeriesType $series_type): void
    {
        $this->series_type = $series_type;
    }

    public function getSeriesType()
    {
        return $this->series_type;
    }

    public function setSeriesStyle(SeriesStyle $series_style = null): void
    {
        $this->series_style = $series_style;
    }

    public function getSeriesStyle()
    {
        return $this->series_style;
    }

    public function setPlaylist(Playlist $playlist): void
    {
        $this->playlist = $playlist;
    }

    public function getPlaylist(): Playlist
    {
        return $this->playlist;
    }

    public function setAnnounce($announce): void
    {
        $this->announce = $announce;
    }

    public function getAnnounce(): bool
    {
        return $this->announce;
    }

    public function isAnnounce(): bool
    {
        return $this->announce;
    }

    public function setHide($hide): void
    {
        $this->hide = $hide;
    }

    public function getHide(): bool
    {
        return $this->hide;
    }

    public function isHide(): bool
    {
        return $this->hide === true;
    }

    public function setPublicDate($public_date): void
    {
        $this->public_date = $public_date;
    }

    public function getPublicDate()
    {
        return $this->public_date;
    }

    public function setTitle($title, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    public function getTitle($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->title[$locale] ?? '';
    }

    public function setI18nTitle(array $title): void
    {
        $this->title = $title;
    }

    public function getI18nTitle(): array
    {
        return $this->title;
    }

    public function setSubtitle($subtitle, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->subtitle[$locale] = $subtitle;
    }

    public function getSubtitle($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->subtitle[$locale] ?? '';
    }

    public function setI18nSubtitle(array $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getI18nSubtitle(): array
    {
        return $this->subtitle;
    }

    public function setDescription($description, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    public function getDescription($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->description[$locale] ?? '';
    }

    public function setComments($comments): void
    {
        $this->comments = $comments;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): array
    {
        return $this->description;
    }

    public function setHeader($header, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->header[$locale] = $header;
    }

    public function getHeader($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->header[$locale] ?? '';
    }

    public function setI18nHeader(array $header): void
    {
        $this->header = $header;
    }

    public function getI18nHeader(): array
    {
        return $this->header;
    }

    public function setFooter($footer, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->footer[$locale] = $footer;
    }

    public function getFooter($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->footer[$locale] ?? '';
    }

    public function setI18nFooter(array $footer): void
    {
        $this->footer = $footer;
    }

    public function getI18nFooter(): array
    {
        return $this->footer;
    }

    public function setCopyright($copyright): void
    {
        $this->setProperty('copyright', $copyright);
    }

    /**
     * @deprecated Dont use this method, use getProperty('copyright')
     */
    public function getCopyright()
    {
        return $this->getProperty('copyright');
    }

    /**
     * @deprecated  Dont use this method, use setProperty('license', $license)
     *
     * @param mixed $license
     */
    public function setLicense($license): void
    {
        $this->setProperty('license', $license);
    }

    /**
     * @deprecated Dont use this method, use getProperty('license')
     */
    public function getLicense()
    {
        return $this->getProperty('license');
    }

    public function setLine2($line2, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->line2[$locale] = $line2;
    }

    public function getLine2($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }

        return $this->line2[$locale] ?? '';
    }

    public function setI18nLine2(array $line2): void
    {
        $this->line2 = $line2;
    }

    public function getI18nLine2(): array
    {
        return $this->line2;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setTextIndex($textindex): void
    {
        $this->textindex = $textindex;
    }

    public function getTextIndex(): array
    {
        return $this->textindex;
    }

    public function setSecondaryTextIndex($secondarytextindex): void
    {
        $this->secondarytextindex = $secondarytextindex;
    }

    public function getSecondaryTextIndex(): array
    {
        return $this->secondarytextindex;
    }
}
