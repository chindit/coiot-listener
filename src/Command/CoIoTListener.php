<?php

namespace App\Command;

use App\Entity\Shelly;
use App\Enums\ShellyCodes;
use App\Event\StatusUpdateEvent;
use App\Event\StatusUpdateRpcEvent;
use App\Model\ShellyStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name:'coiot:listen')]
class CoIoTListener extends Command {
    private SymfonyStyle $io;
    public function __construct(private readonly EventDispatcherInterface $dispatcher, private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $coapListening = new Process(['socat', 'UDP4-RECVFROM:5683,fork', 'STDOUT']);
        $coapListening->setTimeout(null);
        $coapListening->run(function ($type, $buffer): void {
            if ($type === Process::ERR) {
                $this->io->error($buffer);
            } else {
                try {
                    /**
                     * Some new Shelly devices cannot send via CoIoT but via RPC over UDP
                     * CoIoT and RPC are caught in this command, but we need to differentiate them.
                     * RPC payload are 100% JSON compliant with no extra data
                     */
                    if (json_validate($buffer)) {
                        $this->parseRpc($buffer);
                    } else {
                        $this->parseCoIoT($buffer);
                    }
                } catch (\Throwable $t) {
                    $this->logger->error('Unable to parse log.  Received input is : ' . $buffer, ['error' => $t]);
                }
            }
        });

        return Command::SUCCESS;
    }
    
    private function parseCoIoT(string $input): void
    {
        $utf8String = preg_replace('/\s+/', '', mb_convert_encoding($input, 'UTF-8', 'ASCII'));
        $pattern = '/(#([A-Z0-9]+)#).*({".*})/mU';
        preg_match($pattern, $utf8String, $matches);

        if (count($matches) === 4) {
            $deviceID = $matches[2];
            $jsonString =  $matches[3];

            $payload = json_decode($jsonString, true);
            $statuses = [];
            array_walk($payload['G'], function(array $item) use (&$statuses) {
                $value = ShellyCodes::tryFrom($item[1]);
                if ($value) {
                    $statuses[$value->name] = $item[2];
                }
            });
            $this->dispatcher->dispatch(new StatusUpdateEvent(new ShellyStatus($deviceID, $statuses)), StatusUpdateEvent::NAME);
        } else {
            $this->io->warning('Unable to fetch data from response.  Received string is : ' . $utf8String);
        }
    }

    private function parseRpc(string $input): void
    {
        $payload = json_decode($input);
        /**
         * We can receive these methods:
         * - NotifyStatus : status call (ex: device got an IP, is connected to cloud, etc.
         * - NotifyEvent : similar to NotifyStatus.  Contains infos such as sleep, wakeup, etc.
         * - NotifyFullStatus : Full payload of the device, including sensors value (which we are looking for)
         *
         * For the moment, we only support Shelly Plus H&T.
         * Feel free to make a PR if you have other needs
         */
        if ($payload?->method === 'NotifyFullStatus') {
            $deviceId = explode('-', $payload->src);
            $shellyStatus = (new Shelly())
                ->setDeviceId(array_pop($deviceId))
                ->setType('sensor')
                ->setData([
                    'battery' => [
                        'percent' => $payload->params->{'devicepower:0'}->battery->percent,
                        'voltage' => $payload->params->{'devicepower:0'}->battery->V
                    ],
                    'humidity' => $payload->params->{'humidity:0'}->rh,
                    'temperature' => $payload->params->{'temperature:0'}->tC
                ]);

            $this->dispatcher->dispatch(new StatusUpdateRpcEvent($shellyStatus), StatusUpdateRpcEvent::NAME);
        }

        $this->logger->debug('Received RPC message: ' . $input);
    }
}