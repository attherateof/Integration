<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class IntegrationConfig
{
    public function __construct(
        private readonly ReaderInterface $reader,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * ENTRY POINT (handles all CLI cases)
     */
    public function resolve(): array
    {
        // try {
        return $this->reader->read();
        // } catch (\Throwable $th) {
        //     $errorMessage = $th->getMessage();
        //     $stackTrace = $th->getTraceAsString();
        //     $message =  __("Failed to load integration api configuration");
        //     $this->logger->error(
        //         $message,
        //         [
        //             'message' => $errorMessage,
        //             'stack_trace' => $stackTrace
        //         ]
        //     );

        //     throw new LocalizedException($message, $th);
        // }
    }
}
