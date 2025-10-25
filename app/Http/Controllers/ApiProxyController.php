<?php

namespace App\Http\Controllers;

use App\Services\CassandraApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ApiProxyController extends Controller
{
    public function handle(Request $request, CassandraApiService $api, string $path = '') 
    {
        $token = $this->extractToken($request);

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return response()->noContent(204)->withHeaders($this->corsHeaders($request));
        }

        $options = [
            'headers' => $this->forwardHeaders($request),
            'query' => $request->query(),
        ];

        $method = strtoupper($request->getMethod());
        if (!in_array($method, ['GET', 'HEAD'])) {
            $payload = $request->all();
            if (!empty($payload)) {
                $options['json'] = $payload;
            } elseif ($request->getContent()) {
                $options['body'] = $request->getContent();
            }
        }

        $apiPath = ltrim($path ?: '/', '/');
        if ($apiPath !== '' && !str_starts_with($apiPath, 'api/')) {
            $apiPath = 'api/' . $apiPath;
        }

        $response = $api->request($method, $apiPath ?: '/', $options, $token);

        $contentType = Arr::get($response['headers'], 'Content-Type.0', 'application/json');

        return response($response['body'], $response['status'])
            ->withHeaders($this->corsHeaders($request) + [
                'Content-Type' => $contentType,
            ]);
    }

    protected function extractToken(Request $request): ?string
    {
        $auth = $request->header('Authorization');
        if ($auth && preg_match('/^Bearer\\s+(.*)$/i', $auth, $matches)) {
            return trim($matches[1]);
        }
        $alt = $request->header('X-Session-Token');
        return $alt ? trim($alt) : null;
    }

    protected function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach (['Accept', 'Content-Type'] as $key) {
            $value = $request->header($key);
            if ($value) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    protected function corsHeaders(Request $request): array
    {
        $origin = $request->headers->get('Origin', $request->getSchemeAndHttpHost());

        return [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, Accept',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        ];
    }
}
