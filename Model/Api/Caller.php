<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Api;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Caller
{
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Perform the HTTP request and return response data.
     *
     * @param string $method
     * @param string $url
     * @param array<int|string, string> $headers
     * @param array|object|string|null $body
     * @param int $timeout
     * @return array{status:int,body:string,headers:array}
     */
    public function call(
        string $method,
        string $url,
        array $headers = [],
        array|object|string|null $body = null,
        int $timeout = 30
    ): array {
        $method = strtoupper($method);

        try {
            if ($timeout > 0) {
                $this->curl->setOption(CURLOPT_TIMEOUT, $timeout);
            }

            foreach ($headers as $name => $value) {
                $this->curl->addHeader((string)$name, (string)$value);
            }

            switch ($method) {
                case 'POST':
                    $this->curl->post($url, $body ?? []);
                    break;
                case 'GET':
                    $this->curl->get($url);
                    break;
                default:
                    $this->curl->setOption(CURLOPT_CUSTOMREQUEST, $method);
                    $this->curl->post($url, $body ?? []);
            }

            return [
                'status' => $this->curl->getStatus(),
                'body' => $this->curl->getBody(),
                'headers' => [],
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('API call failed: ' . $e->getMessage());

            return [
                'status' => 0,
                'body' => '',
                'headers' => [],
            ];
        }
    }
}
