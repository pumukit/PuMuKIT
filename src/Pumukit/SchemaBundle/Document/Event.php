<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\EventRepository")
 */
class Event
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\ReferenceOne(targetDocument=Live::class, cascade={"persist"})
     */
    private $live;

    /**
     * @MongoDB\Field(type="string")
     */
    private $name;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @MongoDB\Field(type="string")
     */
    private $place;

    /**
     * @MongoDB\Field(type="date")
     */
    private $date;

    /**
     * @MongoDB\Field(type="int")
     */
    private $duration = 60;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $display = true;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $create_serial = true;

    /**
     * @MongoDB\EmbedOne(targetDocument=Pic::class)
     */
    private $pic;

    private $locale = 'en';

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLive($live): void
    {
        $this->live = $live;
    }

    public function getLive(): ?Live
    {
        return $this->live;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
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

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): array
    {
        return $this->description;
    }

    public function setPlace($place): void
    {
        $this->place = $place;
    }

    public function getPlace()
    {
        return $this->place;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDisplay($display): void
    {
        $this->display = $display;
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    public function setCreateSerial($create_serial): void
    {
        $this->create_serial = $create_serial;
    }

    public function getCreateSerial(): bool
    {
        return $this->create_serial;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getSchedule(): array
    {
        return [
            'date' => $this->date,
            'duration' => $this->duration,
        ];
    }

    public function setSchedule($schedule): void
    {
        if ((!empty($schedule['date'])) && (!empty($schedule['duration']))) {
            $this->date = $schedule['date'];
            $this->duration = $schedule['duration'];
        }
    }

    public function setPic(Pic $pic): void
    {
        $this->pic = $pic;
    }

    public function removePic(): void
    {
        $this->pic = null;
    }

    public function getPic()
    {
        return $this->pic;
    }
}
