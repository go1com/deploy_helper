<?php

namespace go1\deploy_helper\command;

use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig_Loader_Filesystem;

class TranslationExtractorCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('translate:extractor')
            ->addOption('php', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Directories of PHP course base to extract translations.')
            ->addOption('twig', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Directories of Twig templates to extract translations.')
            ->addOption('yaml', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Directories of YAML content files to extract translations.')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Directory to store extracted messages.')
            ->addUsage('deploy-helper translate:extractor --target=/path/to/resources/translations --php=/path/php-1 --php=/php/php-2 --twig=/path/to/twig-resources --yaml=/path/to/yaml-files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dictionary = new MessageCatalogue('en');

        if ($php = $input->getOption('php')) {
            $extractor = new PhpExtractor;
            foreach ($php as $path) {
                $extractor->extract($path, $dictionary);
            }
        }

        if ($twig = $input->getOption('twig')) {
            $loader = new Twig_Loader_Filesystem($twig);
            $env = new Environment($loader);
            $extractor = new TwigExtractor($env);
            foreach ($twig as $path) {
                $extractor->extract($path, $dictionary);
            }
        }

        if ($yaml = $input->getOption('yaml')) {
            $collect = function ($key, $array) use (&$dictionary, &$collect) {
                foreach ($array as $k => $element) {
                    if (is_string($element)) {
                        $dictionary->set("$key.$k", $element, 'notify');
                    }
                    elseif (is_array($element)) {
                        $collect("$key.$k", $element);
                    }
                }
            };

            $yaml = function ($file) use (&$collect) {
                $collect('', @Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS));
            };

            foreach ($yaml as $path) {
                array_map($yaml, glob($path));
            }
        }

        if ($php || $twig || $yaml) {
            $target = $input->getOption('target');
            (new PoFileDumper)->dump($dictionary, ['path' => $target]);
        }
    }
}
