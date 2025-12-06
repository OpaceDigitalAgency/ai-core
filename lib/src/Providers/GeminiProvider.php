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

        // Handle system instruction - only for models that support it (gemini-3-pro-preview, gemini-2.5-pro, etc.)
        $system = $this->extractSystemInstruction($messages);
        if ($system) {
            // Check if model supports systemInstruction (gemini-3-pro, gemini-2.5-pro, gemini-2.0-flash do NOT support it in older API versions)
            // gemini-3-pro-preview and later models DO support it
            $supportsSystemInstruction = (
                strpos($model, 'gemini-3') !== false ||
                strpos($model, 'gemini-2.5') !== false ||
                strpos($model, 'gemini-2.0-flash') === false // 2.0-flash-exp doesn't support it
            );

            if ($supportsSystemInstruction) {
                $payload['systemInstruction'] = [
                    'parts' => [ ['text' => $system] ],
                ];
            } else {
                // Prepend system instruction to first user message for models that don't support it
                if (!empty($payload['contents']) && isset($payload['contents'][0]['parts'])) {
                    $systemPrefix = "SYSTEM INSTRUCTIONS:\n" . $system . "\n\nUSER REQUEST:\n";
                    $payload['contents'][0]['parts'][0]['text'] = $systemPrefix . ($payload['contents'][0]['parts'][0]['text'] ?? '');
                }
            }
        }

        // Handle tools option (e.g., Google Search grounding)
        if (!empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
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
                if (!empty($response['models']) && \is_array($response['models'])) {
                    foreach ($response['models'] as $model) {
                        $identifier = $model['name'] ?? '';
                        if (!$identifier) {
                            continue;
                        }
                        $normalized = $this->normalizeModelId($identifier);
                        $canonicalId = ModelRegistry::resolveModelId($normalized);
                        $category = $this->inferCategory($canonicalId);
                        $displayName = $this->generateDisplayName($canonicalId, $model);

                        // Dynamically register ANY model from the API
                        if (!ModelRegistry::modelExists($canonicalId)) {
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'gemini',
                                'display_name' => $displayName,
                                'category' => $category,
                                'capabilities' => $this->inferCapabilities($canonicalId, $category),
                                'priority' => $this->inferPriority($canonicalId),
                            ]);
                        }

                        // Include ALL models from API
                        $apiModels[] = $canonicalId;
                    }
                }
            } catch (\Exception $e) {
                // Ignore failure; fallback handled below.
            }
        }

        // Sort: prioritise models we know about, then append new API models
        $sorted = ModelRegistry::getModelsByProvider('gemini');
        if (!empty($apiModels)) {
            $set = array_flip($apiModels);
            $models = [];
            // First add known models that exist in API
            foreach ($sorted as $id) {
                if (isset($set[$id])) {
                    $models[] = $id;
                }
            }
            // Then add any new models from API we haven't seen before
            foreach ($apiModels as $id) {
                if (!\in_array($id, $models, true)) {
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

    /**
     * Generate a human-readable display name from model ID
     */
    private function generateDisplayName(string $modelId, array $apiData = []): string {
        // Use API display name if available
        if (!empty($apiData['displayName'])) {
            return $apiData['displayName'];
        }

        // Generate from model ID
        $name = $modelId;

        // Remove common prefixes
        $name = preg_replace('/^(models\/|gemini-)/', '', $name);

        // Convert hyphens and underscores to spaces
        $name = str_replace(['-', '_'], ' ', $name);

        // Capitalise each word
        $name = ucwords($name);

        // Fix common patterns
        $name = preg_replace('/(\d+)\.(\d+)/', '$1.$2', $name); // Keep version numbers
        $name = str_replace(' Pro ', ' Pro ', $name);
        $name = str_replace(' Flash ', ' Flash ', $name);
        $name = str_replace(' Preview', ' (Preview)', $name);
        $name = str_replace(' Image', ' Image', $name);

        return 'Gemini ' . trim($name);
    }

    private function inferCategory(string $identifier): string {
        if (strpos($identifier, 'image') !== false || strpos($identifier, 'imagen') !== false) {
            return 'image';
        }
        if (strpos($identifier, 'audio') !== false || strpos($identifier, 'speech') !== false) {
            return 'audio';
        }
        if (strpos($identifier, 'embedding') !== false) {
            return 'embedding';
        }
        return 'text';
    }

    private function inferCapabilities(string $identifier, string $category): array {
        $caps = [$category];

        if ($category === 'text') {
            if (strpos($identifier, 'pro') !== false) {
                $caps[] = 'vision';
                $caps[] = 'reasoning';
            }
            if (strpos($identifier, '2.5') !== false || strpos($identifier, '3') !== false) {
                $caps[] = 'tooluse';
            }
        }

        return array_unique($caps);
    }

    private function inferPriority(string $identifier): int {
        // Higher numbers = higher priority (shown first)
        if (strpos($identifier, '3-pro') !== false) {
            return 100;
        }
        if (strpos($identifier, '2.5-pro') !== false) {
            return 95;
        }
        if (strpos($identifier, '2.5-flash') !== false) {
            return 90;
        }
        if (strpos($identifier, '2.0') !== false) {
            return 80;
        }
        if (strpos($identifier, '1.5-pro') !== false) {
            return 70;
        }
        if (strpos($identifier, '1.5-flash') !== false) {
            return 65;
        }
        if (strpos($identifier, 'preview') !== false) {
            return 50;
        }
        return 30;
    }

    public function supportsModel(string $model): bool {
        $models = $this->getAvailableModels();
        return in_array($model, $models, true);
    }
}
