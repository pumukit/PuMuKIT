<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Document\Job;

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
        $this->cpuService = $cpuService;
    }

/*  TODO
- constructor documentmanager, profileservice, cpuservice

  - add job (path del archivo a transcodificar, perfil (nombre del perfil), prioridad, idioma = null, description (array internacionalizable) = array() ------ check archivo y perfil existentes, crear new job waiting, init timeini, persisitirlo

             - pause job (id)
             - resume job (id) --- continuar si estaba pausado
             - cancel job(id)--- pausado o waiting: borrarlo. en otro caso: exception

             - estados job ()--- contar los job en cada estado. devolver array
             - get next job () --- de los que esté en waiting, el más prioritario (empate el mas viejo), sino null. (TranscodingPeero::getNext())

*/
}