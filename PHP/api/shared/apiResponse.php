<?php
// apiResponse.php

// Define DEBUG_MODE if not already defined
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

class apiResponse {
    /**
     * Send a successful JSON response.
     *
     * @param array|null $data The data to include in the response.
     * @param string $message A descriptive message.
     * @param int $code HTTP response code (default 200).
     */
    public static function success($data = null, $message = "Success", $code = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        
        $response = [
            "success" => true,
            "message" => $message
        ];

        if ($data !== null) {
            foreach ($data as $key => $value) {
                $response[$key] = $value;
            }
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message A descriptive error message.
     * @param int $code HTTP response code (default 400).
     * @param mixed $debugDetails Extra details for debugging (only shown if debug is enabled).
     */
    public static function error($message = "An error occurred", $code = 400, $debugDetails = null) {
        // Clear any previous output to ensure clean JSON
        if (ob_get_length()) ob_clean();

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);

        $response = [
            "success" => false,
            "message" => $message
        ];

        if ($debugDetails !== null && defined('DEBUG_MODE') && DEBUG_MODE === true) {
            $response['debug'] = $debugDetails;
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Global exception handler to ensure all crashes return JSON.
     */
    public static function handleException($e) {
        error_log("Unhandled Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        self::error("A server error occurred.", 500, $e->getMessage());
    }

    /**
     * Global error handler to convert PHP errors into JSON.
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) return false;
        error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
        self::error("A system error occurred.", 500, $errstr);
    }
}

// Register global handlers
set_exception_handler(['apiResponse', 'handleException']);
set_error_handler(['apiResponse', 'handleError']);
