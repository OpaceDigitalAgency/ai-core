<?php
/**
 * AI-Core Library - Model Sorter Utility
 * 
 * Intelligently sorts models by relevance, prioritising latest/newest models
 * 
 * @package AI_Core
 * @version 1.0.0
 */

namespace AICore\Utils;

class ModelSorter {
    
    /**
     * Model priority tiers (higher number = higher priority)
     * 
     * @var array
     */
    private static $priority_patterns = [
        // OpenAI - Latest models first
        '/^gpt-5/' => 1000,
        '/^o4-/' => 950,
        '/^o3-/' => 900,
        '/^gpt-4\.1/' => 850,
        '/^gpt-4o/' => 800,
        '/^chatgpt-4o-latest/' => 790,
        '/^gpt-4o-mini/' => 780,
        '/^gpt-4\.5/' => 770,
        '/^gpt-4-turbo/' => 750,
        '/^gpt-4-/' => 700,
        '/^gpt-3\.5-turbo/' => 500,
        '/^gpt-3\.5/' => 400,
        
        // Anthropic - Latest models first
        '/^claude-sonnet-4-5/' => 1000,
        '/^claude-opus-4-1/' => 950,
        '/^claude-sonnet-4/' => 900,
        '/^claude-opus-4/' => 890,
        '/^claude-3-7-sonnet/' => 850,
        '/^claude-3-5-sonnet/' => 800,
        '/^claude-3-5-haiku/' => 750,
        '/^claude-3-opus/' => 700,
        '/^claude-3-sonnet/' => 650,
        '/^claude-3-haiku/' => 600,
        '/^claude-2/' => 400,
        '/^claude-instant/' => 300,
        
        // Gemini - Latest models first
        '/^gemini-2\.5-flash/' => 1000,
        '/^gemini-2\.5-pro/' => 950,
        '/^gemini-2\.0-flash/' => 900,
        '/^gemini-1\.5-pro/' => 700,
        '/^gemini-1\.5-flash/' => 650,
        '/^gemini-1\.0-pro/' => 500,
        
        // Grok - Latest models first
        '/^grok-4-fast/' => 1000,
        '/^grok-4-/' => 950,
        '/^grok-3/' => 800,
        '/^grok-2/' => 700,
        '/^grok-beta/' => 600,
    ];
    
    /**
     * Deprecated model patterns (lower priority)
     * 
     * @var array
     */
    private static $deprecated_patterns = [
        '/^gpt-3\.5-turbo-0301$/',
        '/^gpt-3\.5-turbo-0613$/',
        '/^gpt-4-0314$/',
        '/^gpt-4-0613$/',
        '/^claude-instant-1/',
        '/^claude-2\.0/',
        '/davinci/',
        '/curie/',
        '/babbage/',
        '/ada/',
    ];
    
    /**
     * Sort models intelligently
     * 
     * @param array $models Array of model identifiers
     * @param string $provider Provider name (optional, for provider-specific sorting)
     * @return array Sorted array of model identifiers
     */
    public static function sort(array $models, string $provider = ''): array {
        if (empty($models)) {
            return $models;
        }
        
        // Create array with models and their priorities
        $model_priorities = [];
        foreach ($models as $model) {
            $model_priorities[$model] = self::calculatePriority($model);
        }
        
        // Sort by key using priorities (desc), then alphabetically on model id
        uksort($model_priorities, function($modelA, $modelB) use ($model_priorities) {
            $pa = $model_priorities[$modelA] ?? 0;
            $pb = $model_priorities[$modelB] ?? 0;
            if ($pa !== $pb) {
                return $pb <=> $pa; // Higher priority first
            }
            return strcmp($modelA, $modelB);
        });
        
        return array_keys($model_priorities);
    }
    
    /**
     * Calculate priority for a model
     * 
     * @param string $model Model identifier
     * @return int Priority score
     */
    private static function calculatePriority(string $model): int {
        // Check if deprecated
        foreach (self::$deprecated_patterns as $pattern) {
            if (preg_match($pattern, $model)) {
                return 100; // Low priority for deprecated models
            }
        }
        
        // Check priority patterns
        foreach (self::$priority_patterns as $pattern => $priority) {
            if (preg_match($pattern, $model)) {
                return $priority;
            }
        }
        
        // Default priority for unknown models
        return 500;
    }
    
    /**
     * Get recommended default model for a provider
     * 
     * @param array $models Available models
     * @param string $provider Provider name
     * @return string|null Recommended model or null if no models
     */
    public static function getRecommendedDefault(array $models, string $provider): ?string {
        if (empty($models)) {
            return null;
        }
        
        $sorted = self::sort($models, $provider);
        
        // Return the highest priority model
        return $sorted[0] ?? null;
    }
    
    /**
     * Check if a model is deprecated
     * 
     * @param string $model Model identifier
     * @return bool True if model is deprecated
     */
    public static function isDeprecated(string $model): bool {
        foreach (self::$deprecated_patterns as $pattern) {
            if (preg_match($pattern, $model)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get model metadata for display
     * 
     * @param string $model Model identifier
     * @return array Metadata array
     */
    public static function getModelMetadata(string $model): array {
        $metadata = [
            'id' => $model,
            'priority' => self::calculatePriority($model),
            'deprecated' => self::isDeprecated($model),
            'display_name' => $model,
        ];
        
        // Add badges/labels
        if ($metadata['deprecated']) {
            $metadata['badge'] = 'Deprecated';
            $metadata['badge_class'] = 'deprecated';
        } elseif ($metadata['priority'] >= 900) {
            $metadata['badge'] = 'Latest';
            $metadata['badge_class'] = 'latest';
        } elseif ($metadata['priority'] >= 700) {
            $metadata['badge'] = 'Recommended';
            $metadata['badge_class'] = 'recommended';
        }
        
        return $metadata;
    }
}

