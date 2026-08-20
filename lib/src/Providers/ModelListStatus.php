<?php
/**
 * Opace AI Hub Library - Model List Status
 *
 * Shared outcome tracking for provider /models listings.
 *
 * A provider that swallows a rejected key and quietly returns the bundled
 * ModelRegistry list is indistinguishable, to the caller, from one that
 * fetched a live list — which is how a key that 401s could still report
 * "Validated". This trait gives every provider the vocabulary to tell the
 * three outcomes apart: a live list, a rejected key, and a transient
 * failure served from the bundled definitions.
 *
 * @package AI_Core
 * @version 0.7.7
 */

namespace AICore\Providers;

trait ModelListStatus {

    /**
     * Outcome of the most recent model listing attempt.
     *
     * One of: unknown, live, cached, auth_error, unconfigured.
     *
     * @var string
     */
    private $modelListStatus = 'unknown';

    /**
     * Provider message for the most recent failed listing attempt.
     *
     * @var string
     */
    private $modelListError = '';

    /**
     * Outcome of the last model listing attempt.
     *
     * @return string unknown|live|cached|auth_error|unconfigured
     */
    public function getModelListStatus(): string {
        return $this->modelListStatus;
    }

    /**
     * Provider error text for the last failed listing ('' when none).
     *
     * @return string
     */
    public function getModelListError(): string {
        return $this->modelListError;
    }

    /**
     * Build the caller-facing result for a listing attempt and remember it.
     *
     * @param string            $status live|cached|auth_error|unconfigured
     * @param array<int,string> $models
     * @param string            $error
     * @return array<string,mixed>
     */
    private function modelListResult(string $status, array $models, string $error = ''): array {
        $this->modelListStatus = $status;
        $this->modelListError = $error;

        return [
            'status' => $status,
            'models' => $models,
            'error' => $error,
            // Only 'live' means the ids came from the provider itself.
            'is_live' => $status === 'live',
        ];
    }

    /**
     * Did this failure mean the provider rejected our credentials?
     *
     * Deliberately fails closed: anything that reads like a credential
     * rejection is treated as one, so a bad key is never reported as a
     * success. A misclassified transient error costs a retry; a
     * misclassified auth error costs the user their revocation signal.
     *
     * @param \Exception $e
     * @return bool
     */
    private function isAuthFailure(\Exception $e): bool {
        $status = $this->httpStatusFromException($e);

        if ($status === 401 || $status === 403) {
            return true;
        }

        // Gemini answers an invalid key with HTTP 400 + API_KEY_INVALID, and
        // several providers describe key problems only in the message body.
        $message = strtolower($e->getMessage());
        $markers = [
            'api_key_invalid',
            'api key not valid',
            'invalid api key',
            'invalid_api_key',
            'incorrect api key',
            'invalid x-api-key',
            'invalid authentication',
            'authentication_error',
            'authentication failed',
            'unauthenticated',
            'unauthorized',
            'permission_denied',
            'permission denied',
        ];

        foreach ($markers as $marker) {
            if (strpos($message, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the HTTP status HttpClient encodes into its exception message.
     *
     * @param \Exception $e
     * @return int Status code, or 0 when the message carries none.
     */
    private function httpStatusFromException(\Exception $e): int {
        if (preg_match('/\bHTTP (\d{3})\b/', $e->getMessage(), $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Order live ids by the registry's own ordering, then append ids the
     * registry has never heard of.
     *
     * A model the registry does not know must still reach the picker, so
     * unrecognised ids are appended rather than filtered away.
     *
     * @param array<int,string> $apiModels     Ids the provider listed.
     * @param array<int,string> $registryOrder Registry ids in priority order.
     * @return array<int,string>
     */
    private function mergeWithRegistryOrder(array $apiModels, array $registryOrder): array {
        $apiSet = array_flip($apiModels);
        $models = [];

        foreach ($registryOrder as $id) {
            if (isset($apiSet[$id])) {
                $models[] = $id;
            }
        }

        foreach ($apiModels as $id) {
            if (!\in_array($id, $models, true)) {
                $models[] = $id;
            }
        }

        return $models;
    }
}
