<?php
/**
 * Demo Package Downloader
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Demo_Downloader {
    
    /**
     * Singleton instance
     *
     * @var VH360_Demo_Downloader
     */
    private static $instance = null;
    
    /**
     * Logger instance
     *
     * @var VH360_Demo_Logger
     */
    private $logger;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Demo_Downloader
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
        $this->logger = VH360_Demo_Logger::get_instance();
    }
    
    /**
     * Download and parse demo manifest
     *
     * @param string $manifest_url Manifest URL
     * @return array|WP_Error Manifest data or error
     */
    public function download_manifest($manifest_url) {
        $this->logger->info('Downloading demo manifest', array('url' => $manifest_url));
        
        $response = wp_remote_get($manifest_url, array(
            'timeout' => 30,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            $error_message = sprintf(
                __('Failed to download manifest: %s', 'videohub360-starter-sites'),
                $response->get_error_message()
            );
            $this->logger->error($error_message);
            return new WP_Error('manifest_download_failed', $error_message);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = sprintf(
                __('Manifest download returned status code: %d', 'videohub360-starter-sites'),
                $response_code
            );
            $this->logger->error($error_message);
            return new WP_Error('manifest_bad_status', $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            $error_message = __('Manifest is empty', 'videohub360-starter-sites');
            $this->logger->error($error_message);
            return new WP_Error('manifest_empty', $error_message);
        }
        
        $manifest = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = sprintf(
                __('Failed to parse manifest JSON: %s', 'videohub360-starter-sites'),
                json_last_error_msg()
            );
            $this->logger->error($error_message);
            return new WP_Error('manifest_invalid_json', $error_message);
        }
        
        // Validate manifest structure
        $validation = $this->validate_manifest($manifest);
        if (is_wp_error($validation)) {
            $this->logger->error('Manifest validation failed: ' . $validation->get_error_message());
            return $validation;
        }
        
        $this->logger->success('Manifest downloaded and validated successfully');
        
        return $manifest;
    }
    
    /**
     * Validate manifest structure
     *
     * @param array $manifest Manifest data
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    private function validate_manifest($manifest) {
        // Required top-level fields
        $required_fields = array('demo_id', 'version', 'files');
        
        foreach ($required_fields as $field) {
            if (!isset($manifest[$field])) {
                return new WP_Error('manifest_missing_field', sprintf(__('Manifest missing required field: %s', 'videohub360-starter-sites'), $field));
            }
        }
        
        if (!is_array($manifest['files'])) {
            return new WP_Error('manifest_invalid_files', __('Manifest files must be an array', 'videohub360-starter-sites'));
        }
        
        // Validate post_import section if present (recommended approach)
        if (isset($manifest['post_import'])) {
            if (!is_array($manifest['post_import'])) {
                return new WP_Error('manifest_invalid_post_import', __('Manifest post_import must be an array', 'videohub360-starter-sites'));
            }
            
            // Validate homepage config if present
            if (isset($manifest['post_import']['homepage'])) {
                if (!is_array($manifest['post_import']['homepage'])) {
                    return new WP_Error('manifest_invalid_homepage', __('Homepage config must be an array', 'videohub360-starter-sites'));
                }
                
                if (!isset($manifest['post_import']['homepage']['slug']) && !isset($manifest['post_import']['homepage']['title'])) {
                    return new WP_Error('manifest_missing_homepage_identifier', __('Homepage must have slug or title', 'videohub360-starter-sites'));
                }
            }
            
            // Validate menus config if present
            if (isset($manifest['post_import']['menus']) && !is_array($manifest['post_import']['menus'])) {
                return new WP_Error('manifest_invalid_menus', __('Menus config must be an array', 'videohub360-starter-sites'));
            }
        }
        
        return true;
    }
    
    /**
     * Download a file from URL to local temp directory
     *
     * @param string $url File URL
     * @param string $filename Local filename
     * @return string|WP_Error Local file path or error
     */
    public function download_file($url, $filename) {
        $this->logger->info('Downloading file', array('url' => $url, 'filename' => $filename));
        
        $temp_dir = vh360_ss_get_temp_dir();
        $local_path = $temp_dir . '/' . $filename;
        
        // Download file
        $response = wp_remote_get($url, array(
            'timeout' => 300, // 5 minutes
            'stream' => true,
            'filename' => $local_path,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            $error_message = sprintf(
                __('Failed to download file %s: %s', 'videohub360-starter-sites'),
                $filename,
                $response->get_error_message()
            );
            $this->logger->error($error_message);
            return new WP_Error('file_download_failed', $error_message);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = sprintf(
                __('File download returned status code %d: %s', 'videohub360-starter-sites'),
                $response_code,
                $filename
            );
            $this->logger->error($error_message);
            return new WP_Error('file_bad_status', $error_message);
        }
        
        // Verify file was created
        if (!file_exists($local_path)) {
            $error_message = sprintf(
                __('File was not created: %s', 'videohub360-starter-sites'),
                $filename
            );
            $this->logger->error($error_message);
            return new WP_Error('file_not_created', $error_message);
        }
        
        $file_size = filesize($local_path);
        $this->logger->success(sprintf('Downloaded %s (%s)', $filename, vh360_ss_format_bytes($file_size)));
        
        return $local_path;
    }
    
    /**
     * Download all files from manifest
     *
     * Expected manifest format:
     * "files": {
     *   "content": {"path": "content.xml"},
     *   "widgets": {"path": "widgets.json"},
     *   "customizer": {"path": "customizer.json"},
     *   "elementor_kit": {"path": "elementor-kit.zip"},
     *   "theme_options": {"path": "theme-options.json"}
     * }
     *
     * @param array $manifest Manifest data
     * @return array|WP_Error Array of local file paths keyed by file type, or error
     */
    public function download_package_files($manifest) {
        $this->logger->info('Downloading package files');
        
        if (!isset($manifest['files']) || !is_array($manifest['files'])) {
            return new WP_Error('manifest_no_files', __('Manifest has no files to download', 'videohub360-starter-sites'));
        }
        
        $base_url = isset($manifest['base_url']) ? trailingslashit($manifest['base_url']) : '';
        $downloaded_files = array();
        $errors = array();
        
        foreach ($manifest['files'] as $file_type => $file_info) {
            // Each file entry must be an array with a 'path' key
            if (!is_array($file_info) || !isset($file_info['path'])) {
                $this->logger->warning(sprintf('Skipping invalid file entry: %s (expected format: {"path": "filename"})', $file_type));
                continue;
            }
            
            $file_url = $base_url . $file_info['path'];
            $filename = basename($file_info['path']);
            
            $local_path = $this->download_file($file_url, $filename);
            
            if (is_wp_error($local_path)) {
                $errors[] = $local_path->get_error_message();
                $this->logger->error(sprintf('Failed to download %s', $file_type));
            } else {
                $downloaded_files[$file_type] = $local_path;
            }
        }
        
        if (!empty($errors) && empty($downloaded_files)) {
            return new WP_Error('download_all_failed', __('Failed to download any files', 'videohub360-starter-sites'), $errors);
        }
        
        if (!empty($errors)) {
            $this->logger->warning(sprintf('Downloaded %d files with %d errors', count($downloaded_files), count($errors)));
        } else {
            $this->logger->success(sprintf('Successfully downloaded all %d files', count($downloaded_files)));
        }
        
        return $downloaded_files;
    }
    
    /**
     * Clean up downloaded files
     *
     * @param array $file_paths Array of file paths to delete
     * @return int Number of files deleted
     */
    public function cleanup_files($file_paths) {
        $deleted = 0;
        
        foreach ($file_paths as $file_path) {
            if (file_exists($file_path)) {
                if (@unlink($file_path)) {
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            $this->logger->info(sprintf('Cleaned up %d downloaded files', $deleted));
        }
        
        return $deleted;
    }
    
    /**
     * Extract ZIP file to directory
     *
     * @param string $zip_file Path to ZIP file
     * @param string $extract_to Directory to extract to
     * @return bool|WP_Error True on success, error otherwise
     */
    public function extract_zip($zip_file, $extract_to) {
        $this->logger->info('Extracting ZIP file', array('file' => basename($zip_file)));
        
        if (!file_exists($zip_file)) {
            return new WP_Error('zip_not_found', __('ZIP file not found', 'videohub360-starter-sites'));
        }
        
        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_not_supported', __('ZIP extraction not supported (ZipArchive class not available)', 'videohub360-starter-sites'));
        }
        
        // Create extract directory if it doesn't exist
        if (!file_exists($extract_to)) {
            wp_mkdir_p($extract_to);
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($zip_file);
        
        if ($result !== true) {
            return new WP_Error('zip_open_failed', sprintf(__('Failed to open ZIP file (error code: %d)', 'videohub360-starter-sites'), $result));
        }
        
        if (!$zip->extractTo($extract_to)) {
            $zip->close();
            return new WP_Error('zip_extract_failed', __('Failed to extract ZIP file', 'videohub360-starter-sites'));
        }
        
        $zip->close();
        
        $this->logger->success('ZIP file extracted successfully');
        
        return true;
    }
}
