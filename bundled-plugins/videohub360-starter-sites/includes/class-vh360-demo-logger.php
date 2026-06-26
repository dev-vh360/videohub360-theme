<?php
/**
 * Demo Import Logger
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Demo_Logger {
    
    /**
     * Log levels
     */
    const LEVEL_INFO = 'info';
    const LEVEL_SUCCESS = 'success';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    
    /**
     * Current log entries
     *
     * @var array
     */
    private $log_entries = array();
    
    /**
     * Demo ID being imported
     *
     * @var string
     */
    private $demo_id = '';
    
    /**
     * Import start time
     *
     * @var int
     */
    private $start_time = 0;
    
    /**
     * Singleton instance
     *
     * @var VH360_Demo_Logger
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Demo_Logger
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
        $this->start_time = time();
    }
    
    /**
     * Set demo ID
     *
     * @param string $demo_id Demo ID
     */
    public function set_demo_id($demo_id) {
        $this->demo_id = sanitize_key($demo_id);
    }
    
    /**
     * Log info message
     *
     * @param string $message Message to log
     * @param array $context Optional context data
     */
    public function info($message, $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log success message
     *
     * @param string $message Message to log
     * @param array $context Optional context data
     */
    public function success($message, $context = array()) {
        $this->log(self::LEVEL_SUCCESS, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Message to log
     * @param array $context Optional context data
     */
    public function warning($message, $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Message to log
     * @param array $context Optional context data
     */
    public function error($message, $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Add log entry
     *
     * @param string $level Log level
     * @param string $message Message
     * @param array $context Optional context data
     */
    private function log($level, $message, $context = array()) {
        $entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
        
        $this->log_entries[] = $entry;
        
        // Also log to WordPress debug log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_message = sprintf(
                '[VH360 Starter Sites] [%s] %s',
                strtoupper($level),
                $message
            );
            
            if (!empty($context)) {
                $log_message .= ' | Context: ' . wp_json_encode($context);
            }
            
            error_log($log_message);
        }
    }
    
    /**
     * Get all log entries
     *
     * @return array Log entries
     */
    public function get_entries() {
        return $this->log_entries;
    }
    
    /**
     * Get log entries by level
     *
     * @param string $level Log level
     * @return array Filtered log entries
     */
    public function get_entries_by_level($level) {
        return array_filter($this->log_entries, function($entry) use ($level) {
            return $entry['level'] === $level;
        });
    }
    
    /**
     * Check if there are any errors
     *
     * @return bool True if errors exist
     */
    public function has_errors() {
        return !empty($this->get_entries_by_level(self::LEVEL_ERROR));
    }
    
    /**
     * Get error count
     *
     * @return int Number of errors
     */
    public function get_error_count() {
        return count($this->get_entries_by_level(self::LEVEL_ERROR));
    }
    
    /**
     * Save log to database
     *
     * @return bool True on success
     */
    public function save() {
        $log_data = array(
            'demo_id' => $this->demo_id,
            'start_time' => $this->start_time,
            'end_time' => time(),
            'duration' => time() - $this->start_time,
            'entries' => $this->log_entries,
            'error_count' => $this->get_error_count(),
            'success' => !$this->has_errors(),
        );
        
        // Save to options table
        $saved = update_option('vh360_ss_last_import_log', $log_data, false);
        
        // Also save to import history (keep last 10)
        $history = get_option('vh360_ss_import_history', array());
        
        // Add current log with timestamp key
        $history[time()] = $log_data;
        
        // Keep only last 10 imports
        if (count($history) > 10) {
            krsort($history); // Sort by timestamp descending
            $history = array_slice($history, 0, 10, true);
        }
        
        update_option('vh360_ss_import_history', $history, false);
        
        return $saved;
    }
    
    /**
     * Get last import log
     *
     * @return array|false Log data or false if none exists
     */
    public static function get_last_log() {
        return get_option('vh360_ss_last_import_log', false);
    }
    
    /**
     * Get import history
     *
     * @param int $limit Number of logs to retrieve
     * @return array Import history
     */
    public static function get_history($limit = 10) {
        $history = get_option('vh360_ss_import_history', array());
        
        if (!empty($history)) {
            krsort($history); // Sort by timestamp descending
            return array_slice($history, 0, $limit, true);
        }
        
        return array();
    }
    
    /**
     * Clear all logs
     *
     * @return bool True on success
     */
    public static function clear_logs() {
        delete_option('vh360_ss_last_import_log');
        delete_option('vh360_ss_import_history');
        return true;
    }
    
    /**
     * Get formatted log output for display
     *
     * @return string HTML formatted log
     */
    public function get_formatted_output() {
        if (empty($this->log_entries)) {
            return '<p>' . esc_html__('No log entries', 'videohub360-starter-sites') . '</p>';
        }
        
        $output = '<div class="vh360-ss-log-entries">';
        
        foreach ($this->log_entries as $entry) {
            $level_class = 'log-' . esc_attr($entry['level']);
            $output .= sprintf(
                '<div class="vh360-ss-log-entry %s"><span class="log-timestamp">[%s]</span> <span class="log-level">[%s]</span> <span class="log-message">%s</span></div>',
                $level_class,
                esc_html($entry['timestamp']),
                esc_html(strtoupper($entry['level'])),
                esc_html($entry['message'])
            );
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Clear current log entries
     */
    public function clear() {
        $this->log_entries = array();
        $this->demo_id = '';
        $this->start_time = time();
    }
}
