<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\TagInterface;
use Pumukit\SchemaBundle\Services\PermissionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class PumukitInitRepoCommand extends Command
{
    private const ROOT_TAG_CODE = 'ROOT';
    private const ROOT_TAG_DATA = ['id' => null, 'cod' => 'ROOT', 'tree_parent_cod' => null, 'metatag' => 1, 'display' => 0, 'name_en' => 'ROOT'];
    private const TAG_REQUIRED_FIELDS = ['cod', 'tree_parent_cod', 'metatag', 'display', 'name_en'];

    /** @var DocumentManager */
    private $dm;
    private $locales;
    private $repoName;
    private $force;
    private $file;
    private $permissionService;
    private $kernel;

    private $tagsPath = 'Resources/data/tags/';
    private $rolesPath = 'Resources/data/roles/';
    private $permissionProfilesPath = 'Resources/data/permissionprofiles/';

    private $allPermissions;

    public function __construct(DocumentManager $documentManager, PermissionService $permissionService, KernelInterface $kernel, array $locales)
    {
        $this->dm = $documentManager;
        $this->permissionService = $permissionService;
        $this->kernel = $kernel;
        $this->locales = $locales;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:init:repo')
            ->setDescription('Load Pumukit data fixtures to your database')
            ->addArgument('repo', InputArgument::OPTIONAL, 'Select the repo to init: tag, role, permissionprofile')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(
                <<<'EOT'

Command to load a controlled set of data into a database. Useful for init Pumukit environment.

Examples:

php bin/console pumukit:init:repo all
php bin/console pumukit:init:repo tag
php bin/console pumukit:init:repo role
php bin/console pumukit:init:repo permissionprofile

Example with file option:

php bin/console pumukit:init:repo tag src/Pumukit/SchemaBundle/Resources/data/tag/unesco_i18n.csv

Example with force option:

php bin/console pumukit:init:repo tag --force

** The --force parameter has to be used to actually drop the database.

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->allPermissions = $this->permissionService->getAllPermissions();
        $this->locales = array_unique(array_merge($this->locales, ['en']));
        $this->repoName = $input->getArgument('repo');
        $this->force = $input->getOption('force');
        $this->file = $input->getArgument('file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = [];

        switch ($this->repoName) {
            case 'tag':
                $result = $this->executeTags();

                break;

            case 'role':
                $result = $this->executeRoles();

                break;

            case 'permissionprofile':
               $result = $this->executePermissionProfiles();

                break;

            case 'all':
                $this->repoName = 'tag';
                $resultTag = $this->executeTags();
                $this->repoName = 'role';
                $resultRoles = $this->executeRoles();
                $this->repoName = 'permissionprofile';
                $resultPermissionProfiles = $this->executePermissionProfiles();

                $result = array_merge($resultTag, $resultRoles, $resultPermissionProfiles);

                break;

            default:
        }

        foreach ($result as $element) {
            foreach ($element as $key => $value) {
                $output->writeln($key.' - '.$value);
            }
        }

        return 0;
    }

    protected function executeTags(): array
    {
        $finder = $this->locateResource($this->tagsPath);

        if ($this->isEmptyFile($finder)) {
            return [
                $this->file => 'Empty file',
            ];
        }

        if ($this->force) {
            $this->removeTags();
        }

        $root = $this->createRoot();

        $files = isset($this->file) ? [$this->file] : $finder;

        $result = [];
        foreach ($files as $fileRoute) {
            $result[] = $this->createFromFile($fileRoute, $root);
        }

        return $result;
    }

    protected function executeRoles(): array
    {
        $finder = $this->locateResource($this->rolesPath);

        if ($this->isEmptyFile($finder)) {
            return [
                $this->file => 'Empty file',
            ];
        }

        if ($this->force) {
            $this->removeRoles();
        }

        $root = $this->createRoot();

        $files = isset($this->file) ? [$this->file] : $finder;

        $result = [];
        foreach ($files as $fileRoute) {
            $result[] = $this->createFromFile($fileRoute, $root);
        }

        return $result;
    }

    protected function executePermissionProfiles(): array
    {
        $finder = $this->locateResource($this->permissionProfilesPath);

        if ($this->isEmptyFile($finder)) {
            return [
                $this->file => 'Empty file',
            ];
        }

        if ($this->force) {
            $this->removePermissionProfiles();
        }

        $root = $this->createRoot();

        $files = isset($this->file) ? [$this->file] : $finder;

        $result = [];
        foreach ($files as $fileRoute) {
            $result[] = $this->createFromFile($fileRoute, $root);
        }

        return $result;
    }

    protected function removeTags(): void
    {
        $this->dm->getDocumentCollection(Tag::class)->deleteMany([]);
    }

    protected function removeRoles(): void
    {
        $this->dm->getDocumentCollection(Role::class)->deleteMany([]);
    }

    protected function removePermissionProfiles(): void
    {
        $this->dm->getDocumentCollection(PermissionProfile::class)->deleteMany([]);
    }

    protected function createRoot(): TagInterface
    {
        $root = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => self::ROOT_TAG_CODE]);
        if (!isset($root)) {
            $root = $this->createTagFromCsvArray(self::ROOT_TAG_DATA);
        }

        $this->dm->flush();

        return $root;
    }

    protected function createFromFile($fileRoute, TagInterface $root): array
    {
        if (!is_string($fileRoute)) {
            $fileRoute = $fileRoute->getPathname();
        }

        $fileWasValidated = $this->validateFile($fileRoute);
        if (!$fileWasValidated['status']) {
            return [$fileRoute => $fileWasValidated['message']];
        }

        $importedTags = [];
        $file = fopen($fileRoute, 'rb');
        $csvTagHeaders = fgetcsv($file, 0, ';');

        $numberPersisted = 0;
        while (false !== ($currentRow = fgetcsv($file, 0, ';'))) {
            if ('id' === trim($currentRow[0])) {
                continue;
            }

            switch ($this->repoName) {
                case 'tag':
                    $csvTagsArray = [];

                    $limit = count($currentRow);

                    for ($i = 0; $i < $limit; ++$i) {
                        $key = $csvTagHeaders[$i];
                        $csvTagsArray[$key] = $currentRow[$i];
                    }

                    $parent = $importedTags[$csvTagsArray['tree_parent_cod']] ?? $this->dm->getRepository(Tag::class)->findOneBy(['cod' => $csvTagsArray['tree_parent_cod']]);
                    if (!isset($parent)) {
                        $parent = $root;
                    }

                    if (!$this->dm->getRepository(Tag::class)->findOneBy(['cod' => $csvTagsArray['cod']])) {
                        $tag = $this->createTagFromCsvArray($csvTagsArray, $parent);
                        $importedTags[$tag->getCod()] = $tag;

                        ++$numberPersisted;
                    }

                    break;

                case 'role':
                    $csvRolesArray = [];

                    $limit = count($currentRow);

                    for ($i = 0; $i < $limit; ++$i) {
                        $key = $csvTagHeaders[$i];
                        $csvRolesArray[$key] = $currentRow[$i];
                    }

                    if (!$this->dm->getRepository(Role::class)->findOneBy(['cod' => $csvRolesArray['cod']])) {
                        $this->createRoleFromCsvArray($csvRolesArray);
                        ++$numberPersisted;
                    }

                    break;

                case 'permissionprofile':
                    if (!$this->dm->getRepository(PermissionProfile::class)->findOneBy(['name' => $currentRow[1]])) {
                        $this->createPermissionProfileFromCsvArray($currentRow);
                        ++$numberPersisted;
                    }

                    break;
            }
        }

        fclose($file);

        $this->dm->flush();

        return [
            $fileRoute => $numberPersisted,
        ];
    }

    private function createTagFromCsvArray(array $csvTagsArray, $tag_parent = null): TagInterface
    {
        $tag = new Tag();
        $tag->setCod($csvTagsArray['cod']);
        $tag->setMetatag((bool) $csvTagsArray['metatag']);
        $tag->setDisplay((bool) $csvTagsArray['display']);
        if ($tag_parent) {
            $tag->setParent($tag_parent);
        }

        foreach ($this->locales as $locale) {
            $key_name = 'name_'.$locale;
            if (isset($csvTagsArray[$key_name])) {
                $tag->setTitle($csvTagsArray[$key_name], $locale);
            } else {
                $tag->setTitle($csvTagsArray['name_en'], $locale);
            }
        }

        foreach (array_keys($csvTagsArray) as $key) {
            if (preg_match('/property_*/', $key, $matches)) {
                $property_name = str_replace($matches[0], '', $key);
                $tag->setProperty($property_name, $csvTagsArray[$key]);
            }
        }

        $this->dm->persist($tag);

        return $tag;
    }

    private function createRoleFromCsvArray(array $csvTagsArray): RoleInterface
    {
        $role = new Role();

        $role->setCod($csvTagsArray['cod']);
        $role->setXml($csvTagsArray['xml']);
        $role->setDisplay((bool) $csvTagsArray['display']);

        foreach ($this->locales as $locale) {
            $key_name = 'name_'.$locale;
            if (isset($csvTagsArray[$key_name])) {
                $role->setName($csvTagsArray[$key_name], $locale);
            } else {
                $role->setName($csvTagsArray['name_en'], $locale);
            }
        }

        foreach ($this->locales as $locale) {
            $key_name = 'text_'.$locale;
            if (isset($csvTagsArray[$key_name])) {
                $role->setText($csvTagsArray[$key_name], $locale);
            } else {
                $role->setText($csvTagsArray['name_en'], $locale);
            }
        }

        $this->dm->persist($role);

        return $role;
    }

    private function createPermissionProfileFromCsvArray(array $permissionProfileArray): PermissionProfile
    {
        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($permissionProfileArray[1]);
        $permissionProfile->setSystem((bool) $permissionProfileArray[2]);
        $permissionProfile->setDefault((bool) $permissionProfileArray[3]);
        if ((PermissionProfile::SCOPE_GLOBAL === $permissionProfileArray[4])
            || (PermissionProfile::SCOPE_PERSONAL === $permissionProfileArray[4])
            || (PermissionProfile::SCOPE_NONE === $permissionProfileArray[4])) {
            $permissionProfile->setScope($permissionProfileArray[4]);
        }
        foreach (array_filter(preg_split('/[,\s]+/', $permissionProfileArray[5])) as $permission) {
            if ('none' === $permission) {
                break;
            }
            if ('all' === $permission) {
                $permissionProfile = $this->addAllPermissions($permissionProfile);

                break;
            }
            if (array_key_exists($permission, $this->allPermissions)) {
                $permissionProfile->addPermission($permission);
            }
        }
        $this->dm->persist($permissionProfile);

        return $permissionProfile;
    }

    private function addAllPermissions(PermissionProfile $permissionProfile): PermissionProfile
    {
        foreach ($this->allPermissions as $key => $value) {
            $permissionProfile->addPermission($key);
        }

        return $permissionProfile;
    }

    private function locateResource(string $filePath): Finder
    {
        $finder = new Finder();
        $finder->files()->in($this->kernel->locateResource('@PumukitSchemaBundle/'.$filePath));

        return $finder;
    }

    private function isEmptyFile(Finder $finder): bool
    {
        if ($finder->count() > 0) {
            return false;
        }

        if (!isset($this->file) || 0 !== strcmp($this->file, '')) {
            return false;
        }

        return true;
    }

    private function validateFile(string $fileRoute): array
    {
        $validate = true;
        $message = '';

        try {
            if (!file_exists($fileRoute)) {
                $message = '<error>'.$this->repoName.': Error stating '.$fileRoute.": File doesn't exist</error>";
                $validate = false;
            }

            if (false === ($file = fopen($fileRoute, 'rb'))) {
                $message = '<error>Error opening '.$fileRoute.": fopen() returned 'false' </error>";
                $validate = false;
            }

            if ('tag' === $this->repoName) {
                if (false === ($csvTagHeaders = fgetcsv($file, 0, ';'))) {
                    $message = '<error>Error reading first row (csv header) of '.
                               $fileRoute.
                               ": fgetcsv returned 'false' </error>";
                    $validate = false;
                }

                $result_diff = array_diff(self::TAG_REQUIRED_FIELDS, $csvTagHeaders);
                if (count($result_diff) > 0) {
                    $message = '<error>Error reading first row (csv header) of '.
                               $fileRoute.
                               ": HEADER doesn't have the required fields: ".
                               print_r($result_diff, true).
                               ' </error>';
                    $validate = false;
                }
            }

            $fileExtension = pathinfo($fileRoute, PATHINFO_EXTENSION);
            $ending = substr($fileExtension, -1);
            if ('~' === $ending || ('#' === $ending)) {
                $message = '<comment>'.$this->repoName.': Ignoring file '.$file.'</comment>';
                $validate = false;
            }
        } catch (\Exception $e) {
            return [
                'status' => $validate,
                'message' => $message,
            ];
        }

        return [
            'status' => $validate,
            'message' => $message,
        ];
    }
}
