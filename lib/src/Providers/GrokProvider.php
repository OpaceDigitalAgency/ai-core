<?php
/**
 * AI-Core Library - xAI Grok Provider
 *
 * Supports xAI Grok models using OpenAI-compatible endpoints with dynamic
 * metadata and parameter mapping.
 *
 * @package AI_Core
 * @version 2.0.0
 */

namespace AICore\Providers;

use AICore\Interfaces\ProviderInterface;
use AICore\Http\HttpClient;
use AICore\Response\ResponseNormalizer;
use AICore\Registry\ModelRegistry;

class GrokProvider implements ProviderInterface {
    private const CHAT_ENDPOINT     = 'https://api.x.ai/v1/chat/completions';
    private const MODELS_ENDPOINT   = 'https://api.x.ai/v1/models';

    private $api_key;

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    public function sendRequest(array $messages, array $options = []): array {
        if (!$this->isConfigured()) {
            throw new \Exception('Grok provider not configured: missing API key');
        }

        $model = $options['model'] ?? ModelRegistry::getPreferredModel('grok');
        if (!$model) {
            throw new \Exception('No Grok model available.');
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        $schema = ModelRegistry::getParameterSchema($model);
        foreach ($schema as $key => $meta) {
            $value = $options[$key] ?? ($meta['default'] ?? null);
            if ($value === null || $value === '') {
                continue;
            }
            $value = $this->coerceParameterValue($value, $meta);
            $requestKey = $meta['request_key'] ?? $key;
            $payload[$requestKey] = $value;
        }

        foreach (['stream', 'stop'] as $optional) {
            if (isset($options[$optional])) {
                $payload[$optional] = $options[$optional];
            }
        }

        try {
            $response = HttpClient::post(self::CHAT_ENDPOINT, $payload, $this->buildHeaders());
            return ResponseNormalizer::normalize($response, 'openai');
        } catch (\Exception $e) {
            throw new \Exception('Grok API request failed: ' . $e->getMessage());
        }
    }

    private function buildHeaders(): array {
        return [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        ];
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

    private function inferCategory(string $identifier): string {
        if (strpos($identifier, 'image') !== false || strpos($identifier, 'vision') !== false) {
            return 'image';
        }
        if (strpos($identifier, 'reasoning') !== false) {
            return 'reasoning';
        }
        return 'text';
    }

    public function isConfigured(): bool {
        return !empty($this->api_key);
    }

    public function getName(): string {
        return 'grok';
    }

    public function supportsModel(string $model): bool {
        $canonical = ModelRegistry::resolveModelId($model) ?: $model;
        // Check registry for known Grok models
        $models = ModelRegistry::getModelsByProvider('grok');
        if (in_array($canonical, $models, true) || in_array($model, $models, true)) {
            return true;
        }
        // Heuristic fallback: Grok models typically start with "grok-"
        return strpos($canonical, 'grok-') === 0;
    }

    public function validateApiKey(): array {
        if (!$this->isConfigured()) {
            return [
                'valid' => false,
                'error' => 'API key is empty',
            ];
        }

        try {
            $model = ModelRegistry::getPreferredModel('grok') ?? 'grok-4-fast';
            $this->sendRequest([
                ['role' => 'user', 'content' => 'Hello'],
            ], [
                'model' => $model,
                'max_tokens' => 10,
            ]);

            return [
                'valid' => true,
                'provider' => 'grok',
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
                $response = HttpClient::get(self::MODELS_ENDPOINT, [], $this->buildHeaders());
                if (!empty($response['data']) && \is_array($response['data'])) {
                    foreach ($response['data'] as $model) {
                        $identifier = $model['id'] ?? '';
                        if (!$identifier) {
                            continue;
                        }
                        $canonicalId = ModelRegistry::resolveModelId($identifier);
                        $category = $this->inferCategory($canonicalId);
                        $displayName = $this->generateDisplayName($canonicalId);

                        // Dynamically register ANY model from the API
                        if (!ModelRegistry::modelExists($canonicalId)) {
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'grok',
                                'display_name' => $displayName,
                                'category' => $category,
                                'capabilities' => $this->inferCapabilities($canonicalId, $category),
                                'priority' => $this->inferPriority($canonicalId),
                            ]);
                        }

                        // Include text, reasoning, and image models
                        if (\in_array($category, ['text', 'reasoning', 'image'], true)) {
                            $apiModels[] = $canonicalId;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback handled below.
            }
        }

        $sorted = ModelRegistry::getModelsByProvider('grok');
        if (!empty($apiModels)) {
            $set = array_flip($apiModels);
            $models = [];
            foreach ($sorted as $id) {
                if (isset($set[$id])) {
                    $models[] = $id;
                }
            }
            foreach ($apiModels as $id) {
                if (!\in_array($id, $models, true)) {
                    $models[] = $id;
                }
            }
            return $models;
        }

        return $sorted;
    }

    /**
     * Generate a human-readable display name from model ID
     */
    private function generateDisplayName(string $modelId): string {
        // Parse Grok model naming: grok-{version}[-variant][-date]
        if (preg_match('/^grok-(\d+)(-.+)?$/', $modelId, $matches)) {
            $version = $matches[1];
            $suffix = $matches[2] ?? '';
            $suffix = preg_replace('/-\d{4}$/', '', $suffix); // Remove date suffix
            $suffix = str_replace(['-', '_'], ' ', $suffix);
            $suffix = ucwords(trim($suffix));
            return "Grok {$version}" . ($suffix ? " {$suffix}" : '');
        }

        // Default: capitalise and clean up
        $name = str_replace(['-', '_'], ' ', $modelId);
        return ucwords($name);
    }

    private function inferCapabilities(string $identifier, string $category): array {
        $caps = [$category];

        if ($category === 'text') {
            if (strpos($identifier, 'grok-4') !== false || strpos($identifier, 'grok-3') !== false) {
                $caps[] = 'tooluse';
            }
        }

        if ($category === 'reasoning') {
            $caps[] = 'text';
        }

        return array_unique($caps);
    }

    private function inferPriority(string $identifier): int {
        // Higher numbers = higher priority
        if (strpos($identifier, 'grok-4') !== false && strpos($identifier, 'fast') !== false) {
            return 95;
        }
        if (strpos($identifier, 'grok-4') !== false) {
            return 90;
        }
        if (strpos($identifier, 'grok-3') !== false) {
            return 80;
        }
        if (strpos($identifier, 'grok-2') !== false && strpos($identifier, 'image') !== false) {
            return 75;
        }
        if (strpos($identifier, 'grok-2') !== false) {
            return 70;
        }
        return 50;
    }
}
