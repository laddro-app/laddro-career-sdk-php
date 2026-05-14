<?php

namespace Laddro\Career;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class Laddro
{
    private Client $http;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey = '', string $baseUrl = 'https://api.laddro.com')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->http = new Client(['base_uri' => $this->baseUrl, 'timeout' => 120]);
    }

    public function listTemplates(): array
    {
        return $this->get('/v1/templates')['templates'];
    }

    public function getTemplate(string $templateId): array
    {
        return $this->get("/v1/templates/{$templateId}");
    }

    public function listFonts(): array
    {
        return $this->get('/v1/fonts')['fonts'];
    }

    public function listLanguages(): array
    {
        return $this->get('/v1/languages')['languages'];
    }

    public function listModels(): array
    {
        return $this->get('/v1/models')['models'];
    }

    public function listResumes(int $limit = 20, int $offset = 0): array
    {
        return $this->get("/v1/resumes?limit={$limit}&offset={$offset}");
    }

    public function getResume(string $resumeId): array
    {
        return $this->get("/v1/resumes/{$resumeId}");
    }

    public function renderResume(string $resumeId, array $options): string
    {
        return $this->putBinary("/v1/resumes/{$resumeId}/render", $options);
    }

    public function tailor(array $request): string
    {
        return $this->postBinary('/v1/tailor', $request);
    }

    public function tailorDetailed(array $request): array
    {
        return $this->postBinaryDetailed('/v1/tailor', $request);
    }

    public function exportPdf(array $request): string
    {
        return $this->postBinary('/v1/export', $request);
    }

    public function listCoverLetters(int $limit = 20, int $offset = 0): array
    {
        return $this->get("/v1/cover-letters?limit={$limit}&offset={$offset}");
    }

    public function getCoverLetter(string $id): array
    {
        return $this->get("/v1/cover-letters/{$id}");
    }

    public function createCoverLetter(array $request): array
    {
        return $this->post('/v1/cover-letters', $request);
    }

    public function generateCoverLetter(array $request): string
    {
        return $this->postBinary('/v1/cover-letters/generate', $request);
    }

    public function generateCoverLetterDetailed(array $request): array
    {
        return $this->postBinaryDetailed('/v1/cover-letters/generate', $request);
    }

    public function renderCoverLetter(string $id, array $options): string
    {
        return $this->putBinary("/v1/cover-letters/{$id}/render", $options);
    }

    public function getSettings(): array
    {
        return $this->get('/v1/settings');
    }

    public function updateAiSettings(array $request): array
    {
        return $this->put('/v1/settings/model', $request);
    }

    public function deleteAiSettings(): array
    {
        return $this->delete('/v1/settings/model');
    }

    private function headers(): array
    {
        $headers = [];
        if ($this->apiKey !== '') {
            $headers['x-api-key'] = $this->apiKey;
        }
        return $headers;
    }

    private function get(string $path): array
    {
        try {
            $response = $this->http->get($path, ['headers' => $this->headers()]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw $this->handleError($e);
        }
    }

    private function post(string $path, array $body): array
    {
        try {
            $response = $this->http->post($path, [
                'headers' => $this->headers(),
                'json' => $body,
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw $this->handleError($e);
        }
    }

    private function postBinary(string $path, array $body): string
    {
        return $this->postBinaryDetailed($path, $body)['data'];
    }

    private function postBinaryDetailed(string $path, array $body): array
    {
        try {
            $response = $this->http->post($path, [
                'headers' => $this->headers(),
                'json' => $body,
            ]);
            return [
                'data' => $response->getBody()->getContents(),
                'metadata' => $this->artifactMetadata($response),
            ];
        } catch (ClientException $e) {
            throw $this->handleError($e);
        }
    }

    private function put(string $path, array $body): array
    {
        try {
            $response = $this->http->put($path, [
                'headers' => $this->headers(),
                'json' => $body,
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw $this->handleError($e);
        }
    }

    private function putBinary(string $path, array $body): string
    {
        try {
            $response = $this->http->put($path, [
                'headers' => $this->headers(),
                'json' => $body,
            ]);
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            throw $this->handleError($e);
        }
    }

    private function delete(string $path): array
    {
        try {
            $response = $this->http->delete($path, ['headers' => $this->headers()]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw $this->handleError($e);
        }
    }

    private function handleError(ClientException $e): LaddroException
    {
        $response = $e->getResponse();
        $status = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true) ?? [];
        $message = $body['error'] ?? $e->getMessage();
        $code = $body['code'] ?? null;
        return new LaddroException($message, $status, $code);
    }

    private function artifactMetadata(ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('content-type');
        return [
            'resumeId' => $response->getHeaderLine('x-resume-id') ?: null,
            'coverLetterId' => $response->getHeaderLine('x-cover-letter-id') ?: null,
            'filename' => $this->contentDispositionFilename($response->getHeaderLine('content-disposition')),
            'mimeType' => $contentType !== '' ? explode(';', $contentType)[0] : null,
        ];
    }

    private function contentDispositionFilename(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        foreach (explode(';', $value) as $part) {
            $part = trim($part);
            if (stripos($part, 'filename') !== 0 || strpos($part, '=') === false) {
                continue;
            }
            [, $filename] = explode('=', $part, 2);
            $filename = trim($filename, '"');
            $filename = preg_replace("/^UTF-8''/", '', $filename);
            return rawurldecode($filename);
        }
        return null;
    }
}
