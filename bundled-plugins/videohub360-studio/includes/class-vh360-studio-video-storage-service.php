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
    const CRON_HOOK = 'vh360_studio_video_assets_cron';

    private $registry;

    public function __construct( VH360_Studio_Provider_Registry $registry ) {
        $this->registry = $registry;
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::CRON_HOOK );
        }
    }

    public static function unschedule() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    public function table_name() {
        return VH360_Studio_Database::video_assets_table_name();
    }

    public function create_asset( $user_id, array $args ) {
        $filename = isset( $args['filename'] ) ? sanitize_file_name( $args['filename'] ) : '';
        $mime     = isset( $args['mime_type'] ) ? sanitize_mime_type( $args['mime_type'] ) : '';
        $size     = isset( $args['file_size'] ) ? absint( $args['file_size'] ) : 0;
        $context  = isset( $args['context'] ) ? sanitize_key( $args['context'] ) : 'video';
        $valid    = $this->validate_file( $filename, $mime, $size, $context );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        if ( in_array( $context, array( 'video', 'lesson' ), true ) ) {
            $can_create = function_exists( 'vh360_user_can_create_videos' ) ? vh360_user_can_create_videos( $user_id ) : ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'vh360_create_videos' ) );
            if ( ! $can_create ) {
                return new WP_Error( 'vh360_studio_video_create_forbidden', __( 'You do not have permission to create videos.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
            if ( class_exists( 'VH360_Studio_Permissions' ) && ! VH360_Studio_Permissions::license_is_valid() ) {
                return new WP_Error( 'vh360_studio_license_invalid', __( 'Studio video uploads are unavailable because the Studio license is inactive.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
        }

        global $wpdb;
        $now      = current_time( 'mysql' );
        $provider = $this->resolve_provider_id();
        $uuid     = wp_generate_uuid4();
        $wpdb->insert(
            $this->table_name(),
            array(
                'asset_uuid'        => $uuid,
                'user_id'           => absint( $user_id ),
                'context'           => $context,
                'provider'          => $provider,
                'status'            => 'pending',
                'original_filename' => $filename,
                'mime_type'         => $mime,
                'file_size'         => $size,
                'expires_at'        => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
                'created_at'        => $now,
                'updated_at'        => $now,
            )
        );
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
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'vh360_studio_video_asset_no_file', __( 'No video file was uploaded.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $detected = $this->detect_uploaded_mime_type( $file );
        $file['type'] = $detected ? $detected : $file['type'];
        $valid = $this->validate_file( $file['name'], $file['type'], absint( $file['size'] ), $asset['context'] );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }
        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        $skip_availability = 'local_media' === $asset['provider'] && 'activity_video' === $asset['context'];
        if ( ! $provider || ( ! $skip_availability && method_exists( $provider, 'is_available' ) && ! $provider->is_available() ) ) {
            return new WP_Error( 'vh360_studio_video_provider_unavailable', __( 'The configured video storage provider is unavailable.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( method_exists( $provider, 'upload_file' ) ) {
            $result = $provider->upload_file( $file, $asset );
        } else {
            $result = $this->upload_to_local_media( $file, $asset );
        }
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
        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( $provider && method_exists( $provider, 'authorize_direct_upload' ) ) {
            return $provider->authorize_direct_upload( $asset );
        }
        return array( 'method' => 'server', 'field' => 'file' );
    }

    public function complete_direct_upload( $uuid, array $payload = array() ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset || ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( $provider && method_exists( $provider, 'complete_direct_upload' ) ) {
            $result = $provider->complete_direct_upload( $asset, $payload );
            if ( is_wp_error( $result ) ) {
                $this->mark_failed( $asset['id'], $result );
                return $result;
            }
            $this->apply_provider_result( $asset['id'], $result );
        }
        return $this->get_asset( $asset['id'] );
    }

    public function refresh_status( $asset ) {
        if ( is_string( $asset ) ) {
            $asset = $this->get_asset_by_uuid( $asset );
        }
        if ( ! $asset ) {
            return null;
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
        $this->update_asset( $asset['id'], array( 'status' => 'pending', 'error_code' => '', 'error_message' => '' ) );
        return $this->get_asset( $asset['id'] );
    }

    public function cancel_or_delete( $uuid ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset || ! $this->current_user_can_access( $asset ) ) {
            return new WP_Error( 'vh360_studio_video_asset_forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( ! empty( $asset['associated_post_id'] ) ) {
            return new WP_Error( 'vh360_studio_video_asset_associated', __( 'Associated video assets cannot be deleted through the upload cancellation endpoint.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $deleted = $this->delete_provider_asset( $asset );
        if ( is_wp_error( $deleted ) ) { return $deleted; }
        $this->update_asset( $asset['id'], array( 'status' => 'deleted' ) );
        return true;
    }

    public function associate_asset( $uuid, $post_id, $post_type = '' ) {
        $asset = $this->get_asset_by_uuid( $uuid );
        if ( ! $asset ) {
            return new WP_Error( 'vh360_studio_video_asset_missing', __( 'Video asset not found.', 'videohub360-studio' ) );
        }
        $post = get_post( absint( $post_id ) );
        $owns_post = $post && absint( $post->post_author ) === get_current_user_id();
        if ( ! $this->current_user_can_access( $asset ) || ( ! $owns_post && ! current_user_can( 'edit_post', absint( $post_id ) ) ) ) {
            return new WP_Error( 'vh360_studio_video_asset_association_forbidden', __( 'You cannot associate this video asset with that post.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( in_array( $asset['status'], array( 'failed', 'cancelled', 'deleted' ), true ) ) {
            return new WP_Error( 'vh360_studio_video_asset_not_associable', __( 'This video asset cannot be associated.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $this->update_asset( $asset['id'], array( 'associated_post_id' => absint( $post_id ), 'associated_post_type' => $post_type ? sanitize_key( $post_type ) : get_post_type( $post_id ), 'expires_at' => null ) );
        update_post_meta( $post_id, '_vh360_studio_video_asset_id', absint( $asset['id'] ) );
        return $this->get_asset( $asset['id'] );
    }


    public function delete_asset_for_post( $post_id ) {
        global $wpdb;
        $asset = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE associated_post_id = %d LIMIT 1", absint( $post_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( ! $asset ) { return true; }
        $deleted = $this->delete_provider_asset( $asset );
        if ( is_wp_error( $deleted ) ) { return $deleted; }
        $this->update_asset( $asset['id'], array( 'status' => 'deleted', 'associated_post_id' => 0 ) );
        return true;
    }

    public function get_playback( $asset_id ) {
        $asset = is_array( $asset_id ) ? $asset_id : $this->get_asset( absint( $asset_id ) );
        if ( ! $asset ) {
            return array( 'status' => 'failed', 'render_mode' => 'native', 'src' => '', 'embed_url' => '', 'poster_url' => '', 'mime_type' => '' );
        }
        if ( 'ready' !== $asset['status'] ) {
            return array( 'status' => $asset['status'], 'render_mode' => 'native', 'src' => '', 'embed_url' => '', 'poster_url' => '', 'mime_type' => $asset['mime_type'] );
        }
        return array( 'status' => 'ready', 'render_mode' => ! empty( $asset['embed_url'] ) ? 'embed' : 'native', 'src' => esc_url_raw( $asset['playback_url'] ), 'embed_url' => esc_url_raw( $asset['embed_url'] ), 'poster_url' => esc_url_raw( $asset['poster_url'] ), 'mime_type' => sanitize_mime_type( $asset['mime_type'] ) );
    }

    public function run_cron() {
        global $wpdb;
        $table = $this->table_name();
        $assets = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'processing' ORDER BY updated_at ASC LIMIT 20", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( $assets as $asset ) {
            $this->refresh_status( $asset );
        }
        $expired = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE associated_post_id = 0 AND expires_at IS NOT NULL AND expires_at < %s AND status IN ('pending','uploading','processing','failed','cancelled') LIMIT 20", current_time( 'mysql' ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        foreach ( $expired as $asset ) {
            $deleted = $this->delete_provider_asset( $asset );
            if ( ! is_wp_error( $deleted ) ) {
                $this->update_asset( $asset['id'], array( 'status' => 'deleted' ) );
            }
        }
    }

    public function prepare_response( $asset ) {
        $upload = $this->authorize_direct_upload( $asset['asset_uuid'] );
        if ( is_wp_error( $upload ) ) {
            $upload = array( 'method' => 'server', 'field' => 'file' );
        }
        return array( 'id' => absint( $asset['id'] ), 'asset_uuid' => $asset['asset_uuid'], 'context' => $asset['context'], 'provider' => $asset['provider'], 'status' => $asset['status'], 'original_filename' => $asset['original_filename'], 'mime_type' => $asset['mime_type'], 'file_size' => absint( $asset['file_size'] ), 'playback_url' => esc_url_raw( $asset['playback_url'] ), 'embed_url' => esc_url_raw( $asset['embed_url'] ), 'poster_url' => esc_url_raw( $asset['poster_url'] ), 'error_code' => $asset['error_code'], 'error_message' => $asset['error_message'], 'associated_post_id' => absint( $asset['associated_post_id'] ), 'upload' => $upload, 'playback' => $this->get_playback( $asset ) );
    }

    private function resolve_provider_id() {
        if ( class_exists( 'VH360_Studio_Replay_Storage_Settings' ) ) {
            return VH360_Studio_Replay_Storage_Settings::resolve_default_provider();
        }
        return sanitize_key( get_option( 'vh360_studio_default_replay_storage_provider', 'videopress' ) );
    }


    private function detect_uploaded_mime_type( array $file ) {
        $detected = '';
        if ( ! empty( $file['tmp_name'] ) && function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            if ( $finfo ) {
                $value = finfo_file( $finfo, $file['tmp_name'] );
                finfo_close( $finfo );
                if ( $value ) { $detected = sanitize_mime_type( $value ); }
            }
        }
        if ( ! $detected && function_exists( 'wp_check_filetype_and_ext' ) && ! empty( $file['tmp_name'] ) && ! empty( $file['name'] ) ) {
            $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
            if ( ! empty( $check['type'] ) ) { $detected = sanitize_mime_type( $check['type'] ); }
        }
        return $detected;
    }

    private function validate_file( $filename, $mime, $size, $context ) {
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
        } elseif ( function_exists( 'vh360_get_video_upload_settings' ) ) {
            $settings = vh360_get_video_upload_settings();
            if ( empty( $settings['enable_video_upload'] ) ) {
                return new WP_Error( 'vh360_studio_video_upload_disabled', __( 'Video upload is currently disabled.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
            if ( ! current_user_can( 'upload_files' ) ) {
                return new WP_Error( 'vh360_studio_video_upload_forbidden', __( 'You do not have permission to upload files.', 'videohub360-studio' ), array( 'status' => 403 ) );
            }
            $max_size = ! empty( $settings['max_file_size'] ) ? absint( $settings['max_file_size'] ) * 1024 * 1024 : 500 * 1024 * 1024;
            if ( $size > $max_size ) {
                return new WP_Error( 'vh360_studio_video_too_large', __( 'File size exceeds the maximum allowed size.', 'videohub360-studio' ), array( 'status' => 400 ) );
            }
            $allowed = ! empty( $settings['allowed_formats'] ) ? array_map( 'trim', explode( ',', $settings['allowed_formats'] ) ) : array( 'mp4', 'webm', 'mov' );
            $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
            if ( $ext && ! in_array( $ext, $allowed, true ) ) {
                return new WP_Error( 'vh360_studio_video_extension_not_allowed', __( 'File type not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
            }
        }
        return true;
    }

    private function upload_to_local_media( $file, $asset ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = media_handle_sideload( array( 'name' => $file['name'], 'type' => $file['type'], 'tmp_name' => $file['tmp_name'], 'error' => 0, 'size' => absint( $file['size'] ) ), 0 );
        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }
        return array( 'provider' => 'local_media', 'status' => 'ready', 'provider_asset_id' => (string) $attachment_id, 'wp_attachment_id' => absint( $attachment_id ), 'videopress_guid' => '', 'playback_url' => wp_get_attachment_url( $attachment_id ), 'embed_url' => '', 'poster_url' => wp_get_attachment_image_url( $attachment_id, 'large' ), 'mime_type' => get_post_mime_type( $attachment_id ), 'file_size' => absint( $file['size'] ), 'metadata' => array(), 'error_code' => '', 'error_message' => '' );
    }

    private function apply_provider_result( $id, array $result ) {
        $this->update_asset( $id, array( 'provider' => sanitize_key( $result['provider'] ?? $result['provider_id'] ?? '' ), 'status' => sanitize_key( $result['status'] ?? 'processing' ), 'provider_asset_id' => sanitize_text_field( $result['provider_asset_id'] ?? $result['provider_file_id'] ?? $result['publitio_file_id'] ?? '' ), 'wp_attachment_id' => absint( $result['wp_attachment_id'] ?? $result['attachment_id'] ?? 0 ), 'videopress_guid' => sanitize_text_field( $result['videopress_guid'] ?? '' ), 'playback_url' => esc_url_raw( $result['playback_url'] ?? '' ), 'embed_url' => esc_url_raw( $result['embed_url'] ?? $result['provider_embed_url'] ?? '' ), 'poster_url' => esc_url_raw( $result['poster_url'] ?? '' ), 'mime_type' => sanitize_mime_type( $result['mime_type'] ?? '' ), 'file_size' => absint( $result['file_size'] ?? 0 ), 'provider_metadata' => wp_json_encode( $result['metadata'] ?? $result ), 'error_code' => sanitize_key( $result['error_code'] ?? '' ), 'error_message' => sanitize_textarea_field( $result['error_message'] ?? '' ) ) );
    }

    private function mark_failed( $id, WP_Error $error ) {
        $data = $error->get_error_data();
        $update = array( 'status' => 'failed', 'error_code' => $error->get_error_code(), 'error_message' => $error->get_error_message() );
        if ( is_array( $data ) && ! empty( $data['provider_asset_id'] ) ) { $update['provider_asset_id'] = sanitize_text_field( $data['provider_asset_id'] ); }
        $this->update_asset( $id, $update );
    }

    private function update_asset( $id, array $data ) {
        global $wpdb;
        $data['updated_at'] = current_time( 'mysql' );
        $wpdb->update( $this->table_name(), $data, array( 'id' => absint( $id ) ) );
    }

    public function get_asset_by_uuid( $uuid ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE asset_uuid = %s", sanitize_text_field( $uuid ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    public function get_asset( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name()} WHERE id = %d", absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    private function current_user_can_access( $asset ) {
        return current_user_can( 'manage_options' ) || absint( $asset['user_id'] ) === get_current_user_id();
    }

    private function delete_provider_asset( $asset ) {
        $provider = $this->registry->get_storage_provider( $asset['provider'] );
        if ( $provider && method_exists( $provider, 'delete_asset' ) ) {
            return $provider->delete_asset( $asset );
        }
        return true;
    }
}

function vh360_studio_get_video_playback( $asset_id ) {
    if ( ! class_exists( 'VH360_Studio_Plugin' ) ) {
        return array( 'status' => 'failed', 'render_mode' => 'native', 'src' => '', 'embed_url' => '', 'poster_url' => '', 'mime_type' => '' );
    }
    return VH360_Studio_Plugin::instance()->video_storage()->get_playback( $asset_id );
}
