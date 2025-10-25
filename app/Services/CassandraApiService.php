<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

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
            $httpOptions['json'] = Arr::get($options, 'json');
        } elseif (!empty($options['payload'])) {
            $httpOptions['json'] = Arr::get($options, 'payload');
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
}
