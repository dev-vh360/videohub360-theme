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
    const TYPE_COUNTDOWN    = 'countdown';
    const TYPE_BIBLE        = 'bible';

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

        $config = $this->sanitize_config( isset( $payload['config'] ) ? $payload['config'] : $payload, $type );
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
        update_post_meta( $post_id, self::META_TYPE, $type );
        update_post_meta( $post_id, self::META_CONFIG, $config );

        return $this->get( $post_id, $user_id, $type );
    }

    public function update( $post_id, $user_id, $payload ) {
        $submitted_type = $this->sanitize_type( isset( $payload['type'] ) ? sanitize_key( $payload['type'] ) : self::TYPE_LOWER_THIRD );
        if ( is_wp_error( $submitted_type ) ) {
            return $submitted_type;
        }

        $existing_post = get_post( (int) $post_id );
        $stored_type   = $existing_post ? get_post_meta( $existing_post->ID, self::META_TYPE, true ) : '';
        $type          = $this->sanitize_type( $stored_type );
        if ( is_wp_error( $type ) ) {
            return $type;
        }
        if ( $submitted_type !== $type ) {
            return new WP_Error( 'vh360_overlay_type_change_forbidden', __( 'Overlay preset type cannot be changed.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $post = $this->get_owned_post( $post_id, $user_id, $type );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $config = $this->sanitize_config( isset( $payload['config'] ) ? $payload['config'] : $payload, $type );
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

        return $this->get( $post->ID, $user_id, $type );
    }

    public function delete( $post_id, $user_id ) {
        $post_obj = get_post( (int) $post_id );
        $type     = $post_obj ? $this->sanitize_type( get_post_meta( $post_obj->ID, self::META_TYPE, true ) ) : self::TYPE_LOWER_THIRD;
        if ( is_wp_error( $type ) ) {
            return $type;
        }
        $post = $this->get_owned_post( $post_id, $user_id, $type );
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
        $type = get_post_meta( $post->ID, self::META_TYPE, true );
        $type = is_wp_error( $this->sanitize_type( $type ) ) ? self::TYPE_LOWER_THIRD : $type;
        $config = get_post_meta( $post->ID, self::META_CONFIG, true );
        if ( ! is_array( $config ) ) {
            $config = $this->default_config( $type );
        }

        return array(
            'id'        => (int) $post->ID,
            'type'      => $type,
            'name'      => get_the_title( $post ),
            'config'    => $config,
            'createdAt' => mysql_to_rfc3339( $post->post_date_gmt ?: $post->post_date ),
            'updatedAt' => mysql_to_rfc3339( $post->post_modified_gmt ?: $post->post_modified ),
        );
    }

    public function sanitize_config( $config, $type = self::TYPE_LOWER_THIRD ) {
        if ( self::TYPE_COUNTDOWN === $type ) {
            return $this->sanitize_countdown_config( $config );
        }
        if ( self::TYPE_BIBLE === $type ) {
            return $this->sanitize_bible_config( $config );
        }
        return $this->sanitize_lower_third_config( $config );
    }

    public function sanitize_lower_third_config( $config ) {
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

	    public function sanitize_countdown_config( $config ) {
	        if ( ! is_array( $config ) ) {
	            return new WP_Error( 'vh360_overlay_invalid_config', __( 'Invalid overlay configuration.', 'videohub360-studio' ), array( 'status' => 400 ) );
	        }
        $default  = $this->default_config( self::TYPE_COUNTDOWN );
        $content  = isset( $config['content'] ) && is_array( $config['content'] ) ? $config['content'] : array();
        $timer    = isset( $config['timer'] ) && is_array( $config['timer'] ) ? $config['timer'] : array();
        $style    = isset( $config['style'] ) && is_array( $config['style'] ) ? $config['style'] : array();
        $behavior = isset( $config['behavior'] ) && is_array( $config['behavior'] ) ? $config['behavior'] : array();
        $name     = isset( $config['name'] ) ? $this->sanitize_name( $config['name'] ) : $default['name'];
        if ( is_wp_error( $name ) ) { return $name; }
	        $mode = $this->allowlisted( isset( $timer['mode'] ) ? $timer['mode'] : '', array( 'duration', 'target_time' ), $default['timer']['mode'] );
	        $duration_seconds = isset( $timer['durationSeconds'] ) ? absint( $timer['durationSeconds'] ) : absint( $default['timer']['durationSeconds'] );
	        if ( $duration_seconds < 1 || $duration_seconds > 86400 ) {
	            return new WP_Error( 'vh360_overlay_countdown_duration_invalid', __( 'Countdown duration must be between 1 and 86400 seconds.', 'videohub360-studio' ), array( 'status' => 400 ) );
	        }
	        $target = isset( $timer['targetLocalDateTime'] ) ? sanitize_text_field( $timer['targetLocalDateTime'] ) : '';
	        if ( 'target_time' === $mode ) {
	            $date = DateTime::createFromFormat( 'Y-m-d\TH:i', $target );
	            $errors = DateTime::getLastErrors();
	            if ( '' === $target || false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) || $date->format( 'Y-m-d\TH:i' ) !== $target ) {
	                return new WP_Error( 'vh360_overlay_countdown_target_invalid', __( 'Countdown target date and time is invalid.', 'videohub360-studio' ), array( 'status' => 400 ) );
	            }
	        } elseif ( '' !== $target ) {
	            $date = DateTime::createFromFormat( 'Y-m-d\TH:i', $target );
	            $errors = DateTime::getLastErrors();
	            if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) || $date->format( 'Y-m-d\TH:i' ) !== $target ) {
	                $target = '';
	            }
	        }
	        return array(
	            'id' => isset( $config['id'] ) ? max( 0, absint( $config['id'] ) ) : 0,
	            'type' => self::TYPE_COUNTDOWN,
	            'name' => $name,
	            'content' => array( 'label' => isset( $content['label'] ) ? $this->limit_text( sanitize_text_field( $content['label'] ), 120 ) : '', 'endMessage' => isset( $content['endMessage'] ) ? $this->limit_text( sanitize_text_field( $content['endMessage'] ), 160 ) : '' ),
	            'timer' => array( 'mode' => $mode, 'durationSeconds' => $duration_seconds, 'targetLocalDateTime' => $target, 'endBehavior' => $this->allowlisted( isset( $timer['endBehavior'] ) ? $timer['endBehavior'] : '', array( 'hold_zero', 'show_message', 'hide' ), $default['timer']['endBehavior'] ), 'messageDurationSeconds' => min( 300, max( 0, absint( isset( $timer['messageDurationSeconds'] ) ? $timer['messageDurationSeconds'] : $default['timer']['messageDurationSeconds'] ) ) ) ),
            'style' => array( 'template' => $this->allowlisted( isset( $style['template'] ) ? $style['template'] : '', array( 'full_screen', 'center_card', 'lower_center', 'corner' ), $default['style']['template'] ), 'position' => $this->allowlisted( isset( $style['position'] ) ? $style['position'] : '', array( 'top_left', 'top_right', 'bottom_left', 'bottom_right' ), $default['style']['position'] ), 'scale' => min( 140, max( 75, absint( isset( $style['scale'] ) ? $style['scale'] : $default['style']['scale'] ) ) ), 'accentColor' => $this->sanitize_hex( isset( $style['accentColor'] ) ? $style['accentColor'] : $default['style']['accentColor'], $default['style']['accentColor'] ), 'backgroundColor' => $this->sanitize_hex( isset( $style['backgroundColor'] ) ? $style['backgroundColor'] : $default['style']['backgroundColor'], $default['style']['backgroundColor'] ), 'backgroundOpacity' => min( 100, max( 0, absint( isset( $style['backgroundOpacity'] ) ? $style['backgroundOpacity'] : $default['style']['backgroundOpacity'] ) ) ), 'timerColor' => $this->sanitize_hex( isset( $style['timerColor'] ) ? $style['timerColor'] : $default['style']['timerColor'], $default['style']['timerColor'] ), 'labelColor' => $this->sanitize_hex( isset( $style['labelColor'] ) ? $style['labelColor'] : $default['style']['labelColor'], $default['style']['labelColor'] ) ),
            'behavior' => array( 'entrance' => $this->allowlisted( isset( $behavior['entrance'] ) ? $behavior['entrance'] : '', array( 'fade', 'none' ), $default['behavior']['entrance'] ), 'exit' => $this->allowlisted( isset( $behavior['exit'] ) ? $behavior['exit'] : '', array( 'fade', 'none' ), $default['behavior']['exit'] ), 'durationMs' => min( 2000, max( 0, absint( isset( $behavior['durationMs'] ) ? $behavior['durationMs'] : $default['behavior']['durationMs'] ) ) ) ),
        );
    }



    public function sanitize_bible_config( $config ) {
        if ( ! is_array( $config ) ) {
            return new WP_Error( 'vh360_overlay_invalid_config', __( 'Invalid overlay configuration.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $default    = $this->default_config( self::TYPE_BIBLE );
        $scripture  = isset( $config['scripture'] ) && is_array( $config['scripture'] ) ? $config['scripture'] : array();
        $style      = isset( $config['style'] ) && is_array( $config['style'] ) ? $config['style'] : array();
        $pagination = isset( $config['pagination'] ) && is_array( $config['pagination'] ) ? $config['pagination'] : array();
        $behavior   = isset( $config['behavior'] ) && is_array( $config['behavior'] ) ? $config['behavior'] : array();
        $name       = isset( $config['name'] ) ? $this->sanitize_name( $config['name'] ) : $default['name'];
        if ( is_wp_error( $name ) ) { return $name; }
        $ranges = array();
        if ( isset( $scripture['ranges'] ) && is_array( $scripture['ranges'] ) ) {
            foreach ( $scripture['ranges'] as $range ) {
                if ( ! is_array( $range ) ) { continue; }
                $ranges[] = array(
                    'bookKey'      => sanitize_key( isset( $range['bookKey'] ) ? $range['bookKey'] : '' ),
                    'startChapter' => max( 1, absint( isset( $range['startChapter'] ) ? $range['startChapter'] : 1 ) ),
                    'startVerse'   => max( 1, absint( isset( $range['startVerse'] ) ? $range['startVerse'] : 1 ) ),
                    'endChapter'   => max( 1, absint( isset( $range['endChapter'] ) ? $range['endChapter'] : 1 ) ),
                    'endVerse'     => max( 1, absint( isset( $range['endVerse'] ) ? $range['endVerse'] : 1 ) ),
                );
            }
        }
        return array(
            'id'         => isset( $config['id'] ) ? max( 0, absint( $config['id'] ) ) : 0,
            'type'       => self::TYPE_BIBLE,
            'name'       => $name,
            'scripture'  => array(
                'translationKey'   => sanitize_key( isset( $scripture['translationKey'] ) ? $scripture['translationKey'] : '' ),
                'translationLabel' => $this->limit_text( sanitize_text_field( isset( $scripture['translationLabel'] ) ? $scripture['translationLabel'] : '' ), 40 ),
                'reference'        => $this->limit_text( sanitize_text_field( isset( $scripture['reference'] ) ? $scripture['reference'] : '' ), 120 ),
                'ranges'           => $ranges,
                'datasetVersion'   => $this->limit_text( sanitize_text_field( isset( $scripture['datasetVersion'] ) ? $scripture['datasetVersion'] : '' ), 100 ),
                'sourceHash'       => preg_match( '/^[a-f0-9]{64}$/', isset( $scripture['sourceHash'] ) ? $scripture['sourceHash'] : '' ) ? $scripture['sourceHash'] : '',
            ),
            'style'      => array(
                'template'          => $this->allowlisted( isset( $style['template'] ) ? $style['template'] : '', array( 'lower_band', 'scripture_card', 'full_width_panel' ), $default['style']['template'] ),
                'position'          => $this->allowlisted( isset( $style['position'] ) ? $style['position'] : '', array( 'bottom_center', 'center', 'top_center' ), $default['style']['position'] ),
                'scale'             => min( 140, max( 75, absint( isset( $style['scale'] ) ? $style['scale'] : $default['style']['scale'] ) ) ),
                'backgroundColor'   => $this->sanitize_hex( isset( $style['backgroundColor'] ) ? $style['backgroundColor'] : $default['style']['backgroundColor'], $default['style']['backgroundColor'] ),
                'backgroundOpacity' => min( 100, max( 0, absint( isset( $style['backgroundOpacity'] ) ? $style['backgroundOpacity'] : $default['style']['backgroundOpacity'] ) ) ),
                'scriptureColor'    => $this->sanitize_hex( isset( $style['scriptureColor'] ) ? $style['scriptureColor'] : $default['style']['scriptureColor'], $default['style']['scriptureColor'] ),
                'referenceColor'    => $this->sanitize_hex( isset( $style['referenceColor'] ) ? $style['referenceColor'] : $default['style']['referenceColor'], $default['style']['referenceColor'] ),
                'textAlign'         => $this->allowlisted( isset( $style['textAlign'] ) ? $style['textAlign'] : '', array( 'left', 'center', 'right' ), $default['style']['textAlign'] ),
                'showVerseNumbers'  => ! empty( $style['showVerseNumbers'] ),
                'showReference'     => ! array_key_exists( 'showReference', $style ) || ! empty( $style['showReference'] ),
                'showTranslation'   => ! array_key_exists( 'showTranslation', $style ) || ! empty( $style['showTranslation'] ),
            ),
            'pagination' => array( 'maximumLines' => min( 12, max( 1, absint( isset( $pagination['maximumLines'] ) ? $pagination['maximumLines'] : $default['pagination']['maximumLines'] ) ) ) ),
            'behavior'   => array( 'entrance' => $this->allowlisted( isset( $behavior['entrance'] ) ? $behavior['entrance'] : '', array( 'fade', 'none' ), $default['behavior']['entrance'] ), 'exit' => $this->allowlisted( isset( $behavior['exit'] ) ? $behavior['exit'] : '', array( 'fade', 'none' ), $default['behavior']['exit'] ), 'durationMs' => min( 2000, max( 0, absint( isset( $behavior['durationMs'] ) ? $behavior['durationMs'] : $default['behavior']['durationMs'] ) ) ) ),
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
        return in_array( $type, array( self::TYPE_LOWER_THIRD, self::TYPE_COUNTDOWN, self::TYPE_BIBLE ), true ) ? $type : new WP_Error( 'vh360_overlay_invalid_type', __( 'Invalid overlay type.', 'videohub360-studio' ), array( 'status' => 400 ) );
    }

    private function sanitize_hex( $value, $fallback ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        return preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? strtolower( $value ) : $fallback;
    }

    private function allowlisted( $value, $allowed, $fallback ) {
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    public function default_config( $type = self::TYPE_LOWER_THIRD ) {
        if ( self::TYPE_BIBLE === $type ) {
            return array(
                'id' => 0, 'type' => self::TYPE_BIBLE, 'name' => __( 'Untitled Scripture Cue', 'videohub360-studio' ),
                'scripture' => array( 'translationKey' => '', 'translationLabel' => '', 'reference' => '', 'ranges' => array(), 'datasetVersion' => '', 'sourceHash' => '' ),
                'style' => array( 'template' => 'lower_band', 'position' => 'bottom_center', 'scale' => 100, 'backgroundColor' => '#0f172a', 'backgroundOpacity' => 88, 'scriptureColor' => '#ffffff', 'referenceColor' => '#dbeafe', 'textAlign' => 'center', 'showVerseNumbers' => true, 'showReference' => true, 'showTranslation' => true ),
                'pagination' => array( 'maximumLines' => 6 ),
                'behavior' => array( 'entrance' => 'fade', 'exit' => 'fade', 'durationMs' => 300 ),
            );
        }
        if ( self::TYPE_COUNTDOWN === $type ) {
            return array(
                'id' => 0, 'type' => self::TYPE_COUNTDOWN, 'name' => __( 'Untitled Countdown', 'videohub360-studio' ),
	                'content' => array( 'label' => __( 'Service Begins In', 'videohub360-studio' ), 'endMessage' => __( 'Service Is Beginning', 'videohub360-studio' ) ),
                'timer' => array( 'mode' => 'duration', 'durationSeconds' => 600, 'targetLocalDateTime' => '', 'endBehavior' => 'show_message', 'messageDurationSeconds' => 5 ),
                'style' => array( 'template' => 'center_card', 'position' => 'top_right', 'scale' => 100, 'accentColor' => '#4f46e5', 'backgroundColor' => '#0f172a', 'backgroundOpacity' => 88, 'timerColor' => '#ffffff', 'labelColor' => '#dbeafe' ),
                'behavior' => array( 'entrance' => 'fade', 'exit' => 'fade', 'durationMs' => 300 ),
            );
        }
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
