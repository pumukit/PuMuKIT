<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;

class PumukitInitRepoCommand extends ContainerAwareCommand
{
    const BROADCAST_DEFAULT = 'default';
    const BROADCAST_CAS = 'cas';

    private $dm = null;
    private $tagsRepo = null;
    private $broadcastsRepo = null;
    private $rolesRepo = null;

    private $tagsPath = "../Resources/data/tags/";
    private $broadcastsPath = "../Resources/data/broadcasts/";
    private $rolesPath = "../Resources/data/roles/";
    private $permissionProfilesPath = "../Resources/data/permissionprofiles/";

    private $broadcastOption = self::BROADCAST_DEFAULT;

    protected function configure()
    {
        $this
            ->setName('pumukit:init:repo')
            ->setDescription('Load Pumukit data fixtures to your database')
            ->addArgument('repo', InputArgument::REQUIRED, 'Select the repo to init: tag, broadcast, role, permissionprofile, all')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('option', 'o', InputOption::VALUE_OPTIONAL, 'Input Broadcast option: default, cas. Default if none given.', $this->broadcastOption)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT

Command to load a controlled set of data into a database. Useful for init Pumukit environment.

The --force parameter has to be used to actually drop the database.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        if ($input->getOption('force') && ($repoName = $input->getArgument('repo'))) {
            switch ($repoName) {
                case "all":
                  $errorExecuting = $this->executeTags($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    $errorExecuting = $this->executeBroadcasts($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    $errorExecuting = $this->executeRoles($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    $errorExecuting = $this->executePermissionProfiles($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    break;
                case "tag":
                    $errorExecuting = $this->executeTags($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    break;
                case "broadcast":
                    $errorExecuting = $this->executeBroadcasts($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    break;
                case "role":
                    $errorExecuting = $this->executeRoles($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    break;
                case "permissionprofile":
                    $errorExecuting = $this->executePermissionProfiles($input, $output);
                    if (-1 === $errorExecuting) return -1;
                    break;
            }
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute and with --repo to chose the repository to initialize.');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }
    }

    protected function executeTags(InputInterface $input, OutputInterface $output)
    {
        $this->tagsRepo = $this->dm->getRepository("PumukitSchemaBundle:Tag");

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->tagsPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, "")) && (!$finder)) {
            $output->writeln("<error>Tags: There's no data to initialize</error>");
        
            return -1;
        }
        $this->removeTags();
        $root = $this->createRoot();
        if ($file) {
            $this->createFromFile($file, $root, $output, 'tag');
        } else {
            foreach ($finder as $tagFile) {
                $this->createFromFile($tagFile, $root, $output, 'tag');
            }
        }

        return 0;
    }

    protected function executeBroadcasts(InputInterface $input, OutputInterface $output)
    {
        $this->broadcastsRepo = $this->dm->getRepository("PumukitSchemaBundle:Broadcast");

        if ($broadcastOption = $input->getOption('option')) {
            if (($broadcastOption === self::BROADCAST_DEFAULT) || ($broadcastOption === self::BROADCAST_CAS)) {
                $this->broadcastOption = $broadcastOption;
            } else {
                throw new \Exception('Broadcast Option: "'.$broadcastOption.'" not valid. Valid values: "'
                                    .self::BROADCAST_DEFAULT.'" or "'.self::BROADCAST_CAS.'".');
            }
        }

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->broadcastsPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, "")) && (!$finder)) {
            $output->writeln("<error>Broadcasts: There's no data to initialize</error>");

            return -1;
        }
        $this->removeBroadcasts();
        if ($file) {
            $this->createFromFile($file, null, $output, 'broadcast');
        } else {
            foreach ($finder as $broadcastFile) {
                if (0 === strpos(pathinfo($broadcastFile, PATHINFO_FILENAME), $this->broadcastOption)) {
                    $this->createFromFile($broadcastFile, null, $output, 'broadcast');
                    break;
                }
            }
        }

        return 0;
    }

    protected function executeRoles(InputInterface $input, OutputInterface $output)
    {
        $this->rolesRepo = $this->dm->getRepository("PumukitSchemaBundle:Role");

        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->rolesPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, "")) && (!$finder)) {
            $output->writeln("<error>Roles: There's no data to initialize</error>");

            return -1;
        }
        $this->removeRoles();
        if ($file) {
            $this->createFromFile($file, null, $output, 'role');
        } else {
            foreach ($finder as $roleFile) {
                $this->createFromFile($roleFile, null, $output, 'role');
            }
        }

