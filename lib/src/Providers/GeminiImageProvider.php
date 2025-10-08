<?php

namespace AICore\Providers;

use AICore\Interfaces\ImageProviderInterface;
use AICore\Http\HttpClient;

/**
 * Gemini Image Provider
 *
 * Handles image generation using Google's Gemini 2.5 Flash Image models
 * Uses the :generateContent endpoint (same as text generation)
 *
 * @package AI_Core
 * @version 3.0.0
 */
class GeminiImageProvider implements ImageProviderInterface {

    private string $api_key;
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';
    private const DEFAULT_MODEL = 'gemini-2.5-flash-image';

    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Generate image from prompt
     *
     * Uses Gemini 2.5 Flash Image model with :generateContent endpoint
     * Returns Base64-encoded PNG images in inlineData format
     *
     * @param string $prompt Image generation prompt
     * @param array $options Generation options (model, n, etc.)
     * @return array Response with image URL or data
     * @throws \Exception On API errors
     */
    public function generateImage(string $prompt, array $options = []): array {
        $model = $options['model'] ?? self::DEFAULT_MODEL;

        // Determine which endpoint to use based on model
        if ($this->isLegacyImagenModel($model)) {
            return $this->generateImageLegacy($prompt, $options);
        }

        // Use new :generateContent endpoint for gemini-2.5-flash-image models
        return $this->generateImageWithGenerateContent($prompt, $model, $options);
    }

    /**
     * Get the provider name
     *
     * @return string Provider identifier
     */
    public function getName(): string {
        return 'gemini-image';
    }

    /**
     * Get supported image sizes
     *
     * @return array Array of supported size strings
     */
    public function getSupportedSizes(): array {
        return ['1024x1024', '1536x1536', '2048x2048'];
    }

    /**
     * Get supported quality levels
     *
     * @return array Array of supported quality levels
     */
    public function getSupportedQualities(): array {
        return ['standard', 'hd'];
    }

    /**
     * Validate provider configuration
     *
     * @return bool True if provider is properly configured
     */
    public function isConfigured(): bool {
        return !empty($this->api_key);
    }

    /**
     * Check if model is a legacy Imagen model (uses :predict endpoint)
     *
     * @param string $model Model identifier
     * @return bool True if legacy Imagen model
     */
    private function isLegacyImagenModel(string $model): bool {
        return strpos($model, 'imagen-') === 0;
    }

    /**
     * Convert size string to Gemini aspect ratio format
     *
     * @param string $size Size string (e.g., '1024x1024', '1792x1024')
     * @return string|null Aspect ratio string (e.g., '1:1', '16:9') or null if invalid
     */
    private function convertSizeToAspectRatio(string $size): ?string {
        $sizeMap = [
            '1024x1024' => '1:1',
            '1024x768' => '4:3',
            '1792x1024' => '16:9',
            '1024x1792' => '9:16',
        ];

        return $sizeMap[$size] ?? null;
    }

    /**
     * Generate image using new :generateContent endpoint (Gemini 2.5 Flash Image)
     *
     * @param string $prompt Image generation prompt
     * @param string $model Model identifier
     * @param array $options Generation options
     * @return array Response with image data
     * @throws \Exception On API errors
     */
    private function generateImageWithGenerateContent(string $prompt, string $model, array $options): array {
        // Build endpoint: /v1beta/models/{model}:generateContent?key={api_key}
        $endpoint = sprintf(
            '%s/models/%s:generateContent?key=%s',
            self::BASE_URL,
            $model,
            rawurlencode($this->api_key)
        );

        // Build request body following Gemini's generateContent format
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        // Add generation config for aspect ratio if size is specified
        if (!empty($options['size'])) {
            $aspectRatio = $this->convertSizeToAspectRatio($options['size']);
            if ($aspectRatio) {
                $body['generationConfig'] = [
                    'aspectRatio' => $aspectRatio
                ];
            }
        }

        try {
            $response = HttpClient::post($endpoint, $body, ['Content-Type' => 'application/json']);

            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Gemini image generation failed');
            }

            // Extract images from response (inlineData format)
            $images = [];
            if (isset($response['candidates']) && is_array($response['candidates'])) {
                foreach ($response['candidates'] as $candidate) {
                    if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                        foreach ($candidate['content']['parts'] as $part) {
                            if (isset($part['inlineData']['data'])) {
                                // Base64 PNG image data
                                $base64Data = $part['inlineData']['data'];
                                $images[] = [
                                    'url' => 'data:image/png;base64,' . $base64Data,
                                    'b64_json' => $base64Data
                                ];
                            }
                        }
                    }
                }
            }

            if (empty($images)) {
                // Check if we got text instead (model doesn't support image generation)
                $textResponse = '';
                if (isset($response['candidates'][0]['content']['parts'])) {
                    foreach ($response['candidates'][0]['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $textResponse .= $part['text'];
                        }
                    }
                }

                if ($textResponse) {
                    throw new \Exception('Model returned text instead of image. This model may not support image generation. Response: ' . $textResponse);
                }

                throw new \Exception('No images returned from Gemini. Full response: ' . json_encode($response));
            }

            return [
                'data' => $images,
                'created' => time(),
                'model' => $model
            ];

        } catch (\Exception $e) {
            throw new \Exception('Gemini image generation error: ' . $e->getMessage());
        }
    }

    /**
     * Generate image using legacy :predict endpoint (Imagen 3.0 models)
     *
     * @param string $prompt Image generation prompt
     * @param array $options Generation options
     * @return array Response with image data
     * @throws \Exception On API errors
     */
    private function generateImageLegacy(string $prompt, array $options): array {
        $model = $options['model'] ?? 'imagen-3.0-generate-001';
        $number_of_images = $options['n'] ?? 1;
        $aspect_ratio = $options['aspect_ratio'] ?? '1:1';
        $safety_filter_level = $options['safety_filter_level'] ?? 'block_some';
        $person_generation = $options['person_generation'] ?? 'allow_adult';

        // Build the endpoint
        $endpoint = self::BASE_URL . '/models/' . $model . ':predict?key=' . rawurlencode($this->api_key);

        // Build request body
        $body = [
            'instances' => [
                [
                    'prompt' => $prompt
                ]
            ],
            'parameters' => [
                'sampleCount' => $number_of_images,
                'aspectRatio' => $aspect_ratio,
                'safetyFilterLevel' => $safety_filter_level,
                'personGeneration' => $person_generation
            ]
        ];

        try {
            $response = HttpClient::post($endpoint, $body, ['Content-Type' => 'application/json']);

            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Gemini image generation failed');
            }

            // Extract images from response
            $images = [];
            if (isset($response['predictions']) && is_array($response['predictions'])) {
                foreach ($response['predictions'] as $prediction) {
                    if (isset($prediction['bytesBase64Encoded'])) {
                        // Convert base64 to data URL
                        $images[] = [
                            'url' => 'data:image/png;base64,' . $prediction['bytesBase64Encoded']
                        ];
                    }
                }
            }

            if (empty($images)) {
                throw new \Exception('No images returned from Gemini');
            }

            return [
                'data' => $images,
                'created' => time()
            ];

        } catch (\Exception $e) {
            throw new \Exception('Gemini image generation error: ' . $e->getMessage());
        }
    }
}

