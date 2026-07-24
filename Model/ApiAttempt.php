<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use MageStack\Integration\Api\Data\ApiAttemptInterface;
use MageStack\Integration\Model\ResourceModel\ApiAttempt as ApiAttemptResourceModel;
use Magento\Framework\Serialize\SerializerInterface;

class ApiAttempt extends AbstractModel implements ApiAttemptInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct(): void
    {
        $this->_init(ApiAttemptResourceModel::class);
    }

    public function getId(): ?int
    {
        $id = $this->getData(self::ATTEMPT_ID);
        return $id === null ? null : (int) $id;
    }

    public function getApiCode(): ?string
    {
        return $this->getData(self::API_CODE);
    }

    public function setApiCode(string $code): self
    {
        $this->setData(self::API_CODE, $code);
        return $this;
    }

    public function getEndpointCode(): ?string
    {
        return $this->getData(self::ENDPOINT_CODE);
    }

    public function setEndpointCode(string $code): self
    {
        $this->setData(self::ENDPOINT_CODE, $code);
        return $this;
    }

    public function getWebsiteCode(): ?string
    {
        return $this->getData(self::WEBSITE_CODE);
    }

    public function setWebsiteCode(string $code): self
    {
        $this->setData(self::WEBSITE_CODE, $code);
        return $this;
    }

    public function getInputData(): ?array
    {
        $inputData = $this->getData(self::DATA);
        if (is_string($inputData) && json_validate($inputData)) {
            $inputData = $this->serializer->unserialize($inputData);
        }

        if (is_array($inputData)) {
            return $inputData;
        }

        return [];
    }

    public function setInputData(array $data = []): self
    {
        $serialized = $this->serializer->serialize($data);
        $this->setData(self::DATA, $serialized);

        return $this;
    }

    public function getUrlParams(): ?array
    {
        $urlParams = $this->getData(self::URL_PARAMS);
        if (is_string($urlParams) && json_validate($urlParams)) {
            $urlParams = $this->serializer->unserialize($urlParams);
        }

        if (is_array($urlParams)) {
            return $urlParams;
        }

        return [];
    }

    public function setUrlParams(array $params = []): self
    {
        $serialized = $this->serializer->serialize($params);

        $this->setData(self::URL_PARAMS, $serialized);

        return $this;
    }

    public function getHeaders(): ?array
    {
        $headers = $this->getData(self::HEADERS);
        if (is_string($headers) && json_validate($headers)) {
            $headers = $this->serializer->unserialize($headers);
        }

        if (is_array($headers)) {
            return $headers;
        }

        return [];
    }

    public function setHeaders(array $headers = []): self
    {
        $serialized = $this->serializer->serialize($headers);

        $this->setData(self::HEADERS, $serialized);

        return $this;
    }

    public function getAttempt(): int
    {
        return (int) $this->getData(self::ATTEMPT) ?: 0;
    }

    public function setAttempt(int $attempt): self
    {
        $this->setData(self::ATTEMPT, $attempt);
        return $this;
    }

    public function getMaxAttempt(): int
    {
        return (int) $this->getData(self::MAX_ATTEMPT) ?: 0;
    }

    public function setMaxAttempt(int $maxAttempt): self
    {
        $this->setData(self::MAX_ATTEMPT, $maxAttempt);
        return $this;
    }

    public function getNextAttemptAt(): ?string
    {
        return $this->getData(self::NEXT_ATTEMPT_AT);
    }

    public function setNextAttemptAt(?string $date): self
    {
        $this->setData(self::NEXT_ATTEMPT_AT, $date);
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $date): self
    {
        $this->setData(self::CREATED_AT, $date);
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $date): self
    {
        $this->setData(self::UPDATED_AT, $date);
        return $this;
    }
}
