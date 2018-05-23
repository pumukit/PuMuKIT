<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Document\CpuStatus;

class CpuService
{
    private $dm;
    private $repo;
    private $cpus;

    const TYPE_LINUX = 'linux';
    const TYPE_WINDOWS = 'windows';
    const TYPE_GSTREAMER = 'gstreamer';

    /**
     * Constructor.
     */
    public function __construct(array $cpus, DocumentManager $documentManager)
    {
        $this->cpus = $cpus;
        $this->dm = $documentManager;
        $this->jobRepo = $this->dm->getRepository('PumukitEncoderBundle:Job');
        $this->cpuRepo = $this->dm->getRepository('PumukitEncoderBundle:CpuStatus');
    }

    /**
     * Get available free cpus.
     */
    public function getFreeCpu($profile = null)
    {
        $executingJobs = $this->jobRepo->findWithStatus(array(Job::STATUS_EXECUTING));

        $freeCpus = array();
        foreach ($this->cpus as $name => $cpu) {
            $jobs = $this->getRunningJobs($name, $executingJobs);

            if ($this->isInMaintenance($name) || $jobs >= $cpu['max'] || !$this->isCompatible($cpu, $profile)) {
                continue;
            }

            $freeCpus[] = array(
                'name' => $name,
                'jobs' => $jobs,
                'max' => $cpu['max'],
            );
        }

        return $this->getOptimalCpuName($freeCpus);
    }

    /**
     * Get Cpu by name.
     *
     * @param string the cpu name (case sensitive)
     */
    public function getCpuByName($name)
    {
        if (isset($this->cpus[$name])) {
            return $this->cpus[$name];
        }

        return null;
    }

    /**
     * Get Cpus.
     */
    public function getCpus()
    {
        return $this->cpus;
    }

    /**
     * Is active.
     *
     * Returns true if given cpu is active
     */
    public function isActive($cpu, $cmd = '')
    {
        // TODO
        return true;
    }

    private function getOptimalCpuName($freeCpus = array())
    {
        $optimalCpu = null;
        foreach ($freeCpus as $cpu) {
            if (!$optimalCpu) {
                $optimalCpu = $cpu;
                continue;
            }
            if (($cpu['jobs'] / $cpu['max']) < ($optimalCpu['jobs'] / $optimalCpu['max'])) {
                $optimalCpu = $cpu;
            } elseif (($cpu['jobs'] === 0) && ($optimalCpu['jobs'] === 0) && ($cpu['max'] > $optimalCpu['max'])) {
                $optimalCpu = $cpu;
            }
        }
        if (isset($optimalCpu['name'])) {
            return $optimalCpu['name'];
        }

        return null;
    }

    public function activateMaintenance($cpuName, $flush = true)
    {
        $cpuStatus = $this->cpuRepo->findOneBy(array('name' => $cpuName));
        if (!$cpuStatus) {
            $cpuStatus = new CpuStatus();
            $cpuStatus->setName($cpuName);
            $cpuStatus->setStatus(CpuStatus::STATUS_MAINTENANCE);
        } elseif (CpuStatus::STATUS_MAINTENANCE != $cpuStatus->getStatus()) {
            $cpuStatus->setStatus(CpuStatus::STATUS_MAINTENANCE);
        }
        $this->dm->persist($cpuStatus);
        if ($flush) {
            $this->dm->flush();
        }
    }

    public function deactivateMaintenance($cpuName, $flush = true)
    {
        $cpuStatus = $this->cpuRepo->findOneBy(array('name' => $cpuName));
        //So far, if it exists in the db, it IS in maintenance mode. This may change in the future. Change this logic accordingly.
        if ($cpuStatus) {
            $this->dm->remove($cpuStatus);
            if ($flush) {
                $this->dm->flush();
            }
        }
    }

    public function isInMaintenance($cpuName)
    {
        $cpuStatus = $this->cpuRepo->findOneBy(array('name' => $cpuName));
        if ($cpuStatus && CpuStatus::STATUS_MAINTENANCE == $cpuStatus->getStatus()) {
            return true;
        } else {
            return false;
        }
    }

    private function getRunningJobs($cpuName, $allRunningJobs)
    {
        $jobs = 0;
        foreach ($allRunningJobs as $job) {
            if ($cpuName === $job->getCpu()) {
                ++$jobs;
            }
        }

        return $jobs;
    }

    public function isCompatible($cpu, $profile)
    {
        return $profile === null || empty($cpu['profiles']) || in_array($profile, $cpu['profiles']);
    }

    public function getCpuNamesInMaintenanceMode()
    {
        $cpus = $this->cpuRepo->findBy(array('status' => CpuStatus::STATUS_MAINTENANCE));
        $cpuNames = array_map(function ($a) {
            return $a->getName();
        }, $cpus);

        return $cpuNames;
    }
}
