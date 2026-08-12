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
                'capabilities' => ['text', 'vision', 'image', 'reasoning', 'tooluse'],
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
                'capabilities' => ['text', 'vision', 'image'],
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
                'capabilities' => ['text', 'vision', 'image', 'reasoning'],
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
                'capabilities' => ['text', 'vision', 'image', 'reasoning'],
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
                'capabilities' => ['text', 'vision', 'image', 'tooluse'],
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
                'capabilities' => ['text', 'vision', 'image'],
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
                    // max_tokens is deprecated across Chat Completions in
                    // favour of max_completion_tokens, which the request
                    // schema places under no model restriction.
                    'max_tokens' => $numberParameter(1, 8192, 2048, 1, 'max_completion_tokens', 'Max Output Tokens'),
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
                    'max_tokens' => $numberParameter(1, 4096, 1024, 1, 'max_completion_tokens', 'Max Output Tokens'),
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
            'gemini-3-pro-preview' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 3 Pro (Preview)',
                'category' => 'text',
                'endpoint' => 'gemini.generateContent',
                'priority' => 100,
                'released' => '2025-11-01',
                'capabilities' => ['text', 'vision', 'reasoning', 'tooluse'],
                'parameters' => [
                    // Google's Gemini 3 guidance: temperature, topP and topK
                    // "are no longer recommended for all Gemini 3.x models.
                    // Remove these parameters from all requests." Declaring
                    // them here would also have contradicted the contract
                    // inferred for every other 3.x model.
                    'max_tokens' => $numberParameter(1, 65536, 8192, 1, 'generationConfig.maxOutputTokens', 'Max Output Tokens'),
                ],
            ],
            'gemini-3-pro-image-preview' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 3 Pro Image (Preview)',
                'category' => 'image',
                'endpoint' => 'gemini.generateContent',
                'priority' => 98,
                'released' => '2025-11-01',
                'capabilities' => ['image', 'text'],
                'parameters' => [],
            ],
            'gemini-2.5-pro' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Pro',
                'category' => 'text',
                'endpoint' => 'gemini.generateContent',
                'priority' => 95,
                'capabilities' => ['text', 'vision', 'image', 'reasoning'],
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
                'capabilities' => ['text', 'vision', 'image'],
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
                'capabilities' => ['text', 'vision', 'image'],
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
                'capabilities' => ['text', 'image'],
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
                'endpoint' => 'gemini.generateContent',
                'priority' => 72,
                'capabilities' => ['image'],
                'parameters' => [],
            ],
            'gemini-2.5-flash-image-preview' => [
                'provider' => 'gemini',
                'display_name' => 'Gemini 2.5 Flash Image (Preview)',
                'category' => 'image',
                'endpoint' => 'gemini.generateContent',
                'priority' => 71,
                'capabilities' => ['image'],
                'parameters' => [],
            ],

            // --- xAI (Grok) ---
            // Withheld. The xAI integration has never been exercised against a
            // live key, so it is not offered as a choice anywhere in the UI.
            // GrokProvider, the xai.chat endpoint hints and grokParameterSchema()
            // all remain in place: restoring the four model definitions that
            // used to sit here, plus 'grok' in getSupportedProviders(), is the
            // whole of re-enabling it.
        ];
    }

    /**
     * Providers AI-Core currently offers as a choice.
     *
     * The single source of truth for "may a user pick this provider". A
     * provider absent from this list keeps its class, its endpoint hints and
     * its parameter contract — it simply is not offered, and no key field,
     * model list or default is built for it.
     *
     * @return array<int,string>
     */
    public static function getSupportedProviders(): array {
        return ['openai', 'anthropic', 'gemini'];
    }

    /**
     * Is this provider offered as a choice?
     *
     * @param string $provider Provider id.
     * @return bool
     */
    public static function isProviderSupported(string $provider): bool {
        return in_array($provider, self::getSupportedProviders(), true);
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

        $existing = self::$models[$model] ?? [];

        // The token parameter is named differently per endpoint, so the
        // endpoint has to be settled before the parameter contract can be
        // inferred. An explicit hint always wins over the inference.
        $endpoint = $config['endpoint'] ?? $existing['endpoint'] ?? self::inferEndpoint($provider, $model);

        $defaults = [
            'display_name' => $model,
            'category' => 'text',
            'endpoint' => $endpoint,
            'priority' => 10,
            'released' => null,
            'capabilities' => ['text'],
            'parameters' => self::inferParameterSchema($provider, $model, $endpoint),
            'aliases' => [],
        ];

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
     * Every image-generation model a provider offers, best first.
     *
     * @param string $provider Provider id.
     * @return array<int,string>
     */
    public static function getImageModelsByProvider(string $provider): array {
        return array_values(array_filter(
            self::getModelsByProvider($provider),
            static function ($model) {
                return self::isImageModel($model);
            }
        ));
    }

    /**
     * Can this provider generate images at all?
     *
     * Answered from the registry rather than from a hardcoded list, so a
     * provider that gains (or loses) an image family answers correctly the
     * moment its models are registered. Anthropic has no image model and
     * therefore honestly reports false.
     *
     * @param string $provider Provider id.
     * @return bool
     */
    public static function providerSupportsImages(string $provider): bool {
        return !empty(self::getImageModelsByProvider($provider));
    }

    /**
     * Providers that are both offered and capable of image generation,
     * ordered by the priority of their best image model.
     *
     * @return array<int,string>
     */
    public static function getImageProviders(): array {
        $providers = [];

        foreach (self::getSupportedProviders() as $provider) {
            $best = self::getPreferredImageModel($provider);
            if ($best === null) {
                continue;
            }
            $providers[$provider] = self::getModelConfig($best)['priority'] ?? 0;
        }

        arsort($providers);

        return array_keys($providers);
    }

    /**
     * Suggest the best default model for a provider given available ids.
     *
     * "Best" is the registry's own ranking — priority first, then release
     * recency — never a hardcoded pick. Candidate lists fetched live from a
     * provider arrive in whatever order that endpoint returned, so they are
     * re-ranked here rather than trusted: taking the first known entry of an
     * unsorted list is how a provider's oldest model ends up as the default.
     *
     * An id the registry has never seen carries no capability data, so it can
     * only be chosen when no known model is available, and never at all when a
     * category is required — claiming an unknown id generates images would be
     * a guess the user pays for.
     *
     * @param string                 $provider   Provider id.
     * @param array<int,string>|null $candidates Optional externally fetched ids.
     * @param string|null            $category   Restrict to this category, e.g. 'image'.
     * @return string|null
     */
    public static function getPreferredModel(string $provider, ?array $candidates = null, ?string $category = null): ?string {
        self::ensureInitialised();

        if ($candidates === null) {
            $candidates = self::getModelsByProvider($provider);
        }

        $known = [];
        $unknown = [];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $config = self::getModelConfig($candidate);

            if ($config === null || ($config['provider'] ?? null) !== $provider) {
                $unknown[] = $candidate;
                continue;
            }

            $modelCategory = $config['category'] ?? 'text';

            if ($category !== null) {
                if ($modelCategory !== $category) {
                    continue;
                }
            } elseif ($modelCategory === 'image') {
                continue;
            }

            $known[] = $candidate;
        }

        if (!empty($known)) {
            $isImage = ($category === 'image');
            usort($known, static function ($a, $b) use ($isImage) {
                return self::compareByRank($a, $b, $isImage);
            });

            return $known[0];
        }

        if ($category !== null) {
            // Nothing the registry can vouch for. Fall back to what the
            // registry itself knows this provider offers in that category
            // rather than gambling on an undescribed id.
            $seeded = self::getModelsByProvider($provider);
            return $seeded === $candidates ? null : self::getPreferredModel($provider, $seeded, $category);
        }

        return $unknown[0] ?? null;
    }

    /**
     * Best text model for a provider.
     *
     * @param string                 $provider   Provider id.
     * @param array<int,string>|null $candidates Optional externally fetched ids.
     * @return string|null
     */
    public static function getPreferredTextModel(string $provider, ?array $candidates = null): ?string {
        return self::getPreferredModel($provider, $candidates);
    }

    /**
     * Best image model for a provider, or null when it has none.
     *
     * @param string                 $provider   Provider id.
     * @param array<int,string>|null $candidates Optional externally fetched ids.
     * @return string|null
     */
    public static function getPreferredImageModel(string $provider, ?array $candidates = null): ?string {
        return self::getPreferredModel($provider, $candidates, 'image');
    }

    /**
     * Rank two models for the purpose of choosing a default.
     *
     * Curated priority alone is not enough. A model discovered live from a
     * provider's /models endpoint is registered with the default priority of
     * 10, so ranking on priority hands the default to whichever older model
     * this file happens to have been seeded with — claude-sonnet-4-5 beating
     * claude-opus-5 is not a defensible answer to "the most capable model".
     *
     * For text, generation leads and curated priority breaks the tie. This is
     * the same convention the settings picker already sorts by, and for the
     * same reason: a newly released model must not sink beneath an older one
     * merely because it is newer than this file.
     *
     * For images, curated priority leads. Image ids version independently per
     * family — dall-e-3 is not a later generation than gpt-image-1 — so a
     * version comparison across them means nothing.
     *
     * @param string $a       Canonical model id.
     * @param string $b       Canonical model id.
     * @param bool   $isImage Rank as image models.
     * @return int
     */
    private static function compareByRank(string $a, string $b, bool $isImage = false): int {
        $metaA = self::getModelConfig($a) ?? [];
        $metaB = self::getModelConfig($b) ?? [];

        $priorityA = (int) ($metaA['priority'] ?? 0);
        $priorityB = (int) ($metaB['priority'] ?? 0);

        if (!$isImage) {
            $versionA = self::modelGeneration($a);
            $versionB = self::modelGeneration($b);
            if ($versionA !== $versionB) {
                return $versionB <=> $versionA;
            }

            $tierA = self::capabilityTier($a);
            $tierB = self::capabilityTier($b);
            if ($tierA !== $tierB) {
                return $tierB <=> $tierA;
            }
        }

        if ($priorityA !== $priorityB) {
            return $priorityB <=> $priorityA;
        }

        $dateA = isset($metaA['released']) ? strtotime((string) $metaA['released']) : 0;
        $dateB = isset($metaB['released']) ? strtotime((string) $metaB['released']) : 0;
        if ($dateA !== $dateB) {
            return $dateB <=> $dateA;
        }

        return strcmp($a, $b);
    }

    /**
     * Order a fetched model list the way a picker should present it.
     *
     * Seeded registry order sorts on curated priority, which strands any model
     * newer than this file at the bottom of the dropdown — the exact place a
     * user looks last for the current flagship. Display order is therefore
     * derived from the ids themselves, newest first:
     *
     * 1. The provider's mainline family (the leading token that dominates the
     *    list — gemini above gemma and imagen, gpt above o-series and dall-e)
     *    ranks above side families, so a big version number on a side family
     *    cannot push the mainline out of sight.
     * 2. Text models rank above image models, which version independently.
     * 3. Within a group, compareByRank(): generation, capability tier, curated
     *    priority, release date.
     *
     * @param array<int,string> $models Model ids in any order.
     * @return array<int,string> The same ids, newest and most relevant first.
     */
    public static function sortModelsForDisplay(array $models): array {
        self::ensureInitialised();

        $models = array_values(array_unique(array_filter(array_map('strval', $models), static function ($id) {
            return $id !== '';
        })));

        if (count($models) < 2) {
            return $models;
        }

        $familyOf = static function (string $id): string {
            return preg_match('/^([a-z]+)/i', $id, $m) ? strtolower($m[1]) : '';
        };

        $counts = [];
        foreach ($models as $id) {
            $family = $familyOf($id);
            if ($family !== '') {
                $counts[$family] = ($counts[$family] ?? 0) + 1;
            }
        }
        arsort($counts);
        $mainFamily = $counts ? (string) key($counts) : '';

        usort($models, static function (string $a, string $b) use ($familyOf, $mainFamily): int {
            $mainA = (int) ($mainFamily !== '' && $familyOf($a) === $mainFamily);
            $mainB = (int) ($mainFamily !== '' && $familyOf($b) === $mainFamily);
            if ($mainA !== $mainB) {
                return $mainB <=> $mainA;
            }

            $imageA = (int) ((self::getModelConfig($a)['category'] ?? 'text') === 'image');
            $imageB = (int) ((self::getModelConfig($b)['category'] ?? 'text') === 'image');
            if ($imageA !== $imageB) {
                return $imageA <=> $imageB;
            }

            return self::compareByRank($a, $b, $imageA === 1);
        });

        return $models;
    }

    /**
     * Highest generation number embedded in a model id.
     *
     * gpt-5 => 5.0, claude-opus-4-8 => 4.8, gemini-2.5-pro => 2.5.
     *
     * Date fragments are not versions and must not be read as one. Both shapes
     * providers actually ship are rejected: the packed stamp in
     * claude-sonnet-4-5-20250929, and the zero-padded month in
     * gemini-2.5-flash-preview-09-2025 — which otherwise reads as generation 9
     * and makes a fast preview model outrank every flagship in the list.
     *
     * @param string $model Canonical model id.
     * @return float
     */
    private static function modelGeneration(string $model): float {
        $version = 0.0;

        if (!preg_match_all('/(\d+)(?:[.-](\d+))?/', $model, $matches, PREG_SET_ORDER)) {
            return $version;
        }

        foreach ($matches as $found) {
            $major = $found[1];

            // Zero-padded: a month or a day, never a generation number.
            if (strlen($major) > 1 && $major[0] === '0') {
                continue;
            }

            // A bare year, or a packed date stamp.
            if ((float) $major >= 1000) {
                continue;
            }

            $minor = (isset($found[2]) && strlen($found[2]) < 3 && $found[2][0] !== '0')
                ? (float) ('0.' . $found[2])
                : 0.0;

            $candidate = (float) $major + $minor;

            if ($candidate > $version) {
                $version = $candidate;
            }
        }

        return $version;
    }

    /**
     * Where a model sits in its family's capability ladder.
     *
     * Read from the naming convention every provider uses rather than from a
     * list of model ids, so it keeps working for models that do not exist yet:
     * a plain flagship id carries no tier word at all, and the cheap tiers
     * announce themselves (nano, lite, mini, haiku, flash).
     *
     * Matched on whole id segments, never as substrings. "gemini" contains the
     * letters of "mini", which as a substring test rated every Gemini Pro model
     * a cheap tier and handed the default to Gemini Flash.
     *
     * @param string $model Canonical model id.
     * @return int Higher is more capable.
     */
    private static function capabilityTier(string $model): int {
        $segments = preg_split('/[-_.]+/', strtolower($model)) ?: [];

        $has = static function (array $words) use ($segments) {
            return !empty(array_intersect($words, $segments));
        };

        if ($has(['nano'])) {
            return 0;
        }

        // Agentic research models (Deep Research and friends) are a side
        // family, not a chat default: they take minutes per answer and bill
        // accordingly. They must never outrank the mainline flagship or fast
        // tier, however high a version number they carry.
        if ($has(['research'])) {
            return 0;
        }

        if ($has(['lite', 'mini', 'haiku', 'small'])) {
            return 1;
        }

        // Flash is the fast tier; fable and mythos are special-purpose models
        // rather than the general-purpose default for a site.
        if ($has(['flash', 'fable', 'mythos', 'turbo'])) {
            return 2;
        }

        if ($has(['opus', 'pro', 'ultra', 'max'])) {
            return 4;
        }

        // Sonnet, and any flagship id that names no tier at all (gpt-5).
        return 3;
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
     * Endpoint for a model the caller gave no hint for.
     *
     * Per model rather than per provider, because a handful of OpenAI
     * models are reachable only on the Responses API — sending one to Chat
     * Completions fails before any parameter is even looked at.
     *
     * @param string $provider Provider id.
     * @param string $model    Canonical model id.
     * @return string
     */
    public static function inferEndpoint(string $provider, string $model): string {
        if ($provider === 'openai' && self::isOpenAIResponsesOnly($model)) {
            return 'responses';
        }

        return self::getDefaultEndpointForProvider($provider);
    }

    /**
     * Models OpenAI documents as reachable only on the Responses API.
     *
     * Taken from the ResponsesOnlyModel enum in OpenAI's published OpenAPI
     * spec and the per-model "Endpoints" tables. Chat Completions is the
     * safe default for everything else, so this stays an explicit list —
     * guessing a family here would strand models that Chat Completions
     * serves perfectly well.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    private static function isOpenAIResponsesOnly(string $model): bool {
        $responsesOnly = [
            'o1-pro',
            'o3-pro',
            'o3-deep-research',
            'o4-mini-deep-research',
            'computer-use-preview',
            'gpt-5-codex',
            'gpt-5-pro',
            'gpt-5.1-codex-max',
            'gpt-5.2-pro',
            'gpt-5.3-codex',
            'gpt-5.6-cyber',
            'gpt-daybreak-blue-latest',
            'gpt-daybreak-red-latest',
        ];

        foreach ($responsesOnly as $prefix) {
            // Prefix match so dated snapshots (o3-pro-2025-06-10) resolve too.
            if (strpos($model, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parameter contract for a model, derived from its family.
     *
     * This is the safety net for every model id the base definitions do not
     * list — a dynamically discovered one, or a saved selection from a
     * provider that has since shipped a new family. The old behaviour was a
     * single flat guess per provider, which sent OpenAI's deprecated
     * max_tokens to every unrecognised model and earned a hard 400 on the
     * reasoning families.
     *
     * Two rules govern everything below. The token parameter is named for
     * the endpoint that will actually carry the request. Every other
     * parameter is declared only where the provider documents support for
     * it, because omitting an optional parameter can never break a request
     * whereas sending a withdrawn one is a guaranteed 400.
     *
     * @param string      $provider Provider id.
     * @param string      $model    Canonical model id.
     * @param string|null $endpoint Endpoint the request will use.
     * @return array<string,array<string,mixed>>
     */
    public static function inferParameterSchema(string $provider, string $model, ?string $endpoint = null): array {
        $endpoint = $endpoint ?? self::inferEndpoint($provider, $model);

        switch ($provider) {
            case 'anthropic':
                return self::anthropicParameterSchema($model);
            case 'gemini':
                return self::geminiParameterSchema($model);
            case 'grok':
                return self::grokParameterSchema($model);
            case 'openai':
            default:
                return self::openAIParameterSchema($model, $endpoint);
        }
    }

    /**
     * Build a number parameter definition.
     *
     * @param float       $min        Minimum.
     * @param float       $max        Maximum.
     * @param float       $default    Default value.
     * @param float       $step       Step; below 1 marks the value as a float.
     * @param string      $requestKey Wire key, dot-notation for nesting.
     * @param string      $label      UI label.
     * @param string      $help       UI help text.
     * @return array<string,mixed>
     */
    private static function numberParameter(float $min, float $max, float $default, float $step, string $requestKey, string $label, string $help = ''): array {
        return [
            'type' => 'number',
            'label' => $label,
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'default' => $default,
            'request_key' => $requestKey,
            'help' => $help,
        ];
    }

    /**
     * OpenAI contract for a model the base definitions do not list.
     *
     * @param string $model    Canonical model id.
     * @param string $endpoint Endpoint the request will use.
     * @return array<string,array<string,mixed>>
     */
    private static function openAIParameterSchema(string $model, string $endpoint): array {
        // Responses names the ceiling max_output_tokens; Chat Completions
        // names it max_completion_tokens. max_tokens is deprecated on Chat
        // Completions and is documented as incompatible with the o-series,
        // so it is never the choice for a model we cannot place.
        $requestKey = $endpoint === 'responses' ? 'max_output_tokens' : 'max_completion_tokens';

        $parameters = [
            'max_tokens' => self::numberParameter(
                1,
                128000,
                4096,
                1,
                $requestKey,
                'Max Output Tokens',
                'Upper bound on generated tokens, including reasoning tokens.'
            ),
        ];

        if (self::openAIAcceptsSampling($model)) {
            $parameters['temperature'] = self::numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature');
        }

        return $parameters;
    }

    /**
     * Does this OpenAI model still take sampling parameters?
     *
     * The reasoning families reject them — sometimes as an unsupported
     * value, sometimes as an unsupported parameter, so even the documented
     * default of 1 is not safe to send. OpenAI's current reasoning guide no
     * longer enumerates which parameters go, so this is an allow list of
     * the families known to keep them rather than a deny list of the ones
     * that dropped them: an id nobody recognises declines.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    private static function openAIAcceptsSampling(string $model): bool {
        // o-series reasoning models.
        if (preg_match('/^o[0-9]/', $model)) {
            return false;
        }

        // GPT-5 and everything after it. The *-chat-latest variants are not
        // reasoning models, but no documentation confirms they still take
        // temperature, so they decline with the rest of the generation.
        if (preg_match('/^gpt-([5-9]|\d{2})/', $model)) {
            return false;
        }

        return preg_match('/^(gpt-3\.5|gpt-4|chatgpt-4o)/', $model) === 1;
    }

    /**
     * Anthropic contract for a model the base definitions do not list.
     *
     * @param string $model Canonical model id.
     * @return array<string,array<string,mixed>>
     */
    private static function anthropicParameterSchema(string $model): array {
        // max_tokens is required on the Messages API and is spelled the
        // same on every model. The old provider-wide default named it
        // max_output_tokens, which is an OpenAI key: every dynamically
        // discovered Claude model was registered unusable.
        $parameters = [
            'max_tokens' => self::numberParameter(
                1,
                self::anthropicMaxOutputTokens($model),
                4096,
                1,
                'max_tokens',
                'Max Output Tokens',
                'Required by the Anthropic Messages API.'
            ),
        ];

        if (self::anthropicAcceptsSampling($model)) {
            $parameters['temperature'] = self::numberParameter(0.0, 1.0, 0.7, 0.01, 'temperature', 'Temperature');
        }

        if (self::anthropicAcceptsEffort($model)) {
            $options = [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ];

            if (self::anthropicAcceptsExtendedEffort($model)) {
                $options[] = ['value' => 'xhigh', 'label' => 'Extra High'];
            }

            $parameters['effort'] = [
                'type' => 'select',
                'label' => 'Effort',
                'options' => $options,
                'default' => 'high',
                'request_key' => 'output_config.effort',
                'help' => 'Controls thinking depth and overall token spend.',
            ];
        }

        return $parameters;
    }

    /**
     * Approximate generation number for a Claude id (0 when unplaceable).
     *
     * claude-opus-4-8 => 4.8, claude-sonnet-5 => 5.0, claude-3-7-sonnet => 3.7.
     *
     * @param string $model Canonical model id.
     * @return float
     */
    public static function anthropicGeneration(string $model): float {
        // Current naming: claude-{family}-{major}[-{minor}][-{date}]
        if (preg_match('/^claude-(?:opus|sonnet|haiku|fable|mythos)-(\d+)(?:-(\d+))?/', $model, $matches)) {
            $minor = isset($matches[2]) && strlen($matches[2]) < 3 ? (int) $matches[2] : 0;
            return (float) $matches[1] + ($minor / 10);
        }

        // Legacy naming: claude-3-7-sonnet-20250219
        if (preg_match('/^claude-(\d+)(?:-(\d+))?-(?:opus|sonnet|haiku)/', $model, $matches)) {
            return (float) $matches[1] + ((int) ($matches[2] ?? 0) / 10);
        }

        return 0.0;
    }

    /**
     * Does this Claude model still accept temperature/top_p/top_k?
     *
     * Anthropic documents that non-default sampling values return a 400 on
     * every request, regardless of thinking, for Fable 5, Mythos 5, Mythos
     * Preview, Opus 5, Opus 4.8, Opus 4.7 and Sonnet 5. An id whose family
     * cannot be placed declines, because omitting is always safe.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    public static function anthropicAcceptsSampling(string $model): bool {
        if (preg_match('/(fable|mythos)/', $model)) {
            return false;
        }

        $generation = self::anthropicGeneration($model);

        if ($generation <= 0.0) {
            return false;
        }

        if (strpos($model, 'opus') !== false) {
            return $generation < 4.7;
        }

        // Sonnet, Haiku and the Claude 3 families kept sampling parameters
        // until the 5 generation.
        return $generation < 5.0;
    }

    /**
     * Does this Claude model accept output_config.effort?
     *
     * Documented on Fable 5, Mythos 5, Mythos Preview, Opus 5, Opus 4.8,
     * Opus 4.7, Opus 4.6, Opus 4.5, Sonnet 5 and Sonnet 4.6. Haiku and the
     * Claude 3 families do not take it.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    public static function anthropicAcceptsEffort(string $model): bool {
        if (strpos($model, 'haiku') !== false) {
            return false;
        }

        if (preg_match('/(fable|mythos)/', $model)) {
            return true;
        }

        $generation = self::anthropicGeneration($model);

        if (strpos($model, 'opus') !== false) {
            return $generation >= 4.5;
        }

        if (strpos($model, 'sonnet') !== false) {
            return $generation >= 4.6;
        }

        return false;
    }

    /**
     * Does this Claude model accept the xhigh effort level?
     *
     * Documented for Fable 5, Mythos 5, Opus 5, Opus 4.8, Opus 4.7 and
     * Sonnet 5 only. Offering it elsewhere would hand the user a value the
     * model rejects.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    private static function anthropicAcceptsExtendedEffort(string $model): bool {
        if (preg_match('/(fable|mythos)-5/', $model)) {
            return true;
        }

        $generation = self::anthropicGeneration($model);

        if (strpos($model, 'opus') !== false) {
            return $generation >= 4.7;
        }

        if (strpos($model, 'sonnet') !== false) {
            return $generation >= 5.0;
        }

        return false;
    }

    /**
     * Honest output-token ceiling for a Claude family.
     *
     * @param string $model Canonical model id.
     * @return int
     */
    private static function anthropicMaxOutputTokens(string $model): int {
        $generation = self::anthropicGeneration($model);

        if (strpos($model, 'haiku') !== false) {
            return $generation >= 4.0 ? 64000 : 8192;
        }

        if ($generation >= 4.6) {
            return 128000;
        }

        if ($generation >= 4.0) {
            return 64000;
        }

        // Unplaceable ids included: a conservative ceiling is always a
        // valid request, an inflated one is a 400.
        return 8192;
    }

    /**
     * Gemini contract for a model the base definitions do not list.
     *
     * @param string $model Canonical model id.
     * @return array<string,array<string,mixed>>
     */
    private static function geminiParameterSchema(string $model): array {
        $parameters = [
            'max_tokens' => self::numberParameter(
                1,
                65536,
                4096,
                1,
                'generationConfig.maxOutputTokens',
                'Max Output Tokens'
            ),
        ];

        // Google's Gemini 3 guidance is explicit: "temperature, top_p, and
        // top_k are no longer recommended for all Gemini 3.x models. Remove
        // these parameters from all requests." Whether they hard-fail or
        // are ignored is not documented, so the 3.x line simply omits them.
        if (self::geminiAcceptsSampling($model)) {
            $parameters['temperature'] = self::numberParameter(0.0, 2.0, 0.7, 0.01, 'generationConfig.temperature', 'Temperature');
            $parameters['top_p'] = self::numberParameter(0.0, 1.0, 1.0, 0.01, 'generationConfig.topP', 'Top P');
        }

        return $parameters;
    }

    /**
     * Does this Gemini model still take temperature/topP/topK?
     *
     * Positive identification only: the sampling controls survive on the
     * 1.x and 2.x generations and are withdrawn from 3.x onward, so an id
     * carrying no recognisable generation declines rather than guesses.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    private static function geminiAcceptsSampling(string $model): bool {
        if (preg_match('/^gemini-(\d+)/', $model, $matches) !== 1) {
            return false;
        }

        return (int) $matches[1] < 3;
    }

    /**
     * xAI Grok contract for a model the base definitions do not list.
     *
     * @param string $model Canonical model id.
     * @return array<string,array<string,mixed>>
     */
    private static function grokParameterSchema(string $model): array {
        // xAI marks max_tokens deprecated in favour of max_completion_tokens
        // on its chat completions endpoint. Both are currently accepted, so
        // the current spelling is the one to grow into.
        $parameters = [
            'max_tokens' => self::numberParameter(
                1,
                64000,
                4096,
                1,
                'max_completion_tokens',
                'Max Output Tokens'
            ),
        ];

        // xAI documents frequency_penalty, presence_penalty and stop as
        // unsupported on reasoning models, but places no restriction on
        // temperature or top_p.
        $parameters['temperature'] = self::numberParameter(0.0, 2.0, 0.7, 0.01, 'temperature', 'Temperature');

        return $parameters;
    }

    /**
     * Is this Grok model one of the reasoning family?
     *
     * Reasoning models reject frequency_penalty, presence_penalty and stop.
     *
     * @param string $model Canonical model id.
     * @return bool
     */
    public static function grokIsReasoningModel(string $model): bool {
        return strpos($model, 'reasoning') !== false
            || strpos($model, 'multi-agent') !== false
            || preg_match('/^grok-(4|[5-9])/', $model) === 1;
    }
}
