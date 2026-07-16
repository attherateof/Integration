<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Queue\Consumer;

use Psr\Log\LoggerInterface;
use MageStack\Integration\Model\ApiAttemptRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProcessAttemptResult
{
    public function __construct(private readonly ApiAttemptRepository $attemptRepository, private readonly LoggerInterface $logger) {}

    /**
     * Message payload expected: ['attempt_id'=>int, 'success'=>bool, 'attempt'=>int, 'next_attempt_at'=>string|null]
     */
    public function process(array $message): void
    {
        $id = (int) ($message['attempt_id'] ?? 0);
        if ($id <= 0) {
            $this->logger->warning('ProcessAttemptResult received invalid message payload.');
            return;
        }

        try {
            $attempt = $this->attemptRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Attempt not found when persisting result: ' . $e->getMessage());
            return;
        }

        try {
            if (!empty($message['success'])) {
                $this->attemptRepository->delete($attempt);
                $this->logger->info(sprintf('Deleted attempt id=%d after success', $id));
                return;
            }

            $attempt->setAttempt((int) ($message['attempt'] ?? $attempt->getAttempt()));
            if (isset($message['next_attempt_at'])) {
                $attempt->setNextAttemptAt((string) $message['next_attempt_at']);
            }

            $this->attemptRepository->save($attempt);
            $this->logger->info(sprintf('Updated attempt id=%d for retry', $id));
        } catch (\Throwable $e) {
            $this->logger->error('Error persisting attempt result: ' . $e->getMessage());
        }
    }
}
