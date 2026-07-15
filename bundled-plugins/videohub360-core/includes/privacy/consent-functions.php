<?php
if (!defined('ABSPATH')) exit;
function videohub360_consent_manager() { return class_exists('VideoHub360_Consent_Manager') ? VideoHub360_Consent_Manager::get_instance() : null; }
function videohub360_has_consent($category) { $m = videohub360_consent_manager(); return $m ? $m->has_consent($category) : true; }
function videohub360_register_consent_service($slug, $args) { $m = videohub360_consent_manager(); return $m ? $m->services()->register($slug, $args) : false; }
function videohub360_has_service_consent($slug) { $m = videohub360_consent_manager(); return $m ? $m->services()->has($slug) : true; }
