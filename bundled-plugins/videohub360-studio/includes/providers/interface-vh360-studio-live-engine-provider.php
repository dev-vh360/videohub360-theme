<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
interface VH360_Studio_Live_Engine_Provider {
    public function get_id();
    public function get_label();
    public function is_available();
}
