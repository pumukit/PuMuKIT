<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\MaterialService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 * @coversNothing
 */
class MaterialServiceTest extends WebTestCase
{
    private $dm;
    private $repoMmobj;
    private $materialService;
    private $factoryService;
    private $originalFilePath;
    private $uploadsPath;
    private $materialDispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repoMmobj = $this->dm
            ->getRepository(MultimediaObject::class)
        ;
        $this->materialService = static::$kernel->getContainer()
            ->get('pumukitschema.material')
        ;
        $this->materialDispatcher = static::$kernel->getContainer()
            ->get('pumukitschema.material_dispatcher')
        ;
        $this->factoryService = static::$kernel->getContainer()
            ->get('pumukitschema.factory')
        ;

        $this->originalFilePath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'file.pdf';
        $this->uploadsPath = realpath(__DIR__.'/../../../../../web/uploads/material');

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Series::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->repoMmobj = null;
        $this->materialService = null;
        $this->materialDispatcher = null;
        $this->factoryService = null;

        $this->originalFilePath = null;
        $this->uploadsPath = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testAddMaterialUrl()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getMaterials()));

        $url = 'http://domain.com/material.pdf';

        $formData['i18n_name'] = ['en' => 'Material'];
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getMaterials()));
    }

    public function testUpdateMaterialInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $url = 'http://domain.com/material.pdf';

        $formData['i18n_name'] = ['en' => 'Material'];
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $materials = $mm->getMaterials();
        $material = $materials[0];

        $this->assertEquals($formData['i18n_name'], $material->getI18nName());

        $newI18nName = ['en' => 'Material', 'es' => 'Material'];
        $material->setI18nName($newI18nName);

        $mm = $this->materialService->updateMaterialInMultimediaObject($mm, $material);
        $mm = $this->repoMmobj->find($mm->getId());

        $materials = $mm->getMaterials();
        $material = $materials[0];

        $this->assertEquals($newI18nName, $material->getI18nName());
    }

    public function testAddMaterialFile()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);
        $mm = $this->repoMmobj->findAll()[0];

        $this->assertEquals(0, count($mm->getMaterials()));

        $filePath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'fileCopy.pdf';
        if (copy($this->originalFilePath, $filePath)) {
            $file = new UploadedFile($filePath, 'file.pdf', null, null, null, true);

            $formData['i18n_name'] = ['en' => 'Material'];
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
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getMaterials()));

        $url = 'http://domain.com/material.pdf';

        $formData['i18n_name'] = ['en' => 'Material'];
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $this->assertEquals(1, count($mm->getMaterials()));

        $materials = $mm->getMaterials();
        $material = $materials[0];

        $materialPath = realpath(__DIR__.'/../Resources').DIRECTORY_SEPARATOR.'materialCopy';
        if (copy($this->originalFilePath, $materialPath)) {
            $materialFile = new UploadedFile($materialPath, 'material', null, null, null, true);
            $mm = $this->materialService->addMaterialFile($mm, $materialFile, $formData);
            $mm = $this->repoMmobj->find($mm->getId());

            $this->assertEquals(2, count($mm->getMaterials()));

            $material = $mm->getMaterials()[1];
            $this->assertTrue($mm->containsMaterial($material));

            $mm = $this->materialService->removeMaterialFromMultimediaObject($mm, $material->getId());
            $this->assertEquals(1, count($mm->getMaterials()));
        }
    }

    public function testUpAndDownMaterialInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $mm = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($mm->getMaterials()));

        $url1 = 'http://domain.com/material1.pdf';

        $formData['i18n_name'] = ['en' => 'Material 1'];
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url1, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $url2 = 'http://domain.com/material2.pdf';

        $formData['i18n_name'] = ['en' => 'Material 2'];
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url2, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $url3 = 'http://domain.com/material3.pdf';

        $formData['i18n_name'] = ['en' => 'Material 3'];
        $formData['hide'] = false;
        $formData['mime_type'] = '9';

        $mm = $this->materialService->addMaterialUrl($mm, $url3, $formData);
        $mm = $this->repoMmobj->find($mm->getId());

        $materials = $mm->getMaterials();
        $material1 = $materials[0];
        $material2 = $materials[1];
        $material3 = $materials[2];
        $arrayMaterials = [$material1, $material2, $material3];

        $this->assertEquals($arrayMaterials, $mm->getMaterials()->toArray());

        $mm = $this->materialService->upMaterialInMultimediaObject($mm, $material2->getId());
        $mm = $this->repoMmobj->find($mm->getId());

        $arrayMaterials = [$material2, $material1, $material3];
        $this->assertEquals($arrayMaterials, $mm->getMaterials()->toArray());

        $mm = $this->materialService->downMaterialInMultimediaObject($mm, $material1->getId());
        $mm = $this->repoMmobj->find($mm->getId());

        $arrayMaterials = [$material2, $material3, $material1];
        $this->assertEquals($arrayMaterials, $mm->getMaterials()->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage for storing Materials does not exist
     */
    public function testInvalidTargetPath()
    {
        $materialService = new MaterialService($this->dm, $this->materialDispatcher, '/non/existing/path', '/uploads/material', true);
    }

    public function testGetCaptions()
    {
        $mm = new MultimediaObject();

        $this->dm->persist($mm);
        $this->dm->flush();

        $captions = $this->materialService->getCaptions($mm)->toArray();
        $this->assertEquals(0, count($captions));

        $material1 = new Material();
        $material2 = new Material();
        $material3 = new Material();
        $material4 = new Material();
        $material5 = new Material();

        $material1->setMimeType('pdf');
        $material2->setMimeType('vtt');
        $material3->setMimeType('vtt');
        $material4->setMimeType('pdf');
        $material5->setMimeType('vtt');

        $mm->addMaterial($material1);
        $mm->addMaterial($material2);
        $mm->addMaterial($material3);
        $mm->addMaterial($material4);
        $mm->addMaterial($material5);

        $this->dm->persist($mm);
        $this->dm->flush();

        $captions = $this->materialService->getCaptions($mm)->toArray();
        $this->assertEquals(3, count($captions));

        $this->assertFalse(in_array($material1, $captions));
        $this->assertTrue(in_array($material2, $captions));
        $this->assertTrue(in_array($material3, $captions));
        $this->assertFalse(in_array($material4, $captions));
        $this->assertTrue(in_array($material5, $captions));
    }

    private function deleteCreatedFiles()
    {
        $mmobjs = $this->repoMmobj->findAll();

        foreach ($mmobjs as $mm) {
            $mmDir = $this->uploadsPath.DIRECTORY_SEPARATOR.$mm->getId().DIRECTORY_SEPARATOR;

            if (is_dir($mmDir)) {
                $files = glob($mmDir.'*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }

                rmdir($mmDir);
            }
        }
    }
}
