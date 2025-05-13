<?php

namespace Plugins\DcatSaas\Support;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    public static function run(string $cmd, mixed $output = null, ?string $cwd = null): SymfonyProcess
    {
        $cwd = $cwd ?? base_path();

        $process = SymfonyProcess::fromShellCommandline($cmd, $cwd);

        $process->setTimeout(900);

        if ($process->isTty()) {
            $process->setTty(true);
        }

        try {
            if ($output !== false) {
                $output = app(OutputInterface::class);
            }
        } catch (\Throwable $e) {
            $output = $output ?? null;
        }

        if ($output) {
            $output->write("\n");
            $process->run(
                function ($type, $line) use ($output) {
                    $output->write($line);
                }
            );
        } else {
            $process->run();
        }

        return $process;
    }
}
