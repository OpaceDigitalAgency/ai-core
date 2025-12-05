<?php
/**
 * AI-Pulse JSON Validator
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * JSON validation class
 */
class AI_Pulse_Validator {

    /**
     * Validate mode structure
     *
     * @param array $data Parsed JSON data
     * @param string $mode Analysis mode
     * @return bool Valid or not
     */
    public function validate_mode_structure($data, $mode) {
        if (!is_array($data)) {
            return false;
        }

        $required_fields = AI_Pulse_Modes::get_structure($mode);
        
        if (!$required_fields) {
            return false;
        }

        // Check all required fields exist
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitise JSON data
     *
     * @param array $data Data to sanitise
     * @return array Sanitised data
     */
    public function sanitise_data($data) {
        if (!is_array($data)) {
            return array();
        }

        $sanitised = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitised[$key] = $this->sanitise_data($value);
            } elseif (is_string($value)) {
                $sanitised[$key] = sanitize_text_field($value);
            } else {
                $sanitised[$key] = $value;
            }
        }

        return $sanitised;
    }
}

