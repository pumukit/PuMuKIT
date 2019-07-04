<?php

namespace Pumukit\EncoderBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pumukit\EncoderBundle\Document\Job.
 *
 * @MongoDB\Document(repositoryClass="Pumukit\EncoderBundle\Repository\JobRepository")
 */
class Job
{
    const STATUS_ERROR = -1;
    const STATUS_PAUSED = 0;
    const STATUS_WAITING = 1;
    const STATUS_EXECUTING = 2;
    const STATUS_FINISHED = 3;

    /**
     * Status codes translation table.
     *
     * @var array
     */
    public static $statusTexts = [
        self::STATUS_ERROR => 'Error',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_WAITING => 'Waiting',
        self::STATUS_EXECUTING => 'Executing',
        self::STATUS_FINISHED => 'Finished',
    ];

    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $mm_id;

    /**
     * //@var int $language_id
     * // language code instead of integer
     * //@MongoDB\Field(type="int").
     */
    //private $language_id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $language_id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $profile;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $cpu;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $url;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     * @MongoDB\Index
     */
    private $status = self::STATUS_WAITING;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $priority;

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $name = ['en' => ''];

    /**
     * @var string
     *
     * @MongoDB\Field(type="raw")
     */
    private $description = ['en' => ''];

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $timeini;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $timestart;

    /**
     * @var \DateTime
     *
     * @MongoDB\Field(type="date")
     */
    private $timeend;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $pid;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $path_ini;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $path_end;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $ext_ini;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $ext_end;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $duration = 0;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int")
     */
    private $new_duration = 0;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $size = '0';

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @Assert\Email
     */
    private $email;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $output = '';

    /**
     * @var array
     *
     * @MongoDB\Field(type="raw")
     */
    private $initVars = [];

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set mm_id.
     *
     * @param string $mm_id
     */
    public function setMmId($mm_id)
    {
        $this->mm_id = $mm_id;
    }

    /**
     * Get mm_id.
     *
     * @return string
     */
    public function getMmId()
    {
        return $this->mm_id;
    }

    /**
     * Set language_id.
     *
     * @param string $language_id
     */
    public function setLanguageId($language_id)
    {
        $this->language_id = $language_id;
    }

    /**
     * Get language_id.
     *
     * @return string
     */
    public function getLanguageId()
    {
        return $this->language_id;
    }

    /**
     * Set profile.
     *
     * @param string $profile
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get profile.
     *
     * @return string
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set cpu.
     *
     * @param string $cpu
     */
    public function setCpu($cpu)
    {
        $this->cpu = $cpu;
    }

    /**
     * Get cpu.
     *
     * @return string
     */
    public function getCpu()
    {
        return $this->cpu;
    }

    /**
     * Set url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set status.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set priority.
     *
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set name.
     *
     * @param $name
     * @param null|string $locale
     */
    public function setName($name, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->name[$locale] = $name;
    }

    /**
     * Get name.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getName($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->name[$locale])) {
            return '';
        }

        return $this->name[$locale];
    }

    /**
     * Set I18n name.
     *
     * @param array $name
     */
    public function setI18nName(array $name)
    {
        $this->name = $name;
    }

    /**
     * Get I18n name.
     *
     * @return string
     */
    public function getI18nName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param $description
     * @param null|string $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param null|string $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    /**
     * Set I18n description.
     *
     * @param array $description
     */
    public function setI18nDescription(array $description)
    {
        $this->description = $description;
    }

    /**
     * Get I18n description.
     *
     * @return string
     */
    public function getI18nDescription()
    {
        return $this->description;
    }

    /**
     * Set timeini.
     *
     * @param \DateTime $timeini
     */
    public function setTimeini($timeini)
    {
        $this->timeini = $timeini;
    }

    /**
     * Get timeini.
     *
     * @param null|string $format
     *
     * @return \DateTime|string
     */
    public function getTimeini($format = null)
    {
        if ((null === $this->timeini) || (null === $format)) {
            return $this->timeini;
        }

        return $this->timeini->format($format);
    }

