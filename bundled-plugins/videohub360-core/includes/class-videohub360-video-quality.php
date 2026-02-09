<?php
/**
 * VideoHub360 Video Quality Class
 * 
 * Handles video quality presets and management for VideoHub360 plugin
 * including 4K streaming capabilities and mirror control
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Video_Quality {
    
    /**
     * Quality presets with enhanced options including 4K support
     * 
     * @var array
     */
    private static $quality_presets = array(
        'auto' => array(
            'label' => 'Auto (Adaptive)',
            'description' => 'Automatically selects the best quality based on connection',
            'bitrate' => 'adaptive',
            'resolution' => 'adaptive',
            'fps' => 30,
            'codec' => 'h264',
            'audio_bitrate' => 128000,
            'priority' => 0
        ),
        'low' => array(
            'label' => '480p (Low)',
            'description' => 'Low quality for slow connections',
            'bitrate' => 1500000,  // 1.5 Mbps
            'resolution' => '854x480',
            'fps' => 30,
            'codec' => 'h264',
            'audio_bitrate' => 96000,
            'priority' => 1
        ),
        'medium' => array(
            'label' => '720p (Medium)',
            'description' => 'Medium quality for standard viewing',
            'bitrate' => 3000000,  // 3 Mbps
            'resolution' => '1280x720',
            'fps' => 30,
            'codec' => 'h264',
            'audio_bitrate' => 128000,
            'priority' => 2
        ),
        'high' => array(
            'label' => '1080p (High)',
            'description' => 'High quality for optimal viewing',
            'bitrate' => 8000000,  // 8 Mbps
            'resolution' => '1920x1080',
            'fps' => 30,
            'codec' => 'h264',
            'audio_bitrate' => 192000,
            'priority' => 3
        ),
        'ultra' => array(
            'label' => '1080p+ (Ultra)',
            'description' => 'Ultra high quality with enhanced encoding',
            'bitrate' => 12000000,  // 12 Mbps
            'resolution' => '1920x1080',
            'fps' => 60,
            'codec' => 'h264',
            'audio_bitrate' => 256000,
            'priority' => 4
        ),
        '4k' => array(
            'label' => '4K (Professional)',
            'description' => '4K Ultra HD for professional streaming',
            'bitrate' => 25000000,  // 25 Mbps
            'resolution' => '3840x2160',
            'fps' => 30,
            'codec' => 'h265',
            'audio_bitrate' => 320000,
            'priority' => 5
        ),
        '4k60' => array(
            'label' => '4K 60fps (Premium)',
            'description' => '4K Ultra HD at 60fps for premium streaming',
            'bitrate' => 40000000,  // 40 Mbps
            'resolution' => '3840x2160',
            'fps' => 60,
            'codec' => 'h265',
            'audio_bitrate' => 320000,
            'priority' => 6
        )
    );
    
    /**
     * Mirror control settings
     * 
     * @var array
     */
    private static $mirror_settings = array(
        'disabled' => array(
            'label' => 'Disabled',
            'description' => 'No mirroring applied'
        ),
        'horizontal' => array(
            'label' => 'Horizontal Mirror',
            'description' => 'Mirror video horizontally (left-right flip)'
        ),
        'vertical' => array(
            'label' => 'Vertical Mirror',
            'description' => 'Mirror video vertically (up-down flip)'
        ),
        'both' => array(
            'label' => 'Both Directions',
            'description' => 'Mirror both horizontally and vertically'
        )
    );
    
    /**
     * Get all quality presets
     * 
     * @return array Quality presets
     */
    public static function get_quality_presets() {
        return apply_filters('videohub360_quality_presets', self::$quality_presets);
    }
    
    /**
     * Get specific quality preset
     * 
     * @param string $quality_key Quality preset key
     * @return array|false Quality preset data or false if not found
     */
    public static function get_quality_preset($quality_key) {
        $presets = self::get_quality_presets();
        return isset($presets[$quality_key]) ? $presets[$quality_key] : false;
    }
    
    /**
     * Get mirror settings
     * 
     * @return array Mirror settings
     */
    public static function get_mirror_settings() {
        return apply_filters('videohub360_mirror_settings', self::$mirror_settings);
    }
    
    /**
     * Get quality options for admin dropdown
     * 
     * @return array Options array for select fields
     */
    public static function get_quality_options() {
        $presets = self::get_quality_presets();
        $options = array();
        
        foreach ($presets as $key => $preset) {
            $options[$key] = $preset['label'] . ' - ' . $preset['description'];
        }
        
        return $options;
    }
    
    /**
     * Get mirror options for admin dropdown
     * 
     * @return array Options array for select fields
     */
    public static function get_mirror_options() {
        $settings = self::get_mirror_settings();
        $options = array();
        
        foreach ($settings as $key => $setting) {
            $options[$key] = $setting['label'] . ' - ' . $setting['description'];
        }
        
        return $options;
    }
    
    /**
     * Validate quality setting
     * 
     * @param string $quality Quality key to validate
     * @return string|false Valid quality key or false
     */
    public static function validate_quality($quality) {
        $presets = self::get_quality_presets();
        return isset($presets[$quality]) ? $quality : false;
    }
    
    /**
     * Validate mirror setting
     * 
     * @param string $mirror Mirror key to validate
     * @return string|false Valid mirror key or false
     */
    public static function validate_mirror($mirror) {
        $settings = self::get_mirror_settings();
        return isset($settings[$mirror]) ? $mirror : false;
    }
    
    /**
     * Get recommended quality based on device and connection
     * 
     * @param array $device_info Device information (optional)
     * @return string Recommended quality key
     */
    public static function get_recommended_quality($device_info = array()) {
        // Basic recommendation logic - can be enhanced with actual device/connection detection
        
        if (isset($device_info['is_mobile']) && $device_info['is_mobile']) {
            return 'medium'; // 720p for mobile devices
        }
        
        if (isset($device_info['connection_speed']) && $device_info['connection_speed'] === 'slow') {
            return 'low'; // 480p for slow connections
        }
        
        // Default to high quality
        return 'high';
    }
    
    /**
     * Generate quality configuration for JavaScript
     * 
     * @return array JavaScript-ready configuration
     */
    public static function get_js_config() {
        $presets = self::get_quality_presets();
        $mirrors = self::get_mirror_settings();
        
        // Get options with proper type casting
        $allow_quality_switching = (bool) get_option('videohub360_allow_quality_switching', 1);
        $allow_mirror_control = (bool) get_option('videohub360_allow_mirror_control', 1);
        $show_quality_badge = (bool) get_option('videohub360_show_quality_badge', 1);
        
        return array(
            'presets' => $presets,
            'mirrors' => $mirrors,
            'default_quality' => get_option('videohub360_default_quality', 'high'),
            'default_mirror' => get_option('videohub360_default_mirror', 'disabled'),
            'allow_quality_switching' => $allow_quality_switching,
            'allow_mirror_control' => $allow_mirror_control,
            'show_quality_badge' => $show_quality_badge
        );
    }
    
    /**
     * Get CSS transforms for mirror settings
     * 
     * @param string $mirror_setting Mirror setting key
     * @return string CSS transform value
     */
    public static function get_mirror_transform($mirror_setting) {
        switch ($mirror_setting) {
            case 'horizontal':
                return 'scaleX(-1)';
            case 'vertical':
                return 'scaleY(-1)';
            case 'both':
                return 'scaleX(-1) scaleY(-1)';
            default:
                return 'none';
        }
    }
    
    /**
     * Check if quality is supported by browser/device
     * 
     * @param string $quality_key Quality preset key
     * @param array $capabilities Browser/device capabilities (optional)
     * @return bool Whether quality is supported
     */
    public static function is_quality_supported($quality_key, $capabilities = array()) {
        $preset = self::get_quality_preset($quality_key);
        if (!$preset) {
            return false;
        }
        
        // For 4K and H.265, check if supported
        if ($quality_key === '4k' || $quality_key === '4k60') {
            // Basic check - in real implementation, this would check browser capabilities
            return apply_filters('videohub360_4k_supported', true, $capabilities);
        }
        
        return true;
    }
    
    /**
     * Get available qualities for current context
     * 
     * @param array $context Context information (device, user role, etc.)
     * @return array Available quality presets
     */
    public static function get_available_qualities($context = array()) {
        $all_presets = self::get_quality_presets();
        $available = array();
        
        foreach ($all_presets as $key => $preset) {
            // Check if quality is supported
            if (!self::is_quality_supported($key, $context)) {
                continue;
            }
            
            // Check user permissions for premium qualities
            if (($key === '4k' || $key === '4k60')) {
                $user_can_4k = apply_filters('videohub360_user_can_use_4k', 
                    current_user_can('manage_options'), $context);
                if (!$user_can_4k) {
                    continue;
                }
            }
            
            $available[$key] = $preset;
        }
        
        return $available;
    }
}