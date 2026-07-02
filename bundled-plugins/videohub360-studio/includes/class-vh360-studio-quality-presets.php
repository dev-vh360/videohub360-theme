<?php
/**
 * Studio quality preset definitions.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Quality_Presets {
    const DEFAULT_PRESET = 'standard_720p';

    /**
     * Get controlled recorder quality presets.
     *
     * @return array
     */
    public static function get_presets() {
        return apply_filters(
            'vh360_studio_quality_presets',
            array(
                'small_480p'    => array(
                    'label'             => __( 'Small 480p', 'videohub360-studio' ),
                    'resolution'        => array( 'width' => 854, 'height' => 480 ),
                    'fps'               => 30,
                    'video_bitrate'     => 1200000,
                    'audio_bitrate'     => 96000,
                    'recommended'       => false,
                ),
                'standard_720p' => array(
                    'label'             => __( 'Standard 720p', 'videohub360-studio' ),
                    'resolution'        => array( 'width' => 1280, 'height' => 720 ),
                    'fps'               => 30,
                    'video_bitrate'     => 2500000,
                    'audio_bitrate'     => 128000,
                    'recommended'       => true,
                ),
                'high_1080p'    => array(
                    'label'             => __( 'High 1080p', 'videohub360-studio' ),
                    'resolution'        => array( 'width' => 1920, 'height' => 1080 ),
                    'fps'               => 30,
                    'video_bitrate'     => 5000000,
                    'audio_bitrate'     => 160000,
                    'recommended'       => false,
                ),
            )
        );
    }

    /**
     * Check whether a preset ID is registered.
     *
     * @param string $preset Preset ID.
     * @return bool
     */
    public static function exists( $preset ) {
        $presets = self::get_presets();
        return isset( $presets[ $preset ] );
    }

    /**
     * Get a preset label.
     *
     * @param string $preset Preset ID.
     * @return string
     */
    public static function get_label( $preset ) {
        $presets = self::get_presets();
        return isset( $presets[ $preset ]['label'] ) ? $presets[ $preset ]['label'] : $preset;
    }
}
