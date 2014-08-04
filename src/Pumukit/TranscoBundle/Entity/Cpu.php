<?php

namespace Pumukit\TranscoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * Pumukit\TranscoBundle\Entity\Cpu
 *
 * @ORM\Table(name="cpu")
 * @ORM\Entity
 */
class Cpu
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
     * @var string $IP
     *
     * @ORM\Column(name="IP", type="string", length=15, unique=true, nullable=false)
     * @Assert\NotNull
     * @Assert\Ip
     * @Serializer\SerializedName("ip")
     */
    //FIXME Validar que no sea nulo
    //DISCASSME
    private $IP;

    /**
     * @var string $endpoint
     *
     * @ORM\Column(name="endpoint", type="string", length=150, nullable=false)
     * @Assert\NotNull
     * @Assert\Url
     */
    //FIXME
    private $endpoint = null;

    /**
     * @var string $so_type
     *
     * @ORM\Column(name="so_type", type="string", length=15, nullable=false)
     * @Assert\Choice(choices={"Linux"="Linux", "Windows"="Windows"}, message="Solo se sorta Linux o Windows")
     */
    //DISCUSSME Esto es un enum.
    private $so_type = null;

    /**
     * @var integer $max_jobs
     *
     * @ORM\Column(name="max_jobs", type="integer", nullable=false)
     */
    private $max_jobs = 0;

    /**
     * @var integer $num_jobs
     *
     * @ORM\Column(name="num_jobs", type="integer", nullable=false)
     */
    private $num_jobs = 0;

    /**
     * @var string $login
     *
     * @ORM\Column(name="login", type="string", length=100, nullable=true)
     */
    private $login = null;

    /**
     * @var string $passwd
     *
     * @ORM\Column(name="passwd", type="string", length=8, nullable=true)
     */
    private $passwd = null;

    /**
     * @var string $description
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var ArrayCollection $video_profiles
     *
     * @ORM\ManyToMany(targetEntity="VideoProfile", mappedBy="cpus")
      */
    private $video_profiles;

    /**
     * @var ArrayCollection $jobs
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="cpu")
     */
    private $jobs;

    public function __construct()
    {
      $this->video_profiles = new \Doctrine\Common\Collections\ArrayCollection();
      $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set IP
     *
     * @param string $IP
     */
    public function setIP($IP)
    {
        $this->IP = $IP;
    }

    /**
     * Get IP
     *
     * @return string $IP
     */
    public function getIP()
    {
        return $this->IP;
    }

    /**
     * Set endpoint
     *
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Get endpoint
     *
     * @return string $endpoint
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set so_type
     *
     * @param string $soType
     */
    public function setSoType($soType)
    {
        $this->so_type = $soType;
    }

    /**
     * Get so_type
     *
     * @return string $soType
     */
    public function getSoType()
    {
        return $this->so_type;
    }

    /**
     * Set max_jobs
     *
     * @param integer $maxJobs
     */
    public function setMaxJobs($maxJobs)
    {
        $this->max_jobs = $maxJobs;
    }

    /**
     * Get max_jobs
     *
     * @return integer $maxJobs
     */
    public function getMaxJobs()
    {
        return $this->max_jobs;
    }

    /**
     * Set num_jobs
     *
     * @param integer $numJobs
     */
    public function setNumJobs($numJobs)
    {
        $this->num_jobs = $numJobs;
    }

    /**
     * Get num_jobs
     *
     * @return integer $numJobs
     */
    public function getNumJobs()
    {
        return $this->num_jobs;
    }

    /**
     * Set login
     *
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * Get login
     *
     * @return string $login
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set passwd
     *
     * @param string $passwd
     */
    public function setPasswd($passwd)
    {
        $this->passwd = $passwd;
    }

    /**
     * Get passwd
     *
     * @return string $passwd
     */
    public function getPasswd()
    {
        return $this->passwd;
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
     * Add video_profiles
     *
     * @param Pumukit\TranscoBundle\Entity\VideoProfile $videoProfiles
     */
    public function addVideoProfiles(\Pumukit\TranscoBundle\Entity\VideoProfile $videoProfiles)
    {
        $this->video_profiles[] = $videoProfiles;
    }

    /**
     * Get video_profiles
     *
     * @return Doctrine\Common\Collections\Collection $videoProfiles
     */
    public function getVideoProfiles()
    {
        return $this->video_profiles;
    }

    /**
     * Add jobs
     *
     * @param Pumukit\TranscoBundle\Entity\Job $jobs
     */
    public function addJobs(\Pumukit\TranscoBundle\Entity\Job $jobs)
    {
        $this->jobs[] = $jobs;
    }

    /**
     * Get jobs
     *
     * @return Doctrine\Common\Collections\Collection $jobs
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * FIXME see Doctrine ArrayAccess.
     */
    public function set($key, $value)
    {
      $this->$key = $value;
    }

    public function __toString()
    {
      return $this->IP;
    }

}
