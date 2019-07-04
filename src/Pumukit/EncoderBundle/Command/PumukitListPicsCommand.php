<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class PumukitListPicsCommand extends ContainerAwareCommand
{
    private $output;
    private $input;
    private $size = 100;
    private $path;
    private $extension;
    private $tags;
    private $exists;
    private $type;
    private $picService;
    private $id;

    protected function configure()
    {
        $this
            ->setName('pumukit:pics:list')
            ->setDescription('Command to get all pics like selected filters.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'List pics by id.')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'List pics by path.')
            ->addOption('extension', null, InputOption::VALUE_OPTIONAL, 'List pics by extension.')
            ->addOption('tags', null, InputOption::VALUE_OPTIONAL, 'List pics by tag.')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type can be series or mmobj', 'mm')
            ->addOption('size', null, InputOption::VALUE_OPTIONAL, 'List pics greater than selected size in KB.')
            ->addOption('exists', null, InputOption::VALUE_OPTIONAL, 'List exists or not exists file pics.')
            ->setHelp(<<<'EOT'
            
Command to get all pics like selected filters. The predefined filter is that the pics must have "path" attribute.

Id example: --id="5b4dd4c22bb478607d8b456b"
Path example: --path="/mnt/storage/" ...
Extension examples: --extension=".jpg" or --extension="jpg" or --extension=".jpg,.png" or --extension="jpg,png". Can be all myme_types...
Tags examples: --tags="pumukit" or --tags="pumukit,auto,frame_0" ...
Size examples: --size=1 or --size=10 or --size=100 ...
Exists:
      If you defined exists option, the command will return exists images or not exists images.
      If you don't defined this option, the command will return all images
      Example:
              --exists="1" or --exists="0" or --exists="true" or --exists="false" ..
                   
Example commands:

php app/console pumukit:pics:list --id="5b4dd4c22bb478607d8b456b" --exists=true --type="mm"
php app/console pumukit:pics:list --tags="master,youtube,hello" --extension=".png,.jpg" --exists=true --type="mm"
php app/console pumukit:pics:list --tags="master,youtube,hello" --extension=".png,.jpg" --exists=true --type="series"
php app/console pumukit:pics:list --tags="master,youtube" --extension=".png,.jpg" --exists=true
php app/console pumukit:pics:list --size=10000
php app/console pumukit:pics:list --tags="master" --size=10000
php app/console pumukit:pics:list --path="/mnt/storage/" --size=10000

EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->picService = $this->getContainer()->get('pumukitencoder.pic');
        $this->output = $output;
        $this->input = $input;
        $this->id = $this->input->getOption('id');
        $this->size = $this->input->getOption('size');
        $this->path = $this->input->getOption('path');
        $this->extension = $this->input->getOption('extension');
        $this->tags = $this->input->getOption('tags');
        $this->exists = $this->input->getOption('exists');
        $this->type = $this->input->getOption('type');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool|int|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validInput = $this->checkInputOptions();
        if (!$validInput['success']) {
            throw new \Exception($validInput['message']);
        }

        $inputs = $this->picService->formatInputs($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type);
        list($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type) = $inputs;

        $pics = $this->picService->findPicsByOptions($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type);

        if ($pics) {
            $this->showData($pics);
        } else {
            $this->output->writeln('No pics found');
        }

        return true;
    }

    /**
     * @return array
     */
    private function checkInputOptions()
    {
        $isValidInput = ['success' => true];
        if ($this->size && !is_string($this->size)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Size must be string, then will be converted';
        }

        if ($this->extension && !is_string($this->extension)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Extension must be string';
        }

        if ($this->path && !is_string($this->path)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Path must be string';
        }

        if ($this->tags && !is_string($this->tags)) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Tags must be string';
        }

        if ($this->exists && !in_array(strtolower($this->exists), ['false', 'true', '1', '0'])) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Exists must be boolean';
        }

        if (!in_array($this->type, ['mm', 'series'])) {
            $isValidInput['success'] = false;
            $isValidInput['message'] = 'Type must be have the value series or mm';
        }

        return $isValidInput;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function showData($data)
    {
        if (empty($data['pics'])) {
            $this->output->writeln('No pics found');
        }

        foreach ($data['pics'] as $pic) {
            if (isset($pic['path'])) {
                $message = $pic['path'];
            } elseif (isset($pic['url'])) {
                $message = $pic['url'];
            } else {
                $message = $pic;
            }

            $this->output->writeln($message);
        }

        return true;
    }
}
