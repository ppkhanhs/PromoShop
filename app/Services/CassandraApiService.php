<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CassandraApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            Config::get('services.cassandra_api.base_url', 'http://127.0.0.1:8001'),
            '/'
        );
    }

    /**
     * Gửi yêu cầu tới CassandraAPI với đầy đủ headers/token cần thiết.
     *
     * @param  string       $method
     * @param  string       $path
     * @param  array        $options
     * @param  string|null  $token
     * @return array{status:int,body:string,headers:array,json:mixed|null}
     */
    public function request(string $method, string $path, array $options = [], ?string $token = null): array
    {
        $url = $this->buildUrl($path);

        $headers = Arr::get($options, 'headers', []);
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $httpOptions = [];
        if ($query = Arr::get($options, 'query')) {
            $httpOptions['query'] = $query;
        }

        if (Arr::has($options, 'body')) {
            $httpOptions['body'] = Arr::get($options, 'body');
        } elseif (Arr::has($options, 'json')) {
            $httpOptions['body'] = $this->encodeJsonPayload(Arr::get($options, 'json'));
            $headers = $this->ensureJsonHeader($headers);
        } elseif (!empty($options['payload'])) {
            $httpOptions['body'] = $this->encodeJsonPayload(Arr::get($options, 'payload'));
            $headers = $this->ensureJsonHeader($headers);
        }

        if ($timeout = Arr::get($options, 'timeout')) {
            $httpOptions['timeout'] = $timeout;
        }

        /** @var Response $response */
        $response = Http::withHeaders($headers)->send(strtoupper($method), $url, $httpOptions);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
            'json' => $this->safeJson($response),
        ];
    }

    protected function buildUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        return sprintf('%s/%s', $this->baseUrl, $cleanPath);
    }

    protected function safeJson(Response $response): mixed
    {
        try {
            return $response->json();
        } catch (\Throwable $th) {
            return null;
        }
    }

    protected function encodeJsonPayload(mixed $payload): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        } elseif (defined('JSON_INVALID_UTF8_IGNORE')) {
            $flags |= JSON_INVALID_UTF8_IGNORE;
        }

        $encoded = json_encode($payload, $flags);
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode payload to JSON: ' . json_last_error_msg());
        }

        return $encoded;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    protected function ensureJsonHeader(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'content-type') {
                return $headers;
            }
        }

        $headers['Content-Type'] = 'application/json';

        return $headers;
    }
}
