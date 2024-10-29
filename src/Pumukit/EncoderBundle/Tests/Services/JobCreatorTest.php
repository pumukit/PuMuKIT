<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Services;

use Monolog\Logger;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\JobExecutor;
use Pumukit\EncoderBundle\Services\JobValidator;
use Pumukit\EncoderBundle\Services\MultimediaObjectPropertyJobService;
use Pumukit\EncoderBundle\Services\ProfileValidator;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 *
 * @coversNothing
 */
final class JobCreatorTest extends PumukitTestCase
{
    private $jobExecutor;
    private $jobValidator;
    private $profileValidator;
    private $propService;
    private $tokenStorage;
    private $logger;
    private $tmpPath;
    private $projectDir;

    private $factoryService;
    private $jobCreator;

    public function setUp(): void
    {
        parent::setUp();

        $this->jobExecutor = self::$kernel->getContainer()->get(JobExecutor::class);
        $this->jobValidator = self::$kernel->getContainer()->get(JobValidator::class);
        $this->profileValidator = self::$kernel->getContainer()->get(ProfileValidator::class);
        $this->propService = self::$kernel->getContainer()->get(MultimediaObjectPropertyJobService::class);
        $this->tokenStorage = self::$kernel->getContainer()->get('security.token_storage');
        $this->logger = new Logger('test');
        $this->tmpPath = self::$kernel->getContainer()->getParameter('pumukit.tmp');

        $this->projectDir = self::$kernel->getContainer()->getParameter('kernel.project_dir');
        $this->factoryService = self::$kernel->getContainer()->get(FactoryService::class);

        $this->jobCreator = new JobCreator(
            $this->dm,
            $this->jobExecutor,
            $this->jobValidator,
            $this->profileValidator,
            $this->propService,
            $this->tokenStorage,
            $this->logger,
            $this->tmpPath
        );
    }

    public function testFromUploadedFile(): void
    {
        $series = $this->createSeries();

        $multimediaObject = $this->createMultimediaObject($series);
        $jobOptions = new JobOptions('master_copy', 2, 'en', [], []);

        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.mp4', 'pumukit.mp4');
        $uploadedFile = new UploadedFile($path, 'pumukit.mp4', null, null, true);
        $result = $this->generateJobFromUploadedFile($multimediaObject, $uploadedFile, $jobOptions);
        $this->assertEquals(MultimediaObject::TYPE_VIDEO, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);

        $multimediaObject = $this->createMultimediaObject($series);
        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.mp3', 'pumukit.mp3');
        $uploadedFile = new UploadedFile($path, 'pumukit.mp3', null, null, true);

        $result = $this->generateJobFromUploadedFile($multimediaObject, $uploadedFile, $jobOptions);
        $this->assertEquals(MultimediaObject::TYPE_AUDIO, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);

        $multimediaObject = $this->createMultimediaObject($series);
        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.pdf', 'pumukit.pdf');
        $uploadedFile = new UploadedFile($path, 'pumukit.pdf', null, null, true);
        $result = $this->generateJobFromUploadedFile($multimediaObject, $uploadedFile, $jobOptions);

        $this->assertEquals(MultimediaObject::TYPE_DOCUMENT, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);

        $multimediaObject = $this->createMultimediaObject($series);
        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.png', 'pumukit.png');
        $uploadedFile = new UploadedFile($path, 'pumukit.png', null, null, true);

        $result = $this->generateJobFromUploadedFile($multimediaObject, $uploadedFile, $jobOptions);

        $this->assertEquals(MultimediaObject::TYPE_IMAGE, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);
    }

    public function testFromPath(): void
    {
        $series = $this->createSeries();

        $multimediaObject = $this->createMultimediaObject($series);
        $jobOptions = new JobOptions('master_copy', 2, 'en', [], []);

        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.mp4', 'pumukit.mp4');
        $path = Path::create($path);
        $result = $this->generateJobFromPath($multimediaObject, $path, $jobOptions);
        $this->assertEquals(MultimediaObject::TYPE_VIDEO, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);

        $multimediaObject = $this->createMultimediaObject($series);
        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.mp3', 'pumukit.mp3');
        $path = Path::create($path);
        $result = $this->generateJobFromPath($multimediaObject, $path, $jobOptions);
        $this->assertEquals(MultimediaObject::TYPE_AUDIO, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);

        $multimediaObject = $this->createMultimediaObject($series);
        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.pdf', 'pumukit.pdf');
        $path = Path::create($path);
        $result = $this->generateJobFromPath($multimediaObject, $path, $jobOptions);

        $this->assertEquals(MultimediaObject::TYPE_DOCUMENT, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);

        $multimediaObject = $this->createMultimediaObject($series);
        $path = $this->copyFileToUse($this->projectDir.'/tests/files/pumukit.png', 'pumukit.png');
        $path = Path::create($path);
        $result = $this->generateJobFromPath($multimediaObject, $path, $jobOptions);

        $this->assertEquals(MultimediaObject::TYPE_IMAGE, $result->getType());
        $jobs = $this->dm->getRepository(Job::class)->findBy([
            'mm_id' => $result->getId(),
            'status' => Job::STATUS_WAITING,
        ]);
        $this->assertCount(1, $jobs);
    }

    public function createMultimediaObject(?Series $series = null): MultimediaObject
    {
        return $this->factoryService->createMultimediaObject($series ?? $this->factoryService->createSeries());
    }

    public function createSeries(): Series
    {
        return $this->factoryService->createSeries();
    }

    public function generateJobFromUploadedFile(MultimediaObject $multimediaObject, UploadedFile $uploadedFile, JobOptions $jobOptions): MultimediaObject
    {
        return $this->jobCreator->fromUploadedFile($multimediaObject, $uploadedFile, $jobOptions);
    }

    public function generateJobFromPath(MultimediaObject $multimediaObject, Path $path, JobOptions $jobOptions): MultimediaObject
    {
        return $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);
    }

    private function copyFileToUse(string $origin, string $fileName): string
    {
        $tmpPath = $this->projectDir.'/tests/tmp/';
        copy($origin, $this->projectDir.'/tests/tmp/'.$fileName);

        return $tmpPath.$fileName;
    }
}
