<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
interface VH360_Studio_Replay_Storage_Provider {
    public function get_id();
    public function get_label();
    public function is_available();
    public function supports_publish();
    public function prepare_publish( array $job, array $recording );
    public function publish_recording( array $job, array $recording );
    public function get_publish_status( array $job );
}
