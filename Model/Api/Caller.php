<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Api;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;

class Caller
{
    private const SUPPORTED_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS',
    ];

    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Perform an HTTP request.
     *
     * @param array<string,string> $headers
     * @param array<string,mixed> $body
     *
     * @return array{
     *     status:int,
     *     body:string,
     *     headers:array<string,mixed>,
     *     transportError:bool,
     *     error:string|null
     * }
     */
    public function call(
        string $method,
        string $url,
        array $headers = [],
        array $body = [],
        int $timeout = 30
    ): array {
        /** @var Curl $curl */
        $curl = $this->curlFactory->create();

        $method = strtoupper($method);

        if (!in_array($method, self::SUPPORTED_METHODS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Unsupported HTTP method "%s".', $method)
            );
        }

        try {
            $curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $curl->setOption(CURLOPT_TIMEOUT, $timeout);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, min(5, $timeout));
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);

            foreach ($headers as $name => $value) {
                $curl->addHeader($name, $value);
            }

            switch ($method) {
                case 'GET':
                    $curl->get($url);
                    break;

                case 'POST':
                    $curl->post($url, $body);
                    break;

                case 'PUT':
                    $curl->put($url, $body);
                    break;

                case 'DELETE':
                    $curl->delete($url);
                    break;

                case 'PATCH':
                    $curl->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
                    $curl->setOption(CURLOPT_POSTFIELDS, http_build_query($body));
                    $curl->get($url);
                    break;

                case 'HEAD':
                    $curl->setOption(CURLOPT_NOBODY, true);
                    $curl->setOption(CURLOPT_CUSTOMREQUEST, 'HEAD');
                    $curl->get($url);
                    break;

                case 'OPTIONS':
                    $curl->setOption(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                    $curl->get($url);
                    break;
            }

            $rawBody = $curl->getBody();
            $body = [];
            if (json_validate($rawBody)) {
                $body = json_decode($rawBody);  // TODO:: follow magento standard
            }

            return [
                'status' => $curl->getStatus(),
                'body' => $body,
                'headers' => method_exists($curl, 'getHeaders')
                    ? $curl->getHeaders()
                    : [],
                'transportError' => false,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $this->logger->error(
                'API request failed.',
                [
                    'method' => $method,
                    'url' => $url,
                    'exception' => $e,
                ]
            );

            return [
                'status' => 0,
                'body' => '',
                'headers' => [],
                'transportError' => true,
                'error' => $e->getMessage(),
            ];
        }
    }
}
