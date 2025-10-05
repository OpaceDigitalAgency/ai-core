<?php
/**
 * AI-Core Library - Model Capabilities Registry
 * 
 * Defines model-specific and provider-specific parameter capabilities
 * 
 * @package AI_Core
 * @version 1.0.0
 */

namespace AICore\Registry;

class ModelCapabilities {
    
    /**
     * Get supported parameters for a model
     * 
     * @param string $model Model identifier
     * @param string $provider Provider name
     * @return array Array of supported parameters with their configurations
     */
    public static function getSupportedParameters(string $model, string $provider): array {
        // Check for model-specific overrides first
        $modelSpecific = self::getModelSpecificParameters($model);
        if (!empty($modelSpecific)) {
            return $modelSpecific;
        }
        
        // Fall back to provider defaults
        return self::getProviderDefaultParameters($provider);
    }
    
    /**
     * Get model-specific parameter configurations
     * 
     * @param string $model Model identifier
     * @return array|null Model-specific parameters or null if none defined
     */
    private static function getModelSpecificParameters(string $model): ?array {
        // OpenAI o-series models (reasoning models)
        if (preg_match('/^(o1|o3|o4)/', $model)) {
            return [
                'reasoning_effort' => [
                    'type' => 'select',
                    'label' => 'Reasoning Effort',
                    'options' => ['low', 'medium', 'high'],
                    'default' => 'medium',
                    'description' => 'Controls the amount of reasoning the model performs',
                ],
                'max_completion_tokens' => [
                    'type' => 'number',
                    'label' => 'Max Completion Tokens',
                    'min' => 1,
                    'max' => 100000,
                    'default' => 16000,
                    'description' => 'Maximum tokens in the completion',
                ],
            ];
        }
        
        return null;
    }
    
    /**
     * Get provider default parameters
     * 
     * @param string $provider Provider name
     * @return array Provider default parameters
     */
    private static function getProviderDefaultParameters(string $provider): array {
        switch ($provider) {
            case 'openai':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0.7,
                        'description' => 'Controls randomness. Lower is more focused, higher is more creative.',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Tokens',
                        'min' => 1,
                        'max' => 128000,
                        'default' => 4000,
                        'description' => 'Maximum tokens in the response',
                    ],
                    'top_p' => [
                        'type' => 'number',
                        'label' => 'Top P',
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                        'default' => 1.0,
                        'description' => 'Nucleus sampling parameter',
                    ],
                    'frequency_penalty' => [
                        'type' => 'number',
                        'label' => 'Frequency Penalty',
                        'min' => -2,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0,
                        'description' => 'Reduces repetition of tokens',
                    ],
                    'presence_penalty' => [
                        'type' => 'number',
                        'label' => 'Presence Penalty',
                        'min' => -2,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0,
                        'description' => 'Encourages new topics',
                    ],
                ];
                
            case 'anthropic':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                        'default' => 0.7,
                        'description' => 'Controls randomness. Lower is more focused, higher is more creative.',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Tokens',
                        'min' => 1,
                        'max' => 200000,
                        'default' => 4000,
                        'description' => 'Maximum tokens in the response (required for Anthropic)',
                    ],
                    'top_p' => [
                        'type' => 'number',
                        'label' => 'Top P',
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                        'default' => 1.0,
                        'description' => 'Nucleus sampling parameter',
                    ],
                    'top_k' => [
                        'type' => 'number',
                        'label' => 'Top K',
                        'min' => 0,
                        'max' => 500,
                        'default' => 0,
                        'description' => 'Top-k sampling parameter (0 = disabled)',
                    ],
                ];
                
            case 'gemini':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0.7,
                        'description' => 'Controls randomness. Lower is more focused, higher is more creative.',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Output Tokens',
                        'min' => 1,
                        'max' => 8192,
                        'default' => 4000,
                        'description' => 'Maximum tokens in the response',
                    ],
                    'top_p' => [
                        'type' => 'number',
                        'label' => 'Top P',
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                        'default' => 1.0,
                        'description' => 'Nucleus sampling parameter',
                    ],
                    'top_k' => [
                        'type' => 'number',
                        'label' => 'Top K',
                        'min' => 1,
                        'max' => 100,
                        'default' => 40,
                        'description' => 'Top-k sampling parameter',
                    ],
                ];
                
            case 'grok':
                return [
                    'temperature' => [
                        'type' => 'number',
                        'label' => 'Temperature',
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0.7,
                        'description' => 'Controls randomness. Lower is more focused, higher is more creative.',
                    ],
                    'max_tokens' => [
                        'type' => 'number',
                        'label' => 'Max Tokens',
                        'min' => 1,
                        'max' => 131072,
                        'default' => 4000,
                        'description' => 'Maximum tokens in the response',
                    ],
                    'top_p' => [
                        'type' => 'number',
                        'label' => 'Top P',
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                        'default' => 1.0,
                        'description' => 'Nucleus sampling parameter',
                    ],
                    'frequency_penalty' => [
                        'type' => 'number',
                        'label' => 'Frequency Penalty',
                        'min' => -2,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0,
                        'description' => 'Reduces repetition of tokens',
                    ],
                    'presence_penalty' => [
                        'type' => 'number',
                        'label' => 'Presence Penalty',
                        'min' => -2,
                        'max' => 2,
                        'step' => 0.1,
                        'default' => 0,
                        'description' => 'Encourages new topics',
                    ],
                ];
                
            default:
                return [];
        }
    }
    
    /**
     * Check if a model supports a specific parameter
     * 
     * @param string $model Model identifier
     * @param string $provider Provider name
     * @param string $parameter Parameter name
     * @return bool True if parameter is supported
     */
    public static function supportsParameter(string $model, string $provider, string $parameter): bool {
        $supported = self::getSupportedParameters($model, $provider);
        return isset($supported[$parameter]);
    }
    
    /**
     * Get default value for a parameter
     * 
     * @param string $model Model identifier
     * @param string $provider Provider name
     * @param string $parameter Parameter name
     * @return mixed Default value or null if not found
     */
    public static function getDefaultValue(string $model, string $provider, string $parameter) {
        $supported = self::getSupportedParameters($model, $provider);
        return $supported[$parameter]['default'] ?? null;
    }
}

