<?php
/**
 * Studio REST API controller.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_REST_Controller {
    private $jobs;
    private $chunks;
    private $validator;
    private $publisher;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs   = $jobs;
        $this->chunks = new VH360_Studio_Recording_Chunks( $jobs );
        $this->validator = new VH360_Studio_Recording_Validator( $this->chunks );
        $this->publisher = new VH360_Studio_Replay_Publisher( VH360_Studio_Plugin::instance()->registry(), $jobs, $this->validator, $this->chunks );
    }

    private function livestream_service() {
        return class_exists( 'VideoHub360_Livestream_Service' ) ? new VideoHub360_Livestream_Service() : null;
    }

    public function register_routes() {
        register_rest_route(
            'vh360-studio/v1',
            '/broadcasts',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_broadcast' ),
                'permission_callback' => array( $this, 'licensed_permissions_check' ),
                'args'                => $this->get_broadcast_args(),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/cover-image',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'upload_cover_image' ),
                'permission_callback' => array( $this, 'licensed_permissions_check' ),
            )
        );

        foreach ( array( 'prepare', 'started' ) as $broadcast_action ) {
            register_rest_route(
                'vh360-studio/v1',
                '/broadcasts/(?P<video_id>\d+)/' . $broadcast_action,
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'broadcast_' . $broadcast_action ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                    'args'                => array( 'video_id' => $this->get_id_arg() ),
                )
            );
        }

        register_rest_route(
            'vh360-studio/v1',
            '/broadcasts/(?P<video_id>\d+)/renew-token',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'broadcast_renew_token' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'video_id' => $this->get_id_arg() ),
            )
        );

        foreach ( array( 'heartbeat', 'end' ) as $broadcast_action ) {
            register_rest_route(
                'vh360-studio/v1',
                '/broadcasts/(?P<video_id>\d+)/' . $broadcast_action,
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'broadcast_' . $broadcast_action ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array( 'video_id' => $this->get_id_arg() ),
                )
            );
        }


        register_rest_route(
            'vh360-studio/v1',
            '/media-sources',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_media_sources' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'upload_media_source' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                    'args'                => array( 'display_name' => $this->get_limited_text_arg( false, 120 ) ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/media-sources/(?P<id>\d+)',
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_media_source' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_jobs' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => $this->get_list_args(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_job' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                    'args'                => $this->get_setup_create_args(),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_job' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array( 'id' => $this->get_id_arg() ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_job' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                    'args'                => array_merge( array( 'id' => $this->get_id_arg() ), $this->get_update_args() ),
                ),
            )
        );



        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/recording/start',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'start_recording' ),
                'permission_callback' => array( $this, 'licensed_permissions_check' ),
                'args'                => array(
                    'id'        => $this->get_id_arg(),
                    'mime_type' => $this->get_mime_type_arg( true ),
                ),
            )
        );
        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/chunks',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'upload_chunk' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array(
                        'id'                 => $this->get_id_arg(),
                        'browser_session_id' => $this->get_browser_session_arg( true ),
                        'chunk_index'        => $this->get_non_negative_int_arg( true ),
                        'mime_type'          => $this->get_mime_type_arg( true ),
                        'chunk_checksum'     => $this->get_checksum_arg( false ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_chunks' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array(
                        'id'                 => $this->get_id_arg(),
                        'browser_session_id' => $this->get_browser_session_arg( false ),
                    ),
                ),
            )
        );
        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/recording/stop',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'stop_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'               => $this->get_id_arg(),
                    'duration_seconds' => $this->get_non_negative_int_arg( false ),
                ),
            )
        );
        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/recording/finalize',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'finalize_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'              => $this->get_id_arg(),
                    'expected_chunks' => $this->get_positive_int_arg( true ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publishing/prepare',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'prepare_publishing' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publishing/publish',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'publish_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publishing/status',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'publishing_status' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publitio/direct-upload/authorize',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'authorize_publitio_direct_upload' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'               => $this->get_id_arg(),
                    'mime_type'        => $this->get_mime_type_arg( true ),
                    'file_size'        => $this->get_positive_int_arg( true ),
                    'duration_seconds' => $this->get_non_negative_int_arg( false ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publitio/direct-upload/complete',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'complete_publitio_direct_upload' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'                  => $this->get_id_arg(),
                    'direct_upload_token' => $this->get_limited_text_arg( true, 128 ),
                    'publitio_file_id'    => $this->get_limited_text_arg( true, 191 ),
                    'playback_url'        => array(
                        'required'          => false,
                        'sanitize_callback' => 'esc_url_raw',
                        'validate_callback' => array( $this, 'validate_optional_url' ),
                    ),
                    'poster_url'          => array(
                        'required'          => false,
                        'sanitize_callback' => 'esc_url_raw',
                        'validate_callback' => array( $this, 'validate_optional_url' ),
                    ),
                    'embed_url'           => array(
                        'required'          => false,
                        'sanitize_callback' => 'esc_url_raw',
                        'validate_callback' => array( $this, 'validate_optional_url' ),
                    ),
                    'file_size'           => $this->get_non_negative_int_arg( false ),
                    'mime_type'           => $this->get_mime_type_arg( false ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/cancel',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'cancel_job' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );
    }


    public function list_media_sources( WP_REST_Request $request ) {
        $query = new WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 100,
                'author'         => get_current_user_id(),
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => array(
                    array(
                        'key'   => '_vh360_studio_media_source',
                        'value' => '1',
                    ),
                    array(
                        'key'   => '_vh360_studio_media_owner',
                        'value' => (string) get_current_user_id(),
                    ),
                ),
            )
        );

        $sources = array_map( array( $this, 'prepare_media_source_response' ), $query->posts );
        return rest_ensure_response( array( 'sources' => array_values( array_filter( $sources ) ) ) );
    }



    public function upload_cover_image( WP_REST_Request $request ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_cover_upload_forbidden', __( 'Cover image uploads require permission to upload files.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new WP_Error( 'vh360_studio_cover_missing_file', __( 'Choose an image file to upload.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $file = $files['file'];
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'vh360_studio_cover_invalid_upload', __( 'The uploaded cover image could not be read.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $mime = $this->detect_cover_image_mime_type( $file );
        if ( is_wp_error( $mime ) ) {
            return $mime;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $this->allowed_cover_image_mimes() ) );
        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'vh360_studio_cover_upload_failed', $upload['error'], array( 'status' => 500 ) );
        }

        $title = pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_FILENAME );
        $attachment_id = wp_insert_attachment( array(
            'post_mime_type' => $upload['type'],
            'post_title'     => $title ? $title : __( 'Studio Cover Image', 'videohub360-studio' ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => get_current_user_id(),
        ), $upload['file'] );

        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return new WP_Error( 'vh360_studio_cover_attachment_failed', __( 'Studio could not create the cover image attachment.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
            wp_update_attachment_metadata( $attachment_id, $metadata );
        }

        update_post_meta( $attachment_id, '_vh360_studio_cover_image', '1' );
        update_post_meta( $attachment_id, '_vh360_studio_media_owner', (string) get_current_user_id() );

        return rest_ensure_response( array(
            'attachment_id' => absint( $attachment_id ),
            'url'           => wp_get_attachment_image_url( $attachment_id, 'large' ) ?: wp_get_attachment_url( $attachment_id ),
            'thumbnail_url' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) ?: wp_get_attachment_url( $attachment_id ),
            'filename'      => wp_basename( get_attached_file( $attachment_id ) ),
            'mime'          => get_post_mime_type( $attachment_id ),
        ) );
    }

    public function upload_media_source( WP_REST_Request $request ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_media_upload_forbidden', __( 'Studio media source imports require permission to upload files.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new WP_Error( 'vh360_studio_media_missing_file', __( 'Choose an image or video file to import.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $file = $files['file'];
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'vh360_studio_media_invalid_upload', __( 'The uploaded media source could not be read.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $mime = $this->detect_media_source_mime_type( $file );
        if ( is_wp_error( $mime ) ) {
            return $mime;
        }

        $type = 0 === strpos( $mime, 'image/' ) ? 'image' : 'video';
        $display_name = sanitize_text_field( wp_unslash( (string) $request->get_param( 'display_name' ) ) );
        if ( '' === $display_name ) {
            $display_name = pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_FILENAME );
        }
        $display_name = trim( substr( $display_name, 0, 120 ) );
        if ( '' === $display_name ) {
            $display_name = __( 'Studio Media Source', 'videohub360-studio' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $this->allowed_media_source_mimes() ) );
        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'vh360_studio_media_upload_failed', $upload['error'], array( 'status' => 500 ) );
        }

        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => $upload['type'],
                'post_title'     => $display_name,
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_author'    => get_current_user_id(),
            ),
            $upload['file']
        );

        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return new WP_Error( 'vh360_studio_media_attachment_failed', __( 'Studio could not create the media attachment.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
            wp_update_attachment_metadata( $attachment_id, $metadata );
        }

        update_post_meta( $attachment_id, '_vh360_studio_media_source', '1' );
        update_post_meta( $attachment_id, '_vh360_studio_media_owner', (string) get_current_user_id() );
        update_post_meta( $attachment_id, '_vh360_studio_media_name', $display_name );
        update_post_meta( $attachment_id, '_vh360_studio_media_type', $type );

        return rest_ensure_response( array( 'source' => $this->prepare_media_source_response( get_post( $attachment_id ) ) ) );
    }

    public function delete_media_source( WP_REST_Request $request ) {
        $attachment_id = absint( $request['id'] );
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return new WP_Error( 'vh360_studio_media_not_found', __( 'Studio media source not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        if ( '1' !== get_post_meta( $attachment_id, '_vh360_studio_media_source', true ) ) {
            return new WP_Error( 'vh360_studio_media_not_studio_source', __( 'This attachment is not a Studio media source.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        if ( (string) get_current_user_id() !== (string) get_post_meta( $attachment_id, '_vh360_studio_media_owner', true ) ) {
            return new WP_Error( 'vh360_studio_media_not_owner', __( 'You can only delete your own Studio media sources.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        $deleted = wp_delete_attachment( $attachment_id, true );
        if ( ! $deleted ) {
            return new WP_Error( 'vh360_studio_media_delete_failed', __( 'Studio could not delete this media source.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'success' => true, 'id' => $attachment_id ) );
    }

    private function detect_media_source_mime_type( $file ) {
        $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $this->allowed_media_source_mimes() );
        $mime = ! empty( $check['type'] ) ? $check['type'] : '';
        if ( ! $mime || 0 !== strpos( $mime, 'image/' ) && 0 !== strpos( $mime, 'video/' ) ) {
            return new WP_Error( 'vh360_studio_media_invalid_type', __( 'Studio media sources must be image or video files.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return $mime;
    }


    private function detect_cover_image_mime_type( $file ) {
        $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $this->allowed_cover_image_mimes() );
        $mime = ! empty( $check['type'] ) ? $check['type'] : '';
        if ( ! $mime || 0 !== strpos( $mime, 'image/' ) ) {
            return new WP_Error( 'vh360_studio_cover_invalid_type', __( 'Studio cover images must be JPG, PNG, GIF, or WebP files.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return $mime;
    }

    private function allowed_cover_image_mimes() {
        return array_intersect( wp_get_mime_types(), array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ) );
    }

    private function user_can_use_image_attachment( $attachment_id ) {
        $attachment_id = absint( $attachment_id );
        if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) || 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
            return false;
        }
        $attachment = get_post( $attachment_id );
        return current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $attachment_id ) || ( $attachment && (int) $attachment->post_author === get_current_user_id() );
    }

    private function allowed_media_source_mimes() {
        return array_filter(
            wp_get_mime_types(),
            function( $mime ) {
                return 0 === strpos( $mime, 'image/' ) || 0 === strpos( $mime, 'video/' );
            }
        );
    }

    private function prepare_media_source_response( $attachment ) {
        if ( ! $attachment ) {
            return null;
        }

        $id = absint( $attachment->ID );
        $mime = get_post_mime_type( $id );
        $type = get_post_meta( $id, '_vh360_studio_media_type', true );
        if ( ! in_array( $type, array( 'image', 'video' ), true ) ) {
            $type = 0 === strpos( $mime, 'image/' ) ? 'image' : 'video';
        }

        return array(
            'id'       => $id,
            'sourceId' => 'media:' . $id,
            'name'     => get_post_meta( $id, '_vh360_studio_media_name', true ) ?: get_the_title( $id ),
            'type'     => $type,
            'url'      => wp_get_attachment_url( $id ),
            'mime'     => $mime,
            'filename' => wp_basename( get_attached_file( $id ) ),
            'created'  => mysql_to_rfc3339( $attachment->post_date_gmt ?: $attachment->post_date ),
        );
    }

    private function get_id_arg() {
        return array(
            'required'          => true,
            'validate_callback' => function( $value ) {
                return is_numeric( $value ) && 0 < absint( $value );
            },
            'sanitize_callback' => 'absint',
        );
    }

    private function get_optional_id_arg() {
        return array(
            'required'          => false,
            'sanitize_callback' => 'absint',
            'validate_callback' => function( $value ) {
                return null === $value || '' === $value || 0 === $value || '0' === $value || ( is_numeric( $value ) && 0 < absint( $value ) );
            },
        );
    }

    private function get_bool_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'validate_callback' => function( $value ) use ( $required ) {
                if ( ! $required && ( null === $value || '' === $value ) ) {
                    return true;
                }
                return is_bool( $value ) || in_array( $value, array( 0, 1, '0', '1', 'true', 'false', true, false ), true );
            },
        );
    }

    private function get_limited_text_arg( $required, $max_length ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value ) use ( $required, $max_length ) {
                if ( ! $required && ( null === $value || '' === $value ) ) {
                    return true;
                }
                return is_scalar( $value ) && strlen( (string) $value ) <= absint( $max_length );
            },
        );
    }

    private function get_limited_textarea_arg( $required, $max_length ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'sanitize_textarea_field',
            'validate_callback' => function( $value ) use ( $required, $max_length ) {
                if ( ! $required && ( null === $value || '' === $value ) ) {
                    return true;
                }
                return is_scalar( $value ) && strlen( (string) $value ) <= absint( $max_length );
            },
        );
    }

    private function get_broadcast_args() {
        return array(
            'video_id'                 => $this->get_optional_id_arg(),
            'title'                    => $this->get_limited_text_arg( false, 200 ),
            'description'              => $this->get_limited_textarea_arg( false, 5000 ),
            'agora_mode'               => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function( $value ) {
                    return null === $value || '' === $value || in_array( sanitize_key( $value ), array( 'broadcast', 'interactive' ), true );
                },
            ),
            'viewer_count'             => $this->get_bool_arg( false ),
            'chat_enabled'             => $this->get_bool_arg( false ),
            'agora_everyone_is_host'   => $this->get_bool_arg( false ),
            'require_passcode'         => $this->get_bool_arg( false ),
            'host_passcode'            => $this->get_limited_text_arg( false, 64 ),
            'featured_image_id'        => $this->get_optional_id_arg(),
            'clear_featured_image'     => $this->get_bool_arg( false ),
            'quality_preset'           => array(
                'required'          => false,
                'sanitize_callback' => array( 'VH360_Studio_Quality_Presets', 'normalize' ),
            ),
            'recording_intent'        => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function( $value ) {
                    return null === $value || '' === $value || in_array( sanitize_key( $value ), array( 'browser', 'none' ), true );
                },
            ),
        );
    }

    private function get_mime_type_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'validate_callback' => function( $value ) use ( $required ) {
                if ( ! $required && ( null === $value || '' === $value ) ) {
                    return true;
                }
                return $this->chunks->is_allowed_mime_type( $value );
            },
        );
    }

    private function get_checksum_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value ) use ( $required ) {
                return ! $required && ( null === $value || '' === $value ) || ( is_string( $value ) && preg_match( '/^[a-fA-F0-9]{64}$/', $value ) );
            },
        );
    }

    private function get_browser_session_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value ) use ( $required ) {
                return ! $required && ( null === $value || '' === $value ) || ( is_string( $value ) && '' !== trim( $value ) && 191 >= strlen( $value ) );
            },
        );
    }

    private function get_non_negative_int_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'absint',
            'validate_callback' => function( $value ) use ( $required ) {
                return ! $required && ( null === $value || '' === $value ) || ( is_numeric( $value ) && 0 <= intval( $value ) );
            },
        );
    }

    private function get_positive_int_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'absint',
            'validate_callback' => function( $value ) {
                return is_numeric( $value ) && 0 < intval( $value );
            },
        );
    }

    private function get_list_args() {
        return array(
            'per_page' => array(
                'default'           => 20,
                'validate_callback' => function( $value ) {
                    return is_numeric( $value ) && 1 <= absint( $value ) && 100 >= absint( $value );
                },
                'sanitize_callback' => 'absint',
            ),
        );
    }

    private function get_setup_create_args() {
        return array(
            'source_type'      => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'validate_source_type' ),
            ),
            'source_id'        => $this->get_limited_text_arg( false, 191 ),
            'live_video_id'    => $this->get_optional_id_arg(),
            'room_id'          => $this->get_limited_text_arg( false, 191 ),
            'recording_mode'   => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'validate_recording_mode' ),
            ),
            'quality_preset'   => array(
                'required'          => false,
                'sanitize_callback' => array( 'VH360_Studio_Quality_Presets', 'normalize' ),
            ),
        );
    }

    private function get_update_args() {
        return array_merge(
            $this->get_setup_create_args(),
            array(
                'status' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $value ) {
                        return in_array( sanitize_key( $value ), $this->jobs->allowed_statuses(), true );
                    },
                ),
            )
        );
    }

    private function setup_payload_from_request( WP_REST_Request $request ) {
        $payload = array();
        $allowed = array( 'source_type', 'source_id', 'live_video_id', 'room_id', 'recording_mode', 'quality_preset' );

        foreach ( $allowed as $key ) {
            if ( null !== $request->get_param( $key ) ) {
                $payload[ $key ] = $request->get_param( $key );
            }
        }

        $payload['status']           = VH360_Studio_Recording_Jobs::STATUS_CREATED;
        $payload['recording_mode']   = isset( $payload['recording_mode'] ) ? $payload['recording_mode'] : 'browser';
        $payload['source_type']      = isset( $payload['source_type'] ) ? $payload['source_type'] : 'studio_setup';
        $payload['storage_provider'] = VH360_Studio_Replay_Storage_Settings::resolve_default_provider();

        return $payload;
    }

    private function update_payload_from_request( WP_REST_Request $request ) {
        $payload = array();
        $allowed = array( 'source_type', 'source_id', 'live_video_id', 'room_id', 'recording_mode', 'quality_preset' );

        foreach ( $allowed as $key ) {
            if ( null !== $request->get_param( $key ) ) {
                $payload[ $key ] = $request->get_param( $key );
            }
        }

        return $payload;
    }

    public function validate_source_type( $value ) {
        return in_array( sanitize_key( $value ), $this->jobs->allowed_source_types(), true );
    }

    public function validate_recording_mode( $value ) {
        return in_array( sanitize_key( $value ), $this->jobs->allowed_recording_modes(), true );
    }

    public function validate_storage_provider( $value ) {
        $registry = VH360_Studio_Plugin::instance()->registry();
        return $registry->has_storage_provider( $this->normalize_storage_provider_id( sanitize_key( $value ) ) );
    }

    private function default_replay_storage_provider() {
        return VH360_Studio_Replay_Storage_Settings::resolve_default_provider();
    }

    public function validate_optional_url( $value ) {
        return null === $value || '' === $value || false !== wp_http_validate_url( $value );
    }

    public function permissions_check( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' ) ?: $request->get_param( '_wpnonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Invalid REST nonce.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        return VH360_Studio_Permissions::user_can_access_studio( get_current_user_id() );
    }

    public function licensed_permissions_check( WP_REST_Request $request ) {
        $access = $this->permissions_check( $request );

        if ( is_wp_error( $access ) || true !== $access ) {
            return $access;
        }

        return VH360_Studio_Permissions::license_permission_result();
    }

    private function prepare_job_response( $job ) {
        if ( is_wp_error( $job ) ) {
            return $job;
        }
        if ( is_array( $job ) ) {
            if ( array_key_exists( 'local_temp_path', $job ) ) {
                unset( $job['local_temp_path'] );
            }
            $job['display_title'] = $this->job_display_title( $job );
        }
        return $job;
    }

    private function job_display_title( array $job ) {
        foreach ( array( 'replay_video_id', 'live_video_id' ) as $post_id_key ) {
            if ( empty( $job[ $post_id_key ] ) ) {
                continue;
            }
            $title = get_the_title( absint( $job[ $post_id_key ] ) );
            if ( $title ) {
                return $title;
            }
        }

        return __( 'Studio replay', 'videohub360-studio' );
    }


    private function prepare_publish_response( $response, array $job ) {
        if ( is_wp_error( $response ) || ! is_array( $response ) ) {
            return $response;
        }

        $display_job = array_merge( $job, $response );
        $job_status  = ! empty( $response['job_status'] ) ? sanitize_key( $response['job_status'] ) : ( ! empty( $response['status'] ) ? sanitize_key( $response['status'] ) : sanitize_key( $job['status'] ) );
        $publish_status = ! empty( $response['publish_provider_status'] ) ? sanitize_key( $response['publish_provider_status'] ) : ( ! empty( $response['publish_status'] ) ? sanitize_key( $response['publish_status'] ) : ( ! empty( $job['publish_provider_status'] ) ? sanitize_key( $job['publish_provider_status'] ) : '' ) );
        $replay_video_id = ! empty( $response['replay_video_id'] ) ? absint( $response['replay_video_id'] ) : ( ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0 );

        $response['id']            = absint( $job['id'] );
        $response['job_id']        = absint( $job['id'] );
        $response['display_title'] = $this->job_display_title( $display_job );
        $response['created_at']    = ! empty( $job['created_at'] ) ? $job['created_at'] : '';
        $response['status']        = $job_status;
        $response['job_status']    = $job_status;
        $response['publish_status'] = $publish_status;
        $response['publish_provider_status'] = $publish_status;
        $response['replay_video_id'] = $replay_video_id;
        if ( empty( $response['replay_url'] ) && $replay_video_id ) {
            $response['replay_url'] = get_permalink( $replay_video_id );
        }
        if ( ! isset( $response['error_message'] ) ) {
            $response['error_message'] = ! empty( $job['error_message'] ) ? $job['error_message'] : '';
        }

        return $response;
    }

    private function broadcast_payload( WP_REST_Request $request ) {
        $mode = $request->get_param( 'agora_mode' ) ?: 'broadcast';
        $everyone = rest_sanitize_boolean( $request->get_param( 'agora_everyone_is_host' ) ) ? 'yes' : 'no';
        $require = rest_sanitize_boolean( $request->get_param( 'require_passcode' ) ) ? 'yes' : 'no';
        if ( 'broadcast' === $mode ) { $everyone = 'no'; $require = 'no'; }
        if ( 'yes' === $everyone ) { $require = 'no'; }
        return array(
            'title'                  => $request->get_param( 'title' ),
            'description'            => $request->get_param( 'description' ),
            'agora_mode'             => in_array( $mode, array( 'broadcast', 'interactive' ), true ) ? $mode : 'broadcast',
            'viewer_count'           => rest_sanitize_boolean( $request->get_param( 'viewer_count' ) ) ? 'yes' : 'no',
            'chat_enabled'           => rest_sanitize_boolean( $request->get_param( 'chat_enabled' ) ) ? 'yes' : 'no',
            'agora_everyone_is_host' => $everyone,
            'require_passcode'       => $require,
            'host_passcode'          => $request->get_param( 'host_passcode' ),
            'featured_image_id'      => absint( $request->get_param( 'featured_image_id' ) ),
            'clear_featured_image'   => rest_sanitize_boolean( $request->get_param( 'clear_featured_image' ) ),
        );
    }

    public function create_broadcast( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $video_id = absint( $request->get_param( 'video_id' ) );
        $payload = $this->broadcast_payload( $request );
        if ( ! empty( $payload['featured_image_id'] ) && ! $this->user_can_use_image_attachment( $payload['featured_image_id'] ) ) { return new WP_Error( 'vh360_studio_invalid_featured_image', __( 'You cannot use that cover image.', 'videohub360-studio' ), array( 'status' => 403 ) ); }
        $broadcast = $service->create_or_update_default_agora_livestream( get_current_user_id(), $payload, $video_id );
        if ( is_wp_error( $broadcast ) ) { return $broadcast; }
        $studio_host_uid = absint( get_post_meta( absint( $broadcast['videoId'] ), '_vh360_studio_host_agora_uid', true ) );
        if ( ! $studio_host_uid ) {
            $studio_host_uid = wp_rand( 100000000, 999999999 );
            update_post_meta( absint( $broadcast['videoId'] ), '_vh360_studio_host_agora_uid', $studio_host_uid );
        }
        update_post_meta( absint( $broadcast['videoId'] ), '_vh360_studio_controlled_live', 'yes' );
        update_post_meta( absint( $broadcast['videoId'] ), '_vh360_studio_host_user_id', get_current_user_id() );
        $recording_intent = sanitize_key( $request->get_param( 'recording_intent' ) ?: 'browser' );
        $job = null;
        if ( 'browser' === $recording_intent ) {
            $job = $this->jobs->create( get_current_user_id(), array(
                'source_type'      => 'livestream_video',
                'source_id'        => 'videohub360-' . absint( $broadcast['videoId'] ),
                'live_video_id'    => absint( $broadcast['videoId'] ),
                'room_id'          => sanitize_text_field( $broadcast['channelName'] ),
                'recording_mode'   => 'browser',
                'quality_preset'   => VH360_Studio_Quality_Presets::normalize( $request->get_param( 'quality_preset' ) ?: VH360_Studio_Quality_Presets::DEFAULT_PRESET ),
                'storage_provider' => $this->default_replay_storage_provider(),
            ) );
            if ( ! is_wp_error( $job ) ) {
                $this->update_live_replay_lifecycle( $job, 'created', 'no', 'no', 'no' );
            }
        }
        return rest_ensure_response( array( 'broadcast' => $broadcast, 'job' => $job ? $this->prepare_job_response( $job ) : null ) );
    }

    public function broadcast_prepare( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        return rest_ensure_response( $service->prepare_agora_broadcast_data( absint( $request['video_id'] ), get_current_user_id() ) );
    }

    public function broadcast_started( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $result = $service->mark_live( absint( $request['video_id'] ), get_current_user_id() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'stream_live' => true, 'broadcast' => $result ) );
    }

    public function broadcast_heartbeat( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $heartbeat = $service->update_studio_heartbeat( absint( $request['video_id'] ), get_current_user_id() );
        if ( is_wp_error( $heartbeat ) ) { return $heartbeat; }
        VideoHub360_Livestream_Service::cleanup_stale_studio_broadcasts();
        return rest_ensure_response( array( 'ok' => true, 'server_time' => current_time( 'mysql' ), 'broadcast' => $heartbeat ) );
    }

    public function broadcast_renew_token( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $video_id = absint( $request['video_id'] );
        if ( 'yes' !== get_post_meta( $video_id, '_vh360_studio_controlled_live', true ) ) {
            return new WP_Error( 'vh360_studio_not_controlled', __( 'This is not a Studio-controlled livestream.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( 'yes' === get_post_meta( $video_id, '_vh360_stream_stopped', true ) ) {
            return new WP_Error( 'vh360_studio_stream_ended', __( 'This livestream has ended.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $host_user_id = absint( get_post_meta( $video_id, '_vh360_studio_host_user_id', true ) );
        if ( $host_user_id && get_current_user_id() !== $host_user_id && ! current_user_can( 'edit_post', $video_id ) && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'vh360_studio_forbidden', __( 'You cannot renew this livestream token.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        $prepared = $service->prepare_agora_broadcast_data( $video_id, get_current_user_id() );
        if ( is_wp_error( $prepared ) ) { return $prepared; }
        return rest_ensure_response( array( 'token' => $prepared['token'], 'expiresAt' => $prepared['expiresAt'], 'uid' => $prepared['uid'], 'channelName' => $prepared['channelName'] ) );
    }

    public function broadcast_end( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $result = $service->mark_ended( absint( $request['video_id'] ), get_current_user_id() );
        if ( is_wp_error( $result ) ) { return $result; }
        return rest_ensure_response( array( 'stream_live' => false, 'broadcast' => $result ) );
    }

    public function list_jobs( WP_REST_Request $request ) {
        return rest_ensure_response( array_map( array( $this, 'prepare_job_response' ), $this->jobs->list( get_current_user_id(), $request->get_param( 'per_page' ) ) ) );
    }

    public function create_job( WP_REST_Request $request ) {
        $payload = $this->setup_payload_from_request( $request );
        if ( in_array( sanitize_key( $payload['source_type'] ), array( 'live_room', 'appointment_session' ), true ) ) {
            return new WP_Error( 'vh360_studio_room_job_route_required', __( 'Live Room and appointment recording jobs must be created through the dedicated Live Room recording endpoint.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->create( get_current_user_id(), $payload ) ) );
    }

    public function get_job( WP_REST_Request $request ) {
        $job = $this->jobs->get( absint( $request['id'] ), get_current_user_id() );
        return $job ? rest_ensure_response( $this->prepare_job_response( $job ) ) : new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
    }

    public function update_job( WP_REST_Request $request ) {
        $job = $this->jobs->get( absint( $request['id'] ), get_current_user_id() );
        if ( $job && 'appointment_session' === sanitize_key( $job['source_type'] ) ) {
            return new WP_Error( 'vh360_studio_private_appointment_job_locked', __( 'Private appointment recording jobs cannot be modified through generic Studio job routes.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->update( absint( $request['id'] ), get_current_user_id(), $this->update_payload_from_request( $request ) ) ) );
    }

    private function provider_status_is_failed( $status ) {
        return in_array( sanitize_key( (string) $status ), array( 'bunny_stream_failed', 'publitio_failed', 'upload_failed', 'failed', 'error' ), true );
    }

    private function publish_response_is_ready( array $response ) {
        foreach ( array( 'job_status', 'status', 'publish_provider_status', 'provider_status', 'publish_status' ) as $key ) {
            if ( ! empty( $response[ $key ] ) && in_array( sanitize_key( (string) $response[ $key ] ), array( 'ready', 'bunny_stream_ready', 'publitio_ready', 'publitio_direct_ready', 'published' ), true ) ) {
                return true;
            }
        }

        return ! empty( $response['replay_video_id'] ) || ! empty( $response['replay_url'] );
    }

    private function publish_response_is_failed( array $response ) {
        foreach ( array( 'publish_provider_status', 'provider_status', 'publish_status', 'status' ) as $key ) {
            if ( ! empty( $response[ $key ] ) && $this->provider_status_is_failed( $response[ $key ] ) ) {
                return true;
            }
        }
        return false;
    }

    private function reconcile_publish_response_lifecycle( array $job, array $response ) {
        if ( $this->publish_response_is_ready( $response ) ) {
            $this->update_live_replay_lifecycle( $job, 'ready', 'no', 'yes', 'no' );
            return;
        }

        if ( $this->publish_response_is_failed( $response ) ) {
            $message = ! empty( $response['message'] ) ? sanitize_textarea_field( $response['message'] ) : __( 'Replay provider processing failed.', 'videohub360-studio' );
            $failed_job = $this->jobs->mark_failed( $job['id'], get_current_user_id(), $message );
            $this->update_live_replay_lifecycle( is_wp_error( $failed_job ) ? $job : $failed_job, 'failed', 'no', 'no', 'yes' );
            return;
        }

        $this->update_live_replay_lifecycle( $job, 'processing', 'yes', 'no', 'no' );
    }

    private function update_live_replay_lifecycle( array $job, $status, $pending = 'yes', $ready = 'no', $failed = 'no' ) {
        $live_video_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        if ( ! $live_video_id || 'videohub360' !== get_post_type( $live_video_id ) ) {
            return;
        }
        if ( ! empty( $job['id'] ) ) {
            update_post_meta( $live_video_id, '_vh360_studio_job_id', absint( $job['id'] ) );
        }
        update_post_meta( $live_video_id, '_vh360_studio_replay_pending', $pending );
        update_post_meta( $live_video_id, '_vh360_studio_replay_ready', $ready );
        update_post_meta( $live_video_id, '_vh360_studio_replay_failed', $failed );
        update_post_meta( $live_video_id, '_vh360_studio_replay_status', sanitize_key( $status ) );
        update_post_meta( $live_video_id, '_vh360_live_room_recording_state', sanitize_key( $status ) );
        if ( in_array( sanitize_key( $status ), array( 'stopping', 'uploading', 'processing', 'ready', 'failed' ), true ) ) {
            update_post_meta( $live_video_id, '_vh360_live_room_recording_stopped_at', current_time( 'mysql' ) );
        }
    }




    private function reject_private_appointment_job_route( array $job ) {
        if ( 'appointment_session' === sanitize_key( $job['source_type'] ) || 'local_private' === sanitize_key( $job['recording_mode'] ) ) {
            return new WP_Error( 'vh360_studio_private_appointment_route_forbidden', __( 'Private appointment recordings cannot use provider-backed Studio recording routes.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        return true;
    }

    public function start_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( VH360_Studio_Recording_Jobs::STATUS_CREATED !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Recording can only start from a created job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $settings = $this->chunks->upload_settings();
        $mime_type = $this->chunks->base_mime_type( $request->get_param( 'mime_type' ) );
        if ( ! in_array( $mime_type, $settings['allowed_mime_types'], true ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_type', __( 'Recording MIME type is not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        $session = wp_generate_uuid4();
        $job = $this->jobs->start_recording( $job['id'], get_current_user_id(), $session, $mime_type );
        if ( is_wp_error( $job ) ) { return $job; }
        $this->update_live_replay_lifecycle( $job, 'recording', 'yes', 'no', 'no' );
        return rest_ensure_response( array( 'job_id' => absint( $job['id'] ), 'browser_session_id' => $session, 'mime_type' => $mime_type, 'upload_settings' => $settings, 'status' => $job['status'] ) );
    }

    public function upload_chunk( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( ! in_array( $job['status'], array( VH360_Studio_Recording_Jobs::STATUS_RECORDING, VH360_Studio_Recording_Jobs::STATUS_STOPPING ), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_chunk_status', __( 'Chunks can only be uploaded while recording is active or stopping.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $session = sanitize_text_field( $request->get_param( 'browser_session_id' ) );
        if ( ! $session || $session !== $job['browser_session_id'] ) { return new WP_Error( 'vh360_studio_invalid_session', __( 'Invalid browser recording session.', 'videohub360-studio' ), array( 'status' => 403 ) ); }
        $chunk_index = $request->get_param( 'chunk_index' );
        if ( null === $chunk_index || ! is_numeric( $chunk_index ) || 0 > intval( $chunk_index ) ) { return new WP_Error( 'vh360_studio_invalid_chunk_index', __( 'Invalid recording chunk index.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $files = $request->get_file_params();
        if ( empty( $files['chunk'] ) ) { return new WP_Error( 'vh360_studio_missing_chunk', __( 'Missing uploaded recording chunk.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $summary = $this->chunks->store_uploaded_chunk( $job, $session, intval( $chunk_index ), $files['chunk'], $request->get_param( 'mime_type' ), $request->get_param( 'chunk_checksum' ) );
        if ( is_wp_error( $summary ) ) {
            if ( ! $this->is_retryable_chunk_error( $summary ) ) {
                $failed_job = $this->jobs->mark_failed( $job['id'], get_current_user_id(), $summary->get_error_message() );
                $this->update_live_replay_lifecycle( is_wp_error( $failed_job ) ? $job : $failed_job, 'failed', 'no', 'no', 'yes' );
            }
            return $summary;
        }
        $summary['job_status'] = $job['status'];
        return rest_ensure_response( $summary );
    }

    private function is_retryable_chunk_error( WP_Error $error ) {
        return in_array(
            $error->get_error_code(),
            array(
                'vh360_studio_missing_chunk',
                'vh360_studio_chunk_store_failed',
                'vh360_studio_chunk_checksum_failed',
                'vh360_studio_chunk_checksum_mismatch',
                'vh360_studio_chunk_integrity_failed',
            ),
            true
        );
    }

    public function list_chunks( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        $session = sanitize_text_field( $request->get_param( 'browser_session_id' ) ? $request->get_param( 'browser_session_id' ) : $job['browser_session_id'] );
        $summary = $this->chunks->received_summary( $job['id'], $session );
        $summary['job_status'] = $job['status'];
        return rest_ensure_response( $summary );
    }

    public function stop_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( VH360_Studio_Recording_Jobs::STATUS_STOPPING === $job['status'] ) {
            $this->update_live_replay_lifecycle( $job, 'stopping', 'yes', 'no', 'no' );
            return rest_ensure_response( $this->prepare_job_response( $job ) );
        }
        if ( VH360_Studio_Recording_Jobs::STATUS_RECORDING !== $job['status'] ) { return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Recording can only stop from the recording status.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        $stopping = $this->jobs->mark_stopping( $job['id'], get_current_user_id(), $request->get_param( 'duration_seconds' ) );
        if ( ! is_wp_error( $stopping ) ) {
            $this->update_live_replay_lifecycle( $stopping, 'stopping', 'yes', 'no', 'no' );
        }
        return rest_ensure_response( $this->prepare_job_response( $stopping ) );
    }

    public function finalize_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( VH360_Studio_Recording_Jobs::STATUS_UPLOADING === $job['status'] ) {
            return rest_ensure_response( array_merge( array( 'message' => __( 'Finalize is already in progress.', 'videohub360-studio' ) ), $this->prepare_job_response( $job ) ) );
        }
        if ( in_array( $job['status'], array( VH360_Studio_Recording_Jobs::STATUS_PROCESSING, VH360_Studio_Recording_Jobs::STATUS_READY ), true ) ) {
            return rest_ensure_response( array_merge( array( 'message' => __( 'Recording finalization has already completed.', 'videohub360-studio' ) ), $this->prepare_job_response( $job ) ) );
        }
        if ( VH360_Studio_Recording_Jobs::STATUS_STOPPING !== $job['status'] ) { return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Recording can only finalize from the stopping status.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        $uploading = $this->jobs->mark_uploading( $job['id'], get_current_user_id() );
        if ( is_wp_error( $uploading ) ) { return $uploading; }
        $this->update_live_replay_lifecycle( $uploading, 'uploading', 'yes', 'no', 'no' );
        $expected_chunks = $request->get_param( 'expected_chunks' );
        $assembled = $this->chunks->assemble_chunks( $uploading, $job['browser_session_id'], $expected_chunks, $job['mime_type'] );
        if ( is_wp_error( $assembled ) ) {
            if ( $this->should_mark_finalize_failed( $assembled ) ) {
                $failed_job = $this->jobs->mark_failed( $job['id'], get_current_user_id(), $assembled->get_error_message() );
                $this->update_live_replay_lifecycle( is_wp_error( $failed_job ) ? $job : $failed_job, 'failed', 'no', 'no', 'yes' );
            } else {
                $this->jobs->mark_finalize_retryable( $job['id'], get_current_user_id(), $assembled->get_error_message() );
            }
            return $assembled;
        }
        $summary = $this->chunks->received_summary( $job['id'], $job['browser_session_id'] );
        $recording = $this->validator->validate_assembled_recording( $uploading, $assembled, $summary, $expected_chunks );
        if ( is_wp_error( $recording ) ) {
            if ( $this->should_mark_finalize_failed( $recording ) ) {
                $failed_job = $this->jobs->mark_failed( $job['id'], get_current_user_id(), $recording->get_error_message() );
                $this->update_live_replay_lifecycle( is_wp_error( $failed_job ) ? $job : $failed_job, 'failed', 'no', 'no', 'yes' );
            } else {
                $this->jobs->mark_finalize_retryable( $job['id'], get_current_user_id(), $recording->get_error_message() );
            }
            return $recording;
        }
        $processed = $this->jobs->mark_processing( $job['id'], get_current_user_id(), array( 'file_size' => absint( $recording['file_size'] ), 'local_temp_path' => $recording['path'], 'mime_type' => $recording['mime_type'], 'expected_chunks' => $recording['expected_chunks'], 'received_chunks' => $recording['received_chunks'], 'assembled_checksum' => $recording['assembled_checksum'], 'assembled_at' => $recording['assembled_at'], 'temp_expires_at' => $recording['temp_expires_at'] ) );
        if ( is_wp_error( $processed ) ) {
            return $processed;
        }
        $this->update_live_replay_lifecycle( $processed, 'processing', 'yes', 'no', 'no' );
        return rest_ensure_response( $this->prepare_job_response( $processed ) );
    }

    private function should_mark_finalize_failed( WP_Error $error ) {
        $recoverable = array(
            'vh360_studio_assembly_failed',
            'vh360_studio_checksum_failed',
        );

        return ! in_array( $error->get_error_code(), $recoverable, true );
    }

    public function prepare_publishing( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        $prepared = $this->publisher->prepare( $job );
        if ( is_wp_error( $prepared ) ) {
            $retryable = $this->jobs->mark_finalize_retryable( $job['id'], get_current_user_id(), $prepared->get_error_message() );
            $this->update_live_replay_lifecycle( is_wp_error( $retryable ) ? $job : $retryable, 'publishing_prepare_failed', 'yes', 'no', 'no' );
            return $prepared;
        }
        return rest_ensure_response( $prepared );
    }

    public function publish_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( VH360_Studio_Recording_Jobs::STATUS_READY === $job['status'] ) {
            $status = $this->publisher->status( $job );
            if ( is_wp_error( $status ) ) { return $status; }
            if ( is_array( $status ) ) {
                $this->reconcile_publish_response_lifecycle( $job, $status );
            }
            return rest_ensure_response( $this->prepare_publish_response( $status, $job ) );
        }
        $this->update_live_replay_lifecycle( $job, 'processing', 'yes', 'no', 'no' );
        $lock_key = 'vh360_studio_publish_lock_' . absint( $job['id'] );
        if ( get_transient( $lock_key ) ) {
            $status = $this->publisher->status( $job );
            if ( is_wp_error( $status ) ) {
                $status = array();
            }
            if ( is_array( $status ) && $this->publish_response_is_ready( $status ) ) {
                $this->reconcile_publish_response_lifecycle( $job, $status );
                return rest_ensure_response( $this->prepare_publish_response( $status, $job ) );
            }
            $status['status']                  = $job['status'];
            $status['job_status']              = $job['status'];
            $status['publish_provider_status'] = 'publishing';
            $status['message']                 = __( 'Replay publishing is already in progress.', 'videohub360-studio' );
            return rest_ensure_response( $this->prepare_publish_response( $status, $job ) );
        }
        set_transient( $lock_key, array( 'user_id' => get_current_user_id(), 'started_at' => time() ), (int) apply_filters( 'vh360_studio_publish_lock_ttl', 2 * HOUR_IN_SECONDS, $job ) );
        try {
            $published = $this->publisher->publish( $job );
        } finally {
            delete_transient( $lock_key );
        }
        if ( is_wp_error( $published ) ) {
            $failed_job = $this->jobs->mark_failed( $job['id'], get_current_user_id(), $published->get_error_message() );
            $this->update_live_replay_lifecycle( is_wp_error( $failed_job ) ? $job : $failed_job, 'publishing_start_failed', 'no', 'no', 'yes' );
            return $published;
        }
        if ( is_array( $published ) ) {
            $this->reconcile_publish_response_lifecycle( $job, $published );
        }
        return rest_ensure_response( $this->prepare_publish_response( $published, $job ) );
    }

    public function publishing_status( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( ! in_array( $job['status'], array( VH360_Studio_Recording_Jobs::STATUS_PROCESSING, VH360_Studio_Recording_Jobs::STATUS_READY ), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_publish_status', __( 'Publishing status is available for processing or ready jobs.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $status = $this->publisher->status( $job );
        if ( is_wp_error( $status ) ) { return $status; }
        if ( is_array( $status ) ) {
            $this->reconcile_publish_response_lifecycle( $job, $status );
        }
        return rest_ensure_response( $this->prepare_publish_response( $status, $job ) );
    }

    public function authorize_publitio_direct_upload( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        if ( ! in_array( $job['status'], array( VH360_Studio_Recording_Jobs::STATUS_STOPPING, VH360_Studio_Recording_Jobs::STATUS_PROCESSING ), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_direct_upload_status', __( 'Fast cloud upload requires a stopped recording job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( 'publitio' !== sanitize_key( $job['storage_provider'] ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_wrong_provider', __( 'Direct upload is only available for this cloud replay job.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( 'direct_browser' !== sanitize_key( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ) ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_disabled', __( 'Fast cloud upload is not enabled.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $preset = sanitize_text_field( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) );
        if ( '' === $preset ) {
            return new WP_Error( 'vh360_studio_direct_upload_preset_missing', __( 'Fast cloud upload is not configured.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $client = new VH360_Studio_Publitio_Client();
        if ( ! $client->has_credentials() ) {
            return new WP_Error( 'vh360_studio_direct_upload_credentials_missing', __( 'Cloud replay credentials are required so Videohub360 can verify direct uploads.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $mime = $this->chunks->base_mime_type( $request->get_param( 'mime_type' ) );
        if ( ! in_array( $mime, array( 'video/mp4', 'video/webm' ), true ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_invalid_type', __( 'Fast cloud upload supports MP4 and WebM recordings only.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        $settings = $this->chunks->upload_settings();
        $file_size  = absint( $request->get_param( 'file_size' ) );
        $duration   = absint( $request->get_param( 'duration_seconds' ) );
        $direct_max = absint( get_option( 'vh360_studio_publitio_direct_max_size', 314572800 ) );
        $max_size   = $direct_max ? $direct_max : 314572800;
        if ( ! empty( $settings['max_total_recording_size'] ) ) {
            $max_size = min( $max_size, absint( $settings['max_total_recording_size'] ) );
        }
        if ( 1 > $file_size || $file_size > $max_size ) {
            return new WP_Error( 'vh360_studio_direct_upload_too_large', __( 'Recording is too large for the configured Studio upload limit.', 'videohub360-studio' ), array( 'status' => 413 ) );
        }

        if ( VH360_Studio_Recording_Jobs::STATUS_STOPPING === $job['status'] ) {
            $uploading = $this->jobs->mark_uploading( $job['id'], get_current_user_id() );
            if ( is_wp_error( $uploading ) ) { return $uploading; }
            $job = $this->jobs->mark_processing( $job['id'], get_current_user_id(), array(
                'file_size'               => $file_size,
                'mime_type'               => $mime,
                'duration_seconds'        => $duration,
                'publish_provider_status' => 'publitio_direct_uploading',
            ) );
            if ( is_wp_error( $job ) ) { return $job; }
        } else {
            $job = $this->jobs->update( $job['id'], get_current_user_id(), array(
                'file_size'               => $file_size,
                'mime_type'               => $mime,
                'duration_seconds'        => $duration,
                'publish_provider_status' => 'publitio_direct_uploading',
            ) );
            if ( is_wp_error( $job ) ) { return $job; }
        }

        $public_id = sanitize_title( 'vh360-studio-replay-' . absint( $job['id'] ) . '-' . wp_generate_password( 6, false, false ) );
        $token     = wp_generate_password( 40, false, false );
        $expires   = time() + ( 30 * MINUTE_IN_SECONDS );
        $meta      = array(
            'job_id'       => absint( $job['id'] ),
            'user_id'      => absint( $job['user_id'] ),
            'mime_type'    => $mime,
            'file_size'    => $file_size,
            'public_id'    => $public_id,
            'title'        => $this->direct_upload_title( $job ),
            'tags'         => 'videohub360,studio,replay,vh360-job-' . absint( $job['id'] ),
            'folder'       => sanitize_text_field( get_option( 'vh360_studio_publitio_folder', '' ) ),
            'preset_id'    => $preset,
            'expires'      => $expires,
        );
        set_transient( $this->publitio_direct_upload_transient_key( $job['id'], $token ), $meta, 30 * MINUTE_IN_SECONDS );

        return rest_ensure_response( array(
            'upload_url'           => esc_url_raw( 'https://api.publit.io/v1/files/create/' . rawurlencode( $preset ) ),
            'upload_preset_id'     => $preset,
            'public_id'            => $public_id,
            'title'                => $meta['title'],
            'description'          => $this->direct_upload_description( $job ),
            'tags'                 => $meta['tags'],
            'folder'               => $meta['folder'],
            'privacy'              => 'private' === sanitize_key( get_option( 'vh360_studio_publitio_privacy', 'public' ) ) ? '0' : '1',
            'option_download'      => get_option( 'vh360_studio_publitio_option_download', '0' ) ? '1' : '0',
            'option_hls'           => get_option( 'vh360_studio_publitio_option_hls', '0' ) ? '1' : '0',
            'option_ad'            => '0',
            'direct_upload_token'  => $token,
            'max_size'             => $max_size,
            'expected_file_size'   => $file_size,
            'allowed_mime_types'   => array( 'video/mp4', 'video/webm' ),
        ) );
    }

    public function complete_publitio_direct_upload( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $private_route_check = $this->reject_private_appointment_job_route( $job );
        if ( is_wp_error( $private_route_check ) ) { return $private_route_check; }
        $token = sanitize_text_field( $request->get_param( 'direct_upload_token' ) );
        $key   = $this->publitio_direct_upload_transient_key( $job['id'], $token );
        $meta  = get_transient( $key );
        if ( ! is_array( $meta ) || empty( $meta['job_id'] ) || absint( $meta['job_id'] ) !== absint( $job['id'] ) || absint( $meta['user_id'] ) !== absint( $job['user_id'] ) || time() > absint( $meta['expires'] ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_token_invalid', __( 'Direct upload token is invalid or expired.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( 'publitio' !== sanitize_key( $job['storage_provider'] ) || VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_direct_upload_job_invalid', __( 'This job is not ready for fast cloud upload completion.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $file_id = sanitize_text_field( $request->get_param( 'publitio_file_id' ) );
        $provider = VH360_Studio_Plugin::instance()->registry()->get_storage_provider( 'publitio' );
        if ( ! $provider || ! method_exists( $provider, 'verify_direct_upload_file' ) ) {
            return new WP_Error( 'vh360_studio_publitio_unavailable', __( 'Cloud replay storage is unavailable for direct upload verification.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        $verified = $provider->verify_direct_upload_file( $file_id );
        if ( is_wp_error( $verified ) ) {
            return $verified;
        }
        if ( empty( $verified['publitio_file_id'] ) || sanitize_text_field( $verified['publitio_file_id'] ) !== $file_id ) {
            return new WP_Error( 'vh360_studio_direct_upload_verification_failed', __( 'Cloud upload verification did not confirm the uploaded file.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $expected_public_id = ! empty( $meta['public_id'] ) ? sanitize_text_field( $meta['public_id'] ) : '';
        $actual_public_id   = ! empty( $verified['public_id'] ) ? sanitize_text_field( $verified['public_id'] ) : '';
        $public_id_matches  = $expected_public_id && $actual_public_id && sanitize_title( $expected_public_id ) === sanitize_title( $actual_public_id );
        $verified['expected_public_id'] = $expected_public_id;
        $verified['actual_public_id']   = $actual_public_id;
        $verified['public_id_matches']  = $public_id_matches ? 1 : 0;

        $file_validation = $this->validate_publitio_direct_file_metadata( $verified, $meta, $request );
        if ( is_wp_error( $file_validation ) ) {
            return $file_validation;
        }

        if ( ! $public_id_matches && $expected_public_id && $actual_public_id ) {
            $verified['message'] = __( 'Cloud upload verified. The provider changed the requested public ID.', 'videohub360-studio' );
        }
        if ( ! empty( $request['playback_url'] ) && empty( $verified['playback_url'] ) ) {
            $verified['playback_url'] = esc_url_raw( $request['playback_url'] );
        }
        if ( ! empty( $request['poster_url'] ) && empty( $verified['poster_url'] ) ) {
            $verified['poster_url'] = esc_url_raw( $request['poster_url'] );
        }
        if ( ! empty( $request['embed_url'] ) && empty( $verified['embed_url'] ) ) {
            $verified['embed_url'] = esc_url_raw( $request['embed_url'] );
        }
        $completed = $this->publisher->complete_verified_publish( $job, $verified );
        if ( is_wp_error( $completed ) ) {
            return $completed;
        }
        delete_transient( $key );
        return rest_ensure_response( $this->prepare_publish_response( $completed, $job ) );
    }

    public function cancel_job( WP_REST_Request $request ) {
        $job_id       = absint( $request['id'] );
        $existing_job = $this->jobs->get( $job_id, get_current_user_id() );
        if ( is_array( $existing_job ) && ( 'appointment_session' === sanitize_key( $existing_job['source_type'] ) || 'local_private' === sanitize_key( $existing_job['recording_mode'] ) ) ) {
            return new WP_Error( 'vh360_studio_private_appointment_job_locked', __( 'Private appointment recordings cannot be cancelled through generic Studio job routes.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        $job          = $this->jobs->cancel( $job_id, get_current_user_id() );

        if ( ! is_wp_error( $job ) ) {
            $recording_started = is_array( $existing_job ) && ! in_array(
                ! empty( $existing_job['status'] ) ? $existing_job['status'] : '',
                array( VH360_Studio_Recording_Jobs::STATUS_CREATED, VH360_Studio_Recording_Jobs::STATUS_CANCELLED ),
                true
            );
            $this->update_live_replay_lifecycle( $job, 'cancelled', 'no', 'no', 'no' );
        }

        return rest_ensure_response( $this->prepare_job_response( $job ) );
    }

    private function publitio_direct_upload_transient_key( $job_id, $token ) {
        return 'vh360_studio_direct_publitio_upload_' . absint( $job_id ) . '_' . hash( 'sha256', (string) $token );
    }

    private function validate_publitio_direct_file_metadata( array $verified, array $meta, WP_REST_Request $request ) {
        $expected_size = ! empty( $meta['file_size'] ) ? absint( $meta['file_size'] ) : absint( $request->get_param( 'file_size' ) );
        $has_actual_size = array_key_exists( 'file_size', $verified ) && '' !== (string) $verified['file_size'];
        $actual_size     = $has_actual_size ? absint( $verified['file_size'] ) : 0;
        if ( $has_actual_size && 1 > $actual_size ) {
            return new WP_Error( 'vh360_studio_direct_upload_empty_file', __( 'Cloud upload verification found an empty uploaded file.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( $has_actual_size && $expected_size ) {
            $allowed_delta = max( 1048576, (int) ceil( $expected_size * 0.05 ) );
            if ( abs( $actual_size - $expected_size ) > $allowed_delta ) {
                return new WP_Error( 'vh360_studio_direct_upload_size_mismatch', __( 'Cloud upload verification found a file size that does not match this Studio upload session.', 'videohub360-studio' ), array( 'status' => 409 ) );
            }
        }

        $allowed_mimes = array( 'video/mp4', 'video/webm' );
        $mime          = ! empty( $verified['mime_type'] ) ? $this->chunks->base_mime_type( $verified['mime_type'] ) : '';
        if ( $mime && 'video' !== $mime && ! in_array( $mime, $allowed_mimes, true ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_invalid_verified_type', __( 'Cloud upload verification found a non-video uploaded file.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }

        $extension = ! empty( $verified['extension'] ) ? strtolower( sanitize_key( $verified['extension'] ) ) : '';
        if ( $extension && ! in_array( $extension, array( 'mp4', 'webm' ), true ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_invalid_verified_extension', __( 'Cloud upload verification found an unsupported uploaded file type.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }

        return true;
    }

    private function direct_upload_title( array $job ) {
        if ( ! empty( $job['live_video_id'] ) ) {
            $title = get_the_title( absint( $job['live_video_id'] ) );
            if ( $title ) { return wp_strip_all_tags( $title ); }
        }
        return sprintf( __( 'Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) );
    }

    private function direct_upload_description( array $job ) {
        if ( ! empty( $job['live_video_id'] ) ) {
            $post = get_post( absint( $job['live_video_id'] ) );
            if ( $post ) { return wp_strip_all_tags( $post->post_content ); }
        }
        return '';
    }
}
