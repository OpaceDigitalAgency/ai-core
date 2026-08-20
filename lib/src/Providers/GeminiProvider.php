<?php
/**
 * Opace AI Hub Library - Google Gemini Provider
 *
 * Provides dynamic model discovery and parameter-aware requests for Gemini.
 *
 * Calls the provider's HTTP API directly by design (not via the WordPress
 * core AI Client): see the rationale in lib/src/AICore.php and the readme FAQ.
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
    // Use v1beta for models list to include preview models
    private const MODELS_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';
    // Use v1beta for generation to support all models including preview
    private const GENERATE_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';
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

        // A stored id Google has since withdrawn (e.g. gemini-3-pro-preview)
        // is registered as an alias of its live successor; resolve it before
        // the endpoint is built so the request cannot 400 on a dead model.
        $model = ModelRegistry::resolveModelId($model);

        $endpoint = $this->buildEndpoint($model);
        $payload = $this->buildPayload($messages, $model, $options);

        try {
            $response = HttpClient::post($endpoint, $payload, ['Content-Type' => 'application/json']);
            return ResponseNormalizer::normalize($response, 'gemini');
        } catch (\Exception $e) {
            throw new \Exception(\esc_html('Gemini API request failed: ' . $e->getMessage()));
        }
    }

    private function buildEndpoint(string $model): string {
        return \sprintf('%s/%s%s?key=%s', self::GENERATE_ENDPOINT, $model, self::GENERATE_SUFFIX, rawurlencode($this->api_key));
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
                // A dotted sub-key nests (thinkingConfig.thinkingLevel →
                // generationConfig.thinkingConfig.thinkingLevel); a flat
                // dotted string is not a field the API knows.
                $segments = explode('.', $subKey);
                $target = &$generationConfig;
                foreach ($segments as $i => $segment) {
                    if ($i === count($segments) - 1) {
                        $target[$segment] = $value;
                        break;
                    }
                    if (!isset($target[$segment]) || !is_array($target[$segment])) {
                        $target[$segment] = [];
                    }
                    $target = &$target[$segment];
                }
                unset($target);
            }
        }

        /*
         * A caller-supplied generationConfig carries responseMimeType and
         * responseSchema — Gemini's structured-output mechanism. Building the
         * config from the registry alone silently dropped it, so every
         * schema-enforced request came back as prose and the caller then
         * reported "Response was not valid JSON". Caller values win: the
         * registry supplies sampling defaults, not the response contract.
         */
        if (isset($options['generationConfig']) && is_array($options['generationConfig'])) {
            $generationConfig = array_merge($generationConfig, $options['generationConfig']);
        }

        $payload = [
            'contents' => $contents,
        ];

        if (!empty($generationConfig)) {
            $payload['generationConfig'] = $generationConfig;
        }

        // Handle system instruction - only for models that support it (gemini-3.x, gemini-2.5-pro, etc.)
        $system = $this->extractSystemInstruction($messages);
        if ($system) {
            // Check if model supports systemInstruction (gemini-2.0-flash does NOT support it in older API versions)
            // gemini-2.5 and the 3.x mainline DO support it
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
                        $methods = isset($model['supportedGenerationMethods']) && \is_array($model['supportedGenerationMethods'])
                            ? array_map('strval', $model['supportedGenerationMethods'])
                            : [];

                        // Dynamically register ANY model from the API
                        if (!ModelRegistry::modelExists($canonicalId)) {
                            // The 3.x line no longer takes the sampling
                            // controls, so the contract is inferred from the
                            // family rather than assumed provider-wide.
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'gemini',
                                'display_name' => $displayName,
                                'category' => $category,
                                'capabilities' => $this->inferCapabilities($canonicalId, $category, $methods),
                                'generation_methods' => $methods,
                                'priority' => $this->inferPriority($canonicalId),
                                'parameters' => ModelRegistry::inferParameterSchema('gemini', $canonicalId, 'gemini.generateContent'),
                            ]);
                        } elseif (!empty($methods)) {
                            $existing = ModelRegistry::getModelConfig($canonicalId) ?? [];
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'gemini',
                                'generation_methods' => $methods,
                                'capabilities' => array_values(array_unique(array_merge(
                                    $existing['capabilities'] ?? [$category],
                                    array_map(static function ($method) {
                                        return 'method:' . (string) $method;
                                    }, $methods)
                                ))),
                            ]);
                        }

                        // Keep the complete provider catalogue. AI-Scribe and
                        // other add-ons filter it by their own capabilities.
                        $apiModels[] = $canonicalId;
                    }
                }
            } catch (\Exception $e) {
                // Ignore failure; fallback handled below.
            }
        }

        // The account's own list when the API answered, the registry's seeded
        // list otherwise. Either way display order is derived from the ids
        // themselves (newest first, mainline family on top), because seeded
        // priority strands anything newer than the registry at the bottom.
        if (!empty($apiModels)) {
            return ModelRegistry::sortModelsForDisplay($apiModels);
        }

        return ModelRegistry::sortModelsForDisplay(ModelRegistry::getModelsByProvider('gemini'));
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
        if (strpos($identifier, 'image') !== false
            || strpos($identifier, 'imagen') !== false
            || strpos($identifier, 'nano-banana') !== false) {
            return 'image';
        }
        if (strpos($identifier, 'audio') !== false || strpos($identifier, 'speech') !== false || strpos($identifier, 'tts') !== false
            || strpos($identifier, 'live') !== false || strpos($identifier, 'lyria') !== false) {
            return 'audio';
        }
        if (strpos($identifier, 'embedding') !== false) {
            return 'embedding';
        }
        if (strpos($identifier, 'veo') !== false) {
            return 'video';
        }
        if (strpos($identifier, 'robotics') !== false) {
            return 'robotics';
        }
        if (strpos($identifier, 'computer-use') !== false || strpos($identifier, 'antigravity') !== false) {
            return 'agent';
        }
        if (strpos($identifier, 'research') !== false) {
            return 'research';
        }
        if ('aqa' === $identifier) {
            return 'question-answering';
        }
        return 'text';
    }

    private function inferCapabilities(string $identifier, string $category, array $methods = []): array {
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

        foreach ($methods as $method) {
            $caps[] = 'method:' . (string) $method;
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
        return ModelRegistry::isTextGenerationModel($model, 'gemini');
    }
}