    /**
     * Set timestart.
     *
     * @param \DateTime $timestart
     */
    public function setTimestart($timestart)
    {
        $this->timestart = $timestart;
    }

    /**
     * Get timestart.
     *
     * @param null|mixed $format
     *
     * @return \DateTime|string
     */
    public function getTimestart($format = null)
    {
        if ((null === $this->timestart) || (null === $format)) {
            return $this->timestart;
        }

        return $this->timestart->format($format);
    }

    /**
     * Set timeend.
     *
     * @param \DateTime $timeend
     */
    public function setTimeend($timeend)
    {
        $this->timeend = $timeend;
    }

    /**
     * Get timeend.
     *
     * @param null|mixed $format
     *
     * @return \DateTime|string
     */
    public function getTimeend($format = null)
    {
        if ((null === $this->timeend) || (null === $format)) {
            return $this->timeend;
        }

        return $this->timeend->format($format);
    }

    /**
     * Set pid.
     *
     * @param int $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * Get pid.
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set path_ini.
     *
     * @param string $path_ini
     */
    public function setPathIni($path_ini)
    {
        $this->path_ini = $path_ini;
    }

    /**
     * Get path_ini.
     *
     * @return string
     */
    public function getPathIni()
    {
        return $this->path_ini;
    }

    /**
     * Set path_end.
     *
     * @param string $path_end
     */
    public function setPathEnd($path_end)
    {
        $this->path_end = $path_end;
    }

    /**
     * Get path_end.
     *
     * @return string
     */
    public function getPathEnd()
    {
        return $this->path_end;
    }

    /**
     * Set ext_ini.
     *
     * @param string $ext_ini
     */
    public function setExtIni($ext_ini)
    {
        $this->ext_ini = $ext_ini;
    }

    /**
     * Get ext_ini.
     *
     * @return string
     */
    public function getExtIni()
    {
        return $this->ext_ini;
    }

    /**
     * Set ext_end.
     *
     * @param string $ext_end
     */
    public function setExtEnd($ext_end)
    {
        $this->ext_end = $ext_end;
    }

    /**
     * Get ext_end.
     *
     * @return string
     */
    public function getExtEnd()
    {
        return $this->ext_end;
    }

    /**
     * Set duration.
     *
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set new_duration.
     *
     * @param int $new_duration
     */
    public function setNewDuration($new_duration)
    {
        $this->new_duration = $new_duration;
    }

    /**
     * Get new_duration.
     *
     * @return int
     */
    public function getNewDuration()
    {
        return $this->new_duration;
    }

    /**
     * Set size.
     *
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get size.
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set email.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set output.
     *
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Append output.
     *
     * @param string $output
     */
    public function appendOutput($output)
    {
        $this->output .= $output;
    }

    /**
     * Get output.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Set initVars.
     *
     * @param string $initVars
     */
    public function setInitVars($initVars)
    {
        $this->initVars = $initVars;
    }

    /**
     * Get initVars.
     *
     * @return array
     */
    public function getInitVars()
    {
        return $this->initVars;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get status text.
     *
     * @return string
     */
    public function getStatusText()
    {
        return self::$statusTexts[$this->getStatus()];
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return self::STATUS_WAITING == $this->status || self::STATUS_PAUSED == $this->status;
    }

    /**
     * @return bool
     */
    public function isWaiting()
    {
        return self::STATUS_WAITING == $this->status;
    }

    /**
     * @return bool
     */
    public function isPaused()
    {
        return self::STATUS_PAUSED == $this->status;
    }

    /**
     * @return bool
     */
    public function isExecuting()
    {
        return self::STATUS_EXECUTING == $this->status;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return self::STATUS_ERROR == $this->status;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return self::STATUS_FINISHED == $this->status;
    }

    /**
     * @return bool
     */
    public function isExecuted()
    {
        return self::STATUS_ERROR == $this->status || self::STATUS_FINISHED == $this->status;
    }
}
