<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Bible_REST_Controller {
    private $namespace='vh360-studio/v1'; private $repo; private $importer;
    public function __construct(){ $this->repo=new VH360_Studio_Bible_Repository(); $this->importer=new VH360_Studio_Bible_Importer(); }
    public function register_routes(){
        register_rest_route($this->namespace,'/bible/translations',array('methods'=>'GET','callback'=>array($this,'translations'),'permission_callback'=>array($this,'can_query')));
        register_rest_route($this->namespace,'/bible/books',array('methods'=>'GET','callback'=>array($this,'books'),'permission_callback'=>array($this,'can_query'),'args'=>array('translation'=>array('required'=>true))));
        register_rest_route($this->namespace,'/bible/chapters',array('methods'=>'GET','callback'=>array($this,'chapters'),'permission_callback'=>array($this,'can_query')));
        register_rest_route($this->namespace,'/bible/verses',array('methods'=>'GET','callback'=>array($this,'verses'),'permission_callback'=>array($this,'can_query')));
        register_rest_route($this->namespace,'/bible/passage',array('methods'=>'GET','callback'=>array($this,'passage'),'permission_callback'=>array($this,'can_query')));
        register_rest_route($this->namespace,'/bible/imports',array('methods'=>'POST','callback'=>array($this,'create_import'),'permission_callback'=>array($this,'can_import')));
        register_rest_route($this->namespace,'/bible/imports/(?P<id>\d+)',array(array('methods'=>'GET','callback'=>array($this,'get_import'),'permission_callback'=>array($this,'can_import')),array('methods'=>'DELETE','callback'=>array($this,'delete_import'),'permission_callback'=>array($this,'can_import'))));
        register_rest_route($this->namespace,'/bible/imports/(?P<id>\d+)/batch',array('methods'=>'POST','callback'=>array($this,'batch'),'permission_callback'=>array($this,'can_import')));
    }
    public function can_query(){return is_user_logged_in() && VH360_Studio_Permissions::user_can_access_studio() && wp_verify_nonce(isset($_SERVER['HTTP_X_WP_NONCE'])?sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])):'','wp_rest');}
    public function can_import(){return current_user_can('manage_options') && wp_verify_nonce(isset($_SERVER['HTTP_X_WP_NONCE'])?sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])):'','wp_rest');}
    public function translations(){return rest_ensure_response($this->repo->list_translations());}
    public function books($r){return rest_ensure_response($this->repo->list_books($r['translation']));}
    public function chapters($r){return rest_ensure_response($this->repo->list_chapters($r['translation'],$r['book']));}
    public function verses($r){return rest_ensure_response($this->repo->get_chapter($r['translation'],$r['book'],absint($r['chapter'])));}
    public function passage($r){$result=$this->repo->resolve_reference($r['translation'],$r['reference']); return is_wp_error($result)?$result:rest_ensure_response($result);}
    public function create_import($r){$meta=$r->get_params();$files=$r->get_file_params();$file=isset($files['file'])?$files['file']:array();$job=$this->importer->create_job(get_current_user_id(),$file,$meta);return is_wp_error($job)?$job:rest_ensure_response($job);}
    public function get_import($r){$job=$this->importer->get_job(absint($r['id']));return $job?rest_ensure_response($job):new WP_Error('vh360_bible_import_missing',__('Import job not found.','videohub360-studio'),array('status'=>404));}
    public function delete_import($r){return rest_ensure_response(array('deleted'=>$this->importer->cancel_job(absint($r['id']))));}
    public function batch($r){$job=$this->importer->process_batch(absint($r['id']));return is_wp_error($job)?$job:rest_ensure_response($job);}
}
