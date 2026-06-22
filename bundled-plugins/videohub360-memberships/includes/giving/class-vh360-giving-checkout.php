<?php
/** VideoHub360 Giving Stripe Checkout and donor actions. */
if (!defined('ABSPATH')) exit;

class VH360_Giving_Checkout {
    private static $instance = null;
    public static function get_instance(){ if(null===self::$instance){ self::$instance=new self(); } return self::$instance; }
    private function __construct(){ add_action('wp_ajax_vh360_giving_create_checkout',array($this,'ajax_create_checkout')); add_action('wp_ajax_vh360_giving_cancel_recurring',array($this,'ajax_cancel_recurring')); add_action('admin_post_vh360_download_giving_history',array($this,'download_history')); add_action('wp_enqueue_scripts',array($this,'enqueue')); }
    public function enqueue(){ if(function_exists('is_page_template')&&is_page_template('template-dashboard.php')&&vh360_giving_is_enabled()){ wp_enqueue_style('vh360-giving',VH360_MEMBERSHIPS_URL.'assets/frontend/giving.css',array(),VH360_MEMBERSHIPS_VERSION); wp_enqueue_script('vh360-giving',VH360_MEMBERSHIPS_URL.'assets/frontend/giving.js',array('jquery'),VH360_MEMBERSHIPS_VERSION,true); wp_localize_script('vh360-giving','vh360Giving',array('ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('vh360_giving_checkout'))); } }

    public function ajax_create_checkout(){
        check_ajax_referer('vh360_giving_checkout','nonce');
        $prepared=$this->prepare_request();
        if(is_wp_error($prepared)) wp_send_json_error(array('message'=>$prepared->get_error_message()));
        if('one_time'===$prepared['frequency']) $this->create_one_time_checkout($prepared);
        $this->create_recurring_checkout($prepared);
    }

    private function prepare_request(){
        $user_id=get_current_user_id(); if(!$user_id) return new WP_Error('login_required',__('You must be logged in.','videohub360-memberships'));
        if(!vh360_giving_is_enabled()) return new WP_Error('giving_disabled',__('Giving is not enabled.','videohub360-memberships'));
        $opts=vh360_giving_options(); $amount=isset($_POST['amount'])?(float)wp_unslash($_POST['amount']):0; if($amount<(float)$opts['minimum_amount']) return new WP_Error('invalid_amount',__('Please enter a valid giving amount.','videohub360-memberships'));
        $frequency=isset($_POST['frequency'])?sanitize_key(wp_unslash($_POST['frequency'])):'one_time'; if(!in_array($frequency,array('one_time','weekly','monthly'),true)) return new WP_Error('invalid_frequency',__('Please select a valid giving frequency.','videohub360-memberships'));
        $fund_id=isset($_POST['fund_id'])?absint($_POST['fund_id']):0; $fund=VH360_Giving_Funds::get_fund($fund_id); if(!$fund||empty($fund->enabled)) return new WP_Error('invalid_fund',__('Please select an active giving fund.','videohub360-memberships'));
        $stripe=VH360_Stripe_Bootstrap::get_instance(); if(!method_exists($stripe,'has_payment_credentials')||!$stripe->has_payment_credentials()) return new WP_Error('stripe_missing',__('Payment is not configured.','videohub360-memberships'));
        return array('user_id'=>$user_id,'opts'=>$opts,'amount'=>$amount,'frequency'=>$frequency,'fund'=>$fund,'stripe'=>$stripe,'note'=>!empty($opts['enable_notes'])&&isset($_POST['note'])?sanitize_textarea_field(wp_unslash($_POST['note'])):'','anonymous'=>!empty($opts['enable_anonymous'])&&!empty($_POST['anonymous'])?1:0,'currency'=>strtolower(sanitize_text_field($opts['default_currency'])));
    }

    private function create_one_time_checkout($d){
        $tx_id=VH360_Giving_Transactions::create(array('user_id'=>$d['user_id'],'fund_id'=>$d['fund']->id,'fund_key'=>$d['fund']->fund_key,'fund_label'=>$d['fund']->label,'amount'=>$d['amount'],'currency'=>$d['currency'],'note'=>$d['note'],'anonymous'=>$d['anonymous'],'source'=>'dashboard'));
        $customer=$this->get_or_create_customer($d['user_id']); if(is_wp_error($customer)){ VH360_Giving_Transactions::update($tx_id,array('status'=>'failed')); wp_send_json_error(array('message'=>$customer->get_error_message())); }
        $session=$d['stripe']->api_request('/v1/checkout/sessions',$this->build_session_params($d,$customer,array('mode'=>'payment','metadata_type'=>'giving','local_id_key'=>'transaction_id','local_id'=>$tx_id)));
        if(is_wp_error($session)){ VH360_Giving_Transactions::update($tx_id,array('status'=>'failed')); wp_send_json_error(array('message'=>$session->get_error_message())); }
        if(empty($session['url'])){ VH360_Giving_Transactions::update($tx_id,array('status'=>'failed','stripe_checkout_session_id'=>sanitize_text_field($session['id']??''))); wp_send_json_error(array('message'=>__('Stripe did not return a checkout URL. Please try again.','videohub360-memberships'))); }
        VH360_Giving_Transactions::update($tx_id,array('stripe_checkout_session_id'=>sanitize_text_field($session['id']??''),'stripe_customer_id'=>sanitize_text_field($customer),'gateway_customer_id'=>sanitize_text_field($customer)));
        wp_send_json_success(array('checkout_url'=>esc_url_raw($session['url'])));
    }

    private function create_recurring_checkout($d){
        $recurring_id=VH360_Giving_Recurring::create(array('user_id'=>$d['user_id'],'fund_id'=>$d['fund']->id,'fund_key'=>$d['fund']->fund_key,'fund_label'=>$d['fund']->label,'amount'=>$d['amount'],'currency'=>$d['currency'],'interval'=>$d['frequency'],'note'=>$d['note'],'anonymous'=>$d['anonymous'],'source'=>'dashboard'));
        $customer=$this->get_or_create_customer($d['user_id']); if(is_wp_error($customer)){ VH360_Giving_Recurring::update_status($recurring_id,'incomplete'); wp_send_json_error(array('message'=>$customer->get_error_message())); }
        $session=$d['stripe']->api_request('/v1/checkout/sessions',$this->build_session_params($d,$customer,array('mode'=>'subscription','metadata_type'=>'giving_recurring','local_id_key'=>'recurring_id','local_id'=>$recurring_id)));
        if(is_wp_error($session)){ VH360_Giving_Recurring::update_status($recurring_id,'incomplete'); wp_send_json_error(array('message'=>$session->get_error_message())); }
        if(empty($session['url'])){ wp_send_json_error(array('message'=>__('Stripe did not return a checkout URL. Please try again.','videohub360-memberships'))); }
        VH360_Giving_Recurring::update($recurring_id,array('stripe_customer_id'=>sanitize_text_field($customer)));
        wp_send_json_success(array('checkout_url'=>esc_url_raw($session['url'])));
    }

    private function build_session_params($d,$customer,$args){
        $dashboard_url=function_exists('vh360_get_dashboard_page_url')?vh360_get_dashboard_page_url():home_url('/dashboard/'); $is_sub='subscription'===$args['mode']; $interval='weekly'===$d['frequency']?'week':'month';
        $params=array('mode'=>$args['mode'],'customer'=>$customer,'line_items[0][price_data][currency]'=>$d['currency'],'line_items[0][price_data][product_data][name]'=>'VideoHub360 Giving - '.$d['fund']->label,'line_items[0][price_data][unit_amount]'=>(int)round($d['amount']*100),'line_items[0][quantity]'=>1,'success_url'=>add_query_arg(array('tab'=>'giving','vh360_giving_success'=>'1','session_id'=>'{CHECKOUT_SESSION_ID}'),$dashboard_url),'cancel_url'=>add_query_arg(array('tab'=>'giving','vh360_giving_cancel'=>'1'),$dashboard_url),'client_reference_id'=>$d['user_id'],'metadata[type]'=>$args['metadata_type'],'metadata[user_id]'=>$d['user_id'],'metadata['.$args['local_id_key'].']'=>$args['local_id'],'metadata[fund_key]'=>$d['fund']->fund_key,'metadata[source]'=>'dashboard');
        if($is_sub){ $params['line_items[0][price_data][recurring][interval]']=$interval; foreach(array('type'=>$args['metadata_type'],$args['local_id_key']=>$args['local_id'],'user_id'=>$d['user_id'],'fund_key'=>$d['fund']->fund_key,'source'=>'dashboard') as $k=>$v){ $params['subscription_data[metadata]['.$k.']']=$v; } } else { $params['payment_intent_data[metadata][type]']='giving'; $params['payment_intent_data[metadata][transaction_id]']=$args['local_id']; $params['payment_intent_data[metadata][source]']='dashboard'; }
        return $params;
    }

    public function ajax_cancel_recurring(){ check_ajax_referer('vh360_giving_checkout','nonce'); $user_id=get_current_user_id(); $id=isset($_POST['recurring_id'])?absint($_POST['recurring_id']):0; $gift=VH360_Giving_Recurring::get($id); if(!$user_id||!$gift||(int)$gift->user_id!==$user_id) wp_send_json_error(array('message'=>__('Recurring gift not found.','videohub360-memberships'))); if(empty($gift->stripe_subscription_id)){ VH360_Giving_Recurring::mark_canceled($id); wp_send_json_success(array('message'=>__('Recurring gift canceled.','videohub360-memberships'))); } $stripe=VH360_Stripe_Bootstrap::get_instance(); $result=$stripe->api_request('/v1/subscriptions/'.rawurlencode($gift->stripe_subscription_id),array('cancel_at_period_end'=>'true'),'POST'); if(is_wp_error($result)) wp_send_json_error(array('message'=>$result->get_error_message())); VH360_Giving_Recurring::update($id,array('cancel_at_period_end'=>1,'status'=>sanitize_key($result['status']??$gift->status),'current_period_end'=>!empty($result['current_period_end'])?gmdate('Y-m-d H:i:s',(int)$result['current_period_end']):$gift->current_period_end)); wp_send_json_success(array('message'=>__('Recurring gift cancellation scheduled.','videohub360-memberships'))); }

    public function download_history(){ if(!is_user_logged_in()) wp_die(esc_html__('You must be logged in.','videohub360-memberships')); check_admin_referer('vh360_download_giving_history'); $user_id=get_current_user_id(); $from=isset($_GET['date_from'])?sanitize_text_field(wp_unslash($_GET['date_from'])):''; $to=isset($_GET['date_to'])?sanitize_text_field(wp_unslash($_GET['date_to'])):''; $rows=VH360_Giving_Transactions::for_user($user_id,10000,$from,$to); nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=vh360-giving-history-'.date('Y-m-d').'.csv'); $out=fopen('php://output','w'); fputcsv($out,array('Date','Fund','Amount','Currency','Status','Frequency','Gateway','Stripe Payment Intent ID','Stripe Invoice ID','Stripe Subscription ID','Source','Note')); foreach($rows as $r){ fputcsv($out,array($r->given_at?:$r->created_at,$r->fund_label,$r->amount,strtoupper($r->currency),$r->status,($r->gateway_mode==='subscription'?'Recurring':'One-time'),$r->gateway,$r->stripe_payment_intent_id,$r->stripe_invoice_id,$r->stripe_subscription_id?:$r->gateway_subscription_id,$r->source,$r->note)); } fclose($out); exit; }

    private function get_or_create_customer($user_id){ if(class_exists('VH360_Stripe_Checkout')) return VH360_Stripe_Checkout::get_instance()->get_or_create_stripe_customer($user_id); return new WP_Error('stripe_checkout_missing',__('Stripe checkout is unavailable.','videohub360-memberships')); }
}
