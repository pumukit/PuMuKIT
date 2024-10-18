<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\TagService;

class ImportMappingDataService
{
    private $documentManager;
    private $tagService;
    private $personService;

    private $mappingData = [
        'status' => 'setStatus',
        'record_date' => 'setRecordDate',
        'public_date' => 'setPublicDate',
        'title' => 'setTitle',
        'subtitle' => 'setSubtitle',
        'description' => 'setDescription',
        'line2' => 'setLine2',
        'copyright' => 'setCopyright',
        'license' => 'setLicense',
        'keywords' => 'setKeywords',
        'properties' => 'setProperty',
        'numview' => 'setNumView',
        'type' => 'setType',
    ];

    // NOTE: Convert all values to \DateTime
    private $mappingDataToDateTime = [
        'record_date' => 'setRecordDate',
        'public_date' => 'setPublicDate',
    ];

    // NOTE: Execute different processing data depends on type
    private $mappingDataExceptions = [
        'materials' => 'processMaterials',
        'pics' => 'processPics',
        'people' => 'processRoles',
        'role' => 'processRoles',
        'tags' => 'processTags',
        'externalplayer' => 'processExternalPlayer',
    ];

    private $validateValuesField = [
        'type' => [
            MultimediaObject::TYPE_UNKNOWN,
            MultimediaObject::TYPE_VIDEO,
            MultimediaObject::TYPE_AUDIO,
            MultimediaObject::TYPE_EXTERNAL,
            MultimediaObject::TYPE_LIVE,
        ],
    ];

    public function __construct(DocumentManager $documentManager, TagService $tagService, PersonService $personService)
    {
        $this->documentManager = $documentManager;
        $this->tagService = $tagService;
        $this->personService = $personService;
    }

    public function validatePath(string $file): bool
    {
        return FileSystemUtils::exists($file);
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */
    public function processFileData(string $file)
    {
        $content = file_get_contents($file);
        if (false === $content) {
            throw new \ErrorException('File cannot be read');
        }

        $processData = json_decode($content, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception(json_last_error_msg());
        }

        return $processData;
    }

    /**
     * @throws \Exception
     */
    public function insertMappingData(MultimediaObject $multimediaObject, array $body): void
    {
        foreach ($body as $key => $value) {
            if (array_key_exists($key, $this->mappingData)) {
                $method = $this->mappingData[$key];
                if (!is_array($value)) {
                    if (array_key_exists($key, $this->mappingDataToDateTime) && is_string($value)) {
                        $value = new \DateTime($value);
                    }

                    if (array_key_exists($key, $this->validateValuesField) && !in_array($value, $this->validateValuesField[$key], true)) {
                        throw new \Exception("{$key} haven't got a valid value");
                    }

                    $multimediaObject->{$method}($value);
                } elseif ('properties' === $key) {
                    foreach ($value as $propertyKey => $propertyValue) {
                        $multimediaObject->{$method}($propertyKey, $propertyValue);
                    }
                } else {
                    foreach ($body[$key] as $lang => $data) {
                        $multimediaObject->{$method}($data, $lang);
                    }
                }
            } elseif (array_key_exists($key, $this->mappingDataExceptions)) {
                $this->processDataExceptions($multimediaObject, $key, $value);
            }
        }

        $this->documentManager->flush();
    }

    /**
     * @param mixed $value
     *
     * @throws \Exception
     */
    private function processDataExceptions(MultimediaObject $multimediaObject, string $key, $value): void
    {
        if (!array_key_exists($key, $this->mappingDataExceptions)) {
            throw new \Exception("{$key} is not supported");
        }

        $method = $this->mappingDataExceptions[$key];
        $this->{$method}($multimediaObject, $value);
    }

    /**
     * @throws \Exception
     */
    private function processTags(MultimediaObject $multimediaObject, array $value): void
    {
        foreach ($value as $tagCod) {
            $tag = $this->documentManager->getRepository(Tag::class)->findOneBy([
                'cod' => $tagCod,
            ]);

            if ($tag) {
                $this->tagService->addTagByCodToMultimediaObject($multimediaObject, $tag->getCod(), false);
            }
        }
    }

    private function processRoles(MultimediaObject $multimediaObject, array $value): void
    {
        foreach ($value as $key => $peopleEmails) {
            $person = null;
            $role = $this->documentManager->getRepository(Role::class)->findOneBy([
                'cod' => $key,
            ]);

            foreach ($peopleEmails as $data) {
                if (is_array($data)) {
                    $person = $this->documentManager->getRepository(Person::class)->findOneBy(['email' => $data['email']]);

                    if (!$person) {
                        $person = new Person();
                        $person->setEmail($data['email']);
                        $person->setName($data['name']);
                        $this->personService->savePerson($person);
                    }
                }

                if ($role && $person) {
                    $this->personService->createRelationPerson($person, $role, $multimediaObject);
                }
            }
        }
    }

    private function processMaterials(MultimediaObject $multimediaObject, array $value): void
    {
        foreach ($value as $key => $fileUrl) {
            $material = new Material();
            $material->setUrl($fileUrl);
            $this->documentManager->persist($material);
            $multimediaObject->addMaterial($material);
        }

        $this->documentManager->flush();
    }

    private function processPics(MultimediaObject $multimediaObject, array $value): void
    {
        foreach ($value as $key => $fileUrl) {
            $pic = new Pic();
            $pic->setUrl($fileUrl);
            $this->documentManager->persist($pic);
            $multimediaObject->addPic($pic);
        }

        $this->documentManager->flush();
    }

    private function processExternalPlayer(MultimediaObject $multimediaObject, string $fileUrl): void
    {
        $multimediaObject->setProperty('externalplayer', $fileUrl);

        $this->documentManager->flush();
    }
}
