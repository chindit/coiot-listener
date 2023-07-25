<?php

namespace App\Command;

use App\Enums\ShellyCodes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name:'generate:description')]
class DeviceDescription extends Command
{
    public function __construct(private string $fileLocation, private readonly HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->fileLocation .= '/src/Enums/ShellyCodes.php';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!is_writable($this->fileLocation)) {
            $io->error(sprintf('File %s must be writable by this script', $this->fileLocation));
            return Command::SUCCESS;
        }

        $enumFileContent = ['<?php', '', 'namespace App\\Enums;', '', 'enum ShellyCodes: int', '{'];
        $override = false;

        if (!empty(ShellyCodes::cases())) {
            $io->info('Description file already exist.');
            $response = $io->choice('Do you want to override it or to append new keys to it ?', ['Override', 'Append', 'Skip']);
            if ($response === 'skip') {
                $io->info('Update skipped');
                return Command::SUCCESS;
            } elseif ($response !== 'Override') {
                array_map(function(ShellyCodes $code) use (&$enumFileContent) {
                    $enumFileContent[] = '    case ' . $code->name . ' = ' . $code->value . ';';
                }, ShellyCodes::cases());
            } else {
                $override = true;
            }
        }

        $targetIp = $io->ask('Entre the IP of the Shelly device you want to add to the mapping: ', validator: function (string $input): string {
            if (!filter_var($input, FILTER_VALIDATE_IP)) {
                throw new \RuntimeException('You must enter a valid IP');
            }
            return trim($input);
        });
        try {
            $descriptionResponse = $this->httpClient->request('GET', 'http://' . $targetIp . '/cit/d')->toArray();

            array_map(function(array $item) use (&$enumFileContent, $override) {
                if (!$override && ShellyCodes::tryFrom($item['I'])) {
                    return;
                }
                $enumLine = '    case ' . $item['D'];
                if (isset($item['U'])) {
                    $enumLine .= '_' . $item['U'];
                }
                $enumLine .= ' = ' . $item['I'] . ';';
                $enumFileContent[] = $enumLine;
            }, $descriptionResponse['sen']);
            $enumFileContent[] = '}';

            file_put_contents($this->fileLocation, join(PHP_EOL, $enumFileContent));
        } catch (\Throwable $t) {
            $io->error('This IP is not a valid Shelly device or is not accessible');
            return Command::SUCCESS;
        }
        $io->info(sprintf('Your devices description was correctly updated.  Check %s for usage.', $this->fileLocation));

        return Command::SUCCESS;
    }
}