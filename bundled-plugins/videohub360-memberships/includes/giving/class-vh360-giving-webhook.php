<?php
if (!defined('ABSPATH')) exit;
class VH360_Giving_Webhook {
    public static function maybe_handle_event($event_type,$object,$event_id){
        if('checkout.session.completed'===$event_type) return self::maybe_handle_checkout_completed($object,$event_id);
        if('invoice.paid'===$event_type) return self::handle_invoice_paid($object,$event_id);
        if('invoice.payment_failed'===$event_type) return self::handle_invoice_failed($object,$event_id);
        if('customer.subscription.updated'===$event_type) return self::handle_subscription_updated($object,$event_id);
        if('customer.subscription.deleted'===$event_type) return self::handle_subscription_deleted($object,$event_id);
        return false;
    }

    public static function maybe_handle_checkout_completed($session,$event_id){
        $type=$session['metadata']['type']??'';
        if('giving'===$type){ $tx_id=absint($session['metadata']['transaction_id']??0); $tx=VH360_Giving_Transactions::get($tx_id); if(!$tx) return new WP_Error('giving_tx_missing','Giving transaction not found.'); if($tx->stripe_event_id===$event_id||$tx->status==='paid') return true; VH360_Giving_Transactions::update($tx_id,array('status'=>'paid','gateway_transaction_id'=>sanitize_text_field($session['payment_intent']??''),'gateway_customer_id'=>sanitize_text_field($session['customer']??''),'gateway_event_id'=>$event_id,'stripe_payment_intent_id'=>sanitize_text_field($session['payment_intent']??''),'stripe_customer_id'=>sanitize_text_field($session['customer']??''),'stripe_event_id'=>$event_id,'given_at'=>current_time('mysql'))); return true; }
        if('giving_recurring'!==$type) return false;
        $recurring_id=absint($session['metadata']['recurring_id']??0); $gift=VH360_Giving_Recurring::get($recurring_id); if(!$gift) return new WP_Error('giving_recurring_missing','Recurring giving record not found.');
        $subscription_id=sanitize_text_field($session['subscription']??'');
        $customer_id=sanitize_text_field($session['customer']??'');
        $data=array('status'=>'active','stripe_subscription_id'=>$subscription_id,'stripe_customer_id'=>$customer_id,'started_at'=>current_time('mysql'));
        if($subscription_id){ $subscription=VH360_Stripe_Bootstrap::get_instance()->api_get('/v1/subscriptions/'.rawurlencode($subscription_id)); if(!is_wp_error($subscription)){ $data=array_merge($data,self::normalize_subscription($subscription)); if(empty($data['started_at'])) $data['started_at']=current_time('mysql'); } }
        VH360_Giving_Recurring::update($recurring_id,$data);
        return true;
    }

    private static function handle_invoice_paid($invoice,$event_id){ $gift=self::resolve_recurring_from_invoice($invoice); if(!$gift) return false; $invoice_id=sanitize_text_field($invoice['id']??''); if(VH360_Giving_Transactions::invoice_exists($invoice_id)) return true; $amount=isset($invoice['amount_paid'])?((float)$invoice['amount_paid']/100):(float)$gift->amount; $currency=strtolower(sanitize_text_field($invoice['currency']??$gift->currency)); $paid_at=!empty($invoice['status_transitions']['paid_at'])?gmdate('Y-m-d H:i:s',(int)$invoice['status_transitions']['paid_at']):current_time('mysql'); VH360_Giving_Transactions::create(array('user_id'=>$gift->user_id,'fund_id'=>$gift->fund_id,'fund_key'=>$gift->fund_key,'fund_label'=>$gift->fund_label,'amount'=>$amount,'currency'=>$currency,'status'=>'paid','gateway'=>'stripe','gateway_mode'=>'subscription','gateway_transaction_id'=>$invoice_id,'gateway_customer_id'=>sanitize_text_field($invoice['customer']??$gift->stripe_customer_id),'gateway_subscription_id'=>sanitize_text_field($invoice['subscription']??$gift->stripe_subscription_id),'gateway_event_id'=>$event_id,'stripe_invoice_id'=>$invoice_id,'stripe_customer_id'=>sanitize_text_field($invoice['customer']??$gift->stripe_customer_id),'stripe_subscription_id'=>sanitize_text_field($invoice['subscription']??$gift->stripe_subscription_id),'source'=>$gift->source,'anonymous'=>$gift->anonymous,'note'=>$gift->note,'given_at'=>$paid_at)); VH360_Giving_Recurring::update_status($gift->id,'active'); return true; }
    private static function handle_invoice_failed($invoice,$event_id){ $gift=self::resolve_recurring_from_invoice($invoice); if(!$gift) return false; VH360_Giving_Recurring::update_status($gift->id,'past_due'); return true; }
    private static function handle_subscription_updated($sub,$event_id){ if(!self::is_giving_subscription($sub)) return false; $gift=self::resolve_recurring_from_subscription($sub); if(!$gift) return false; VH360_Giving_Recurring::update($gift->id,self::normalize_subscription($sub)); return true; }
    private static function handle_subscription_deleted($sub,$event_id){ if(!self::is_giving_subscription($sub)) return false; $gift=self::resolve_recurring_from_subscription($sub); if(!$gift) return false; $data=self::normalize_subscription($sub); $data['status']='canceled'; if(empty($data['canceled_at'])) $data['canceled_at']=current_time('mysql'); VH360_Giving_Recurring::update($gift->id,$data); return true; }

    private static function normalize_subscription($sub){
        $price_id='';
        if(!empty($sub['items']['data'][0]['price']['id'])) $price_id=sanitize_text_field($sub['items']['data'][0]['price']['id']);
        $data=array(
            'status'=>sanitize_key($sub['status']??'active'),
            'stripe_subscription_id'=>sanitize_text_field($sub['id']??''),
            'stripe_customer_id'=>sanitize_text_field($sub['customer']??''),
            'stripe_price_id'=>$price_id,
            'cancel_at_period_end'=>!empty($sub['cancel_at_period_end'])?1:0,
        );
        foreach(array('current_period_start','current_period_end','canceled_at') as $field){ if(!empty($sub[$field])) $data[$field]=gmdate('Y-m-d H:i:s',(int)$sub[$field]); }
        return array_filter($data,function($v){ return $v!=='' && $v!==null; });
    }

    private static function resolve_recurring_from_invoice($invoice){ $sub_id=$invoice['subscription']??''; if($sub_id){ $gift=VH360_Giving_Recurring::get_by_subscription($sub_id); if($gift) return $gift; } $meta=$invoice['subscription_details']['metadata']??($invoice['metadata']??array()); if(($meta['type']??'')==='giving_recurring'&&!empty($meta['recurring_id'])) return VH360_Giving_Recurring::get(absint($meta['recurring_id'])); return null; }
    private static function is_giving_subscription($sub){ $meta=$sub['metadata']??array(); return ($meta['type']??'')==='giving_recurring'||!empty($meta['recurring_id']); }
    private static function resolve_recurring_from_subscription($sub){ if(!empty($sub['id'])){ $gift=VH360_Giving_Recurring::get_by_subscription($sub['id']); if($gift) return $gift; } $meta=$sub['metadata']??array(); return !empty($meta['recurring_id'])?VH360_Giving_Recurring::get(absint($meta['recurring_id'])):null; }
}
