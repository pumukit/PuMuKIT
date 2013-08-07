<?php

namespace Pumukit\TranscoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\TranscoBundle\Entity\Job
 *
 * @ORM\Table(name="job")
 * @ORM\Entity
 */
class Job
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=150)
     */
    private $name;

    /**
     * @var string $exec
     *
     * @ORM\Column(name="exec", type="string", length=255)
     */
    private $exec;

    /**
     * @var integer $mm_obj_id
     *
     * @ORM\Column(name="mm_obj_id", type="integer")
     */
    private $mm_obj_id;

    /**
     * @var string $language
     *
     * @ORM\Column(name="language", type="string", length=2)
     */
    private $language;

    /**
     * @var integer $status_id
     *
     * @ORM\Column(name="status_id", type="integer")
     */
    private $status_id;

    /**
     * @var integer $priority
     *
     * @ORM\Column(name="priority", type="integer")
     */
    private $priority;

    /**
     * @var datetime $time_ini
     *
     * @ORM\Column(name="time_ini", type="datetime")
     */
    private $time_ini;

    /**
     * @var datetime $time_start
     *
     * @ORM\Column(name="time_start", type="datetime")
     */
    private $time_start;

    /**
     * @var datetime $time_end
     *
     * @ORM\Column(name="time_end", type="datetime")
     */
    private $time_end;

    /**
     * @var string $path_ini
     *
     * @ORM\Column(name="path_ini", type="string", length=250)
     */
    private $path_ini;

    /**
     * @var string $path_end
     *
     * @ORM\Column(name="path_end", type="string", length=250)
     */
    private $path_end;

    /**
     * @var integer $duration
     *
     * @ORM\Column(name="duration", type="integer")
     */
    private $duration;

    /**
     * @var integer $size
     *
     * @ORM\Column(name="size", type="integer")
     */
    private $size;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=30)
     */
    private $email;

    /**
     * @var integer $pid
     *
     * @ORM\Column(name="pid", type="integer")
     */
    private $pid;

    /**
     * @var string $postexec
     *
     * @ORM\Column(name="postexec", type="string", length=250)
     */
    private $postexec;

    /**
     * @var string $description
     *
     * @ORM\Column(name="description", type="string", length=200)
     */
    private $description;


    /**
     * @var VideoProfile $video_profile
     *
     * @ORM\ManyToOne(targetEntity="VideoProfile", inversedBy="jobs")
     */
    private $video_profile;


    /**
     * @var Cpu $cpu
     *
     * @ORM\ManyToOne(targetEntity="Cpu", inversedBy="jobs")
     */
    private $cpu;



    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set exec
     *
     * @param string $exec
     */
    public function setExec($exec)
    {
        $this->exec = $exec;
    }

    /**
     * Get exec
     *
     * @return string $exec
     */
    public function getExec()
    {
        return $this->exec;
    }

    /**
     * Set mm_obj_id
     *
     * @param integer $mmObjId
     */
    public function setMmObjId($mmObjId)
    {
        $this->mm_obj_id = $mmObjId;
    }

    /**
     * Get mm_obj_id
     *
     * @return integer $mmObjId
     */
    public function getMmObjId()
    {
        return $this->mm_obj_id;
    }

    /**
     * Set language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get language
     *
     * @return string $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set status_id
     *
     * @param integer $statusId
     */
    public function setStatusId($statusId)
    {
        $this->status_id = $statusId;
    }

    /**
     * Get status_id
     *
     * @return integer $statusId
     */
    public function getStatusId()
    {
        return $this->status_id;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get priority
     *
     * @return integer $priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set time_ini
     *
     * @param datetime $timeIni
     */
    public function setTimeIni($timeIni)
    {
        $this->time_ini = $timeIni;
    }

    /**
     * Get time_ini
     *
     * @return datetime $timeIni
     */
    public function getTimeIni()
    {
        return $this->time_ini;
    }

    /**
     * Set time_start
     *
     * @param datetime $timeStart
     */
    public function setTimeStart($timeStart)
    {
        $this->time_start = $timeStart;
    }

    /**
     * Get time_start
     *
     * @return datetime $timeStart
     */
    public function getTimeStart()
    {
        return $this->time_start;
    }

    /**
     * Set time_end
     *
     * @param datetime $timeEnd
     */
    public function setTimeEnd($timeEnd)
    {
        $this->time_end = $timeEnd;
    }

    /**
     * Get time_end
     *
     * @return datetime $timeEnd
     */
    public function getTimeEnd()
    {
        return $this->time_end;
    }

    /**
     * Set path_ini
     *
     * @param string $pathIni
     */
    public function setPathIni($pathIni)
    {
        $this->path_ini = $pathIni;
    }

    /**
     * Get path_ini
     *
     * @return string $pathIni
     */
    public function getPathIni()
    {
        return $this->path_ini;
    }

    /**
     * Set path_end
     *
     * @param string $pathEnd
     */
    public function setPathEnd($pathEnd)
    {
        $this->path_end = $pathEnd;
    }

    /**
     * Get path_end
     *
     * @return string $pathEnd
     */
    public function getPathEnd()
    {
        return $this->path_end;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration
     *
     * @return integer $duration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set size
     *
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get size
     *
     * @return integer $size
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * Get pid
     *
     * @return integer $pid
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set postexec
     *
     * @param string $postexec
     */
    public function setPostexec($postexec)
    {
        $this->postexec = $postexec;
    }

    /**
     * Get postexec
     *
     * @return string $postexec
     */
    public function getPostexec()
    {
        return $this->postexec;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set video_profile
     *
     * @param Pumukit\TranscoBundle\Entity\VideoProfile $videoProfile
     */
    public function setVideoProfile(\Pumukit\TranscoBundle\Entity\VideoProfile $videoProfile)
    {
        $this->video_profile = $videoProfile;
    }

    /**
     * Get video_profile
     *
     * @return Pumukit\TranscoBundle\Entity\VideoProfile $videoProfile
     */
    public function getVideoProfile()
    {
        return $this->video_profile;
    }

    /**
     * Set cpu
     *
     * @param Pumukit\TranscoBundle\Entity\Cpu $cpu
     */
    public function setCpu(\Pumukit\TranscoBundle\Entity\Cpu $cpu)
    {
        $this->cpu = $cpu;
    }

    /**
     * Get cpu
     *
     * @return Pumukit\TranscoBundle\Entity\Cpu $cpu
     */
    public function getCpu()
    {
        return $this->cpu;
    }
}