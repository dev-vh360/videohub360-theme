<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
interface VH360_Studio_Recording_Provider {
    public function get_id();
    public function get_label();
    public function is_available();
    public function supports_server_recording();
}
