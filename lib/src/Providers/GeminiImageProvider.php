<?php

namespace AICore\Providers;

use AICore\Interfaces\ImageProviderInterface;
use AICore\Utils\HttpClient;

/**
 * Gemini Image Provider
 * 
 * Handles image generation using Google's Gemini Imagen models
 */
class GeminiImageProvider implements ImageProviderInterface {
    
    private string $api_key;
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';
    private const DEFAULT_MODEL = 'imagen-3.0-generate-001';
    
    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Generate image from prompt
     * 
     * @param string $prompt Image generation prompt
     * @param array $options Generation options
     * @return array Response with image URL or data
     * @throws \Exception On API errors
     */
    public function generateImage(string $prompt, array $options = []): array {
        $model = $options['model'] ?? self::DEFAULT_MODEL;
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
            $response = HttpClient::post($endpoint, $body);
            
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
    
    /**
     * Check if provider is configured
     * 
     * @return bool True if API key is set
     */
    public function isConfigured(): bool {
        return !empty($this->api_key);
    }
}

