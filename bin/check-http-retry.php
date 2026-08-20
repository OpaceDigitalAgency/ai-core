<?php
/** Regression checks for provider connection retry safety. */

class WP_Error {
    private $message;
    public function __construct($code, $message) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}

$mode  = 'connect';
$calls = 0;

function is_wp_error($value) { return $value instanceof WP_Error; }
function esc_html($value) { return $value; }
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function wp_remote_post($url, $args) {
    global $mode, $calls;
    ++$calls;
    if ('connect' === $mode && 1 === $calls) {
        return new WP_Error('http_request_failed', 'cURL error 28: Connection timed out after 10003 milliseconds');
    }
    if ('response' === $mode) {
        return new WP_Error('http_request_failed', 'cURL error 28: Operation timed out after 120001 milliseconds with 0 bytes received');
    }
    return array('response' => array('code' => 200), 'body' => '{"ok":true}');
}
function wp_remote_get($url, $args) { return wp_remote_post($url, $args); }

require dirname(__DIR__) . '/lib/src/Http/HttpClient.php';

$result = AICore\Http\HttpClient::post('https://api.example.test', array());
if (2 !== $calls || empty($result['ok'])) {
    fwrite(STDERR, "FAIL: explicit connection timeout was not retried exactly once.\n");
    exit(1);
}

$mode  = 'response';
$calls = 0;
try {
    AICore\Http\HttpClient::post('https://api.example.test', array());
    fwrite(STDERR, "FAIL: response timeout unexpectedly succeeded.\n");
    exit(1);
} catch (Exception $e) {
    if (1 !== $calls) {
        fwrite(STDERR, "FAIL: ambiguous response timeout was retried.\n");
        exit(1);
    }
}

echo "PASS  explicit connection timeout retries once\n";
echo "PASS  ambiguous response timeout is not retried\n";
