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
                return $this->sendResponsesRequest($messages, $model, $parameterValues, $options);
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

        // Preserve legacy optional keys if provided explicitly. The
        // sampling-adjacent ones are gated on the model actually accepting
        // them: the reasoning families reject stop, the penalties and the
        // sampling controls outright, and a caller passing one through
        // should not be able to turn that into a provider 400.
        $samplingGated = ['stop', 'frequency_penalty', 'presence_penalty', 'logprobs', 'top_logprobs', 'n'];
        $acceptsSampling = $this->acceptsSamplingParameters($model);

        // response_format carries structured outputs and is NOT a sampling
        // control: gating or dropping it turns a schema-enforced request into
        // free prose, which the caller then fails to decode. It must pass
        // through for every model, including one the registry has never seen
        // and therefore has no parameter schema for.
        foreach (['response_format', 'tools', 'tool_choice'] as $key) {
            if (array_key_exists($key, $options)) {
                $payload[$key] = $options[$key];
            }
        }

        foreach (['stream', 'stop', 'functions', 'frequency_penalty', 'presence_penalty'] as $key) {
            if (!array_key_exists($key, $options)) {
                continue;
            }
            if (!$acceptsSampling && \in_array($key, $samplingGated, true)) {
                continue;
            }
            $payload[$key] = $options[$key];
        }

        $headers = $this->buildHeaders();

        try {
            $response = HttpClient::post(self::CHAT_COMPLETIONS_ENDPOINT, $payload, $headers);
            return ResponseNormalizer::normalize($response, 'openai');
        } catch (\Exception $e) {
            throw new \Exception(\esc_html('OpenAI chat API request failed: ' . $this->describeRequestFailure($e, $model)));
        }
    }

    /**
     * Does this model still accept sampling-style parameters?
     *
     * Answered from the registered parameter schema rather than a second
     * copy of the family rules, so the model contract has exactly one
     * definition. A schema that declines to declare temperature is a model
     * that rejects the whole sampling group.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    private function acceptsSamplingParameters(string $model): bool {
        $schema = ModelRegistry::getParameterSchema($model);

        return isset($schema['temperature']) || isset($schema['top_p']);
    }

    /**
     * Turn a provider error into something that names the real cause.
     *
     * OpenAI reports a parameter the model does not take with
     * code=unsupported_parameter (or unsupported_value for a rejected
     * value) and names the offender in error.param. That is the durable
     * contract, and it is worth surfacing verbatim: a user should never be
     * left reading a bare HTTP 400 for a request this library built.
     *
     * @param \Exception $e     Underlying failure.
     * @param string     $model Model the request targeted.
     * @return string
     */
    private function describeRequestFailure(\Exception $e, string $model): string {
        $message = $e->getMessage();

        if (stripos($message, 'unsupported parameter') === false
            && stripos($message, 'unsupported value') === false
            && stripos($message, 'unsupported_parameter') === false) {
            return $message;
        }

        return $message . ' (AI-Core built this request for "' . $model
            . '" from its recorded parameter contract; that contract is out of date for this model.'
            . ' Refresh the model list, and report the model id if it persists.)';
    }

    /**
     * Run the Responses API for modern and reasoning models.
     */
    private function sendResponsesRequest(array $messages, string $model, array $parameters, array $options = []): array {
        $input = $this->convertMessagesToInput($messages);

        $payload = array_merge([
            'model' => $model,
            'input' => $input,
        ], $parameters);

        // Structured outputs arrive in $options, not in the schema-derived
        // $parameters. Without this the Responses path never sees
        // response_format and every structured request degrades to prose.
        foreach (['response_format', 'tools', 'tool_choice'] as $passthrough) {
            if (array_key_exists($passthrough, $options)) {
                $payload[$passthrough] = $options[$passthrough];
            }
        }

        // Structured outputs: callers speak the Chat Completions dialect
        // (response_format). The Responses API expects text.format, with the
        // json_schema fields flattened rather than nested. Without this
        // translation a structured request silently degrades to prose and the
        // caller's JSON decode fails.
        if (isset($payload['response_format']) && is_array($payload['response_format'])) {
            $rf = $payload['response_format'];
            unset($payload['response_format']);

            $type = $rf['type'] ?? '';
            if ('json_schema' === $type && isset($rf['json_schema']) && is_array($rf['json_schema'])) {
                $js = $rf['json_schema'];
                $payload['text'] = ['format' => array_filter([
                    'type'   => 'json_schema',
                    'name'   => $js['name'] ?? 'response',
                    'strict' => $js['strict'] ?? true,
                    'schema' => $js['schema'] ?? null,
                ], static function ($v) { return null !== $v; })];
            } elseif ('json_object' === $type) {
                $payload['text'] = ['format' => ['type' => 'json_object']];
            }
        }

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
            throw new \Exception(\esc_html('OpenAI responses API request failed: ' . $this->describeRequestFailure($e, $model)));
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
                if (!empty($response['data']) && \is_array($response['data'])) {
                    foreach ($response['data'] as $entry) {
                        $identifier = $entry['id'] ?? '';
                        if (!$identifier) {
                            continue;
                        }

                        $canonicalId = ModelRegistry::resolveModelId($identifier);
                        $category = $this->inferCategoryFromId($canonicalId);
                        $displayName = $this->generateDisplayName($canonicalId);

                        // Dynamically register ANY model from the API
                        if (!ModelRegistry::modelExists($canonicalId)) {
                            $endpoint = ModelRegistry::inferEndpoint('openai', $canonicalId);
                            ModelRegistry::registerModel($canonicalId, [
                                'provider' => 'openai',
                                'display_name' => $displayName,
                                'category' => $category,
                                'endpoint' => $endpoint,
                                'capabilities' => $this->inferCapabilities($canonicalId, $category),
                                'priority' => $this->inferPriority($canonicalId),
                                'parameters' => ModelRegistry::inferParameterSchema('openai', $canonicalId, $endpoint),
                            ]);
                        }

                        // Include text, reasoning, and image models (exclude embeddings/audio for main list)
                        if (\in_array($category, ['text', 'reasoning', 'image'], true)) {
                            $apiModels[] = $canonicalId;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Network or permission issue; rely on cached definitions.
            }
        }

        // Display order is derived from the ids themselves (newest first,
        // mainline family on top): seeded priority strands anything newer
        // than the registry at the bottom of the dropdown.
        if (!empty($apiModels)) {
            return ModelRegistry::sortModelsForDisplay($apiModels);
        }

        return ModelRegistry::sortModelsForDisplay(ModelRegistry::getModelsByProvider('openai'));
    }

    /**
     * Generate a human-readable display name from model ID
     */
    private function generateDisplayName(string $modelId): string {
        $name = $modelId;

        // Handle GPT models
        if (preg_match('/^gpt-(\d+(?:\.\d+)?)(-.+)?$/', $name, $matches)) {
            $version = $matches[1];
            $suffix = $matches[2] ?? '';
            $suffix = str_replace(['-', '_'], ' ', $suffix);
            $suffix = ucwords(trim($suffix));
            return "GPT-{$version}" . ($suffix ? " {$suffix}" : '');
        }

        // Handle o-series (reasoning)
        if (preg_match('/^o(\d+)(-.+)?$/', $name, $matches)) {
            $version = $matches[1];
            $suffix = $matches[2] ?? '';
            $suffix = str_replace(['-', '_'], ' ', $suffix);
            $suffix = ucwords(trim($suffix));
            return "OpenAI o{$version}" . ($suffix ? " {$suffix}" : '');
        }

        // Handle DALL-E
        if (strpos($name, 'dall-e') !== false) {
            return strtoupper(str_replace('-', '-E-', $name));
        }

        // Default: capitalise and clean up
        $name = str_replace(['-', '_'], ' ', $name);
        return ucwords($name);
    }

    private function inferCategoryFromId(string $identifier): string {
        if (strpos($identifier, 'embedding') !== false) {
            return 'embedding';
        }
        if (strpos($identifier, 'audio') !== false || strpos($identifier, 'tts') !== false || strpos($identifier, 'whisper') !== false) {
            return 'audio';
        }
        if (strpos($identifier, 'dall-e') !== false || strpos($identifier, 'image') !== false) {
            return 'image';
        }
        if (preg_match('/^o[1-9]/', $identifier)) {
            return 'reasoning';
        }
        return 'text';
    }

    private function inferCapabilities(string $identifier, string $category): array {
        $caps = [$category];

        if ($category === 'text') {
            // Most GPT-4+ models have vision
            if (strpos($identifier, 'gpt-4') !== false || strpos($identifier, 'gpt-5') !== false) {
                $caps[] = 'vision';
                $caps[] = 'tooluse';
            }
        }

        if ($category === 'reasoning') {
            $caps[] = 'text';
        }

        return array_unique($caps);
    }

    private function inferPriority(string $identifier): int {
        // Higher numbers = higher priority (shown first)
        if (strpos($identifier, 'gpt-5') === 0) {
            return 100;
        }
        if (preg_match('/^o[3-9]/', $identifier)) {
            return 95;
        }
        if (strpos($identifier, 'gpt-4.1') !== false) {
            return 90;
        }
        if (strpos($identifier, 'gpt-4o') !== false) {
            return 85;
        }
        if (strpos($identifier, 'gpt-4') !== false) {
            return 80;
        }
        if (preg_match('/^o[12]/', $identifier)) {
            return 75;
        }
        if (strpos($identifier, 'gpt-3.5') !== false) {
            return 60;
        }
        if (strpos($identifier, 'dall-e-3') !== false) {
            return 50;
        }
        if (strpos($identifier, 'dall-e') !== false) {
            return 45;
        }
        return 30;
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
