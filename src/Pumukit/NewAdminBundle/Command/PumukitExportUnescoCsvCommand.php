<?php

namespace Pumukit\NewAdminBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\NewAdminBundle\Services\UNESCOService;
use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'pumukit:export-unesco-csv',
    description: 'Export unesco data to CSV and emails a download link when complete',
)]
class PumukitExportUnescoCsvCommand extends Command
{
    private $documentManager;
    private $urlGenerator;

    /** @var SessionInterface */
    private $session;

    /** @var TagCatalogueService */
    private $tagCatalogueService;

    /** @var RequestStack */
    private $requestStack;

    /** @var UNESCOService */
    private $UNESCOService;

    public function __construct(
        DocumentManager $documentManager,
        UrlGeneratorInterface $urlGenerator,
        SessionInterface $session,
        TagCatalogueService $tagCatalogueService,
        RequestStack $requestStack,
        UNESCOService $UNESCOService
    ) {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->tagCatalogueService = $tagCatalogueService;
        $this->requestStack = $requestStack;
        $this->UNESCOService = $UNESCOService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = 'export_unesco_search_result_'.uniqid().'.csv';

        if (!$handle = fopen($filename, 'w+')) {
            $output->writeln('<error>Unable to create temporary file</error>');

            return Command::FAILURE;
        }

        $qb = $this->createUNESCOSearchtoExportCSV();

        $fields = $this->tagCatalogueService->getAllCustomListFields();
        $columns = [];

        foreach ($fields as $field) {
            $columns[] = $field['label'];
        }

        fputcsv($handle, $columns, ';');

        $page = 1;
        $limit = 100;
        $totalViews = 0;

        do {
            $results = $this->getPaginatedResults($qb, $page, $limit);

            foreach ($results as $result) {
                $data = [];
                $numViews = $result->getNumview();
                $totalViews += $numViews;

                foreach ($fields as $key => $field) {
                    $render = $fields[$key]['render'];

                    switch ($key) {
                        case 'series.id':
                            $data[] = trim($result->getSeries()->getId());

                            break;

                        case 'seriesTitle':
                            $data[] = trim($result->getSeriesTitle());

                            break;

                        case 'tracks.name':
                            $tracks = $result->getTracks()->toArray();
                            if (empty($tracks)) {
                                $tracks = $result->getTracks()->getMongoData();
                            }
                            $trackName = '';
                            foreach ($tracks as $track) {
                                if ($track instanceof Track) {
                                    if ($track->getOriginalName()) {
                                        $trackName = $track->getOriginalName();
                                    }
                                } else {
                                    if (isset($track['originalName'])) {
                                        $trackName = $track['originalName'];
                                    }
                                }
                            }
                            $data[] = $trackName;

                            break;

                        case 'groups':
                            $groups = $result->getGroups()->toArray();
                            if (empty($groups)) {
                                $groups = $result->getGroups()->getMongoData();
                            }
                            $groupName = implode(',', $groups);

                            $data[] = $groupName;

                            break;

                        default:
                            if ('role' == $render) {
                                $roles = $result->getRoles()->toArray();
                                if (empty($roles)) {
                                    $roles = $result->getRoles()->getMongoData();
                                }
                                $text = '';
                                foreach ($roles as $role) {
                                    $roleOM = explode('.', $key);
                                    $roleCod = $roleOM[1] ?? $key;
                                    $code = '';
                                    if ($role instanceof EmbeddedRole) {
                                        $code = $role->getCod();
                                    } else {
                                        $code = $role['cod'];
                                    }
                                    if ($code === $roleCod) {
                                        if ($role instanceof EmbeddedRole) {
                                            $people = $role->getPeople();
                                        } else {
                                            $people = $role['people'];
                                        }
                                        foreach ($people as $embeddedPerson) {
                                            if ($embeddedPerson instanceof EmbeddedPerson) {
                                                $text .= $embeddedPerson->getName()."\n";
                                            } else {
                                                $text .= $embeddedPerson['name']."\n";
                                            }
                                        }
                                    }
                                }
                                $data[] = trim($text);
                            } else {
                                $data[] = $this->tagCatalogueService->renderField($result, $this->session, $key);
                            }
                    }
                }
                fputcsv($handle, $data, ';');
            }

            ++$page;
        } while ((is_countable($results) ? count($results) : 0) > 0);

        fputcsv($handle, ['', 'Total Views:', $totalViews]);

        fclose($handle);

        $fileUrl = $this->urlGenerator->generate('download_unesco_search', ['filename' => $filename], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->UNESCOService->sendEmailWithFileLink($fileUrl);

        return Command::SUCCESS;
    }

    private function createUNESCOSearchtoExportCSV()
    {
        $session = $this->session;

        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();

        $configuredTag = $this->tagCatalogueService->getConfiguredTag();
        $tag = $session->get('admin/unesco/tag');

        $tagCondition = $tag;
        if (isset($tag) && !in_array($tag, ['1', '2'])) {
            $tagCondition = 'tag';
        }

        switch ($tagCondition) {
            case '1':
                $selectedTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $configuredTag->getCod()]);
                $qb
                    ->field('tags.cod')
                    ->notEqual($selectedTag->getCod())
                ;

                break;

            case 'tag':
                $selectedTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $session->get('admin/unesco/tag')]);
                $qb
                    ->field('tags.cod')
                    ->equals($selectedTag->getCod())
                ;

                break;
        }

        if ($session->has('admin/unesco/element_sort')) {
            if ($session->get('admin/unesco/text', false)) {
                $qb->sortMeta('score', 'textScore');
            } else {
                $qb->sort($session->get('admin/unesco/element_sort'), $session->get('admin/unesco/type'));
            }
        }

        $criteria = $session->get('UNESCO/criteria');
        if (isset($criteria) && !empty($criteria)) {
            $request = $this->requestStack->getMainRequest();
            $qb = $this->UNESCOService->addCriteria($qb, $criteria, $request->getLocale());
        }

        if (!$this->session->has('admin/unesco/selected_fields')) {
            $defaultSelectedFields = $this->tagCatalogueService->getDefaultListFields();
            $this->session->set('admin/unesco/selected_fields', $defaultSelectedFields);
        }

        return $qb;
    }

    private function getPaginatedResults(Builder $qb, int $page, int $limit)
    {
        return $qb
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray()
        ;
    }
}
