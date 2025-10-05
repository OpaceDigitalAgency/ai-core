<?php
/**
 * AI-Core Library - Model Registry
 *
 * Centralised model metadata with provider mappings, capability flags,
 * request parameter hints, and intelligent sorting.
 *
 * @package AI_Core
 * @version 2.0.0
 */

namespace AICore\Registry;

class ModelRegistry {
    /**
     * Canonical model definitions keyed by model id.
     *
     * @var array<string,array<string,mixed>>
     */
    private static $models = [];

    /**
     * Map of aliases to canonical model ids.
     *
     * @var array<string,string>
     */
    private static $aliases = [];

    /**
     * Whether base metadata has been initialised.
     *
     * @var bool
     */
    private static $initialised = false;

    /**
     * Ensure base metadata is loaded.
     *
     * @return void
     */
    private static function ensureInitialised(): void {
        if (self::$initialised) {
            return;
        }

        // Mark initialised BEFORE registering to avoid recursive re-entry
        self::$initialised = true;

        foreach (self::getBaseDefinitions() as $model => $definition) {
            self::registerModel($model, $definition);
        }
    }

    /**
     * Base metadata hints derived from AI_PROVIDERS_MODELS.md.
     *
     * Each model entry may supply:
     * - provider (required)
     * - display_name
     * - category (text, reasoning, image, embedding, audio)
     * - endpoint (chat, responses, anthropic.messages, gemini.generateContent, xai.chat)
     * - priority (higher surfaces first)
     * - released (Y-m-d for sorting)
     * - capabilities array (text, vision, reasoning, tooluse, streaming, image, audio)
     * - parameters array keyed by UI control id with metadata
     * - aliases array of alternate ids mapping back to canonical id
     *
     * @return array<string,array<string,mixed>>
     */
    private static function getBaseDefinitions(): array {
        $numberParameter = function (float $min, float $max, float $default, float $step = 1, ?string $requestKey = null, string $label = '', string $help = '') {
            return [
                'type' => 'number',
                'label' => $label ?: 'Value',
                'min' => $min,
                'max' => $max,
                'step' => $step,
                'default' => $default,
                'request_key' => $requestKey,
                'help' => $help,
            ];
        };

        $selectParameter = function (array $options, string $default, string $requestKey, string $label = '', string $help = '') {
            return [
                'type' => 'select',
                'label' => $label ?: 'Option',
                'options' => $options,
                'default' => $default,
                'request_key' => $requestKey,
                'help' => $help,
            ];
        };

        return [
            // --- OpenAI ---
            // GPT-5 models do NOT support temperature parameter
            'gpt-5' => [
                'provider' => 'openai',
                'display_name' => 'GPT-5',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 100,
                'released' => '2025-09-15',
                'capabilities' => ['text', 'vision', 'reasoning', 'tooluse'],
                'parameters' => [
                    'max_tokens' => $numberParameter(1, 128000, 4096, 1, 'max_output_tokens', 'Max Output Tokens', 'Hard limit of generated tokens.'),
                ],
                'aliases' => ['chatgpt-5-latest'],
            ],
            'gpt-5-mini' => [
                'provider' => 'openai',
                'display_name' => 'GPT-5 Mini',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 95,
                'released' => '2025-09-15',
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'max_tokens' => $numberParameter(1, 96000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'gpt-5-nano' => [
                'provider' => 'openai',
                'display_name' => 'GPT-5 Nano',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 92,
                'capabilities' => ['text'],
                'parameters' => [
                    'max_tokens' => $numberParameter(1, 64000, 2048, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'o1-preview' => [
                'provider' => 'openai',
                'display_name' => 'OpenAI o1 Preview',
                'category' => 'reasoning',
                'endpoint' => 'chat',
                'priority' => 92,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'max_tokens' => $numberParameter(1, 32768, 8192, 1, 'max_completion_tokens', 'Max Completion Tokens'),
                ],
            ],
            'o1-mini' => [
                'provider' => 'openai',
                'display_name' => 'OpenAI o1 Mini',
                'category' => 'reasoning',
                'endpoint' => 'chat',
                'priority' => 91,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'max_tokens' => $numberParameter(1, 65536, 8192, 1, 'max_completion_tokens', 'Max Completion Tokens'),
                ],
            ],
            'o3' => [
                'provider' => 'openai',
                'display_name' => 'OpenAI o3',
                'category' => 'reasoning',
                'endpoint' => 'responses',
                'priority' => 90,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'reasoning_effort' => $selectParameter([
                        ['value' => 'low', 'label' => 'Low'],
                        ['value' => 'medium', 'label' => 'Medium'],
                        ['value' => 'high', 'label' => 'High'],
                    ], 'medium', 'reasoning.effort', 'Reasoning Effort', 'Higher effort increases cost and latency.'),
                    'max_tokens' => $numberParameter(1, 128000, 8192, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'o3-mini' => [
                'provider' => 'openai',
                'display_name' => 'OpenAI o3 Mini',
                'category' => 'reasoning',
                'endpoint' => 'responses',
                'priority' => 88,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'reasoning_effort' => $selectParameter([
                        ['value' => 'low', 'label' => 'Low'],
                        ['value' => 'medium', 'label' => 'Medium'],
                    ], 'medium', 'reasoning.effort', 'Reasoning Effort'),
                    'max_tokens' => $numberParameter(1, 64000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'o4-mini' => [
                'provider' => 'openai',
                'display_name' => 'OpenAI o4 Mini',
                'category' => 'reasoning',
                'endpoint' => 'responses',
                'priority' => 87,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'reasoning_effort' => $selectParameter([
                        ['value' => 'low', 'label' => 'Low'],
                        ['value' => 'medium', 'label' => 'Medium'],
                        ['value' => 'high', 'label' => 'High'],
                    ], 'medium', 'reasoning.effort', 'Reasoning Effort'),
                    'max_tokens' => $numberParameter(1, 96000, 8192, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'gpt-4.1' => [
                'provider' => 'openai',
                'display_name' => 'GPT-4.1',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 85,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 128000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'gpt-4.1-mini' => [
                'provider' => 'openai',
                'display_name' => 'GPT-4.1 Mini',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 83,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.8, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 96000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'gpt-4o' => [
                'provider' => 'openai',
                'display_name' => 'GPT-4o',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 80,
                'capabilities' => ['text', 'vision', 'tooluse'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 128000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
                'aliases' => ['chatgpt-4o-latest'],
            ],
            'gpt-4o-mini' => [
                'provider' => 'openai',
                'display_name' => 'GPT-4o Mini',
                'category' => 'text',
                'endpoint' => 'responses',
                'priority' => 76,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.8, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 64000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'gpt-4' => [
                'provider' => 'openai',
                'display_name' => 'GPT-4 (Chat Completions)',
                'category' => 'text',
                'endpoint' => 'chat',
                'priority' => 60,
                'capabilities' => ['text', 'tooluse'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 8192, 2048, 1, 'max_tokens', 'Max Tokens'),
                    'top_p' => $numberParameter(0.0, 1.0, 1.0, 0.01, 'top_p', 'Top P'),
                ],
            ],
            'gpt-3.5-turbo' => [
                'provider' => 'openai',
                'display_name' => 'GPT-3.5 Turbo',
                'category' => 'text',
                'endpoint' => 'chat',
                'priority' => 40,
                'capabilities' => ['text'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 4096, 1024, 1, 'max_tokens', 'Max Tokens'),
                ],
            ],
            'gpt-image-1' => [
                'provider' => 'openai',
                'display_name' => 'GPT Image 1',
                'category' => 'image',
                'endpoint' => 'images',
                'priority' => 35,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'dall-e-3' => [
                'provider' => 'openai',
                'display_name' => 'DALL-E 3',
                'category' => 'image',
                'endpoint' => 'images',
                'priority' => 30,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'dall-e-2' => [
                'provider' => 'openai',
                'display_name' => 'DALL-E 2',
                'category' => 'image',
                'endpoint' => 'images',
                'priority' => 25,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'text-embedding-3-large' => [
                'provider' => 'openai',
                'display_name' => 'Text Embedding 3 Large',
                'category' => 'embedding',
                'endpoint' => 'embeddings',
                'priority' => 20,
                'capabilities' => ['embedding'],
                'parameters' => [],
            ],

            // --- Anthropic (Claude) ---
            'claude-sonnet-4-5-20250929' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude Sonnet 4.5',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 95,
                'capabilities' => ['text', 'vision', 'reasoning'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 200000, 4096, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
                'aliases' => ['claude-sonnet-4-5'],
            ],
            'claude-sonnet-4-20250514' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude Sonnet 4',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 90,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 200000, 4096, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
            ],
            'claude-3-7-sonnet-20250219' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude 3.7 Sonnet',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 88,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 160000, 4096, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
                'aliases' => ['claude-3-7-sonnet-latest'],
            ],
            'claude-opus-4-1-20250805' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude Opus 4.1',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 87,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.6, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 200000, 4096, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
            ],
            'claude-opus-4-20250514' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude Opus 4',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 84,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.6, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 200000, 4096, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
            ],
            'claude-3-5-haiku-20241022' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude 3.5 Haiku',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 80,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.8, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 120000, 4096, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
                'aliases' => ['claude-3-5-haiku-latest'],
            ],
            'claude-3-haiku-20240307' => [
                'provider' => 'anthropic',
                'display_name' => 'Claude 3 Haiku',
                'category' => 'text',
                'endpoint' => 'anthropic.messages',
                'priority' => 50,
                'capabilities' => ['text'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 1.0, 0.8, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 90000, 2048, 1, 'max_tokens', 'Max Tokens', 'Required by Anthropic API.'),
                ],
            ],

            // --- Gemini ---
            'gemini-2.5-pro' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Pro',
                'category' => 'text',
                'endpoint' => 'gemini.generateContent',
                'priority' => 95,
                'capabilities' => ['text', 'vision', 'reasoning'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'generationConfig.temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 8192, 4096, 1, 'generationConfig.maxOutputTokens', 'Max Output Tokens'),
                    'top_p' => $numberParameter(0.0, 1.0, 1.0, 0.01, 'generationConfig.topP', 'Top P'),
                ],
            ],
            'gemini-2.5-flash' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Flash',
                'category' => 'text',
                'endpoint' => 'gemini.generateContent',
                'priority' => 90,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'generationConfig.temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 8192, 2048, 1, 'generationConfig.maxOutputTokens', 'Max Output Tokens'),
                    'top_p' => $numberParameter(0.0, 1.0, 1.0, 0.01, 'generationConfig.topP', 'Top P'),
                ],
            ],
            'gemini-2.5-flash-preview-09-2025' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Flash (Preview 09-2025)',
                'category' => 'text',
                'endpoint' => 'gemini.generateContent',
                'priority' => 88,
                'capabilities' => ['text', 'vision'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'generationConfig.temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 8192, 2048, 1, 'generationConfig.maxOutputTokens', 'Max Output Tokens'),
                ],
            ],
            'gemini-2.5-flash-lite' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Flash Lite',
                'category' => 'text',
                'endpoint' => 'gemini.generateContent',
                'priority' => 82,
                'capabilities' => ['text'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.8, 0.01, 'generationConfig.temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 4096, 1024, 1, 'generationConfig.maxOutputTokens', 'Max Output Tokens'),
                ],
            ],
            'imagen-3.0-generate-001' => [
                'provider' => 'gemini',
                'display_name' => 'Imagen 3.0',
                'category' => 'image',
                'endpoint' => 'gemini.generateImage',
                'priority' => 75,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'imagen-3.0-fast-generate-001' => [
                'provider' => 'gemini',
                'display_name' => 'Imagen 3.0 Fast',
                'category' => 'image',
                'endpoint' => 'gemini.generateImage',
                'priority' => 70,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'gemini-2.5-flash-image' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Flash Image',
                'category' => 'image',
                'endpoint' => 'gemini.generateImage',
                'priority' => 65,
                'capabilities' => ['image'],
                'parameters' => [],
            ],

            // --- xAI (Grok) ---
            'grok-2-image-1212' => [
                'provider' => 'grok',
                'display_name' => 'Grok 2 Image',
                'category' => 'image',
                'endpoint' => 'xai.images',
                'priority' => 85,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'grok-4-fast' => [
                'provider' => 'grok',
                'display_name' => 'Grok 4 Fast',
                'category' => 'text',
                'endpoint' => 'xai.chat',
                'priority' => 80,
                'capabilities' => ['text', 'tooluse'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.8, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 64000, 4096, 1, 'max_tokens', 'Max Tokens'),
                ],
            ],
            'grok-4-fast-reasoning' => [
                'provider' => 'grok',
                'display_name' => 'Grok 4 Fast (Reasoning)',
                'category' => 'reasoning',
                'endpoint' => 'xai.responses',
                'priority' => 78,
                'capabilities' => ['text', 'reasoning'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.6, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 64000, 4096, 1, 'max_output_tokens', 'Max Output Tokens'),
                ],
            ],
            'grok-3' => [
                'provider' => 'grok',
                'display_name' => 'Grok 3',
                'category' => 'text',
                'endpoint' => 'xai.chat',
                'priority' => 65,
                'capabilities' => ['text'],
                'parameters' => [
                    'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature'),
                    'max_tokens' => $numberParameter(1, 32000, 2048, 1, 'max_tokens', 'Max Tokens'),
                ],
            ],
        ];
    }

    /**
     * Register (or update) a model definition.
     *
     * @param string $model
     * @param array  $config
     * @return void
     */
    public static function registerModel(string $model, array $config): void {
        self::ensureInitialised();

        $model = trim($model);
        if ($model === '') {
            return;
        }

        if (isset(self::$aliases[$model])) {
            $model = self::$aliases[$model];
        }

        $provider = $config['provider'] ?? self::getProvider($model);
        if (!$provider) {
            throw new \InvalidArgumentException('Model registration requires a provider.');
        }

        $defaults = [
            'display_name' => $model,
            'category' => 'text',
            'endpoint' => self::getDefaultEndpointForProvider($provider),
            'priority' => 10,
            'released' => null,
            'capabilities' => ['text'],
            'parameters' => self::getDefaultParametersForProvider($provider),
            'aliases' => [],
        ];

        $existing = self::$models[$model] ?? [];

        $definition = array_merge(
            $defaults,
            $existing,
            $config,
            ['provider' => $provider]
        );

        $definition['capabilities'] = array_values(array_unique($definition['capabilities']));

        self::$models[$model] = $definition;

        if (!empty($definition['aliases']) && is_array($definition['aliases'])) {
            foreach ($definition['aliases'] as $alias) {
                $alias = trim((string) $alias);
                if ($alias === '') {
                    continue;
                }
                self::$aliases[$alias] = $model;
            }
        }
    }

    /**
     * Resolve a model id to its canonical identifier.
     *
     * @param string $model
     * @return string
     */
    public static function resolveModelId(string $model): string {
        self::ensureInitialised();
        return self::$aliases[$model] ?? $model;
    }

    /**
     * Get provider for a model.
     *
     * @param string $model
     * @return string|null
     */
    public static function getProvider(string $model): ?string {
        self::ensureInitialised();
        $canonical = self::resolveModelId($model);
        return self::$models[$canonical]['provider'] ?? null;
    }

    public static function isOpenAIModel(string $model): bool {
        return self::getProvider($model) === 'openai';
    }

    public static function isAnthropicModel(string $model): bool {
        return self::getProvider($model) === 'anthropic';
    }

    public static function isGeminiModel(string $model): bool {
        return self::getProvider($model) === 'gemini';
    }

    public static function isGrokModel(string $model): bool {
        return self::getProvider($model) === 'grok';
    }

    public static function isImageModel(string $model): bool {
        self::ensureInitialised();
        $canonical = self::resolveModelId($model);
        return (self::$models[$canonical]['category'] ?? '') === 'image';
    }

    /**
     * Retrieve metadata for a model.
     *
     * @param string $model
     * @return array|null
     */
    public static function getModelConfig(string $model): ?array {
        self::ensureInitialised();
        $canonical = self::resolveModelId($model);
        return self::$models[$canonical] ?? null;
    }

    /**
     * Return parameter schema for a model (generic keys used in settings UI).
     *
     * @param string $model
     * @return array<string,array<string,mixed>>
     */
    public static function getParameterSchema(string $model): array {
        $config = self::getModelConfig($model);
        return $config['parameters'] ?? [];
    }

    /**
     * Determine the canonical request endpoint behaviour for a model.
     *
     * @param string $model
     * @return string
     */
    public static function getEndpoint(string $model): string {
        $config = self::getModelConfig($model);
        return $config['endpoint'] ?? 'chat';
    }

    /**
     * Get all models for a provider sorted by priority and release recency.
     *
     * @param string $provider
     * @return array<int,string>
     */
    public static function getModelsByProvider(string $provider): array {
        self::ensureInitialised();
        $results = [];
        foreach (self::$models as $model => $config) {
            if (($config['provider'] ?? null) === $provider) {
                $results[] = $model;
            }
        }

        usort($results, function ($a, $b) {
            $metaA = self::$models[$a];
            $metaB = self::$models[$b];

            $priorityA = $metaA['priority'] ?? 0;
            $priorityB = $metaB['priority'] ?? 0;
            if ($priorityA !== $priorityB) {
                return $priorityB <=> $priorityA;
            }

            $dateA = isset($metaA['released']) ? strtotime($metaA['released']) : 0;
            $dateB = isset($metaB['released']) ? strtotime($metaB['released']) : 0;
            if ($dateA !== $dateB) {
                return $dateB <=> $dateA;
            }

            return strcmp($a, $b);
        });

        return $results;
    }

    /**
     * Suggest the best default model for a provider given available ids.
     *
     * @param string $provider
     * @param array<int,string>|null $candidates Optional externally fetched ids
     * @return string|null
     */
    public static function getPreferredModel(string $provider, ?array $candidates = null): ?string {
        $models = $candidates ?: self::getModelsByProvider($provider);
        if (empty($models)) {
            return null;
        }

        // Filter to models we know about
        $known = array_values(array_filter($models, function ($model) use ($provider) {
            return self::getProvider($model) === $provider;
        }));

        if (!empty($known)) {
            return $known[0];
        }

        return $models[0];
    }

    /**
     * Export metadata for front-end consumption.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function exportProviderMetadata(): array {
        self::ensureInitialised();
        $providers = [];
        foreach (self::$models as $model => $config) {
            $provider = $config['provider'];
            if (!isset($providers[$provider])) {
                $providers[$provider] = [];
            }

            $providers[$provider][$model] = [
                'id' => $model,
                'display_name' => $config['display_name'],
                'category' => $config['category'],
                'endpoint' => $config['endpoint'],
                'priority' => $config['priority'],
                'released' => $config['released'],
                'capabilities' => $config['capabilities'],
                'parameters' => $config['parameters'],
            ];
        }

        return $providers;
    }

    /**
     * Register an alias for a model.
     *
     * @param string $alias
     * @param string $canonical
     * @return void
     */
    public static function registerAlias(string $alias, string $canonical): void {
        self::ensureInitialised();
        if (!self::modelExists($canonical)) {
            return;
        }
        self::$aliases[$alias] = $canonical;
    }

    /**
     * Check whether model exists.
     *
     * @param string $model
     * @return bool
     */
    public static function modelExists(string $model): bool {
        self::ensureInitialised();
        $canonical = self::resolveModelId($model);
        return isset(self::$models[$canonical]);
    }

    /**
     * Get all model ids (canonical).
     *
     * @return array<int,string>
     */
    public static function getAllModels(): array {
        self::ensureInitialised();
        return array_keys(self::$models);
    }

    /**
     * Default endpoint for provider when hints are missing.
     *
     * @param string $provider
     * @return string
     */
    private static function getDefaultEndpointForProvider(string $provider): string {
        switch ($provider) {
            case 'openai':
                return 'chat';
            case 'anthropic':
                return 'anthropic.messages';
            case 'gemini':
                return 'gemini.generateContent';
            case 'grok':
                return 'xai.chat';
            default:
                return 'chat';
        }
    }

    /**
     * Default parameter schema for providers (fallback for unknown models).
     *
     * @param string $provider
     * @return array<string,array<string,mixed>>
     */
    private static function getDefaultParametersForProvider(string $provider): array {
        switch ($provider) {
            case 'anthropic':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                        'default' => 0.7,
                        'request_key' => 'temperature',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Output Tokens',
                        'min' => 1,
                        'max' => 200000,
                        'step' => 1,
                        'default' => 4096,
                        'request_key' => 'max_output_tokens',
                    ],
                ];
            case 'gemini':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.01,
                        'default' => 0.7,
                        'request_key' => 'generationConfig.temperature',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Output Tokens',
                        'min' => 1,
                        'max' => 8192,
                        'step' => 1,
                        'default' => 2048,
                        'request_key' => 'generationConfig.maxOutputTokens',
                    ],
                ];
            case 'grok':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.01,
                        'default' => 0.7,
                        'request_key' => 'temperature',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Tokens',
                        'min' => 1,
                        'max' => 64000,
                        'step' => 1,
                        'default' => 2048,
                        'request_key' => 'max_tokens',
                    ],
                ];
            case 'openai':
            default:
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.01,
                        'default' => 0.7,
                        'request_key' => 'temperature',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Tokens',
                        'min' => 1,
                        'max' => 8192,
                        'step' => 1,
                        'default' => 2048,
                        'request_key' => 'max_tokens',
                    ],
                ];
        }
    }
}
