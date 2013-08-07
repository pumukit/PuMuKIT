<?php

namespace Pumukit\TranscoBundle\Entity;

/**
 * Pumukit\TranscoBundle\Entity\Cpu
 *
 * @orm:Table(name="cpu")
 * @orm:Entity
 */
class Cpu
{
    /**
     * @var integer $id
     *
     * @orm:Column(name="id", type="integer")
     * @orm:Id
     * @orm:GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $IP
     *
     * @orm:Column(name="IP", type="string", length=15, unique=true, nullable=false)
     * @assert:NotNull
     * @assert:Ip
     */
    //FIXME Validar que no sea nulo
    //DISCASSME
    private $IP;

    /**
     * @var string $endpoint
     *
     * @orm:Column(name="endpoint", type="string", length=150, nullable=false)
     * @assert:NotNull
     * @assert:Url
     */
    //FIXME
    private $endpoint = null;

    /**
     * @var string $so_type
     *
     * @orm:Column(name="so_type", type="string", length=15, nullable=false)
     * @assert:Choice(choices={"Linux"="Linux", "Windows"="Windows"}, message="Solo se sorta Linux o Windows")
     */
    //DISCUSSME Esto es un enum.
    private $so_type = null;

    /**
     * @var integer $max_jobs
     *
     * @orm:Column(name="max_jobs", type="integer", nullable=false)
     * @assert:Min(limit=0, message="Tiene que ser mayor que cero")
     */
    private $max_jobs = 0;

    /**
     * @var integer $num_jobs
     *
     * @orm:Column(name="num_jobs", type="integer", nullable=false)
     * @assert:Min(0)
     */
    private $num_jobs = 0;

    /**
     * @var string $login
     *
     * @orm:Column(name="login", type="string", length=100, nullable=true)
     */
    private $login = null;

    /**
     * @var string $passwd
     *
     * @orm:Column(name="passwd", type="string", length=8, nullable=true)
     */
    private $passwd = null;

    /**
     * @var string $description
     *
     * @orm:Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var ArrayCollection $video_profiles
     *
     * @orm:ManyToMany(targetEntity="VideoProfile", mappedBy="cpus")
      */
    private $video_profiles;

    /**
     * @var ArrayCollection $jobs
     *
     * @orm:OneToMany(targetEntity="Job", mappedBy="cpu")
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
    public function set($key, $value){
      $this->$key = $value;
    }


    public function __toString()
    {
      return $this->IP;
    }

}