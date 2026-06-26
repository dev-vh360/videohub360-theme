<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple widget that prints a PWA status indicator.
 *
 * The JS in assets/public/pwa-public.js will populate this element.
 */
class VH360_PWA_Status_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'vh360_pwa_status_widget',
			__( 'VH360 PWA Status', 'vh360-pwa-app' ),
			array( 'description' => __( 'Shows whether the VH360 PWA is installed / installable.', 'vh360-pwa-app' ) )
		);
	}

	public function widget( $args, $instance ) {
		if ( ! function_exists( 'vh360_pwa_is_enabled' ) || ! vh360_pwa_is_enabled() ) {
			return;
		}
		echo $args['before_widget'];
		$title = isset( $instance['title'] ) ? sanitize_text_field( (string) $instance['title'] ) : '';
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}
		echo '<div class="vh360-pwa-status" data-vh360-pwa-status="1"></div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? (string) $instance['title'] : __( 'PWA Status', 'vh360-pwa-app' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vh360-pwa-app' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = isset( $new_instance['title'] ) ? sanitize_text_field( (string) $new_instance['title'] ) : '';
		return $instance;
	}
}
