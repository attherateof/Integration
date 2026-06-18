<?php

declare(strict_types=1);

namespace MageStack\Integration\Console\Command;

use Magento\Framework\Console\Cli;
use MageStack\Integration\Model\ConfigResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowConfigs extends Command
{
    private const ARG_API = 'api';
    private const ARG_WEBSITE = 'website';

    public function __construct(
        private readonly ConfigResolver $resolver
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magestack:integration:configs')
            ->setDescription('Display resolved API configuration')
            ->addArgument(
                self::ARG_API,
                InputArgument::REQUIRED,
                'API code (e.g. erp)'
            )
            ->addArgument(
                self::ARG_WEBSITE,
                InputArgument::REQUIRED,
                'Website code (e.g. IN)'
            );

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        try {
            $config = $this->resolver->resolve(
                (string) $input->getArgument(self::ARG_API),
                $input->getArgument(self::ARG_WEBSITE) ?? null
            );

            $output->writeln(
                json_encode(
                    $config,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );

            return Cli::RETURN_SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    $exception->getMessage()
                )
            );

            return Cli::RETURN_FAILURE;
        }
    }
}
