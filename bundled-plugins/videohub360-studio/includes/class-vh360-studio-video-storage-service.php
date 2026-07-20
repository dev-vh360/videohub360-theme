<?php
/**
 * Canonical Studio video asset storage service.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Video_Storage_Service {
    const CRON_HOOK     = 'vh360_studio_video_assets_cron';
    const CRON_SCHEDULE = 'vh360_studio_ten_minutes';

    private $registry;

    public function __construct( VH360_Studio_Provider_Registry $registry ) {
        $this->registry = $registry;
    }

    public static function schedule() {
        $event = function_exists( 'wp_get_scheduled_event' ) ? wp_get_scheduled_event( self::CRON_HOOK ) : false;
        if ( $event && self::CRON_SCHEDULE !== $event->schedule ) {
            wp_clear_scheduled_hook( self::CRON_HOOK );
            $event = false;
        }

        if ( ! $event && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + ( 10 * MINUTE_IN_SECONDS ), self::CRON_SCHEDULE, self::CRON_HOOK );
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    public function table_name() {
        return VH360_Studio_Database::video_assets_table_name();
    }

    public function create_asset( $user_id, array $args ) {
        $user_id  = absint( $user_id );
        $filename = isset( $args['filename'] ) ? sanitize_file_name( $args['filename'] ) : '';
        $mime     = isset( $args['mime_type'] ) ? $this->canonical_mime_type( $args['mime_type'], $filename ) : '';
        $size     = isset( $args['file_size'] ) ? absint( $args['file_size'] ) : 0;
        $context  = isset( $args['context'] ) ? sanitize_key( $args['context'] ) : 'video';

        if ( ! $this->is_allowed_context( $context ) ) {
            return new WP_Error( 'vh360_studio_video_context_invalid', __( 'This video upload context is not supported.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $valid = $this->validate_file( $filename, $mime, $size, $context, $user_id );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        if ( in_array( $context, array( 'video', 'lesson' ), true ) ) {
            $can_create = function_exists( 'vh360_user_can_create_videos' )
                ? vh360_user_can_create_videos( $user_id )
                : ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'vh360_create_videos' ) );

            if ( ! $can_create ) {
                return new WP_Error( 'vh360_studio_video_create_forbidden', __( 'You do not have permission to create videos.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }

            if ( class_exists( 'VH360_Studio_Permissions' ) && ! VH360_Studio_Permissions::license_is_valid() ) {
                return new WP_Error( 'vh360_studio_license_invalid', __( 'Studio video uploads are unavailable because the Studio license is inactive.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
        }

        $provider = $this->resolve_provider_id( $context );
        if ( is_wp_error( $provider ) ) {
            return $provider;
        }

        global $wpdb;
        $now  = current_time( 'mysql' );
        $uuid = wp_generate_uuid4();
        $inserted = $wpdb->insert(
            $this->table_name(),
            array(
                'asset_uuid'        => $uuid,
                'user_id'           => $user_id,
                'context'           => $context,
                'provider'          => $provider,
                'status'            => 'pending',
                'original_filename' => $filename,
                'mime_type'         => $mime,
                'file_size'         => $size,
                'expires_at'        => wp_date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS, wp_timezone() ),
                'created_at'        => $now,
                'updated_at'        => $now,
            )
        );

        if ( false === $inserted ) {
            return new WP_Error( 'vh360_studio_video_asset_create_failed', __( 'The video upload could not be initialized.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        return $this->get_asset_by_uuid( $uuid );
    }

    public function upload_asset( $uuid, $file ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset ) {
            return new WP_Error( 'vh360_studio_video_asset_missing', __( 'Video asset not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }
        if ( ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'You cannot upload to this video asset.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( ! empty( $asset['associated_post_id'] ) || ! in_array( $asset['status'], array( 'pending', 'failed' ), true ) ) {
            return new WP_Error( 'vh360_studio_video_asset_upload_state_invalid', __( 'This video asset is not available for another upload.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! empty( $asset['provider_asset_id'] ) || ! empty( $asset['wp_attachment_id'] ) ) {
            return new WP_Error( 'vh360_studio_video_asset_upload_already_created', __( 'This video asset already has a provider resource.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'vh360_studio_video_asset_no_file', __( 'No video file was uploaded.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $detected    = $this->detect_uploaded_mime_type( $file );
        $file['type'] = $this->canonical_mime_type( $detected ? $detected : ( $file['type'] ?? '' ), $file['name'] ?? '' );
        $valid       = $this->validate_file( $file['name'] ?? '', $file['type'], absint( $file['size'] ?? 0 ), $asset['context'], absint( $asset['user_id'] ) );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $matches = $this->validate_file_matches_asset( $file, $asset );
        if ( is_wp_error( $matches ) ) {
            return $matches;
        }

        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( ! $provider || ! $this->provider_is_available_for_context( $provider, $asset['context'] ) ) {
            return new WP_Error( 'vh360_studio_video_provider_unavailable', __( 'The configured video storage provider is unavailable.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $this->update_asset(
            $asset['id'],
            array(
                'status'        => 'uploading',
                'error_code'    => '',
                'error_message' => '',
            )
        );

        $provider_asset = $asset;
        $provider_asset['_persist_provider_asset_id'] = function( $provider_asset_id ) use ( $asset ) {
            $provider_asset_id = sanitize_text_field( $provider_asset_id );
            if ( '' !== $provider_asset_id ) {
                $this->update_asset( $asset['id'], array( 'provider_asset_id' => $provider_asset_id ) );
            }
        };

        $result = method_exists( $provider, 'upload_file' )
            ? $provider->upload_file( $file, $provider_asset )
            : $this->upload_to_local_media( $file, $provider_asset );

        if ( is_wp_error( $result ) ) {
            $this->mark_failed( $asset['id'], $result );
            return $result;
        }

        $this->apply_provider_result( $asset['id'], $result );
        return $this->get_asset( $asset['id'] );
    }

    public function authorize_direct_upload( $uuid ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset || ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( 'pending' !== $asset['status'] || ! empty( $asset['associated_post_id'] ) ) {
            return array( 'method' => 'none' );
        }

        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( ! $provider || ! $this->provider_is_available_for_context( $provider, $asset['context'] ) ) {
            return new WP_Error( 'vh360_studio_video_provider_unavailable', __( 'The configured video storage provider is unavailable.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $authorization = method_exists( $provider, 'authorize_direct_upload' )
            ? $provider->authorize_direct_upload( $asset )
            : array( 'method' => 'server', 'field' => 'file' );

        if ( is_wp_error( $authorization ) ) {
            return $authorization;
        }

        if ( ! empty( $authorization['_asset_metadata'] ) && is_array( $authorization['_asset_metadata'] ) ) {
            $this->merge_provider_metadata( $asset['id'], $authorization['_asset_metadata'] );
            unset( $authorization['_asset_metadata'] );
        }

        if ( ! empty( $authorization['method'] ) && 'direct' === sanitize_key( $authorization['method'] ) ) {
            $this->update_asset(
                $asset['id'],
                array(
                    'status'        => 'uploading',
                    'error_code'    => '',
                    'error_message' => '',
                )
            );
        }

        return $authorization;
    }

    public function complete_direct_upload( $uuid, array $payload = array() ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset || ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( ! empty( $asset['associated_post_id'] ) || ! in_array( $asset['status'], array( 'pending', 'uploading' ), true ) ) {
            return new WP_Error( 'vh360_studio_video_direct_completion_state_invalid', __( 'This direct upload can no longer be completed.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( ! $provider || ! method_exists( $provider, 'complete_direct_upload' ) ) {
            return new WP_Error( 'vh360_studio_video_direct_completion_unavailable', __( 'Direct upload completion is unavailable for this provider.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $result = $provider->complete_direct_upload( $asset, $payload );
        if ( is_wp_error( $result ) ) {
            $this->mark_failed( $asset['id'], $result );
            return $result;
        }

        $this->apply_provider_result( $asset['id'], $result );
        return $this->get_asset( $asset['id'] );
    }

    public function refresh_status( $asset, $force = false ) {
        if ( is_string( $asset ) ) {
            $asset = $this->get_asset_by_uuid( $asset );
        }
        if ( ! $asset ) {
            return null;
        }
        if ( ! $force && in_array( $asset['status'], array( 'ready', 'cancelled', 'deleted', 'delete_failed' ), true ) ) {
            return $asset;
        }

        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( $provider && method_exists( $provider, 'check_asset_status' ) ) {
            $result = $provider->check_asset_status( $asset );
            if ( ! is_wp_error( $result ) && is_array( $result ) ) {
                $this->apply_provider_result( $asset['id'], $result );
                $asset = $this->get_asset( $asset['id'] );
            }
        }

        return $asset;
    }

    public function retry( $uuid ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset || ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( in_array( $asset['status'], array( 'deleted', 'delete_failed', 'cancelled' ), true ) ) {
            return new WP_Error( 'vh360_studio_video_retry_unavailable', __( 'This video asset cannot be retried.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( empty( $asset['provider_asset_id'] ) && empty( $asset['wp_attachment_id'] ) ) {
            return new WP_Error( 'vh360_studio_video_retry_requires_upload', __( 'Please select the video file again to retry this upload.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        $this->update_asset(
            $asset['id'],
            array(
                'status'        => 'processing',
                'error_code'    => '',
                'error_message' => '',
            )
        );

        return $this->refresh_status( $this->get_asset( $asset['id'] ), true );
    }

    public function cancel_or_delete( $uuid ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset || ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( ! empty( $asset['associated_post_id'] ) ) {
            return new WP_Error( 'vh360_studio_video_asset_associated', __( 'Associated video assets cannot be deleted through the upload cancellation endpoint.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        return $this->delete_asset_record( $asset );
    }

    public function validate_asset_for_association( $uuid, $post_type, $post_id = 0, $expected_context = '' ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset ) {
            return new WP_Error( 'vh360_studio_video_asset_missing', __( 'Video asset not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }
        if ( ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_association_forbidden', __( 'You cannot associate this video asset.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        $post_type        = sanitize_key( $post_type );
        $expected_context = sanitize_key( $expected_context );
        $allowed_types    = $this->allowed_post_types_for_context( $asset['context'] );

        if ( $expected_context && $expected_context !== sanitize_key( $asset['context'] ) ) {
            return new WP_Error( 'vh360_studio_video_asset_context_mismatch', __( 'This video upload belongs to a different form.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! in_array( $post_type, $allowed_types, true ) ) {
            return new WP_Error( 'vh360_studio_video_asset_post_type_mismatch', __( 'This video asset cannot be attached to that content type.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! in_array( $asset['status'], array( 'processing', 'ready' ), true ) ) {
            return new WP_Error( 'vh360_studio_video_asset_not_associable', __( 'The video upload has not completed and cannot be attached yet.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! empty( $asset['associated_post_id'] ) && absint( $asset['associated_post_id'] ) !== absint( $post_id ) ) {
            return new WP_Error( 'vh360_studio_video_asset_already_associated', __( 'This video asset is already attached to different content.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        if ( $post_id ) {
            $post = get_post( absint( $post_id ) );
            if ( ! $post || $post_type !== $post->post_type ) {
                return new WP_Error( 'vh360_studio_video_asset_post_missing', __( 'The destination content could not be found.', 'videohub360-studio' ), array( 'status' => 404 ) );
            }
            $owns_post = absint( $post->post_author ) === get_current_user_id();
            if ( ! $owns_post && ! current_user_can( 'edit_post', absint( $post_id ) ) ) {
                return new WP_Error( 'vh360_studio_video_asset_association_forbidden', __( 'You cannot associate this video asset with that post.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
        }

        return $asset;
    }

    public function associate_asset( $uuid, $post_id, $post_type = '', $expected_context = '' ) {
        $post_id   = absint( $post_id );
        $post      = get_post( $post_id );
        $post_type = $post_type ? sanitize_key( $post_type ) : ( $post ? $post->post_type : '' );
        $asset     = $this->validate_asset_for_association( $uuid, $post_type, $post_id, $expected_context );

        if ( is_wp_error( $asset ) ) {
            return $asset;
        }

        if ( absint( $asset['associated_post_id'] ) === $post_id ) {
            update_post_meta( $post_id, '_vh360_studio_video_asset_id', absint( $asset['id'] ) );
            return $asset;
        }

        $updated = $this->update_asset(
            $asset['id'],
            array(
                'associated_post_id'   => $post_id,
                'associated_post_type' => $post_type,
                'expires_at'           => null,
            )
        );

        if ( false === $updated ) {
            return new WP_Error( 'vh360_studio_video_asset_association_failed', __( 'The video asset could not be attached to the content.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        update_post_meta( $post_id, '_vh360_studio_video_asset_id', absint( $asset['id'] ) );
        return $this->get_asset( $asset['id'] );
    }

    public function delete_asset_by_id( $asset_id ) {
        $asset = $this->get_asset( absint( $asset_id ) );
        if ( ! $asset ) {
            return true;
        }

        return $this->delete_asset_record( $asset );
    }

    public function delete_asset_for_post( $post_id ) {
        global $wpdb;
        $assets = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE associated_post_id = %d", absint( $post_id ) ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        if ( ! $assets ) {
            return true;
        }

        $first_error = null;
        foreach ( $assets as $asset ) {
            $deleted = $this->delete_asset_record( $asset );
            if ( is_wp_error( $deleted ) && ! $first_error ) {
                $first_error = $deleted;
            }
        }

        return $first_error ? $first_error : true;
    }

    public function get_playback( $asset_id ) {
        $asset = is_array( $asset_id ) ? $asset_id : $this->get_asset( absint( $asset_id ) );
        if ( ! $asset ) {
            return array( 'status' => 'failed', 'render_mode' => 'native', 'src' => '', 'embed_url' => '', 'embed_html' => '', 'poster_url' => '', 'mime_type' => '' );
        }
        if ( 'ready' !== $asset['status'] ) {
            return array( 'status' => $asset['status'], 'render_mode' => 'native', 'src' => '', 'embed_url' => '', 'embed_html' => '', 'poster_url' => '', 'mime_type' => sanitize_mime_type( $asset['mime_type'] ) );
        }

        if ( 'videopress' === sanitize_key( $asset['provider'] ) && ! empty( $asset['videopress_guid'] ) ) {
            $embed_html = $this->render_videopress_embed_html( $asset['videopress_guid'] );
            if ( $embed_html ) {
                return array(
                    'status'      => 'ready',
                    'render_mode' => 'embed_html',
                    'src'         => '',
                    'embed_url'   => '',
                    'embed_html'  => $embed_html,
                    'poster_url'  => esc_url_raw( $asset['poster_url'] ),
                    'mime_type'   => sanitize_mime_type( $asset['mime_type'] ),
                );
            }
        }

        return array(
            'status'      => 'ready',
            'render_mode' => ! empty( $asset['embed_url'] ) ? 'embed' : 'native',
            'src'         => esc_url_raw( $asset['playback_url'] ),
            'embed_url'   => esc_url_raw( $asset['embed_url'] ),
            'embed_html'  => '',
            'poster_url'  => esc_url_raw( $asset['poster_url'] ),
            'mime_type'   => sanitize_mime_type( $asset['mime_type'] ),
        );
    }

    public function run_cron() {
        global $wpdb;
        $table = $this->table_name();
        $limit = max( 1, min( 100, (int) apply_filters( 'vh360_studio_video_asset_cron_limit', 50 ) ) );

        $assets = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'processing' ORDER BY updated_at ASC LIMIT %d", $limit ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( $assets as $asset ) {
            $this->refresh_status( $asset );
        }

        $expired = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE associated_post_id = 0 AND expires_at IS NOT NULL AND expires_at < %s AND status IN ('pending','uploading','processing','failed','cancelled','delete_failed') ORDER BY updated_at ASC LIMIT %d",
                current_time( 'mysql' ),
                $limit
            ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        foreach ( $expired as $asset ) {
            $this->delete_asset_record( $asset );
        }
    }

    public function prepare_response( $asset ) {
        $upload = array( 'method' => 'none' );
        if ( 'pending' === $asset['status'] && empty( $asset['associated_post_id'] ) ) {
            $upload = $this->authorize_direct_upload( $asset['asset_uuid'] );
            if ( is_wp_error( $upload ) ) {
                $upload = array( 'method' => 'server', 'field' => 'file' );
            }
            $asset = $this->get_asset( $asset['id'] );
        }

        return array(
            'id'                 => absint( $asset['id'] ),
            'asset_uuid'         => $asset['asset_uuid'],
            'context'            => $asset['context'],
            'provider'           => $asset['provider'],
            'status'             => $asset['status'],
            'original_filename'  => $asset['original_filename'],
            'mime_type'          => $asset['mime_type'],
            'file_size'          => absint( $asset['file_size'] ),
            'playback_url'       => esc_url_raw( $asset['playback_url'] ),
            'embed_url'          => esc_url_raw( $asset['embed_url'] ),
            'poster_url'         => esc_url_raw( $asset['poster_url'] ),
            'error_code'         => $asset['error_code'],
            'error_message'      => $asset['error_message'],
            'associated_post_id' => absint( $asset['associated_post_id'] ),
            'upload'             => $upload,
            'playback'           => $this->get_playback( $asset ),
        );
    }

    public function get_asset_by_uuid( $uuid ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE asset_uuid = %s", sanitize_text_field( $uuid ) ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    public function get_asset( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE id = %d", absint( $id ) ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    private function resolve_provider_id( $context = 'video' ) {
        $saved = sanitize_key( get_option( 'vh360_studio_default_replay_storage_provider', 'videopress' ) );
        if ( 'local' === $saved ) {
            $saved = 'local_media';
        }

        $candidates = array_values( array_unique( array_filter( array_merge( array( $saved ), array( 'videopress', 'publitio', 'bunny_stream', 'local_media' ) ) ) ) );
        foreach ( $candidates as $provider_id ) {
            $provider = $this->registry->get_storage_provider( $provider_id );
            if ( $provider && $this->provider_is_available_for_context( $provider, $context ) ) {
                return sanitize_key( $provider_id );
            }
        }

        return new WP_Error( 'vh360_studio_video_provider_unavailable', __( 'No configured video storage provider is currently available.', 'videohub360-studio' ), array( 'status' => 503 ) );
    }

    private function provider_is_available_for_context( $provider, $context ) {
        if ( method_exists( $provider, 'is_available_for_video_upload' ) ) {
            return (bool) $provider->is_available_for_video_upload( $context );
        }
        return ! method_exists( $provider, 'is_available' ) || (bool) $provider->is_available();
    }

    private function is_allowed_context( $context ) {
        $contexts = (array) apply_filters( 'vh360_studio_video_upload_contexts', array( 'video', 'lesson', 'activity_video' ) );
        return in_array( sanitize_key( $context ), array_map( 'sanitize_key', $contexts ), true );
    }

    private function allowed_post_types_for_context( $context ) {
        $map = array(
            'video'          => array( 'videohub360' ),
            'lesson'         => array( 'videohub360' ),
            'activity_video' => array( 'vh360_post' ),
        );
        $map = (array) apply_filters( 'vh360_studio_video_asset_context_post_types', $map );
        return isset( $map[ $context ] ) ? array_map( 'sanitize_key', (array) $map[ $context ] ) : array();
    }

    private function detect_uploaded_mime_type( array $file ) {
        $finfo_type = '';
        $wp_type    = '';

        if ( ! empty( $file['tmp_name'] ) && function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            if ( $finfo ) {
                $value = finfo_file( $finfo, $file['tmp_name'] );
                finfo_close( $finfo );
                if ( $value ) {
                    $finfo_type = $this->canonical_mime_type( $value, $file['name'] ?? '' );
                }
            }
        }

        if ( function_exists( 'wp_check_filetype_and_ext' ) && ! empty( $file['tmp_name'] ) && ! empty( $file['name'] ) ) {
            $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
            if ( ! empty( $check['type'] ) ) {
                $wp_type = $this->canonical_mime_type( $check['type'], $file['name'] );
            }
        }

        if ( 0 === strpos( $finfo_type, 'video/' ) ) {
            return $finfo_type;
        }
        if ( 0 === strpos( $wp_type, 'video/' ) ) {
            return $wp_type;
        }

        return $finfo_type ? $finfo_type : $wp_type;
    }

    private function canonical_mime_type( $mime, $filename = '' ) {
        $mime = strtolower( sanitize_mime_type( (string) $mime ) );
        $aliases = array(
            'video/x-m4v'          => 'video/mp4',
            'application/mp4'      => 'video/mp4',
            'video/x-quicktime'    => 'video/quicktime',
            'application/ogg'      => 'video/ogg',
            'application/octet-stream' => '',
        );
        if ( array_key_exists( $mime, $aliases ) ) {
            $mime = $aliases[ $mime ];
        }

        if ( ! $mime || 0 !== strpos( $mime, 'video/' ) ) {
            $extension = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
            $by_extension = array(
                'mp4'  => 'video/mp4',
                'm4v'  => 'video/mp4',
                'webm' => 'video/webm',
                'mov'  => 'video/quicktime',
                'qt'   => 'video/quicktime',
                'ogv'  => 'video/ogg',
                'ogg'  => 'video/ogg',
            );
            if ( isset( $by_extension[ $extension ] ) ) {
                $mime = $by_extension[ $extension ];
            }
        }

        return $mime;
    }

    private function validate_file( $filename, $mime, $size, $context, $user_id ) {
        if ( $size <= 0 ) {
            return new WP_Error( 'vh360_studio_video_empty_file', __( 'The video file is empty.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( 0 !== strpos( (string) $mime, 'video/' ) ) {
            return new WP_Error( 'vh360_studio_video_invalid_type', __( 'Only video files may be uploaded.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }

        if ( 'activity_video' === $context && function_exists( 'vh360_validate_post_upload' ) ) {
            $validation = vh360_validate_post_upload( $mime, $size );
            if ( empty( $validation['allowed'] ) ) {
                return new WP_Error( 'vh360_studio_video_upload_not_allowed', ! empty( $validation['message'] ) ? $validation['message'] : __( 'This video upload is not allowed.', 'videohub360-studio' ), array( 'status' => 400 ) );
            }
            return true;
        }

        if ( function_exists( 'vh360_get_video_upload_settings' ) ) {
            $settings = vh360_get_video_upload_settings();
            if ( empty( $settings['enable_video_upload'] ) ) {
                return new WP_Error( 'vh360_studio_video_upload_disabled', __( 'Video upload is currently disabled.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
            if ( ! user_can( $user_id, 'upload_files' ) ) {
                return new WP_Error( 'vh360_studio_video_upload_forbidden', __( 'You do not have permission to upload files.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }

            $max_size = ! empty( $settings['max_file_size'] ) ? absint( $settings['max_file_size'] ) * MB_IN_BYTES : 500 * MB_IN_BYTES;
            if ( $size > $max_size ) {
                return new WP_Error( 'vh360_studio_video_too_large', __( 'File size exceeds the maximum allowed size.', 'videohub360-studio' ), array( 'status' => 400 ) );
            }

            $allowed = ! empty( $settings['allowed_formats'] )
                ? array_map( 'sanitize_key', array_map( 'trim', explode( ',', $settings['allowed_formats'] ) ) )
                : array( 'mp4', 'webm', 'mov' );
            $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
            if ( $extension && ! in_array( $extension, $allowed, true ) ) {
                return new WP_Error( 'vh360_studio_video_extension_not_allowed', __( 'File type not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
            }
        }

        return true;
    }

    private function validate_file_matches_asset( array $file, array $asset ) {
        $actual_size   = absint( $file['size'] ?? 0 );
        $expected_size = absint( $asset['file_size'] ?? 0 );
        if ( $expected_size && $actual_size !== $expected_size ) {
            return new WP_Error( 'vh360_studio_video_asset_size_mismatch', __( 'The uploaded file does not match the initialized video upload.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        $actual_mime   = $this->canonical_mime_type( $file['type'] ?? '', $file['name'] ?? '' );
        $expected_mime = $this->canonical_mime_type( $asset['mime_type'] ?? '', $asset['original_filename'] ?? '' );
        if ( $expected_mime && $actual_mime && $expected_mime !== $actual_mime ) {
            return new WP_Error( 'vh360_studio_video_asset_mime_mismatch', __( 'The uploaded file type does not match the initialized video upload.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        return true;
    }

    private function upload_to_local_media( $file, $asset ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_sideload(
            array(
                'name'     => $file['name'],
                'type'     => $file['type'],
                'tmp_name' => $file['tmp_name'],
                'error'    => 0,
                'size'     => absint( $file['size'] ),
            ),
            0
        );
        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        return array(
            'provider'          => 'local_media',
            'status'            => 'ready',
            'provider_asset_id' => (string) $attachment_id,
            'wp_attachment_id'  => absint( $attachment_id ),
            'videopress_guid'   => '',
            'playback_url'      => wp_get_attachment_url( $attachment_id ),
            'embed_url'         => '',
            'poster_url'        => wp_get_attachment_image_url( $attachment_id, 'large' ),
            'mime_type'         => get_post_mime_type( $attachment_id ),
            'file_size'         => absint( $file['size'] ),
            'metadata'          => array(),
            'error_code'        => '',
            'error_message'     => '',
        );
    }

    private function apply_provider_result( $id, array $result ) {
        $existing = $this->get_asset( $id );
        $provider = sanitize_key( $result['provider'] ?? $result['provider_id'] ?? '' );
        if ( ! $provider && $existing ) {
            $provider = sanitize_key( $existing['provider'] );
        }

        $this->update_asset(
            $id,
            array(
                'provider'          => $provider,
                'status'            => sanitize_key( $result['status'] ?? 'processing' ),
                'provider_asset_id' => sanitize_text_field( $result['provider_asset_id'] ?? $result['provider_file_id'] ?? $result['publitio_file_id'] ?? '' ),
                'wp_attachment_id'  => absint( $result['wp_attachment_id'] ?? $result['attachment_id'] ?? 0 ),
                'videopress_guid'   => sanitize_text_field( $result['videopress_guid'] ?? '' ),
                'playback_url'      => esc_url_raw( $result['playback_url'] ?? '' ),
                'embed_url'         => esc_url_raw( $result['embed_url'] ?? $result['provider_embed_url'] ?? '' ),
                'poster_url'        => esc_url_raw( $result['poster_url'] ?? '' ),
                'mime_type'         => sanitize_mime_type( $result['mime_type'] ?? ( $existing['mime_type'] ?? '' ) ),
                'file_size'         => absint( $result['file_size'] ?? ( $existing['file_size'] ?? 0 ) ),
                'provider_metadata' => wp_json_encode( $result['metadata'] ?? $result ),
                'error_code'        => sanitize_key( $result['error_code'] ?? '' ),
                'error_message'     => sanitize_textarea_field( $result['error_message'] ?? '' ),
            )
        );
    }

    private function mark_failed( $id, WP_Error $error ) {
        $data   = $error->get_error_data();
        $update = array(
            'status'        => 'failed',
            'error_code'    => $error->get_error_code(),
            'error_message' => $error->get_error_message(),
        );
        if ( is_array( $data ) && ! empty( $data['provider_asset_id'] ) ) {
            $update['provider_asset_id'] = sanitize_text_field( $data['provider_asset_id'] );
        }
        $this->update_asset( $id, $update );
    }

    private function delete_asset_record( array $asset ) {
        if ( 'deleted' === $asset['status'] ) {
            return true;
        }

        $this->update_asset(
            $asset['id'],
            array(
                'status'               => 'deleting',
                'associated_post_id'   => 0,
                'associated_post_type' => '',
            )
        );

        $deleted = $this->delete_provider_asset( $asset );
        if ( is_wp_error( $deleted ) ) {
            $this->update_asset(
                $asset['id'],
                array(
                    'status'        => 'delete_failed',
                    'error_code'    => $deleted->get_error_code(),
                    'error_message' => $deleted->get_error_message(),
                    'expires_at'    => wp_date( 'Y-m-d H:i:s', time() + ( 10 * MINUTE_IN_SECONDS ), wp_timezone() ),
                )
            );
            return $deleted;
        }

        $this->update_asset(
            $asset['id'],
            array(
                'status'        => 'deleted',
                'error_code'    => '',
                'error_message' => '',
                'expires_at'    => null,
            )
        );
        return true;
    }

    private function update_asset( $id, array $data ) {
        global $wpdb;
        $data['updated_at'] = current_time( 'mysql' );
        return $wpdb->update( $this->table_name(), $data, array( 'id' => absint( $id ) ) );
    }

    private function merge_provider_metadata( $id, array $metadata ) {
        $asset    = $this->get_asset( $id );
        $existing = $asset ? json_decode( (string) $asset['provider_metadata'], true ) : array();
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }
        $this->update_asset( $id, array( 'provider_metadata' => wp_json_encode( array_merge( $existing, $metadata ) ) ) );
    }

    private function current_user_can_access( $asset ) {
        return current_user_can( 'manage_options' ) || absint( $asset['user_id'] ) === get_current_user_id();
    }

    private function delete_provider_asset( $asset ) {
        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( ! $provider ) {
            return new WP_Error( 'vh360_studio_video_provider_missing', __( 'The video storage provider is no longer registered.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! method_exists( $provider, 'delete_asset' ) ) {
            return true;
        }

        $deleted = $provider->delete_asset( $asset );
        if ( false === $deleted ) {
            return new WP_Error( 'vh360_studio_video_provider_delete_failed', __( 'The video could not be removed from its storage provider.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }
        return $deleted;
    }

    private function render_videopress_embed_html( $guid ) {
        $guid = sanitize_text_field( $guid );
        if ( ! $guid || ! preg_match( '/^[A-Za-z0-9_-]{8,}$/', $guid ) || ! function_exists( 'do_shortcode' ) ) {
            return '';
        }

        $shortcode = '[videopress ' . $guid . ']';
        $rendered  = do_shortcode( $shortcode );
        if ( ! is_string( $rendered ) || '' === trim( $rendered ) || trim( $rendered ) === $shortcode ) {
            return '';
        }

        $global_attributes = array(
            'class' => true,
            'id'    => true,
            'style' => true,
            'role'  => true,
        );
        $allowed = apply_filters(
            'vh360_studio_allowed_videopress_embed_html',
            array(
                'iframe' => array(
                    'src'             => true,
                    'width'           => true,
                    'height'          => true,
                    'frameborder'     => true,
                    'allow'           => true,
                    'allowfullscreen' => true,
                    'title'           => true,
                    'loading'         => true,
                    'referrerpolicy'  => true,
                    'style'           => true,
                    'class'           => true,
                ),
                'div'    => $global_attributes,
                'figure' => $global_attributes,
                'video'  => array_merge(
                    $global_attributes,
                    array(
                        'src'         => true,
                        'controls'    => true,
                        'playsinline' => true,
                        'poster'      => true,
                        'preload'     => true,
                        'width'       => true,
                        'height'      => true,
                    )
                ),
                'source' => array(
                    'src'  => true,
                    'type' => true,
                ),
                'a'      => array(
                    'href'   => true,
                    'target' => true,
                    'rel'    => true,
                    'class'  => true,
                ),
            ),
            $guid,
            $rendered
        );

        return wp_kses( $rendered, $allowed );
    }
}

function vh360_studio_get_video_playback( $asset_id ) {
    if ( ! class_exists( 'VH360_Studio_Plugin' ) ) {
        return array( 'status' => 'failed', 'render_mode' => 'native', 'src' => '', 'embed_url' => '', 'embed_html' => '', 'poster_url' => '', 'mime_type' => '' );
    }
    return VH360_Studio_Plugin::instance()->video_storage()->get_playback( $asset_id );
}
