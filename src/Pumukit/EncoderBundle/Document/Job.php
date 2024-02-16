<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\EncoderBundle\Repository\JobRepository")
 *
 * @ApiResource(
 *          collectionOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}},
 *          itemOperations={"get"={"method"="GET", "access_control"="is_granted('ROLE_ACCESS_API')"}}
 *      )
 */
class Job
{
    public const STATUS_ERROR = -1;
    public const STATUS_PAUSED = 0;
    public const STATUS_WAITING = 1;
    public const STATUS_EXECUTING = 2;
    public const STATUS_FINISHED = 3;

    public static $statusTexts = [
        self::STATUS_ERROR => 'Error',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_WAITING => 'Waiting',
        self::STATUS_EXECUTING => 'Executing',
        self::STATUS_FINISHED => 'Finished',
    ];

    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     *
     * @MongoDB\Index
     */
    private $mm_id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $language_id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $profile;

    /**
     * @MongoDB\Field(type="string")
     */
    private $cpu;

    /**
     * @MongoDB\Field(type="string")
     */
    private $url;

    /**
     * @MongoDB\Field(type="int")
     *
     * @MongoDB\Index
     */
    private $status = self::STATUS_WAITING;

    /**
     * @MongoDB\Field(type="int")
     */
    private $priority;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @MongoDB\Field(type="date")
     */
    private $timeini;

    /**
     * @MongoDB\Field(type="date")
     */
    private $timestart;

    /**
     * @MongoDB\Field(type="date")
     */
    private $timeend;

    /**
     * @MongoDB\Field(type="int")
     */
    private $pid;

    /**
     * @MongoDB\Field(type="string")
     */
    private $path_ini;

    /**
     * @MongoDB\Field(type="string")
     */
    private $path_end;

    /**
     * @MongoDB\Field(type="string")
     */
    private $ext_ini;

    /**
     * @MongoDB\Field(type="string")
     */
    private $ext_end;

    /**
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @MongoDB\Field(type="int")
     */
    private $new_duration = 0;

    /**
     * @MongoDB\Field(type="string")
     */
    private $size = '0';

    /**
     * @MongoDB\Field(type="string")
     *
     * @Assert\Email
     */
    private $email;

    /**
     * @MongoDB\Field(type="string")
     */
    private $output = '';

    /**
     * @MongoDB\Field(type="raw")
     */
    private $initVars = [];

    private $locale = 'en';

    public function getId()
    {
        return $this->id;
    }

    public function setMmId($mm_id): void
    {
        $this->mm_id = $mm_id;
    }

    public function getMmId()
    {
        return $this->mm_id;
    }

    public function setLanguageId($language_id): void
    {
        $this->language_id = $language_id;
    }

    public function getLanguageId()
    {
        return $this->language_id;
    }

    public function setProfile($profile): void
    {
        $this->profile = $profile;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function setCpu($cpu): void
    {
        $this->cpu = $cpu;
    }

    public function getCpu()
    {
        return $this->cpu;
    }

    public function setUrl($url): void
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setPriority($priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setName(string $name, $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    public function getName($locale = null): string
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    public function setI18nName(array $name): void
    {
        $this->name = $name;
    }

    public function getI18nName(): array
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
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    public function setI18nDescription(array $description): void
    {
        $this->description = $description;
    }

    public function getI18nDescription(): array
    {
        return $this->description;
    }

    public function setTimeini($timeini): void
    {
        $this->timeini = $timeini;
    }

    public function getTimeini($format = null)
    {
        if ((null === $this->timeini) || (null === $format)) {
            return $this->timeini;
        }

        return $this->timeini->format($format);
    }

    public function setTimestart($timestart): void
    {
        $this->timestart = $timestart;
    }

    public function getTimestart($format = null)
    {
        if ((null === $this->timestart) || (null === $format)) {
            return $this->timestart;
        }

        return $this->timestart->format($format);
    }

    public function setTimeend($timeend): void
    {
        $this->timeend = $timeend;
    }

    public function getTimeend($format = null)
    {
        if ((null === $this->timeend) || (null === $format)) {
            return $this->timeend;
        }

        return $this->timeend->format($format);
    }

    public function setPid($pid): void
    {
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function setPathIni($path_ini): void
    {
        $this->path_ini = $path_ini;
    }

    public function getPathIni()
    {
        return $this->path_ini;
    }

    public function setPathEnd($path_end): void
    {
        $this->path_end = $path_end;
    }

    public function getPathEnd()
    {
        return $this->path_end;
    }

    public function setExtIni($ext_ini): void
    {
        $this->ext_ini = $ext_ini;
    }

    public function getExtIni()
    {
        return $this->ext_ini;
    }

    public function setExtEnd($ext_end): void
    {
        $this->ext_end = $ext_end;
    }

    public function getExtEnd()
    {
        return $this->ext_end;
    }

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setNewDuration($new_duration): void
    {
        $this->new_duration = $new_duration;
    }

    public function getNewDuration(): int
    {
        return $this->new_duration;
    }

    public function setSize($size): void
    {
        $this->size = $size;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setOutput($output): void
    {
        $this->output = $output;
    }

    public function appendOutput($output): void
    {
        $this->output .= $output;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function setInitVars($initVars): void
    {
        $this->initVars = $initVars;
    }

    public function getInitVars(): array
    {
        return $this->initVars;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getStatusText(): string
    {
        return self::$statusTexts[$this->getStatus()];
    }

    public function isPending(): bool
    {
        return self::STATUS_WAITING == $this->status || self::STATUS_PAUSED == $this->status;
    }

    public function isWaiting(): bool
    {
        return self::STATUS_WAITING == $this->status;
    }

    public function isPaused(): bool
    {
        return self::STATUS_PAUSED == $this->status;
    }

    public function isExecuting(): bool
    {
        return self::STATUS_EXECUTING == $this->status;
    }

    public function isFailed(): bool
    {
        return self::STATUS_ERROR == $this->status;
    }

    public function isFinished(): bool
    {
        return self::STATUS_FINISHED == $this->status;
    }

    public function isExecuted(): bool
    {
        return self::STATUS_ERROR == $this->status || self::STATUS_FINISHED == $this->status;
    }
}
