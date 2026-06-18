<?php

declare(strict_types=1);

namespace MageStack\Integration\Api;

use MageStack\Integration\Api\Data\CredentialInterface;
use MageStack\Integration\Api\Data\CredentialSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface CredentialRepositoryInterface
{
    /**
     * Save credential row.
     */
    public function save(
        CredentialInterface $credential
    ): CredentialInterface;

    /**
     * Get credential by ID.
     */
    public function getById(
        int $credentialId
    ): CredentialInterface;

    /**
     * Get credentials list.
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): CredentialSearchResultsInterface;

    /**
     * Delete credential.
     */
    public function delete(
        CredentialInterface $credential
    ): bool;

    /**
     * Delete credential by ID.
     */
    public function deleteById(
        int $credentialId
    ): bool;

    /**
     * Returns all credential values for API/Website.
     *
     * Example:
     *
     * [
     *     'client_id'     => 'xxx',
     *     'client_secret' => 'yyy',
     *     'username'      => 'john',
     * ]
     */
    public function getByApiCodeAndWebsite(
        string $apiCode,
        int $websiteId
    ): array;

    /**
     * Returns a single credential row.
     */
    public function getCredential(
        string $apiCode,
        int $websiteId,
        string $fieldName
    ): ?CredentialInterface;

    /**
     * Creates or updates a credential.
     */
    public function saveCredential(
        string $apiCode,
        int $websiteId,
        string $authType,
        string $fieldName,
        string $value
    ): void;

    /**
     * Deletes all credentials for API/Website.
     */
    public function deleteByApiCodeAndWebsite(
        string $apiCode,
        int $websiteId
    ): void;
}
