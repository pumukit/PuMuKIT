<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\ApiResource\Keywords;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\HttpFoundation\Request;

final class KeywordsProvider implements ProviderInterface
{
    private const LIMIT = 1000;

    public function __construct(private readonly DocumentManager $documentManager) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $request = $context['request'] ?? null;
        $locale = $request instanceof Request ? $request->getLocale() : 'en';
        $class = 'api_keywords_series' === $operation->getName() ? Series::class : MultimediaObject::class;

        $items = [];
        foreach ($this->documentManager->getDocumentCollection($class)->aggregate([
            ['$project' => ['keyword' => '$keywords.'.$locale, '_id' => false]],
            ['$unwind' => '$keyword'],
            ['$match' => ['keyword' => ['$nin' => ['', null]]]],
            ['$group' => ['_id' => '$keyword', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1, '_id' => 1]],
            ['$limit' => self::LIMIT],
        ]) as $row) {
            $items[] = ['keyword' => (string) $row['_id'], 'count' => $row['count']];
        }

        return new Keywords($items);
    }
}
