<?php
/**
 * Sensible per-provider defaults, chosen from the account's own model list.
 *
 * Adding a key used to leave both the text and the image model unset, so every
 * consumer had to invent its own answer and they disagreed. The hub owns
 * provider configuration, so it is the hub that decides: on the first save of
 * a key, the newest text model and the newest image model that account can
 * actually serve are recorded. The user can change either afterwards, and a
 * choice they have made is never overwritten.
 *
 * @package AI_Core
 * @since 0.7.8
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Core_Model_Defaults {

    /** Ids that name a modality which cannot produce prose. */
    const NOT_TEXT = '/(^|-)(image|imagen|tts|audio|speech|embedding|embed|veo|lyria|computer-use|live|rerank|guard|nano-banana|robotics|aqa)(-|$)/i';

    /** Ids that produce still images (not video, music or audio). */
    const IS_IMAGE = '/(^|-)(image|imagen|nano-banana)(-|$)/i';

    /** Image-adjacent ids that are not general image generators. */
    const NOT_IMAGE = '/(^|-)(veo|lyria|edit|upscale|tts|audio)(-|$)/i';

    /**
     * Special-purpose text ids (Deep Research and similar) that can prose but
     * must never be a site's default: minutes per answer, billed accordingly.
     * Preferred over only when nothing mainline is available.
     */
    const NICHE_TEXT = '/(^|-)(research|codex|cyber|thinking)(-|$)/i';

    /** @var string|null Family that dominates the list being ranked. */
    private static $main_family = null;

    /**
     * Fill in any missing text/image default for every provider with a key.
     *
     * @param array $settings The ai_core_settings array being saved.
     * @return array The same array with defaults filled in where they were absent.
     */
    public static function apply(array $settings) {
        if (!class_exists('\\AICore\\Registry\\ModelRegistry')) {
            return $settings;
        }

        $settings['provider_models']       = isset($settings['provider_models']) && is_array($settings['provider_models'])
            ? $settings['provider_models']
            : array();
        $settings['provider_image_models'] = isset($settings['provider_image_models']) && is_array($settings['provider_image_models'])
            ? $settings['provider_image_models']
            : array();

        foreach (\AICore\Registry\ModelRegistry::getSupportedProviders() as $provider) {
            if (empty($settings[$provider . '_api_key'])) {
                continue;
            }

            $models = self::list_models($provider, (string) $settings[$provider . '_api_key']);
            if (empty($models)) {
                continue;
            }

            // A model the user chose is never replaced; one that their account
            // no longer serves is, because it can only fail.
            $chosen = isset($settings['provider_models'][$provider]) ? (string) $settings['provider_models'][$provider] : '';
            if ('' === $chosen || !in_array($chosen, $models, true)) {
                $best = self::best_text_model($models, $provider);
                if ('' !== $best) {
                    $settings['provider_models'][$provider] = $best;
                }
            }

            $chosen_image = isset($settings['provider_image_models'][$provider]) ? (string) $settings['provider_image_models'][$provider] : '';
            if ('' === $chosen_image || !in_array($chosen_image, $models, true)) {
                $best_image = self::best_image_model($models, $provider);
                if ('' !== $best_image) {
                    $settings['provider_image_models'][$provider] = $best_image;
                } else {
                    // This account has no image model for this provider.
                    unset($settings['provider_image_models'][$provider]);
                }
            }
        }

        return $settings;
    }

    /**
     * The account's live model list, cached briefly so saving twice in a row
     * does not make two identical calls.
     *
     * @param string $provider Provider id.
     * @param string $api_key  Key for that provider.
     * @return array Model ids.
     */
    public static function list_models($provider, $api_key) {
        $cache_key = self::cache_key($provider, $api_key);
        $cached    = get_transient($cache_key);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $instance = self::provider_instance($provider, $api_key);
        if (null === $instance) {
            return array();
        }

        try {
            $models = $instance->getAvailableModels();
        } catch (\Exception $e) {
            return array();
        }

        if (!is_array($models) || empty($models)) {
            return array();
        }

        set_transient($cache_key, $models, HOUR_IN_SECONDS);

        return $models;
    }

    /**
     * Keep development mock discovery separate from real provider discovery.
     *
     * A cached mock list previously survived after AI_SCRIBE_MOCK was disabled,
     * making a validated Gemini account look text-only until somebody happened
     * to find and refresh a different screen. The schema token also invalidates
     * pre-capability caches once when this code is upgraded.
     *
     * @param string $provider Provider id.
     * @param string $api_key  Provider key (hashed; never stored in the name).
     * @return string Transient key.
     */
    public static function cache_key($provider, $api_key) {
        $mock_active = defined('AI_SCRIBE_MOCK') && AI_SCRIBE_MOCK
            && defined('AI_SCRIBE_AUTOMATED_TEST') && AI_SCRIBE_AUTOMATED_TEST;
        $context = $mock_active ? 'mock' : 'live';

        return 'ai_core_models_v2_' . $context . '_' . $provider . '_' . substr(md5($api_key), 0, 8);
    }

    /**
     * Newest text model in a list.
     *
     * @param array  $models   Model ids.
     * @param string $provider Provider id when known.
     * @return string
     */
    public static function best_text_model(array $models, $provider = '') {
        if ('' !== $provider && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $preferred = \AICore\Registry\ModelRegistry::getPreferredTextModel($provider, $models);
            if (is_string($preferred) && '' !== $preferred) {
                return $preferred;
            }
        }

        $usable = array();
        $niche  = array();
        foreach ($models as $id) {
            $id = (string) $id;
            if ('' === $id || preg_match(self::NOT_TEXT, $id)) {
                continue;
            }
            if (preg_match(self::NICHE_TEXT, $id)) {
                $niche[] = $id;
            } else {
                $usable[] = $id;
            }
        }

        $best = self::pick_newest($usable);

        return '' !== $best ? $best : self::pick_newest($niche);
    }

    /**
     * Newest image model in a list.
     *
     * @param array  $models   Model ids.
     * @param string $provider Provider id when known.
     * @return string
     */
    public static function best_image_model(array $models, $provider = '') {
        if ('' !== $provider && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $preferred = \AICore\Registry\ModelRegistry::getPreferredImageModel($provider, $models);
            if (is_string($preferred) && '' !== $preferred) {
                return $preferred;
            }
        }

        $usable = array();
        foreach ($models as $id) {
            $id = (string) $id;
            if ('' !== $id && preg_match(self::IS_IMAGE, $id) && !preg_match(self::NOT_IMAGE, $id)) {
                $usable[] = $id;
            }
        }

        return self::pick_newest($usable);
    }

    /**
     * Rank a list newest-first and return the winner.
     *
     * @param array $ids Candidate ids.
     * @return string
     */
    private static function pick_newest(array $ids) {
        if (empty($ids)) {
            return '';
        }

        self::$main_family = self::dominant_family($ids);
        usort($ids, array(__CLASS__, 'compare'));

        return $ids[0];
    }

    /**
     * The leading token that occurs most often, so a provider's main line
     * ranks above its side families. Gemini's list also carries gemma and
     * imagen, and imagen-4.0 is not "newer" than gemini-3.6.
     *
     * @param array $ids Candidate ids.
     * @return string|null
     */
    private static function dominant_family(array $ids) {
        $counts = array();
        foreach ($ids as $id) {
            $family = self::family_of($id);
            if ('' !== $family) {
                $counts[$family] = isset($counts[$family]) ? $counts[$family] + 1 : 1;
            }
        }
        arsort($counts);

        return empty($counts) ? null : key($counts);
    }

    /**
     * Newest-first comparison.
     *
     * @param string $a First id.
     * @param string $b Second id.
     * @return int
     */
    public static function compare($a, $b) {
        $fa = (null !== self::$main_family && self::family_of($a) === self::$main_family) ? 1 : 0;
        $fb = (null !== self::$main_family && self::family_of($b) === self::$main_family) ? 1 : 0;
        if ($fa !== $fb) {
            return $fb <=> $fa;
        }

        $va = self::version_of($a);
        $vb = self::version_of($b);
        if ($va !== $vb) {
            return $vb <=> $va;
        }

        // A plain release id is shorter than its dated or variant siblings.
        $la = strlen((string) $a);
        $lb = strlen((string) $b);
        if ($la !== $lb) {
            return $la <=> $lb;
        }

        return strcmp((string) $a, (string) $b);
    }

    /**
     * Version carried by an id, ignoring release dates and parameter counts.
     *
     * @param string $id Model id.
     * @return float
     */
    public static function version_of($id) {
        $stem = (string) $id;
        $stem = preg_replace('/-\d{4}-\d{2}-\d{2}(?=-|$)/', '', $stem);
        $stem = preg_replace('/-\d{2}-\d{2}-\d{4}(?=-|$)/', '', $stem);
        $stem = preg_replace('/-\d{8}(?=-|$)/', '', $stem);
        $stem = preg_replace('/-\d{2}-\d{4}(?=-|$)/', '', $stem);
        $stem = preg_replace('/-(19|20)\d{2}(?=-|$)/', '', $stem);

        if (!preg_match('/(?:^|-)(\d+)(?:[.-](\d+))?(?![0-9a-z])/', $stem, $m)) {
            return 0.0;
        }

        $version = (float) $m[1] + (isset($m[2]) ? (float) ('0.' . $m[2]) : 0.0);

        return $version >= 1000 ? 0.0 : $version;
    }

    /**
     * Leading alphabetic token of a model id.
     *
     * @param string $id Model id.
     * @return string
     */
    public static function family_of($id) {
        return preg_match('/^([a-z]+)/i', (string) $id, $m) ? strtolower($m[1]) : '';
    }

    /**
     * Text provider instance for a live model listing.
     *
     * @param string $provider Provider id.
     * @param string $api_key  Key.
     * @return object|null
     */
    private static function provider_instance($provider, $api_key) {
        $map = array(
            'openai'    => '\\AICore\\Providers\\OpenAIProvider',
            'anthropic' => '\\AICore\\Providers\\AnthropicProvider',
            'gemini'    => '\\AICore\\Providers\\GeminiProvider',
        );

        if (!isset($map[$provider]) || !class_exists($map[$provider])) {
            return null;
        }

        return new $map[$provider]($api_key);
    }
}
