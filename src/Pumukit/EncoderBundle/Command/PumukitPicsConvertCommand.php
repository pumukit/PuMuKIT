<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Command;

use Pumukit\EncoderBundle\Services\PicService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitPicsConvertCommand extends Command
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
    private $convert_quality;
    private $no_replace;
    private $convert_max_width;
    private $convert_max_height;

    public function __construct(PicService $picService)
    {
        $this->picService = $picService;
        parent::__construct();
    }

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
            ->addOption('convert_quality', null, InputOption::VALUE_OPTIONAL, 'Convert quality of image ( 0 to 100 )', 100)
            ->addOption('convert_max_width', null, InputOption::VALUE_OPTIONAL, 'Set max width of the new image')
            ->addOption('convert_max_height', null, InputOption::VALUE_OPTIONAL, 'Set max height of the new image')
            ->addOption('no_replace', null, InputOption::VALUE_NONE, 'Replace original image or not')
            ->setHelp(
                <<<'EOT'

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
--convert_quality=100
--convert_max_width=1920
--convert_max_height=1080
--no_replace

Examples:

php app/console pumukit:pics:convert --path="{pathToPuMuKITUploadsPicDir}/5b4f4af72bb478f9048b457d/" --type="mm" --convert
php app/console pumukit:pics:convert --path="{pathToPuMuKITUploadsMaterialDir}/5b4f4af72bb478f9048b457d/" --type="mm" --convert --no_replace



EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
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
        $this->convert_quality = $this->input->getOption('convert_quality');
        $this->convert_max_width = $this->input->getOption('convert_max_width');
        $this->convert_max_height = $this->input->getOption('convert_max_height');
        $this->no_replace = $this->input->getOption('no_replace');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension not installed. See http://php.net/manual/en/image.installation.php for installation options.');
        }
        $this->validateInputOptions();

        $inputs = $this->picService->formatInputs($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type);
        [$this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type] = $inputs;

        $pics = $this->picService->findPicsByOptions($this->id, $this->size, $this->path, $this->extension, $this->tags, $this->exists, $this->type);

        if ($this->convert) {
            $params = [
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

        return Command::SUCCESS;
    }

    private function validateInputOptions(): void
    {
        if (!in_array($this->type, ['mm', 'series'])) {
            throw new \Exception('Type must be have the value series or mm');
        }
    }

    private function showData(array $data): bool
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

        return true;
    }

    private function showOutput(array $data): void
    {
        foreach ($data as $message) {
            $this->output->writeln($message);
        }
    }
}