        return 0;
    }

    protected function executePermissionProfiles(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->permissionProfilesPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, "")) && (!$finder)) {
            $output->writeln("<error>PermissionProfiles: There's no data to initialize</error>");

            return -1;
        }
        $this->removePermissionProfiles();
        if ($file) {
            $this->createFromFile($file, null, $output, 'permissionprofile');
        } else {
            foreach ($finder as $permissionProfilesFile) {
                $this->createFromFile($permissionProfilesFile, null, $output, 'permissionprofile');
            }
        }

        return 0;
    }

    protected function removeTags()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
    }

    protected function removeBroadcasts()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
    }

    protected function removeRoles()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
    }

    protected function removePermissionProfiles()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')->remove(array());
    }

    protected function createRoot()
    {
        $root = $this->createTagFromCsvArray(array(null, "ROOT", 1, 1, "ROOT", "ROOT", "ROOT"));
        $this->dm->flush();

        return $root;
    }

    protected function createFromFile($file, $root, OutputInterface $output, $repoName)
    {
        if (!file_exists($file)) {
            $output->writeln("<error>".$repoName.": Error stating ".$file."</error>");

            return -1;
        }
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
        $ending = substr($fileExtension, -1);
        if (('~' === $ending) || ('#' === $ending)) {
            $output->writeln("<comment>".$repoName.": Ignoring file ".$file."</comment>");
            return -1;
        }
        $output->writeln("<info>Found file: ".realpath($file)."</info>");

        $idCodMapping = array();

        $row = 1;
        if (($file = fopen($file, "r")) !== false) {
            while (($currentRow = fgetcsv($file, 300, ";")) !== false) {
                $number = count($currentRow);
                if ((('tag' === $repoName) && ($number == 6 || $number == 8)) || 
                    (('broadcast' === $repoName) && ($number == 5 || $number == 8)) || 
                    (('role' === $repoName) && ($number == 7 || $number == 10)) ||
                    (('permissionprofile' === $repoName) && ($number == 6))){
                    //Check header rows
                    if (trim($currentRow[0]) == "id") {
                        continue;
                    }

                    if ('tag' === $repoName){
                        $parent = isset($idCodMapping[$currentRow[2]])
                          ? $idCodMapping[$currentRow[2]]
                          : $root;
                    }

                    try {
                        switch ($repoName){
                            case 'tag':
                                $tag = $this->createTagFromCsvArray($currentRow, $parent);
                                $idCodMapping[$currentRow[0]] = $tag;
                                $output->writeln("Tag persisted - new id: ".$tag->getId()." cod: ".$tag->getCod());
                                break;
                            case 'broadcast':
                                $broadcast = $this->createBroadcastFromCsvArray($currentRow);
                                $idCodMapping[$currentRow[0]] = $broadcast;
                                $output->writeln("Broadcast persisted - new id: ".$broadcast->getId()." name: ".$broadcast->getName().", type: ".$broadcast->getBroadcastTypeId());
                                break;
                            case 'role':
                                $role = $this->createRoleFromCsvArray($currentRow);
                                $idCodMapping[$currentRow[0]] = $role;
                                $output->writeln("Role persisted - new id: ".$role->getId()." code: ".$role->getCod());
                                break;
                            case 'permissionprofile':
                                $permissionProfile = $this->createPermissionProfileFromCsvArray($currentRow);
                                $idCodMapping[$currentRow[0]] = $permissionProfile;
                                $output->writeln("PermissionProfile persisted - new id: ".$permissionProfile->getId()." name: ".$permissionProfile->getName());
                                break;
                        }
                    } catch (\Exception $e) {
                        $output->writeln("<error>".$repoName.': '.$e->getMessage()."</error>");
                    }
                } else {
                    $output->writeln($repoName.": Last valid row = ...");
                    $output->writeln("Error: line $row has $number elements");
                }

                if ($row % 100 == 0) {
                    echo "Row ".$row."\n";
                }
                $previous_content = $currentRow;
                $row++;
            }
            fclose($file);
            $this->dm->flush();
        } else {
            $output->writeln("<error>Error opening ".$file."</error>");

            return -1;
        }
    }

    /**
     *
     */
    private function createTagFromCsvArray($csv_array, $tag_parent = null)
    {
        if ($tag = $this->tagsRepo->findOneByCod($csv_array[1])) {
            throw new \LengthException("Nothing done - Tag retrieved from DB id: ".$tag->getId()." cod: ".$tag->getCod());
        }

        $tag = new Tag();
        $tag->setCod($csv_array[1]);
        $tag->setMetatag($csv_array[3]);
        $tag->setDisplay($csv_array[4]);
        if ($tag_parent) {
            $tag->setParent($tag_parent);
        }
        // NOTE Take care of csv language order!
        if (isset($csv_array[5])) {
            $tag->setTitle($csv_array[5], 'es');
        }
        if (isset($csv_array[6])) {
            $tag->setTitle($csv_array[6], 'gl');
        }
        if (isset($csv_array[7])) {
            $tag->setTitle($csv_array[7], 'en');
        }

        $this->dm->persist($tag);

        return $tag;
    }

    /**
     * Create Broadcast from CSV array
     */
    private function createBroadcastFromCsvArray($csv_array)
    {
        $broadcast = new Broadcast();

        $broadcast->setName($csv_array[1]);
        if (in_array($csv_array[2], array(Broadcast::BROADCAST_TYPE_PUB, Broadcast::BROADCAST_TYPE_PRI, Broadcast::BROADCAST_TYPE_COR))){
            $broadcast->setBroadcastTypeId($csv_array[2]);
        }else{
            $broadcast->setBroadcastTypeId(Broadcast::BROADCAST_TYPE_PRI);
        }
        $broadcast->setPasswd($csv_array[3]);
        $broadcast->setDefaultSel($csv_array[4]);
        // NOTE Take care of csv language order!
        if (isset($csv_array[5])) {
            $broadcast->setDescription($csv_array[5], 'es');
        }
        if (isset($csv_array[6])) {
            $broadcast->setDescription($csv_array[6], 'gl');
        }
        if (isset($csv_array[7])) {
            $broadcast->setDescription($csv_array[7], 'en');
        }
        
        $this->dm->persist($broadcast);

        return $broadcast;
    }

    /**
     * Create Role from CSV array
     */
    private function createRoleFromCsvArray($csv_array)
    {
        $role = new Role();

        $role->setCod($csv_array[1]);
        $role->setXml($csv_array[2]);
        $role->setDisplay($csv_array[3]);
        // NOTE Take care of csv language order!
        $role->setName($csv_array[4], 'es');
        if (isset($csv_array[5])) {
            $role->setName($csv_array[5], 'gl');
        }
        if (isset($csv_array[6])) {
            $role->setName($csv_array[6], 'en');
        }
        // NOTE Take care of csv language order!
        if (isset($csv_array[7])) {
            $role->setText($csv_array[7], 'es');
        }
        if (isset($csv_array[8])) {
            $role->setText($csv_array[8], 'gl');
        }
        if (isset($csv_array[9])) {
            $role->setText($csv_array[9], 'en');
        }

        $this->dm->persist($role);

        return $role;
    }

    /**
     * Create PermissionProfile from CSV array
     */
    private function createPermissionProfileFromCsvArray($csv_array)
    {
        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($csv_array[1]);
        $permissionProfile->setSystem($csv_array[2]);
        $permissionProfile->setDefault($csv_array[3]);
        if (($csv_array[4] === PermissionProfile::SCOPE_GLOBAL) ||
            ($csv_array[4] === PermissionProfile::SCOPE_PERSONAL) ||
            ($csv_array[4] === PermissionProfile::SCOPE_NONE)) {
            $permissionProfile->setScope($csv_array[4]);
        }
        foreach (array_filter(preg_split('/[,\s]+/', $csv_array[5])) as $permission) {
            if ($permission === 'none') {
                break;
            } elseif ($permission === 'all') {
                $permissionProfile = $this->addAllPermissions($permissionProfile);
                break;
            } elseif (array_key_exists($permission, Permission::$permissionDescription)) {
                $permissionProfile->addPermission($permission);
            }
        }

        $this->dm->persist($permissionProfile);

        return $permissionProfile;
    }

    private function addAllPermissions(PermissionProfile $permissionProfile)
    {
        foreach (Permission::$permissionDescription as $key => $value) {
            $permissionProfile->addPermission($key);
        }

        return $permissionProfile;
    }
}
