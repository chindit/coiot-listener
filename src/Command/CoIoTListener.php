<?php

namespace App\Command;

use App\Enums\ShellyCodes;
use App\Event\StatusUpdateEvent;
use App\Model\ShellyStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name:'coiot:listen')]
class CoIoTListener extends Command {
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $coapListening = new Process(['socat', 'UDP4-RECVFROM:5683,fork', 'STDOUT']);
        $coapListening->setTimeout(null);
        $coapListening->run(function ($type, $buffer) use ($io): void {
            if ($type === Process::ERR) {
                $io->error($buffer);
            } else {
                $utf8String = mb_convert_encoding($buffer, 'UTF-8', 'ASCII');
                $pattern = '/(#(.*)#).*({.*})/';
                preg_match($pattern, $utf8String, $matches);

                if (count($matches) === 4) {
                    $deviceID = $matches[2];
                    $jsonString =  $matches[3];

                    $payload = json_decode($jsonString, true);
                    $statuses = [];
                    array_walk($payload, function(array $item) use (&$statuses) {
                        $value = ShellyCodes::tryFrom($item[1]);
                        if ($value) {
                            $statuses[$value->name] = $item[2];
                        }
                    });

                    $this->dispatcher->dispatch(new StatusUpdateEvent(new ShellyStatus($deviceID, $statuses)));
                }
                $io->warning('Unable to fetch data from response.  Received string is : ' . $utf8String);
            }
        });

        return Command::SUCCESS;
    }
}