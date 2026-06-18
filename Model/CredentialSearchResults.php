<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use MageStack\Integration\Api\Data\CredentialSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class CredentialSearchResults extends SearchResults implements CredentialSearchResultsInterface {}
