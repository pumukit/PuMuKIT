<?php

namespace Pumukit\TranscoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pumukit\TranscoBundle\Entity\VideoProfile
 *
 * @ORM\Table(name="video_profile")
 * @ORM\Entity
 */
//FIXME behavior sortable, hideable
class VideoProfile
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
     * @ORM\Column(name="name", type="string", length=25, nullable=false)
     */
    private $name;

    /**
     * @var string $exec_template
     *
     * @ORM\Column(name="exec_template", type="string", length=255, nullable=false)
     */
    private $exec_template;

    /**
     * @var boolean $wizard
     *
     * @ORM\Column(name="wizard", type="boolean", nullable=false)
     */
    private $wizard;

    /**
     * @var boolean $file_master
     *
     * @ORM\Column(name="file_master", type="boolean")
     */
    private $file_master;

    /**
     * @var boolean $file_hide
     *
     * @ORM\Column(name="file_hide", type="boolean")
     */
    private $file_hide;

    /**
     * @var string $file_cfg
     *
     * @ORM\Column(name="file_cfg", type="string", length=150)
     */
    private $file_cfg;

    /**
     * @var integer $streamserver_id
     *
     * @ORM\Column(name="streamserver_id", type="integer")
     */
    private $streamserver_id;

    /**
     * @var string $prescript
     *
     * @ORM\Column(name="prescript", type="string", length=255)
     */
    private $prescript;

    /**
     * @var string $prescript_type
     *
     * @ORM\Column(name="prescript_type", type="string", length=5)
     */
    private $prescript_type;

    /**
     * @var string $mime_type
     *
     * @ORM\Column(name="mime_type", type="string", length=35)
     */
    private $mime_type;

    /**
     * @var string $extension
     *
     * @ORM\Column(name="extension", type="string", length=5)
     */
    private $extension;

    /**
     * @var string $format
     *
     * @ORM\Column(name="format", type="string", length=35)
     */
    private $format;

    /**
     * @var string $video_codec
     *
     * @ORM\Column(name="video_codec", type="string", length=35)
     */
    private $video_codec;

    /**
     * @var string $audio_codec
     *
     * @ORM\Column(name="audio_codec", type="string", length=35)
     */
    private $audio_codec;

    /**
     * @var integer $frame_width
     *
     * @ORM\Column(name="frame_width", type="integer")
     */
    private $frame_width;

    /**
     * @var integer $frame_height
     *
     * @ORM\Column(name="frame_height", type="integer")
     */
    private $frame_height;

    /**
     * @var integer $frame_rate
     *
     * @ORM\Column(name="frame_rate", type="integer")
     */
    private $frame_rate;

    /**
     * @var string $video_bit_rate
     *
     * @ORM\Column(name="video_bit_rate", type="string", length=50)
     */
    private $video_bit_rate;

    /**
     * @var string $audio_bit_rate
     *
     * @ORM\Column(name="audio_bit_rate", type="string", length=50)
     */
    private $audio_bit_rate;

    /**
     * @var integer $audio_channels
     *
     * @ORM\Column(name="audio_channels", type="integer")
     */
    private $audio_channels;

    /**
     * @var boolean $only_audio
     *
     * @ORM\Column(name="only_audio", type="boolean")
     */
    private $only_audio;

    /**
     * @var integer $rel_size_duration
     *
     * @ORM\Column(name="rel_size_duration", type="integer")
     */
    private $rel_size_duration;

    /**
     * @var string $link
     *
     * @ORM\Column(name="link", type="string", length=100)
     */
    private $link;

    /**
     * @var string $description
     *
     * @ORM\Column(name="description", type="string", length=200)
     */
    private $description;


    /**
     * @var ArrayCollection $cpus
     *
     * @ORM\ManyToMany(targetEntity="Cpu", inversedBy="video_profiles")
     * @ORM\JoinTable(name="video_profile_cpu",
     *      joinColumns={@ORM\JoinColumn(name="video_profile_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="cpu_id", referencedColumnName="id")}
     *      )
     */
    private $cpus;


    /**
     * @var ArrayCollection $jobs
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="video_profile")
     */
    private $jobs;


    public function __construct()
    {
        $this->cpus = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set exec_template
     *
     * @param string $execTemplate
     */
    public function setExecTemplate($execTemplate)
    {
        $this->exec_template = $execTemplate;
    }

    /**
     * Get exec_template
     *
     * @return string $execTemplate
     */
    public function getExecTemplate()
    {
        return $this->exec_template;
    }

    /**
     * Set wizard
     *
     * @param boolean $wizard
     */
    public function setWizard($wizard)
    {
        $this->wizard = $wizard;
    }

    /**
     * Get wizard
     *
     * @return boolean $wizard
     */
    public function getWizard()
    {
        return $this->wizard;
    }

    /**
     * Set file_master
     *
     * @param boolean $fileMaster
     */
    public function setFileMaster($fileMaster)
    {
        $this->file_master = $fileMaster;
    }

    /**
     * Get file_master
     *
     * @return boolean $fileMaster
     */
    public function getFileMaster()
    {
        return $this->file_master;
    }

    /**
     * Set file_hide
     *
     * @param boolean $fileHide
     */
    public function setFileHide($fileHide)
    {
        $this->file_hide = $fileHide;
    }

    /**
     * Get file_hide
     *
     * @return boolean $fileHide
     */
    public function getFileHide()
    {
        return $this->file_hide;
    }

    /**
     * Set file_cfg
     *
     * @param string $fileCfg
     */
    public function setFileCfg($fileCfg)
    {
        $this->file_cfg = $fileCfg;
    }

    /**
     * Get file_cfg
     *
     * @return string $fileCfg
     */
    public function getFileCfg()
    {
        return $this->file_cfg;
    }

    /**
     * Set streamserver_id
     *
     * @param integer $streamserverId
     */
    public function setStreamserverId($streamserverId)
    {
        $this->streamserver_id = $streamserverId;
    }

    /**
     * Get streamserver_id
     *
     * @return integer $streamserverId
     */
    public function getStreamserverId()
    {
        return $this->streamserver_id;
    }

    /**
     * Set prescript
     *
     * @param string $prescript
     */
    public function setPrescript($prescript)
    {
        $this->prescript = $prescript;
    }

    /**
     * Get prescript
     *
     * @return string $prescript
     */
    public function getPrescript()
    {
        return $this->prescript;
    }

    /**
     * Set prescript_type
     *
     * @param string $prescriptType
     */
    public function setPrescriptType($prescriptType)
    {
        $this->prescript_type = $prescriptType;
    }

    /**
     * Get prescript_type
     *
     * @return string $prescriptType
     */
    public function getPrescriptType()
    {
        return $this->prescript_type;
    }

    /**
     * Set mime_type
     *
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mime_type = $mimeType;
    }

    /**
     * Get mime_type
     *
     * @return string $mimeType
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Set extension
     *
     * @param string $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Get extension
     *
     * @return string $extension
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set format
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Get format
     *
     * @return string $format
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set video_codec
     *
     * @param string $videoCodec
     */
    public function setVideoCodec($videoCodec)
    {
        $this->video_codec = $videoCodec;
    }

    /**
     * Get video_codec
     *
     * @return string $videoCodec
     */
    public function getVideoCodec()
    {
        return $this->video_codec;
    }

    /**
     * Set audio_codec
     *
     * @param string $audioCodec
     */
    public function setAudioCodec($audioCodec)
    {
        $this->audio_codec = $audioCodec;
    }

    /**
     * Get audio_codec
     *
     * @return string $audioCodec
     */
    public function getAudioCodec()
    {
        return $this->audio_codec;
    }

    /**
     * Set frame_width
     *
     * @param integer $frameWidth
     */
    public function setFrameWidth($frameWidth)
    {
        $this->frame_width = $frameWidth;
    }

    /**
     * Get frame_width
     *
     * @return integer $frameWidth
     */
    public function getFrameWidth()
    {
        return $this->frame_width;
    }

    /**
     * Set frame_height
     *
     * @param integer $frameHeight
     */
    public function setFrameHeight($frameHeight)
    {
        $this->frame_height = $frameHeight;
    }

    /**
     * Get frame_height
     *
     * @return integer $frameHeight
     */
    public function getFrameHeight()
    {
        return $this->frame_height;
    }

    /**
     * Set frame_rate
     *
     * @param integer $frameRate
     */
    public function setFrameRate($frameRate)
    {
        $this->frame_rate = $frameRate;
    }

    /**
     * Get frame_rate
     *
     * @return integer $frameRate
     */
    public function getFrameRate()
    {
        return $this->frame_rate;
    }

    /**
     * Set video_bit_rate
     *
     * @param string $videoBitRate
     */
    public function setVideoBitRate($videoBitRate)
    {
        $this->video_bit_rate = $videoBitRate;
    }

    /**
     * Get video_bit_rate
     *
     * @return string $videoBitRate
     */
    public function getVideoBitRate()
    {
        return $this->video_bit_rate;
    }

    /**
     * Set audio_bit_rate
     *
     * @param string $audioBitRate
     */
    public function setAudioBitRate($audioBitRate)
    {
        $this->audio_bit_rate = $audioBitRate;
    }

    /**
     * Get audio_bit_rate
     *
     * @return string $audioBitRate
     */
    public function getAudioBitRate()
    {
        return $this->audio_bit_rate;
    }

    /**
     * Set audio_channels
     *
     * @param integer $audioChannels
     */
    public function setAudioChannels($audioChannels)
    {
        $this->audio_channels = $audioChannels;
    }

    /**
     * Get audio_channels
     *
     * @return integer $audioChannels
     */
    public function getAudioChannels()
    {
        return $this->audio_channels;
    }

    /**
     * Set only_audio
     *
     * @param boolean $onlyAudio
     */
    public function setOnlyAudio($onlyAudio)
    {
        $this->only_audio = $onlyAudio;
    }

    /**
     * Get only_audio
     *
     * @return boolean $onlyAudio
     */
    public function getOnlyAudio()
    {
        return $this->only_audio;
    }

    /**
     * Set rel_size_duration
     *
     * @param integer $relSizeDuration
     */
    public function setRelSizeDuration($relSizeDuration)
    {
        $this->rel_size_duration = $relSizeDuration;
    }

    /**
     * Get rel_size_duration
     *
     * @return integer $relSizeDuration
     */
    public function getRelSizeDuration()
    {
        return $this->rel_size_duration;
    }

    /**
     * Set link
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Get link
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
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
     * Add cpus
     *
     * @param Pumukit\TranscoBundle\Entity\Cpu $cpus
     */
    public function addCpus(\Pumukit\TranscoBundle\Entity\Cpu $cpus)
    {
        $this->cpus[] = $cpus;
    }

    /**
     * Get cpus
     *
     * @return Doctrine\Common\Collections\Collection $cpus
     */
    public function getCpus()
    {
        return $this->cpus;
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
}