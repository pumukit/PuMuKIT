<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Broadcast;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MaterialServiceTest extends WebTestCase
{
    private $dm;
    private $repoMmobj;
    private $materialService;
    private $factoryService;
    private $originalFilePath;
    private $uploadsPath;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
        $this->repoMmobj = $this->dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->materialService = $kernel->getContainer()
          ->get('pumukitschema.material');
        $this->factoryService = $kernel->getContainer()
          ->get('pumukitschema.factory');

        $this->originalFilePath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'file.pdf';
        $this->uploadsPath = realpath(__DIR__.'/../../../../../web/uploads/material');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
        $this->dm->flush();
    }

    public function testAddMaterialUrl()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getMaterials()));

        $url = 'http://domain.com/material.pdf';

        $formData['i18n_name'] = array('en' => 'Material');
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getMaterials()));
    }

    public function testUpdateMaterialInMultimediaObject()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $url = 'http://domain.com/material.pdf';

        $formData['i18n_name'] = array('en' => 'Material');
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $materials = $mm->getMaterials();
        $material = $materials[0];

        $this->assertEquals($formData['i18n_name'], $material->getI18nName());

        $newI18nName = array('en' => 'Material', 'es' => 'Material');
        $material->setI18nName($newI18nName);

        $mm = $this->materialService->updateMaterialInMultimediaObject($mm);
        $mm = $this->repoMmobj->find($mm->getId());

        $materials = $mm->getMaterials();
        $material = $materials[0];

        $this->assertEquals($newI18nName, $material->getI18nName());
    }

    public function testAddMaterialFile()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);
        $mm = $this->repoMmobj->findAll()[0];

        $this->assertEquals(0, count($mm->getMaterials()));

        $filePath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'fileCopy.pdf';
        if (copy($this->originalFilePath, $filePath)){
            $file = new UploadedFile($filePath, 'file.pdf', null, null, null, true);
            
            $formData['i18n_name'] = array('en' => 'Material');
            $formData['hide'] = false;
            $formData['mime_type'] = '9';
            
            $mm = $this->materialService->addMaterialFile($mm, $file, $formData);
            $mm = $this->repoMmobj->find($mm->getId());
            
            $this->assertEquals(1, count($mm->getMaterials()));
          
            $material = $mm->getMaterials()[0];
            $this->assertTrue($mm->containsMaterial($material));

            $uploadedFile = '/uploads/material/'.$mm->getId().DIRECTORY_SEPARATOR.$file->getClientOriginalName();
            $this->assertEquals($uploadedFile, $material->getUrl());
        }
        
        $this->deleteCreatedFiles();
    }

    public function testRemoveMaterialFromMultimediaObject()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getMaterials()));

        $url = 'http://domain.com/material.pdf';

        $formData['i18n_name'] = array('en' => 'Material');
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getMaterials()));

        $materials = $mm->getMaterials();
        $material = $materials[0];

        $mm = $this->materialService->removeMaterialFromMultimediaObject($mm, $material->getId());

        $this->assertEquals(0, count($mm->getMaterials()));
    }

    public function testUpAndDownMaterialInMultimediaObject()
    {
        $broadcast = $this->createBroadcast(Broadcast::BROADCAST_TYPE_PUB);

        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getMaterials()));

        $url1 = 'http://domain.com/material1.pdf';

        $formData['i18n_name'] = array('en' => 'Material 1');
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url1, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $url2 = 'http://domain.com/material2.pdf';

        $formData['i18n_name'] = array('en' => 'Material 2');
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url2, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $url3 = 'http://domain.com/material3.pdf';

        $formData['i18n_name'] = array('en' => 'Material 3');
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url3, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $materials = $mm->getMaterials();
        $material1 = $materials[0];
        $material2 = $materials[1];
        $material3 = $materials[2];
        $arrayMaterials = array($material1, $material2, $material3);
        
        $this->assertEquals($arrayMaterials, $mm->getMaterials()->toArray());

        $mm = $this->materialService->upMaterialInMultimediaObject($mm, $material2->getId());
        $mm = $this->repoMmobj->find($mm->getId());

        $arrayMaterials = array($material2, $material1, $material3);        
        $this->assertEquals($arrayMaterials, $mm->getMaterials()->toArray());

        $mm = $this->materialService->downMaterialInMultimediaObject($mm, $material1->getId());
        $mm = $this->repoMmobj->find($mm->getId());

        $arrayMaterials = array($material2, $material3, $material1);
        $this->assertEquals($arrayMaterials, $mm->getMaterials()->toArray());
    }

    private function createBroadcast($broadcastTypeId)
    {
        $broadcast = new Broadcast();
        $broadcast->setName(ucfirst($broadcastTypeId));
        $broadcast->setBroadcastTypeId($broadcastTypeId);
        $broadcast->setPasswd('password');
        if (0 === strcmp(Broadcast::BROADCAST_TYPE_PRI, $broadcastTypeId)) {
            $broadcast->setDefaultSel(true);
        } else {
            $broadcast->setDefaultSel(false);
        }
        $broadcast->setDescription(ucfirst($broadcastTypeId).' broadcast');

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }

    private function deleteCreatedFiles()
    {
        $mmobjs = $this->repoMmobj->findAll();

        foreach($mmobjs as $mm){
            $mmDir = $this->uploadsPath.DIRECTORY_SEPARATOR.$mm->getId().DIRECTORY_SEPARATOR;

            if (is_dir($mmDir)){
                $files = glob($mmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)){
                      unlink($file);
                    }
                }

                rmdir($mmDir);
            }
        }
    }
}