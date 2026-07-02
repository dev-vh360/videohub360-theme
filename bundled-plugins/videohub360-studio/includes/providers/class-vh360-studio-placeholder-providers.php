<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
abstract class VH360_Studio_Abstract_Placeholder_Provider {
    protected $id;
    protected $label;
    protected $available;
    public function __construct( $id, $label, $available = false ) { $this->id = $id; $this->label = $label; $this->available = $available; }
    public function get_id() { return $this->id; }
    public function get_label() { return $this->label; }
    public function is_available() { return (bool) $this->available; }
}
class VH360_Studio_Placeholder_Live_Engine_Provider extends VH360_Studio_Abstract_Placeholder_Provider implements VH360_Studio_Live_Engine_Provider {}
class VH360_Studio_Placeholder_Recording_Provider extends VH360_Studio_Abstract_Placeholder_Provider implements VH360_Studio_Recording_Provider {
    public function supports_server_recording() { return false; }
}
class VH360_Studio_Placeholder_Replay_Storage_Provider extends VH360_Studio_Abstract_Placeholder_Provider implements VH360_Studio_Replay_Storage_Provider {}
