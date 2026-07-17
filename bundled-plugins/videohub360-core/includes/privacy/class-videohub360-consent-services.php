<?php
if (!defined('ABSPATH')) exit;
class VideoHub360_Consent_Services {
    private $manager;
    private $services = array();
    public function __construct($manager) { $this->manager = $manager; }
    public function register($slug, $args) { $slug = sanitize_key($slug); $cat = sanitize_key($args['category'] ?? 'necessary'); if (!$slug) return false; $this->services[$slug] = array('category'=>$cat,'label'=>sanitize_text_field($args['label'] ?? $slug),'description'=>sanitize_text_field($args['description'] ?? '')); return true; }
    public function all() { return apply_filters('videohub360_consent_services', $this->services); }
    public function has($slug) { $services = $this->all(); $slug = sanitize_key($slug); return isset($services[$slug]) ? $this->manager->has_consent($services[$slug]['category']) : true; }
}
