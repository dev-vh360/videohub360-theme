<?php
if (!defined('ABSPATH')) exit;
class VideoHub360_Consent_WP_Consent_API { public function __construct($manager){ add_filter('wp_consent_api_registered_'.$this->map('necessary'), '__return_true'); add_filter('videohub360_wp_consent_api_map', function($m){ return $m; }); } private function map($c){ $m=array('necessary'=>'functional','preferences'=>'preferences','analytics'=>'statistics-anonymous','advertising'=>'marketing'); return $m[$c] ?? $c; } }
