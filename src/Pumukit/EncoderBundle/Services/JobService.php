<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Document\Job;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class JobService
{
    private $dm;
    private $repo;
    private $profileService;
    private $cpuService;

    public function __construct(DocumentManager $documentManager, ProfileService $profileService, CpuService $cpuService)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitEncoderBundle:Job');
        $this->profileService = $profileService;
        // TODO - necesario aqui?
        $this->cpuService = $cpuService;
    }

    /**
     * Add job
     */
    public function addJob($pathFile, $profile, $priority, $language = null, $description = array())
    {
        //- add job (path del archivo a transcodificar, perfil (nombre del perfil), prioridad, idioma = null, description (array internacionalizable) = array() ------ check archivo y perfil existentes, crear new job waiting, init timeini, persisitirlo

        if (!is_file($pathFile)) {
          throw new FileNotFoundException($pathFile); 
        }

        if (null === $this->profileService->getProfile($profile['name'])){
          // throw exception
        }
        
        $job = new Job();
        $job->setTimeini(new \DateTime('now'));
        $this->dm->persist($job);
        $this->dm->flush();

        // TODO - algo mas?
    }

    /**
     * Pause job
     */
    public function pauseJob($id)
    {
        $job = $this->repo->find($id);

        if (null === $job){
          //throw exception
        }
        if (Job::STATUS_WAITING === $job->getStatus()){
            $job->setStatus(Job::STATUS_PAUSED);
            $this->dm->persist($job);
            $this->dm->flush();
        }

    }


/*  TODO
             - pause job (id)
             - resume job (id) --- continuar si estaba pausado
             - cancel job(id)--- pausado o waiting: borrarlo. en otro caso: exception

             - estados job ()--- contar los job en cada estado. devolver array
     
        - get next job () --- de los que esté en waiting, el más prioritario (empate el mas viejo), sino null. (TranscodingPeer::getNext())
*/

}