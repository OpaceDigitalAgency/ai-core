<?php
/**
 * AI-Core Library - OpenAI Provider
 *
 * Handles communication with the OpenAI API, dynamically adapting to model
 * capabilities (Responses API, Chat Completions, reasoning models, etc.).
 *
 * @package AI_Core
 * @version 0.7.3
 */

namespace AICore\Providers;

use AICore\Interfaces\ProviderInterface;
use AICore\Http\HttpClient;
use AICore\Response\ResponseNormalizer;
use AICore\Registry\ModelRegistry;

class OpenAIProvider implements ProviderInterface {
    private const CHAT_COMPLETIONS_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const RESPONSES_ENDPOINT       = 'https://api.openai.com/v1/responses';
    private const MODELS_ENDPOINT          = 'https://api.openai.com/v1/models';

    /**
     * API key for authentication
     */
    private $api_key;

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(array $messages, array $options = []): array {
        if (!$this->isConfigured()) {
            throw new \Exception('OpenAI provider not configured: missing API key');
        }

        $model = $options['model'] ?? ModelRegistry::getPreferredModel('openai');
        if (!$model) {
            throw new \Exception('No OpenAI model configured.');
        }

        $endpoint = ModelRegistry::getEndpoint($model);
        $parameterValues = $this->buildParameterPayload($model, $options);

        switch ($endpoint) {
            case 'responses':
                return $this->sendResponsesRequest($messages, $model, $parameterValues);
            case 'embeddings':
                throw new \Exception('Embedding models must be invoked via embedding helper methods.');
            case 'chat':
            default:
                return $this->sendChatRequest($messages, $model, $parameterValues, $options);
        }
    }

    /**
     * Run the Chat Completions API.
     */
    private function sendChatRequest(array $messages, string $model, array $parameters, array $options = []): array {
        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
        ], $parameters);

        // Preserve legacy optional keys if provided explicitly.
        foreach (['stream', 'stop', 'functions', 'frequency_penalty', 'presence_penalty'] as $key) {
            if (array_key_exists($key, $options)) {
                $payload[$key] = $options[$key];
            }
        }

        $headers = $this->buildHeaders();

        try {
            $response = HttpClient::post(self::CHAT_COMPLETIONS_ENDPOINT, $payload, $headers);
            return ResponseNormalizer::normalize($response, 'openai');
        } catch (\Exception $e) {
            throw new \Exception('OpenAI chat API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Run the Responses API for modern and reasoning models.
     */
    private function sendResponsesRequest(array $messages, string $model, array $parameters): array {
        $input = $this->convertMessagesToInput($messages);

        $payload = array_merge([
            'model' => $model,
            'input' => $input,
        ], $parameters);

        // Responses API uses 'text' object with 'format' structure
        // Only set if not already specified by caller
        if (!isset($payload['text']) || !isset($payload['text']['format'])) {
            if (!isset($payload['text'])) {
                $payload['text'] = [];
            }
            if (!isset($payload['text']['format'])) {
                // Responses API expects format as object: {'type': 'text'}
                $payload['text']['format'] = ['type' => 'text'];
            }
        }

        $headers = $this->buildHeaders();

        try {
            $response = HttpClient::post(self::RESPONSES_ENDPOINT, $payload, $headers);
            return ResponseNormalizer::normalize($response, 'openai');
        } catch (\Exception $e) {
            throw new \Exception('OpenAI responses API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Convert chat-style messages into the Responses API content array.
     */
    private function convertMessagesToInput(array $messages): array {
        $input = [];
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';

            if (is_array($content)) {
                $parts = [];
                foreach ($content as $part) {
                    if (is_string($part)) {
                        $parts[] = ['type' => $role === 'assistant' ? 'output_text' : 'input_text', 'text' => $part];
                    } elseif (is_array($part)) {
                        $parts[] = $part;
                    }
                }
            } else {
                $parts = [[
                    'type' => $role === 'assistant' ? 'output_text' : 'input_text',
                    'text' => (string) $content,
                ]];
            }

            $input[] = [
                'role' => $role,
                'content' => $parts,
            ];
        }

        return $input;
    }

    /**
     * Build parameter payload based on model metadata.
     */
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

        return $payload;
    }

    private function coerceParameterValue($value, array $meta) {
        switch ($meta['type'] ?? '') {
            case 'number':
                if (isset($meta['step']) && $meta['step'] < 1) {
                    return (float) $value;
                }
                return (int) $value;
            case 'select':
                return $value;
            default:
                return $value;
        }
    }

    private function setNestedValue(array &$payload, string $requestKey, $value): void {
        $segments = explode('.', $requestKey);
        $cursor =& $payload;
        $lastIndex = count($segments) - 1;
        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }
            if ($index === $lastIndex) {
                $cursor[$segment] = $value;
                return;
            }
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor =& $cursor[$segment];
        }
    }

    private function buildHeaders(): array {
        return [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        ];
    }

    public function getName(): string {
        return 'openai';
    }

    public function supportsModel(string $model): bool {
        $config = ModelRegistry::getModelConfig($model);
        if (!$config) {
            return false;
        }
        if ($config['category'] === 'embedding' || $config['category'] === 'image') {
            return false;
        }
        return true;
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
                        $category = $this->inferCategoryFromId($canonicalId);

                        if (!ModelRegistry::modelExists($canonicalId)) {
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'openai',
                                'category' => $category,
                            ]);
                        }

                        if ($category === 'text' || $category === 'reasoning') {
                            $apiModels[] = $canonicalId;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Network or permission issue; rely on cached definitions.
            }
        }

        $sorted = ModelRegistry::getModelsByProvider('openai');

        if (!empty($apiModels)) {
            $apiSet = array_flip($apiModels);
            $models = [];
            foreach ($sorted as $id) {
                if (isset($apiSet[$id])) {
                    $models[] = $id;
                }
            }
            // Include any new API ids we don't yet understand (append at end).
            foreach ($apiModels as $id) {
                if (!in_array($id, $models, true)) {
                    $models[] = $id;
                }
            }
            return $models;
        }

        return $sorted;
    }

    private function inferCategoryFromId(string $identifier): string {
        if (strpos($identifier, 'embedding') !== false) {
            return 'embedding';
        }
        if (strpos($identifier, 'audio') !== false || strpos($identifier, 'tts') !== false || strpos($identifier, 'whisper') !== false) {
            return 'audio';
        }
        if (strpos($identifier, 'image') !== false) {
            return 'image';
        }
        if (strpos($identifier, 'o3') === 0 || strpos($identifier, 'o4') === 0) {
            return 'reasoning';
        }
        return 'text';
    }

    public function isConfigured(): bool {
        return !empty($this->api_key) && strlen($this->api_key) > 10;
    }

    public function testConnection(): bool {
        try {
            $testMessages = [
                ['role' => 'user', 'content' => 'ping'],
            ];
            $this->sendRequest($testMessages, ['model' => ModelRegistry::getPreferredModel('openai') ?? 'gpt-4o']);
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
            $model = ModelRegistry::getPreferredModel('openai') ?? 'gpt-4o';
            $this->sendRequest($messages, ['model' => $model]);

            return [
                'valid' => true,
                'provider' => 'openai',
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
