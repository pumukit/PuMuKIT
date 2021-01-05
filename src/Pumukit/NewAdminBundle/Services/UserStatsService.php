<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserStatsService
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getUserMultimediaObjectsGroupByStats(UserInterface $user): array
    {
        $collection = $this->documentManager->getDocumentCollection(MultimediaObject::class);
        $code = 'owner';
        $pipeline = $this->generateUserFilterPipeline($user, $code);

        $pipelinePublished = $pipeline;
        $pipelinePublished[] = ['$match' => ['status' => MultimediaObject::STATUS_PUBLISHED]];

        $published = $collection->aggregate($pipelinePublished, ['cursor' => []]);

        $pipelineBlocked = $pipeline;
        $pipelineBlocked[] = ['$match' => ['status' => MultimediaObject::STATUS_BLOCKED]];

        $blocked = $collection->aggregate($pipelineBlocked, ['cursor' => []]);

        $pipelineHidden = $pipeline;
        $pipelineHidden[] = ['$match' => ['status' => MultimediaObject::STATUS_HIDDEN]];
        $hidden = $collection->aggregate($pipelineHidden, ['cursor' => []]);

        return [
            iterator_count($published),
            iterator_count($blocked),
            iterator_count($hidden),
        ];
    }

    public function getUserMultimediaObjectsGroupByRole(UserInterface $user): array
    {
        $result = [];
        $roles = $this->documentManager->getRepository(Role::class)->findAll();
        $multimediaObjectCollection = $this->documentManager->getDocumentCollection(MultimediaObject::class);
        foreach ($roles as $role) {
            $pipeline = $this->generateUserFilterPipeline($user, $role->getCod());
            $pipeline[] = [
                '$group' => [
                    '_id' => $role->getCod(),
                    'multimediaObjects' => ['$addToSet' => '$_id'],
                ],
            ];
            $result[$role->getCod()] = iterator_to_array($multimediaObjectCollection->aggregate($pipeline, ['cursor' => []]));
        }

        return $result;
    }

    public function getUserStorageMB(UserInterface $user)
    {
        $collection = $this->documentManager->getDocumentCollection(MultimediaObject::class);
        $code = 'owner';
        $pipeline = $this->generateUserFilterPipeline($user, $code);
        $pipeline[] = ['$unwind' => '$tracks'];
        $pipeline[] = [
            '$project' => [
                'size' => ['$sum' => '$tracks.size'],
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'size' => ['$sum' => '$size'],
            ],
        ];
        $result = iterator_to_array($collection->aggregate($pipeline, ['cursor' => []]));

        return reset($result)['size'] / 1048576;
    }

    public function getUserUploadedHours(UserInterface $user)
    {
        $collection = $this->documentManager->getDocumentCollection(MultimediaObject::class);
        $code = 'owner';
        $pipeline = $this->generateUserFilterPipeline($user, $code);
        $pipeline[] = ['$unwind' => '$tracks'];
        $pipeline[] = [
            '$project' => [
                'duration' => ['$sum' => '$tracks.duration'],
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'duration' => ['$sum' => '$duration'],
            ],
        ];
        $result = iterator_to_array($collection->aggregate($pipeline, ['cursor' => []]));
        $seconds = reset($result)['duration'];

        return gmdate('H:i:s', $seconds);
    }

    private function generateUserFilterPipeline(User $user, string $code): array
    {
        return [
            [
                '$match' => [
                    'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE],
                    'people' => [
                        '$elemMatch' => [
                            'cod' => $code,
                            'people._id' => new ObjectId($user->getPerson()->getId()),
                        ],
                    ],
                ],
            ],
        ];
    }
}
