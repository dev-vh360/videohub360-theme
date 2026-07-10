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
    const DEFAULT_PRESET = 'high_1080p';

    private static function built_in_presets() {
        return array(
            'small_480p'       => array(
                'label'                   => __( 'Small 480p', 'videohub360-studio' ),
                'resolution'              => array( 'width' => 854, 'height' => 480 ),
                'fps'                     => 30,
                'recording_video_bitrate' => 1200000,
                'recording_audio_bitrate' => 96000,
                'live_resolution'         => array( 'width' => 854, 'height' => 480 ),
                'live_fps'                => 30,
                'live_bitrate_max'        => 1200,
                'recommended'             => false,
                'advanced'                => false,
            ),
            'standard_720p'    => array(
                'label'                   => __( 'Standard 720p', 'videohub360-studio' ),
                'resolution'              => array( 'width' => 1280, 'height' => 720 ),
                'fps'                     => 30,
                'recording_video_bitrate' => 2500000,
                'recording_audio_bitrate' => 128000,
                'live_resolution'         => array( 'width' => 1280, 'height' => 720 ),
                'live_fps'                => 30,
                'live_bitrate_max'        => 2500,
                'recommended'             => false,
                'advanced'                => false,
            ),
            'high_1080p'      => array(
                'label'                   => __( 'High 1080p', 'videohub360-studio' ),
                'resolution'              => array( 'width' => 1920, 'height' => 1080 ),
                'fps'                     => 30,
                'recording_video_bitrate' => 6000000,
                'recording_audio_bitrate' => 160000,
                'live_resolution'         => array( 'width' => 1920, 'height' => 1080 ),
                'live_fps'                => 30,
                'live_bitrate_max'        => 5000,
                'recommended'             => true,
                'advanced'                => false,
            ),
            'ultra_1080p'     => array(
                'label'                   => __( 'Ultra 1080p', 'videohub360-studio' ),
                'resolution'              => array( 'width' => 1920, 'height' => 1080 ),
                'fps'                     => 30,
                'recording_video_bitrate' => 10000000,
                'recording_audio_bitrate' => 192000,
                'live_resolution'         => array( 'width' => 1920, 'height' => 1080 ),
                'live_fps'                => 30,
                'live_bitrate_max'        => 5000,
                'recommended'             => false,
                'advanced'                => true,
            ),
            'professional_4k' => array(
                'label'                   => __( '4K', 'videohub360-studio' ),
                'resolution'              => array( 'width' => 3840, 'height' => 2160 ),
                'fps'                     => 30,
                'recording_video_bitrate' => 25000000,
                'recording_audio_bitrate' => 256000,
                'live_resolution'         => array( 'width' => 1920, 'height' => 1080 ),
                'live_fps'                => 30,
                'live_bitrate_max'        => 5000,
                'recommended'             => false,
                'advanced'                => true,
            ),
        );
    }

    public static function get_presets() {
        $built_in = self::built_in_presets();
        $filtered = apply_filters( 'vh360_studio_quality_presets', $built_in );
        if ( ! is_array( $filtered ) ) {
            $filtered = $built_in;
        }
        $normalized = array();
        foreach ( $filtered as $id => $preset ) {
            $id = sanitize_key( $id );
            if ( '' === $id || ! is_array( $preset ) ) {
                continue;
            }
            $fallback          = isset( $built_in[ $id ] ) ? $built_in[ $id ] : $built_in[ self::DEFAULT_PRESET ];
            $normalized[ $id ] = self::normalize_definition( $preset, $fallback );
        }
        if ( ! isset( $normalized[ self::DEFAULT_PRESET ] ) ) {
            $normalized[ self::DEFAULT_PRESET ] = $built_in[ self::DEFAULT_PRESET ];
        }
        return $normalized;
    }

    private static function positive_int( $value, $fallback ) {
        $value = absint( $value );
        return $value > 0 ? $value : absint( $fallback );
    }

    private static function resolution( $value, $fallback ) {
        $value = is_array( $value ) ? $value : array();
        return array(
            'width'  => self::positive_int( isset( $value['width'] ) ? $value['width'] : 0, $fallback['width'] ),
            'height' => self::positive_int( isset( $value['height'] ) ? $value['height'] : 0, $fallback['height'] ),
        );
    }

    private static function normalize_definition( $preset, $fallback ) {
        $preset['recording_video_bitrate'] = isset( $preset['recording_video_bitrate'] ) ? $preset['recording_video_bitrate'] : ( isset( $preset['video_bitrate'] ) ? $preset['video_bitrate'] : null );
        $preset['recording_audio_bitrate'] = isset( $preset['recording_audio_bitrate'] ) ? $preset['recording_audio_bitrate'] : ( isset( $preset['audio_bitrate'] ) ? $preset['audio_bitrate'] : null );
        $preset['live_resolution']         = isset( $preset['live_resolution'] ) ? $preset['live_resolution'] : ( isset( $preset['resolution'] ) ? $preset['resolution'] : null );
        $preset['live_fps']                = isset( $preset['live_fps'] ) ? $preset['live_fps'] : ( isset( $preset['fps'] ) ? $preset['fps'] : null );
        $live_bitrate_max                  = absint( isset( $preset['live_bitrate_max'] ) ? $preset['live_bitrate_max'] : 0 );
        if ( $live_bitrate_max < 100 || $live_bitrate_max > 5000 ) {
            $live_bitrate_max = absint( $fallback['live_bitrate_max'] );
        }
        return array(
            'label'                   => ! empty( $preset['label'] ) ? $preset['label'] : $fallback['label'],
            'resolution'              => self::resolution( isset( $preset['resolution'] ) ? $preset['resolution'] : array(), $fallback['resolution'] ),
            'fps'                     => self::positive_int( isset( $preset['fps'] ) ? $preset['fps'] : 0, $fallback['fps'] ),
            'recording_video_bitrate' => self::positive_int( $preset['recording_video_bitrate'], $fallback['recording_video_bitrate'] ),
            'recording_audio_bitrate' => self::positive_int( $preset['recording_audio_bitrate'], $fallback['recording_audio_bitrate'] ),
            'live_resolution'         => self::resolution( $preset['live_resolution'], $fallback['live_resolution'] ),
            'live_fps'                => self::positive_int( $preset['live_fps'], $fallback['live_fps'] ),
            'live_bitrate_max'        => $live_bitrate_max,
            'recommended'             => ! empty( $preset['recommended'] ),
            'advanced'                => ! empty( $preset['advanced'] ),
        );
    }

    public static function exists( $preset ) {
        $presets = self::get_presets();
        return isset( $presets[ sanitize_key( $preset ) ] );
    }

    public static function normalize( $preset ) {
        $preset = sanitize_key( $preset );
        return self::exists( $preset ) ? $preset : self::DEFAULT_PRESET;
    }

    public static function get_default_preset() {
        return self::normalize( get_option( 'vh360_studio_default_quality_preset', self::DEFAULT_PRESET ) );
    }

    public static function get_preset( $preset ) {
        $presets = self::get_presets();
        $preset  = self::normalize( $preset );
        return isset( $presets[ $preset ] ) ? $presets[ $preset ] : $presets[ self::DEFAULT_PRESET ];
    }

    public static function get_label( $preset ) {
        $presets = self::get_presets();
        return isset( $presets[ $preset ]['label'] ) ? $presets[ $preset ]['label'] : $preset;
    }
}
