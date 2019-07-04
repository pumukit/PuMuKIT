<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class PumukitPicsConvertCommand extends ContainerAwareCommand
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
    private $convert;
    private $convert_size;
    private $convert_ext;
    private $convert_quality;
    private $no_replace;
    private $convert_max_width;
    private $convert_max_height;

    protected function configure()
    {
        $this
            ->setName('pumukit:pics:convert')
            ->setDescription('Command to get all pics like selected filters and create new images with low size.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'List pics by id.')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'List pics by path.')
            ->addOption('extension', null, InputOption::VALUE_OPTIONAL, 'List pics by extension.')
            ->addOption('tags', null, InputOption::VALUE_OPTIONAL, 'List pics by tag.')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type can be series or mmobj', 'mm')
            ->addOption('size', null, InputOption::VALUE_OPTIONAL, 'List pics greater than selected size in KB.')
            ->addOption('convert', null, InputOption::VALUE_NONE, 'Convert result pics')
            ->addOption('convert_ext', null, InputOption::VALUE_OPTIONAL, 'Convert result extension', 'jpg')
            ->addOption('convert_size', null, InputOption::VALUE_REQUIRED, 'Max size for the new images ( Default 100K )', 100)
            ->addOption('convert_quality', null, InputOption::VALUE_OPTIONAL, 'Convert quality of image ( 0 to 100 )', 100)
            ->addOption('convert_max_width', null, InputOption::VALUE_OPTIONAL, 'Set max width of the new image')
            ->addOption('convert_max_height', null, InputOption::VALUE_OPTIONAL, 'Set max height of the new image')
            ->addOption('no_replace', null, InputOption::VALUE_NONE, 'Replace original image or not')
            ->setHelp(<<<'EOT'
        
Command to get all pics like selected filters and create new images with low size. The predefined filter is that the pics must have "path" attribute.

Filters: 

Id example: --id="5b4dd4c22bb478607d8b456b"
Path example: --path="/mnt/storage/" ...
Extension examples: --extension=".jpg" or --extension="jpg" or --extension=".jpg,.png" or --extension="jpg,png". Can be all myme_types...
Tags examples: --tags="pumukit" or --tags="pumukit,auto,frame_0" ...
Size examples: --size=1 or --size=10 or --size=100 ...
                   
Example commands to set filters:

php app/console pumukit:pics:convert --id="5b4dd4c22bb478607d8b456b" --exists=true --type="mm"
php app/console pumukit:pics:convert --tags="master,youtube,hello" --extension=".png,.jpg" --type="mm"
php app/console pumukit:pics:convert --tags="master,youtube,hello" --extension=".png,.jpg" --type="series"
php app/console pumukit:pics:convert --tags="master,youtube" --extension=".png,.jpg"
php app/console pumukit:pics:convert --size=10000
php app/console pumukit:pics:convert --tags="master" --size=10000
php app/console pumukit:pics:convert --path="/mnt/storage/" --size=10000

Create image options:

--convert
--convert_ext="jpg"  ( Doesnt work, ever convert to JPG ) 
--convert_size="10000" ( Doesnt work ) 
--convert_quality=100
--convert_max_width=1920
--convert_max_height=1080
--no_replace 

Examples: 

php app/console pumukit:pics:convert --path="/var/www/html/pumukit2/web/uploads/pic/5b4f4af72bb478f9048b457d/" --type="mm" --convert
php app/console pumukit:pics:convert --path="/var/www/html/pumukit2/web/uploads/pic/5b4f4af72bb478f9048b457d/" --type="mm" --convert --no_replace



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
        $this->exists = 'true';
        $this->type = $this->input->getOption('type');

        $this->convert = $this->input->getOption('convert');
        $this->convert_size = $this->input->getOption('convert_size');
        $this->convert_ext = $this->input->getOption('convert_ext');
        $this->convert_quality = $this->input->getOption('convert_quality');
        $this->convert_max_width = $this->input->getOption('convert_max_width');
        $this->convert_max_height = $this->input->getOption('convert_max_height');
        $this->no_replace = $this->input->getOption('no_replace');
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
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension not installed. See http://php.net/manual/en/image.installation.php for installation options.');
        }
        $validInput = $this->checkInputOptions();
        if (!$validInput['success']) {
            throw new \Exception($validInput['message']);
        }

        try {
            $inputs = $this->picService->formatInputs($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type);
            [$this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type] = $inputs;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        $pics = $this->picService->findPicsByOptions($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type);

        if ($this->convert) {
            $params = [
                'size' => $this->convert_size,
                'ext' => $this->convert_ext,
                'quality' => $this->convert_quality,
                'max_width' => $this->convert_max_width,
                'max_height' => $this->convert_max_height,
            ];
            $data = $this->picService->convertImage($pics, $params, $this->no_replace);
            $this->showOutput($data);
        } else {
            $this->showData($pics);
            $this->output->writeln('<info>Please set option --convert to start convert</info>');
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

            return false;
        }

        foreach ($data['pics'] as $pic) {
            if (isset($pic['path'])) {
                $message = 'Image: '.$pic['path'];
            } else {
                $message = $pic;
            }

            $this->output->writeln($message);
        }
    }

    /**
     * @param $data
     */
    private function showOutput($data)
    {
        foreach ($data as $message) {
            $this->output->writeln($message);
        }
    }
}
