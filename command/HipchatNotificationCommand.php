<?php

namespace go1\deploy_helper\command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HipchatNotificationCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('notify:hipchat')
            ->addArgument('room', InputArgument::REQUIRED)
            ->addArgument('message', InputArgument::REQUIRED)
            ->addOption('key', null, InputOption::VALUE_REQUIRED)
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, '', 'CI')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, '', 'html')
            ->addOption('notify', null, InputOption::VALUE_OPTIONAL, '', false)
            ->addOption('color', null, InputOption::VALUE_OPTIONAL, '', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $room = $input->getArgument('room');
        $message = $input->getArgument('message');
        $key = $input->getOption('key');
        $from = $input->getOption('from');
        $format = $input->getOption('format');
        $color = $input->getOption('color');

        (new Client)->post(
            $url = "https://api.hipchat.com/v1/rooms/message?auth_token={$key}&format=json",
            [
                'form_params' => [
                    'room_id'        => $room,
                    'from'           => $from,
                    'message'        => $message,
                    'message_format' => $format,
                    'color'          => $color,
                ],
            ]
        );
    }
}
