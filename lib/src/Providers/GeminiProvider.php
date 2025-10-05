<?php
/**
 * AI-Core Library - Google Gemini Provider
 *
 * Provides dynamic model discovery and parameter-aware requests for Gemini.
 *
 * @package AI_Core
 * @version 2.0.0
 */

namespace AICore\Providers;

use AICore\Interfaces\ProviderInterface;
use AICore\Http\HttpClient;
use AICore\Response\ResponseNormalizer;
use AICore\Registry\ModelRegistry;

class GeminiProvider implements ProviderInterface {
    private const MODELS_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models';
    private const GENERATE_SUFFIX = ':generateContent';

    private $api_key;

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    public function sendRequest(array $messages, array $options = []): array {
        if (!$this->isConfigured()) {
            throw new \Exception('Gemini provider not configured: missing API key');
        }

        $model = $options['model'] ?? ModelRegistry::getPreferredModel('gemini');
        if (!$model) {
            throw new \Exception('No Gemini model available.');
        }

        $endpoint = $this->buildEndpoint($model);
        $payload = $this->buildPayload($messages, $model, $options);

        try {
            $response = HttpClient::post($endpoint, $payload, ['Content-Type' => 'application/json']);
            return ResponseNormalizer::normalize($response, 'gemini');
        } catch (\Exception $e) {
            throw new \Exception('Gemini API request failed: ' . $e->getMessage());
        }
    }

    private function buildEndpoint(string $model): string {
        return sprintf('%s/%s%s?key=%s', self::MODELS_ENDPOINT, $model, self::GENERATE_SUFFIX, rawurlencode($this->api_key));
    }

    private function buildPayload(array $messages, string $model, array $options): array {
        $contents = $this->convertMessages($messages);
        $schema   = ModelRegistry::getParameterSchema($model);
        $generationConfig = [];

        foreach ($schema as $key => $meta) {
            $value = $options[$key] ?? ($meta['default'] ?? null);
            if ($value === null || $value === '') {
                continue;
            }

            $value = $this->coerceParameterValue($value, $meta);
            $requestKey = $meta['request_key'] ?? $key;
            if (strpos($requestKey, 'generationConfig.') === 0) {
                $subKey = substr($requestKey, strlen('generationConfig.'));
                $generationConfig[$subKey] = $value;
            }
        }

        $payload = [
            'contents' => $contents,
        ];

        if (!empty($generationConfig)) {
            $payload['generationConfig'] = $generationConfig;
        }

        $system = $this->extractSystemInstruction($messages);
        if ($system) {
            $payload['systemInstruction'] = [
                'parts' => [ ['text' => $system] ],
            ];
        }

        return $payload;
    }

    private function coerceParameterValue($value, array $meta) {
        if (($meta['type'] ?? '') === 'number') {
            if (isset($meta['step']) && $meta['step'] < 1) {
                return (float) $value;
            }
            return (int) $value;
        }
        return $value;
    }

    private function convertMessages(array $messages): array {
        $contents = [];
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            $geminiRole = $role === 'assistant' ? 'model' : 'user';

            $parts = [];
            if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_string($part)) {
                        $parts[] = ['text' => $part];
                    } elseif (is_array($part)) {
                        $parts[] = $part;
                    }
                }
            } else {
                $parts[] = ['text' => (string) $content];
            }

            if ($geminiRole === 'system') {
                continue;
            }

            $contents[] = [
                'role' => $geminiRole,
                'parts' => $parts,
            ];
        }

        return $contents;
    }

    private function extractSystemInstruction(array $messages): ?string {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $content = $message['content'] ?? '';
                if (is_array($content)) {
                    return implode("\n", array_map('strval', $content));
                }
                return (string) $content;
            }
        }
        return null;
    }

    public function isConfigured(): bool {
        return !empty($this->api_key);
    }

    public function getName(): string {
        return 'gemini';
    }

    public function validateApiKey(): array {
        if (!$this->isConfigured()) {
            return [
                'valid' => false,
                'error' => 'API key is empty',
            ];
        }

        try {
            // First, try to fetch available models to validate the API key
            $endpoint = self::MODELS_ENDPOINT . '?key=' . rawurlencode($this->api_key);
            $response = HttpClient::get($endpoint);
            
            // If we get here without exception, the API key is valid
            $model = ModelRegistry::getPreferredModel('gemini') ?? 'gemini-2.5-flash';
            
            return [
                'valid' => true,
                'provider' => 'gemini',
                'model' => $model,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getAvailableModels(): array {
        $apiModels = [];
        if ($this->isConfigured()) {
            try {
                $endpoint = self::MODELS_ENDPOINT . '?key=' . rawurlencode($this->api_key);
                $response = HttpClient::get($endpoint);
                if (!empty($response['models']) && is_array($response['models'])) {
                    foreach ($response['models'] as $model) {
                        $identifier = $model['name'] ?? '';
                        if (!$identifier) {
                            continue;
                        }
                        $normalized = $this->normalizeModelId($identifier);
                        $canonicalId = ModelRegistry::resolveModelId($normalized);
                        $category = $this->inferCategory($canonicalId);

                        if (!ModelRegistry::modelExists($canonicalId)) {
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'gemini',
                                'category' => $category,
                                'capabilities' => $category === 'image' ? ['image'] : ['text'],
                            ]);
                        }

                        // Include ALL models (both text and image)
                        $apiModels[] = $canonicalId;
                    }
                }
            } catch (\Exception $e) {
                // Ignore failure; fallback handled below.
            }
        }

        $sorted = ModelRegistry::getModelsByProvider('gemini');
        if (!empty($apiModels)) {
            $set = array_flip($apiModels);
            $models = [];
            foreach ($sorted as $id) {
                if (isset($set[$id])) {
                    $models[] = $id;
                }
            }
            foreach ($apiModels as $id) {
                if (!in_array($id, $models, true)) {
                    $models[] = $id;
                }
            }
            return $models;
        }

        return $sorted;
    }

    private function normalizeModelId(string $identifier): string {
        return strpos($identifier, 'models/') === 0
            ? substr($identifier, strlen('models/'))
            : $identifier;
    }

    private function inferCategory(string $identifier): string {
        if (strpos($identifier, 'image') !== false) {
            return 'image';
        }
        if (strpos($identifier, 'audio') !== false) {
            return 'audio';
        }
        return 'text';
    }

    public function supportsModel(string $model): bool {
        $models = $this->getAvailableModels();
        return in_array($model, $models, true);
    }
}
