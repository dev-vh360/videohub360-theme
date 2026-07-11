<?php
/**
 * Studio overlay preset repository.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Overlay_Repository {
    const POST_TYPE   = 'vh360_studio_overlay';
    const META_TYPE   = '_vh360_studio_overlay_type';
    const META_CONFIG = '_vh360_studio_overlay_config';

    const TYPE_LOWER_THIRD = 'lower_third';

    public static function register_post_type() {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels'       => array(
                    'name'          => __( 'Studio Overlays', 'videohub360-studio' ),
                    'singular_name' => __( 'Studio Overlay', 'videohub360-studio' ),
                ),
                'public'       => false,
                'show_ui'      => false,
                'show_in_rest' => false,
                'supports'     => array( 'title', 'author' ),
            )
        );
    }

    public function list( $user_id, $type = self::TYPE_LOWER_THIRD ) {
        $type = $this->sanitize_type( $type );
        if ( is_wp_error( $type ) ) {
            return $type;
        }

        $query = new WP_Query(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'publish',
                'author'         => (int) $user_id,
                'posts_per_page' => 100,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    array(
                        'key'   => self::META_TYPE,
                        'value' => $type,
                    ),
                ),
            )
        );

        return array_map( array( $this, 'format_post' ), $query->posts );
    }

    public function create( $user_id, $payload ) {
        $type = $this->sanitize_type( isset( $payload['type'] ) ? sanitize_key( $payload['type'] ) : self::TYPE_LOWER_THIRD );
        if ( is_wp_error( $type ) ) {
            return $type;
        }

        $config = $this->sanitize_config( isset( $payload['config'] ) ? $payload['config'] : $payload );
        if ( is_wp_error( $config ) ) {
            return $config;
        }

        $name = isset( $payload['name'] ) ? $this->sanitize_name( $payload['name'] ) : $config['name'];
        if ( is_wp_error( $name ) ) {
            return $name;
        }
        $config['name'] = $name;

        $post_id = wp_insert_post(
            array(
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => $name,
                'post_author' => (int) $user_id,
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $config['id'] = (int) $post_id;
        update_post_meta( $post_id, self::META_TYPE, self::TYPE_LOWER_THIRD );
        update_post_meta( $post_id, self::META_CONFIG, $config );

        return $this->get( $post_id, $user_id, self::TYPE_LOWER_THIRD );
    }

    public function update( $post_id, $user_id, $payload ) {
        $type = $this->sanitize_type( isset( $payload['type'] ) ? sanitize_key( $payload['type'] ) : self::TYPE_LOWER_THIRD );
        if ( is_wp_error( $type ) ) {
            return $type;
        }

        $post = $this->get_owned_post( $post_id, $user_id, self::TYPE_LOWER_THIRD );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $config = $this->sanitize_config( isset( $payload['config'] ) ? $payload['config'] : $payload );
        if ( is_wp_error( $config ) ) {
            return $config;
        }

        $name = isset( $payload['name'] ) ? $this->sanitize_name( $payload['name'] ) : $config['name'];
        if ( is_wp_error( $name ) ) {
            return $name;
        }
        $config['name'] = $name;

        $updated = wp_update_post(
            array(
                'ID'         => $post->ID,
                'post_title' => $name,
            ),
            true
        );

        if ( is_wp_error( $updated ) ) {
            return $updated;
        }

        $config['id'] = (int) $post->ID;
        update_post_meta( $post->ID, self::META_CONFIG, $config );

        return $this->get( $post->ID, $user_id, self::TYPE_LOWER_THIRD );
    }

    public function delete( $post_id, $user_id ) {
        $post = $this->get_owned_post( $post_id, $user_id, self::TYPE_LOWER_THIRD );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $deleted = wp_delete_post( $post->ID, true );
        if ( ! $deleted ) {
            return new WP_Error( 'vh360_overlay_delete_failed', __( 'Preset could not be deleted.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        return true;
    }

    public function get( $post_id, $user_id, $type = self::TYPE_LOWER_THIRD ) {
        $post = $this->get_owned_post( $post_id, $user_id, $type );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        return $this->format_post( $post );
    }

    private function get_owned_post( $post_id, $user_id, $type ) {
        $post = get_post( (int) $post_id );
        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return new WP_Error( 'vh360_overlay_not_found', __( 'Overlay preset not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        if ( (int) $post->post_author !== (int) $user_id ) {
            return new WP_Error( 'vh360_overlay_forbidden', __( 'You cannot access this overlay preset.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        if ( get_post_meta( $post->ID, self::META_TYPE, true ) !== $type ) {
            return new WP_Error( 'vh360_overlay_invalid_type', __( 'Invalid overlay preset type.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        return $post;
    }

    private function format_post( $post ) {
        $config = get_post_meta( $post->ID, self::META_CONFIG, true );
        if ( ! is_array( $config ) ) {
            $config = $this->default_config();
        }

        return array(
            'id'        => (int) $post->ID,
            'type'      => self::TYPE_LOWER_THIRD,
            'name'      => get_the_title( $post ),
            'config'    => $config,
            'createdAt' => mysql_to_rfc3339( $post->post_date_gmt ?: $post->post_date ),
            'updatedAt' => mysql_to_rfc3339( $post->post_modified_gmt ?: $post->post_modified ),
        );
    }

    public function sanitize_config( $config ) {
        if ( ! is_array( $config ) ) {
            return new WP_Error( 'vh360_overlay_invalid_config', __( 'Invalid overlay configuration.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $default = $this->default_config();
        $content = isset( $config['content'] ) && is_array( $config['content'] ) ? $config['content'] : array();
        $style = isset( $config['style'] ) && is_array( $config['style'] ) ? $config['style'] : array();
        $behavior = isset( $config['behavior'] ) && is_array( $config['behavior'] ) ? $config['behavior'] : array();

        $name = isset( $config['name'] ) ? $this->sanitize_name( $config['name'] ) : $default['name'];
        if ( is_wp_error( $name ) ) { return $name; }

        $primary = isset( $content['primary'] ) ? $this->limit_text( sanitize_text_field( $content['primary'] ), 120 ) : '';
        if ( '' === $primary ) {
            return new WP_Error( 'vh360_overlay_primary_required', __( 'Primary text is required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $secondary = isset( $content['secondary'] ) ? $this->limit_text( sanitize_text_field( $content['secondary'] ), 160 ) : '';

        return array(
            'id'       => isset( $config['id'] ) ? max( 0, absint( $config['id'] ) ) : 0,
            'type'     => self::TYPE_LOWER_THIRD,
            'name'     => $name,
            'content'  => array(
                'primary'   => $primary,
                'secondary' => $secondary,
            ),
            'style'    => array(
                'template'          => $this->allowlisted( isset( $style['template'] ) ? $style['template'] : '', array( 'accent_bar', 'solid_band', 'minimal' ), $default['style']['template'] ),
                'position'          => $this->allowlisted( isset( $style['position'] ) ? $style['position'] : '', array( 'bottom_left', 'bottom_center', 'bottom_right' ), $default['style']['position'] ),
                'scale'             => min( 140, max( 75, absint( isset( $style['scale'] ) ? $style['scale'] : $default['style']['scale'] ) ) ),
                'accentColor'       => $this->sanitize_hex( isset( $style['accentColor'] ) ? $style['accentColor'] : $default['style']['accentColor'], $default['style']['accentColor'] ),
                'backgroundColor'   => $this->sanitize_hex( isset( $style['backgroundColor'] ) ? $style['backgroundColor'] : $default['style']['backgroundColor'], $default['style']['backgroundColor'] ),
                'backgroundOpacity' => min( 100, max( 0, absint( isset( $style['backgroundOpacity'] ) ? $style['backgroundOpacity'] : $default['style']['backgroundOpacity'] ) ) ),
                'primaryColor'      => $this->sanitize_hex( isset( $style['primaryColor'] ) ? $style['primaryColor'] : $default['style']['primaryColor'], $default['style']['primaryColor'] ),
                'secondaryColor'    => $this->sanitize_hex( isset( $style['secondaryColor'] ) ? $style['secondaryColor'] : $default['style']['secondaryColor'], $default['style']['secondaryColor'] ),
            ),
            'behavior' => array(
                'entrance'        => $this->allowlisted( isset( $behavior['entrance'] ) ? $behavior['entrance'] : '', array( 'slide_left', 'fade', 'none' ), $default['behavior']['entrance'] ),
                'exit'            => $this->allowlisted( isset( $behavior['exit'] ) ? $behavior['exit'] : '', array( 'slide_left', 'fade', 'none' ), $default['behavior']['exit'] ),
                'durationMs'      => min( 2000, max( 0, absint( isset( $behavior['durationMs'] ) ? $behavior['durationMs'] : $default['behavior']['durationMs'] ) ) ),
                'autoHideSeconds' => min( 300, max( 0, absint( isset( $behavior['autoHideSeconds'] ) ? $behavior['autoHideSeconds'] : $default['behavior']['autoHideSeconds'] ) ) ),
            ),
        );
    }

    private function sanitize_name( $name ) {
        $name = $this->limit_text( sanitize_text_field( $name ), 120 );
        if ( '' === $name ) {
            return new WP_Error( 'vh360_overlay_name_required', __( 'Preset name is required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return $name;
    }

    private function limit_text( $value, $length ) {
        return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
    }

    private function sanitize_type( $type ) {
        return self::TYPE_LOWER_THIRD === $type ? $type : new WP_Error( 'vh360_overlay_invalid_type', __( 'Invalid overlay type.', 'videohub360-studio' ), array( 'status' => 400 ) );
    }

    private function sanitize_hex( $value, $fallback ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        return preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? strtolower( $value ) : $fallback;
    }

    private function allowlisted( $value, $allowed, $fallback ) {
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    public function default_config() {
        return array(
            'id'       => 0,
            'type'     => self::TYPE_LOWER_THIRD,
            'name'     => __( 'Untitled Lower Third', 'videohub360-studio' ),
            'content'  => array(
                'primary'   => '',
                'secondary' => '',
            ),
            'style'    => array(
                'template'          => 'accent_bar',
                'position'          => 'bottom_left',
                'scale'             => 100,
                'accentColor'       => '#4f46e5',
                'backgroundColor'   => '#0f172a',
                'backgroundOpacity' => 90,
                'primaryColor'      => '#ffffff',
                'secondaryColor'    => '#dbeafe',
            ),
            'behavior' => array(
                'entrance'        => 'slide_left',
                'exit'            => 'fade',
                'durationMs'      => 300,
                'autoHideSeconds' => 0,
            ),
        );
    }
}
