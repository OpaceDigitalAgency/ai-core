<?php
/**
 * AI-Core Validator Class
 * 
 * Handles API key validation and testing
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Validator Class
 * 
 * Validates API keys and tests connections
 */
class AI_Core_Validator {
    
    /**
     * Class instance
     * 
     * @var AI_Core_Validator
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Validator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }
    
    /**
     * Validate API key for a provider
     * 
     * @param string $provider Provider name
     * @param string $api_key API key to validate
     * @return array Validation result
     */
    public function validate_api_key($provider, $api_key) {
        if (empty($api_key)) {
            return array(
                'valid' => false,
                'error' => __('API key is empty', 'opace-ai-core-integration-hub-prompt-engine')
            );
        }

        if (class_exists('\\AICore\\Registry\\ModelRegistry')
            && !\AICore\Registry\ModelRegistry::isProviderSupported($provider)) {
            return array(
                'valid' => false,
                'error' => __('Provider not supported', 'opace-ai-core-integration-hub-prompt-engine')
            );
        }

        try {
            // Initialize AI-Core with the API key
            $config = array($provider . '_api_key' => $api_key);
            \AICore\AICore::init($config);

            // Get the provider instance
            $provider_instance = $this->get_provider_instance($provider, $api_key);

            if (!$provider_instance) {
                return array(
                    'valid' => false,
                    'error' => __('Provider not supported', 'opace-ai-core-integration-hub-prompt-engine')
                );
            }

            // Validate using provider's method
            if (method_exists($provider_instance, 'validateApiKey')) {
                $result = $provider_instance->validateApiKey();
            } else {
                // Fallback: try a simple request
                $result = $this->test_with_request($provider_instance);
            }

            // A key that checks out is the moment this provider becomes usable,
            // so it is the moment to give it sensible defaults. Applied here
            // rather than in the AJAX handler so the Test Key button and the
            // paste-to-save path behave identically.
            if (!empty($result['valid'])) {
                $result['defaults'] = $this->apply_provider_defaults($provider, $api_key);
            }

            return $result;

        } catch (\Exception $e) {
            return array(
                'valid' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get provider instance
     * 
     * @param string $provider Provider name
     * @param string $api_key API key
     * @return object|null Provider instance
     */
    private function get_provider_instance($provider, $api_key) {
        switch ($provider) {
            case 'openai':
                return new \AICore\Providers\OpenAIProvider($api_key);
            case 'anthropic':
                return new \AICore\Providers\AnthropicProvider($api_key);
            case 'gemini':
                return new \AICore\Providers\GeminiProvider($api_key);
            default:
                return null;
        }
    }

    /**
     * Choose this provider's defaults the first time its key checks out.
     *
     * Only ever fills a blank: a model the user picked themselves is never
     * overwritten, and neither is one set by an earlier validation. "Best" is
     * whatever the registry ranks highest for that provider — text always,
     * images too where the provider has an image family at all. Anthropic has
     * none, so it gets a text default and no image default, which is the
     * honest answer rather than a model that would 404 on first use.
     *
     * @param string $provider Provider name
     * @param string $api_key  Validated API key
     * @return array Defaults applied, keyed 'model' and 'image_model'
     */
    public function apply_provider_defaults($provider, $api_key = null) {
        $applied = array();

        if (!class_exists('\\AICore\\Registry\\ModelRegistry')
            || !\AICore\Registry\ModelRegistry::isProviderSupported($provider)) {
            return $applied;
        }

        $settings = get_option('ai_core_settings', array());
        $settings = is_array($settings) ? $settings : array();
        $changed  = false;

        // The live list is authoritative about what this key can actually
        // reach; the registry is authoritative about which of those is best.
        $available = $this->get_available_models($provider, $api_key);

        if (!isset($settings['provider_models']) || !is_array($settings['provider_models'])) {
            $settings['provider_models'] = array();
        }

        if (empty($settings['provider_models'][$provider])) {
            $model = \AICore\Registry\ModelRegistry::getPreferredTextModel($provider, $available ?: null);
            if (!empty($model)) {
                $settings['provider_models'][$provider] = $model;
                $applied['model'] = $model;
                $changed = true;
            }
        }

        if (!isset($settings['provider_image_models']) || !is_array($settings['provider_image_models'])) {
            $settings['provider_image_models'] = array();
        }

        if (empty($settings['provider_image_models'][$provider])
            && \AICore\Registry\ModelRegistry::providerSupportsImages($provider)) {
            $image_model = \AICore\Registry\ModelRegistry::getPreferredImageModel($provider, $available ?: null);
            if (!empty($image_model)) {
                $settings['provider_image_models'][$provider] = $image_model;
                $applied['image_model'] = $image_model;
                $changed = true;
            }
        }

        if (empty($settings['default_provider'])) {
            $settings['default_provider'] = $provider;
            $changed = true;
        }

        if ($changed) {
            update_option('ai_core_settings', $settings);
        }

        return $applied;
    }
    
    /**
     * Test provider with a simple request
     * 
     * @param object $provider Provider instance
     * @return array Test result
     */
    private function test_with_request($provider) {
        try {
            $messages = array(
                array('role' => 'user', 'content' => 'Hello')
            );
            
            $response = $provider->sendRequest($messages, array('max_tokens' => 10));
            
            return array(
                'valid' => true,
                'provider' => $provider->getName()
            );
            
        } catch (Exception $e) {
            return array(
                'valid' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get available models for a provider
     *
     * Models are ALWAYS fetched from provider APIs when possible.
     * Results are cached for 1 hour by default to avoid excessive API calls.
     * Set force_refresh=true to bypass cache and get latest models.
     *
     * @param string $provider Provider name
     * @param string|null $api_key Optional API key (uses saved key if not provided)
     * @param bool $force_refresh Force refresh from API, bypassing cache
     * @return array List of models
     */
    public function get_available_models($provider, $api_key = null, $force_refresh = false) {
        $settings = get_option('ai_core_settings', array());

        if (null === $api_key || '' === $api_key) {
            $api_key = $settings[$provider . '_api_key'] ?? '';
        }

        if (empty($api_key)) {
            return array();
        }

        // ALWAYS cache model lists - this is not optional
        // Cache duration: 1 hour default, configurable via settings
        $cache_duration = isset($settings['cache_duration']) ? absint($settings['cache_duration']) : HOUR_IN_SECONDS;
        $cache_duration = $cache_duration > 0 ? $cache_duration : HOUR_IN_SECONDS;
        $cache_key = AI_Core_Model_Defaults::cache_key($provider, $api_key);

        // Check cache first (unless force refresh)
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false && is_array($cached) && !empty($cached)) {
                // Re-sort on the way out, never trust the stored order: a
                // cache written under an older ranking (or by an older
                // build) would otherwise pin a stale order on every screen
                // until the transient expires.
                return \AICore\Registry\ModelRegistry::sortModelsForDisplay($cached);
            }
        } else {
            delete_transient($cache_key);
        }

        try {
            $provider_instance = $this->get_provider_instance($provider, $api_key);

            if (!$provider_instance || !method_exists($provider_instance, 'getAvailableModels')) {
                $fallback = \AICore\Registry\ModelRegistry::getModelsByProvider($provider);
                set_transient($cache_key, $fallback, $cache_duration);
                return $fallback;
            }

            $models = $provider_instance->getAvailableModels();

            // If API returned models, cache and return them
            if (!empty($models)) {
                set_transient($cache_key, $models, $cache_duration);
                return $models;
            }

            // Empty result from API - use fallback but with shorter cache
            $fallback = \AICore\Registry\ModelRegistry::getModelsByProvider($provider);
            set_transient($cache_key, $fallback, 5 * MINUTE_IN_SECONDS);
            return $fallback;

        } catch (Exception $e) {
            // On error, use fallback with short cache so we retry soon
            $fallback = \AICore\Registry\ModelRegistry::getModelsByProvider($provider);
            set_transient($cache_key, $fallback, 5 * MINUTE_IN_SECONDS);
            return $fallback;
        }
    }
}
