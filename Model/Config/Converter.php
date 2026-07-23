<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Config;

use DOMDocument;
use DOMElement;
use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * Root-level keys
     */
    private const KEY_APIS = 'apis';

    /**
     * Tag names
     */
    private const TAG_API = 'api';
    private const TITLE = 'title';
    private const TAG_BASE_URL = 'base_url';
    private const TAG_TIMEOUT = 'timeout';
    private const TAG_AUTHENTICATION = 'authentication';
    private const TAG_RETRY = 'retry';
    private const TAG_CACHE = 'cache';
    private const TAG_ENDPOINTS = 'endpoints';
    private const TAG_ENDPOINT = 'endpoint';
    private const TAG_WEBSITES = 'websites';
    private const TAG_WEBSITE = 'website';
    private const TAG_HTTP_CODE = 'http_code';

    /**
     * Attribute names
     */
    private const ATTR_ID = 'id';
    private const ATTR_CODE = 'code';
    private const ATTR_ENABLED = 'enabled';
    private const ATTR_METHOD = 'method';
    private const ATTR_PATH = 'path';
    private const ATTR_GUEST = 'guest';
    private const ATTR_TIMEOUT = 'timeout';
    private const ATTR_TYPE = 'type';
    private const ATTR_TOKEN_ENDPOINT = 'token_endpoint';
    private const ATTR_TOKEN_TTL = 'token_ttl';
    private const ATTR_MAX_ATTEMPTS = 'max_attempts';
    private const ATTR_BACKOFF_MULTIPLIER = 'backoff_multiplier';
    private const ATTR_TTL = 'ttl';
    private const ATTR_KEY_PREFIX = 'key_prefix';

    /**
     * Endpoint config keys (direct child text nodes)
     */
    private const TAG_REQUEST_BUILDER = 'request_builder';
    private const TAG_RESPONSE_HANDLER = 'response_handler';
    private const TAG_RESPONSE_VALIDATOR = 'response_validator';

    /**
     * Result array keys
     */
    private const KEY_HTTP_CODES = 'http_codes';

    /**
     * Attribute lists for generic attribute parsing
     */
    private const AUTHENTICATION_ATTRIBUTES = [
        self::ATTR_TYPE,
        self::ATTR_TOKEN_ENDPOINT,
        self::ATTR_TOKEN_TTL,
    ];

    private const RETRY_ATTRIBUTES = [
        self::ATTR_ENABLED,
        self::ATTR_MAX_ATTEMPTS,
        self::ATTR_BACKOFF_MULTIPLIER,
    ];

    private const CACHE_ATTRIBUTES = [
        self::ATTR_ENABLED,
        self::ATTR_TTL,
        self::ATTR_KEY_PREFIX,
    ];

    /**
     * Boolean token matching
     */
    private const BOOL_TRUE_VALUES = ['true', '1', 'yes'];
    private const BOOL_LIKE_VALUES = ['true', 'false', 'yes', 'no'];

    public function convert($source): array
    {
        /** @var DOMDocument $source */
        $config = [self::KEY_APIS => []];

        foreach ($source->documentElement->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->tagName !== self::TAG_API) {
                continue;
            }

            $config[self::KEY_APIS][$child->getAttribute(self::ATTR_ID)] = $this->parseApi($child);
        }

        return $config;
    }

    private function parseApi(DOMElement $node): array
    {
        return [
            self::ATTR_ENABLED => $this->getOptionalBoolAttribute($node, self::ATTR_ENABLED),
            self::TITLE => $this->getDirectChildText($node, self::TITLE),
            self::TAG_BASE_URL => $this->getDirectChildText($node, self::TAG_BASE_URL),
            self::TAG_TIMEOUT => $this->getDirectChildText($node, self::TAG_TIMEOUT) !== ''
                ? (int) $this->getDirectChildText($node, self::TAG_TIMEOUT)
                : null,

            self::TAG_AUTHENTICATION => $this->parseAuthentication($node),
            self::TAG_RETRY => $this->parseRetry($node),
            self::TAG_CACHE => $this->parseCache($node),

            self::TAG_ENDPOINTS => $this->parseEndpoints($node),
            self::TAG_WEBSITES => $this->parseWebsites($node),
        ];
    }

    private function parseWebsite(DOMElement $node): array
    {
        return [
            self::TAG_BASE_URL => $this->getDirectChildText($node, self::TAG_BASE_URL) ?: null,

            self::TAG_TIMEOUT => $this->getDirectChildText($node, self::TAG_TIMEOUT) !== ''
                ? (int) $this->getDirectChildText($node, self::TAG_TIMEOUT)
                : null,

            self::TAG_AUTHENTICATION => $this->parseAuthentication($node),
            self::TAG_RETRY => $this->parseRetry($node),
            self::TAG_CACHE => $this->parseCache($node),

            self::TAG_ENDPOINTS => $this->parseEndpoints($node),
        ];
    }

    private function parseEndpoints(DOMElement $parent): array
    {
        $container = $this->getDirectChild($parent, self::TAG_ENDPOINTS);

        if (!$container) {
            return [];
        }

        $endpoints = [];

        foreach ($container->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->tagName !== self::TAG_ENDPOINT) {
                continue;
            }

            $endpointId = $child->getAttribute(self::ATTR_ID);

            $endpoints[$endpointId] = $this->parseEndpoint($child);
        }

        return $endpoints;
    }

    private function parseEndpoint(DOMElement $node): array
    {
        return [
            self::ATTR_METHOD => $node->getAttribute(self::ATTR_METHOD) ?: null,
            self::ATTR_PATH => $node->getAttribute(self::ATTR_PATH) ?: null,
            self::ATTR_GUEST => $this->getOptionalBoolAttribute($node, self::ATTR_GUEST) ?? false,
            self::ATTR_ENABLED => $this->getOptionalBoolAttribute($node, self::ATTR_ENABLED),
            self::ATTR_TIMEOUT => $this->getOptionalIntAttribute($node, self::ATTR_TIMEOUT),

            self::TAG_CACHE => $this->parseCache($node),
            self::TAG_RETRY => $this->parseRetry($node),

            self::TAG_REQUEST_BUILDER =>
            $this->getDirectChildText($node, self::TAG_REQUEST_BUILDER) ?: null,

            self::TAG_RESPONSE_HANDLER =>
            $this->getDirectChildText($node, self::TAG_RESPONSE_HANDLER) ?: null,

            self::TAG_RESPONSE_VALIDATOR =>
            $this->getDirectChildText($node, self::TAG_RESPONSE_VALIDATOR) ?: null,
        ];
    }

    private function parseAuthentication(DOMElement $parent): array
    {
        $node = $this->getDirectChild($parent, self::TAG_AUTHENTICATION);

        if (!$node) {
            return [];
        }

        return $this->parseAttributes(
            $node,
            self::AUTHENTICATION_ATTRIBUTES
        );
    }

    private function parseRetry(DOMElement $parent): array
    {
        $node = $this->getDirectChild($parent, self::TAG_RETRY);

        if (!$node) {
            return [];
        }

        $retry = $this->parseAttributes(
            $node,
            self::RETRY_ATTRIBUTES
        );

        $retry[self::KEY_HTTP_CODES] = [];

        foreach ($node->childNodes as $child) {
            if (
                $child instanceof DOMElement
                && $child->tagName === self::TAG_HTTP_CODE
            ) {
                $retry[self::KEY_HTTP_CODES][] = (int) trim($child->textContent);
            }
        }

        return $retry;
    }

    private function parseCache(DOMElement $parent): array
    {
        $node = $this->getDirectChild($parent, self::TAG_CACHE);

        if (!$node) {
            return [];
        }

        return $this->parseAttributes(
            $node,
            self::CACHE_ATTRIBUTES
        );
    }

    private function parseWebsites(DOMElement $parent): array
    {
        $container = $this->getDirectChild($parent, self::TAG_WEBSITES);

        if (!$container) {
            return [];
        }

        $websites = [];

        foreach ($container->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->tagName !== self::TAG_WEBSITE) {
                continue;
            }

            $websiteCode = $child->getAttribute(self::ATTR_CODE);

            $websites[$websiteCode] = $this->parseWebsite($child);
        }

        return $websites;
    }

    private function parseAttributes(
        DOMElement $node,
        array $attributes
    ): array {
        $result = [];

        foreach ($attributes as $attribute) {
            $result[$attribute] = $node->hasAttribute($attribute)
                ? $this->castScalarValue($node->getAttribute($attribute))
                : null;
        }

        return $result;
    }

    private function getDirectChild(
        DOMElement $parent,
        string $tagName
    ): ?DOMElement {
        foreach ($parent->childNodes as $child) {
            if (
                $child instanceof DOMElement
                && $child->tagName === $tagName
            ) {
                return $child;
            }
        }

        return null;
    }

    private function getDirectChildText(
        DOMElement $parent,
        string $tagName
    ): string {
        $node = $this->getDirectChild($parent, $tagName);

        return $node
            ? trim($node->textContent)
            : '';
    }

    private function getOptionalBoolAttribute(
        DOMElement $node,
        string $attribute
    ): ?bool {
        return $node->hasAttribute($attribute)
            ? $this->parseBool($node->getAttribute($attribute))
            : null;
    }

    private function getOptionalIntAttribute(
        DOMElement $node,
        string $attribute
    ): ?int {
        return $node->hasAttribute($attribute)
            ? (int) $node->getAttribute($attribute)
            : null;
    }

    private function parseBool(string $value): bool
    {
        return in_array(
            strtolower(trim($value)),
            self::BOOL_TRUE_VALUES,
            true
        );
    }

    private function castScalarValue(
        string $value
    ): bool|int|float|string {
        $value = trim($value);
        $lower = strtolower($value);

        if (in_array(
            $lower,
            self::BOOL_LIKE_VALUES,
            true
        )) {
            return $this->parseBool($value);
        }

        if (is_numeric($value)) {
            return str_contains($value, '.')
                ? (float) $value
                : (int) $value;
        }

        return $value;
    }
}
