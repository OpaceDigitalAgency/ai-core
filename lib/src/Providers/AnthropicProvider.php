<?php
/**
 * AI-Core Library - Anthropic Provider
 *
 * Supports Claude models with dynamic model metadata and parameter mapping.
 *
 * @package AI_Core
 * @version 2.0.0
 */

namespace AICore\Providers;

use AICore\Interfaces\ProviderInterface;
use AICore\Http\HttpClient;
use AICore\Response\ResponseNormalizer;
use AICore\Registry\ModelRegistry;

class AnthropicProvider implements ProviderInterface {
    private const MESSAGES_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const MODELS_ENDPOINT   = 'https://api.anthropic.com/v1/models';
    private const API_VERSION       = '2023-06-01';

    private $api_key;

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    public function sendRequest(array $messages, array $options = []): array {
        if (!$this->isConfigured()) {
            throw new \Exception('Anthropic provider not configured: missing API key');
        }

        $model = $options['model'] ?? ModelRegistry::getPreferredModel('anthropic');
        if (!$model) {
            throw new \Exception('No Claude model available.');
        }

        $payload = [
            'model' => $model,
            'messages' => $this->convertMessages($messages),
        ];

        $parameters = $this->buildParameterPayload($model, $options);
        $payload = array_merge($payload, $parameters);

        // Support system prompt if present.
        $system = $this->extractSystemMessage($messages);
        if ($system !== null && $system !== '') {
            $payload['system'] = $system;
        }

        $headers = $this->buildHeaders();

        try {
            $response = HttpClient::post(self::MESSAGES_ENDPOINT, $payload, $headers);
            return ResponseNormalizer::normalize($response, 'anthropic');
        } catch (\Exception $e) {
            throw new \Exception('Anthropic API request failed: ' . $e->getMessage());
        }
    }

    private function buildHeaders(): array {
        return [
            'x-api-key' => $this->api_key,
            'anthropic-version' => self::API_VERSION,
            'Content-Type' => 'application/json',
        ];
    }

    private function convertMessages(array $messages): array {
        $claudeMessages = [];
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            if ($role === 'system') {
                // handled separately
                continue;
            }
            $claudeMessages[] = [
                'role' => $role,
                'content' => is_array($message['content'] ?? null) ? $message['content'] : (string) ($message['content'] ?? ''),
            ];
        }
        return $claudeMessages;
    }

    private function extractSystemMessage(array $messages): ?string {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                return is_array($message['content'] ?? null)
                    ? implode("\n", $message['content'])
                    : (string) ($message['content'] ?? '');
            }
        }
        return null;
    }

    private function buildParameterPayload(string $model, array $options): array {
        $schema = ModelRegistry::getParameterSchema($model);
        $payload = [];

        foreach ($schema as $key => $meta) {
            $value = $options[$key] ?? ($meta['default'] ?? null);
            if ($value === null || $value === '') {
                continue;
            }
            $value = $this->coerceParameterValue($value, $meta);
            $requestKey = $meta['request_key'] ?? $key;
            $this->setNestedValue($payload, $requestKey, $value);
        }

        // Anthropic API requires max_tokens - ensure it's always present
        if (!isset($payload['max_tokens'])) {
            $payload['max_tokens'] = 4096; // Safe default
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

    private function setNestedValue(array &$payload, string $requestKey, $value): void {
        $segments = explode('.', $requestKey);
        $cursor =& $payload;
        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }
            if ($index === count($segments) - 1) {
                $cursor[$segment] = $value;
                return;
            }
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor =& $cursor[$segment];
        }
    }

    public function getName(): string {
        return 'anthropic';
    }

    public function supportsModel(string $model): bool {
        $config = ModelRegistry::getModelConfig($model);
        return ($config['provider'] ?? null) === 'anthropic';
    }

    public function getAvailableModels(): array {
        $apiModels = [];

        if ($this->isConfigured()) {
            try {
                $response = HttpClient::get(self::MODELS_ENDPOINT, [], $this->buildHeaders());
                if (!empty($response['data']) && is_array($response['data'])) {
                    foreach ($response['data'] as $entry) {
                        $identifier = $entry['id'] ?? '';
                        if (!$identifier) {
                            continue;
                        }

                        $canonicalId = ModelRegistry::resolveModelId($identifier);

                        if (!ModelRegistry::modelExists($canonicalId)) {
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'anthropic',
                            ]);
                        }

                        if ($this->supportsModel($canonicalId)) {
                            $apiModels[] = $canonicalId;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Gracefully fall back.
            }
        }

        $sorted = ModelRegistry::getModelsByProvider('anthropic');
        if (!empty($apiModels)) {
            $apiSet = array_flip($apiModels);
            $models = [];
            foreach ($sorted as $id) {
                if (isset($apiSet[$id])) {
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

    public function isConfigured(): bool {
        return !empty($this->api_key) && strlen($this->api_key) > 10;
    }

    public function testConnection(): bool {
        try {
            $messages = [['role' => 'user', 'content' => 'Hello']];
            $this->sendRequest($messages, ['model' => ModelRegistry::getPreferredModel('anthropic') ?? 'claude-sonnet-4-20250514']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateApiKey(): array {
        if (!$this->isConfigured()) {
            return [
                'valid' => false,
                'error' => 'API key is empty',
            ];
        }

        try {
            $messages = [['role' => 'user', 'content' => 'Hello']];
            $model = ModelRegistry::getPreferredModel('anthropic') ?? 'claude-sonnet-4-20250514';
            $this->sendRequest($messages, ['model' => $model]);

            return [
                'valid' => true,
                'provider' => 'anthropic',
                'model' => $model,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getMaskedApiKey(): string {
        if (empty($this->api_key)) {
            return 'Not configured';
        }

        return substr($this->api_key, 0, 6) . '...' . substr($this->api_key, -4);
    }
}
