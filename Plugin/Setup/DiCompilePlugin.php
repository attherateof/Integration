<?php

declare(strict_types=1);

namespace MageStack\Integration\Plugin\Setup;

use Magento\Framework\Console\Cli as CliFramework;
use Magento\Setup\Console\Command\DiCompileCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MageStack\Integration\Model\Setup\IntegrationCacheRefresher;

class DiCompilePlugin
{
    public function __construct(
        private readonly IntegrationCacheRefresher $refresher
    ) {}

    /**
     * After plugin for DiCompileCommand::execute
     *
     * @param DiCompileCommand $subject
     * @param int $result
     */
    public function afterExecute(
        DiCompileCommand $subject,
        int $result,
        InputInterface $input,
        OutputInterface $output
    ): int {
        // Attempt to refresh integration cache after DI compile
        $this->refresher->refreshAll();

        return $result;
    }
}
