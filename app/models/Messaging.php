<?php

/**
 * Get order identifier by order_id
 * @param int $order_id
 * @return string
 */
if (!function_exists('orderIdentifier')) {
    function orderIdentifier($order_id)
    {
        if (empty($order_id)) return '';

        try {
            $db = new Database();
            $db->query("SELECT order_identifier FROM orders WHERE order_id = :id LIMIT 1");
            $db->bind(':id', $order_id);
            $result = $db->single();

            return $result ? $result->order_identifier : '';
        } catch (Exception $e) {
            return '';
        }
    }
}
/*
 * Copyright (C) 2018 Easy CMS Framework Ahmed Elmahdy
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License
 * @license    https://opensource.org/licenses/GPL-3.0
 *
 * @package    Easy CMS MVC framework
 * @author     Ahmed Elmahdy
 * @link       https://ahmedx.com
 *
 * For more information about the author , see <http://www.ahmedx.com/>.
 */

class Messaging extends ModelAdmin
{

    private $donorModel;
    public function __construct()
    {
        parent::__construct('Donor');
    }

    

    public function log()
    {
    }

    /**
     * sendning message to member
     *
     * @return void
     */
    public function sendSMS($data)
    {
        $smsSettings = $this->donorModel->getSettings('sms'); // load sms setting 
        $sms = json_decode($smsSettings->value);
        flash('donor_msg', 'هناك خطأ ما بوابة الارسال غير مفعلة', 'alert alert-danger');
        redirect('donors');

        $members = $this->donorModel->getUsersData($data['members']);
        foreach ($members as $member) {
            $mobile = str_replace(' ', '', $member->mobile);
            $message = str_replace('[[name]]', $member->full_name, $_POST['message']); // replace name string with user name
            $message = str_replace('[[identifier]]', $member->donor_identifier, $message); // replace name string with user name
            $message = str_replace('[[total]]', $member->total, $message); // replace name string with user name
            $message = str_replace('[[project]]', $member->project, $message); // replace name string with user name
            sendSMS($sms->sms_username, $sms->sms_password, $message, $mobile, $sms->sender_name, $sms->gateurl, $sms->gateway);
        }
    }

    /**
     * send sms code to varifay mobile number
     *
     * @param [array] $data
     * @return void
     */
    public function mobileCodeSend($data)
    {
        $smsSettings = $this->getSettings('sms'); // load sms setting 
        $sms = json_decode($smsSettings->value);
        if ($sms->smsenabled) {
            return sendSMS($sms->sms_username, $sms->sms_password, $data['msg'], $data['mobile'], $sms->sender_name, $sms->gateurl, $sms->gateway);
        } else {
            return false;
        }
    }

    /**
     * donation Admin Notification
     *
     * @param [array] $data
     * @return void
     */
    public function donationAdminNotify($data)
    {
        $emailSettings = $this->getSettings('email'); // load email setting 
        $email = json_decode($emailSettings->value);
        $this->Email($email->donation_email, $data['subject'], nl2br($data['msg'])); // sending Email
    }

    /**
     * donation donor Notification
     *
     * @param [array] $data
     * @return void
     */
    public function donationDonorNotify($data)
    {
        if (!empty($data['mailto'])) { // if message through Email
            $msg_option = json_decode($this->getSettings('notifications')->value);
            if ($msg_option->inform_enabled) {
                $msg = str_replace('[[name]]', $data['donor'], $msg_option->inform_msg); // replace name string with user name
                $msg = str_replace('[[identifier]]', $data['identifier'], $msg); // replace identifier string with identifier
                $msg = str_replace('[[total]]', $data['total'], $msg); // replace total string with order total
                $msg = str_replace('[[project]]', $data['project'], $msg); // replace project string with project name
                $this->Email($data['mailto'], $msg_option->inform_subject, nl2br($msg)); // sending Email
            }
        }
    }

    /**
     * send Email and SMS Confirmation
     *
     * @param [array] $data
     * @return void
     */
    public function sendConfirmation($data)
    {
        $msg_option = json_decode($this->getSettings('notifications')->value);
        if ($msg_option->confirm_enabled) {
            // prepar EMAIL MSG
            $msg = str_replace('[[name]]', $data['donor'], $msg_option->confirm_msg); // replace name string with user name
            $msg = str_replace('[[identifier]]', $data['identifier'], $msg); // replace identifier string with identifier
            $msg = str_replace('[[total]]', $data['total'], $msg); // replace total string with order total
            $msg = str_replace('[[project]]', $data['project'], $msg); // replace project string with project name
            $msg = str_replace('[[link]]', URLROOT .'/invoices/show/' . orderIdentifier(@$data['order_id'])??"", $msg); // replace link string with order invoices 

            
            // send email
            if (!empty($data['mailto'])) $this->Email($data['mailto'],  $msg_option->confirm_subject, $msg);
        }
        
        if ($msg_option->confirm_sms) {
            $smsmsg = str_replace('[[name]]', $data['donor'], $msg_option->confirm_sms_msg); // replace name string with user name
            $smsmsg = str_replace('[[identifier]]', $data['identifier'], $smsmsg); // replace identifier string with identifier
            $smsmsg = str_replace('[[total]]', $data['total'], $smsmsg); // replace total string with order total
            $smsmsg = str_replace('[[project]]', $data['project'], $smsmsg); // replace project string with project name
            $smsmsg = str_replace('[[link]]', URLROOT .'/invoices/show/' . orderIdentifier($data['order_id'])??"", $smsmsg); // replace link string with order invoices 

            // send SMS
            $this->SMS($data['mobile'], $smsmsg);
        }
        // send whatsapp message confirmation
        $this->ConfirmedOrdersApp("$data[mobile]", "$data[donor]", (object) $data);
    }

    public function sendGiftCard(object $order)
    {
        $msg_option = json_decode($this->getSettings('notifications')->value);
        $gift = json_decode($this->getSettings('gift')->value); // loading sending settings
        $gift_data = json_decode($order->gift_data);
        (!empty($order->store_id)) ? $store = $order->store_id . '/' : $store = '';
        $card =  str_replace('.jpg', '', str_replace('/gifts/img_',  URLROOT . '/gift/' . $store, @$gift_data->card));
        // prepar  MSG
        $message = str_replace('[[giver_group]]', $gift_data->giver_group, $gift->msg); // replace giver_group string with giver group
        $message = str_replace('[[giver_name]]', $gift_data->giver_name, $message); // replace giver name string with  giver name
        $message = str_replace('[[card]]',  $card, $message); // replace total string with card
        $message = str_replace('[[project]]', $order->projects, $message); // replace name string with project
        $message = str_replace('[[from_name]]', $order->donor_name, $message); // replace name string with from name

        if ($msg_option->confirm_enabled) {
            // send email
            if (!empty($gift_data->giver_email)) $this->Email($gift_data->giver_email,  'إلى / ' . $gift_data->giver_group . ' ' . $gift_data->giver_name . ' أهديك شيئاً نلتقي به في الجنة', $message);
        }
        if ($msg_option->confirm_sms) {
            if (!empty($gift_data->giver_number)) $this->SMS($gift_data->giver_number, $message);
        }
        // send whatsappp
        $msg_option = json_decode($this->getSettings('whatsapp')->value);
        if ($msg_option->template_name_gift_confirm != "") {
            $datawhats = [
                'name'          => $gift_data->giver_name,
                'donor_name'    => @$gift_data->donor_name,
                'card'          => MEDIAURL . "/" . @$gift_data->card,
                'project'       => $order->projects,
                'from_name'       => @$order->donor_name,
            ];
            $this->NotficationsWhatsApp($gift_data->giver_number, $datawhats, 'gift_confirm');
        }
    }

    /**
     * send Email and SMS message 
     *
     * @param [array] $data
     * @return void
     */
    public function sendMessages($data, $message)
    {
        $this->Email($data['email'], $data['subject'], $message);
        $this->SMS($data['mobile'], $message);
    }


    /**
     * send Notficationto (EMail and SMS ) Confirmation for deceseds 
     *
     * @param [array] $data
     * @return void
     */
    public function deceasedsNoitfy($data)
    {
        $msg_option = json_decode($this->getSettings('deceased')->value);

        if ($msg_option->deceased_enabled) {
            // prepar EMAIL MSG
            $msg = str_replace('[[name]]', $data['name'], $msg_option->deceased_msg); // replace name string with user name
            $msg = str_replace('[[identifier]]', $data['identifier'],  $msg); // // replace name string with user name
            $msg = str_replace('[[total]]', $data['total'],  $msg); // replace name string with user name
            $msg = str_replace('[[project]]', $data['project'], $msg); // replace name string with user name
            // send email
            if (!empty($data['mailto'])) $this->Email($data['mailto'],  $msg_option->deceased_subject, $msg);
        }
        $smsmsg = str_replace('[[name]]', $data['name'], $msg_option->deceased_sms_msg); // replace name string with user name
        @$data['identifier'] ? $smsmsg = str_replace('[[identifier]]', $data['identifier'],  $smsmsg) : ""; // // replace name string with user name
        @$data['total'] ? $smsmsg = str_replace('[[total]]', $data['total'],  $smsmsg) : ""; // replace name string with user name
        @$data['project'] ? $smsmsg = str_replace('[[project]]', $data['project'], $smsmsg) : ""; // replace name string with user name
        // send SMS
        if ($msg_option->deceased_sms) {
            // $this->SMS($data['mobile'], $smsmsg);
        }
        $this->NotficationWhatssApp("$data[mobile]", "$data[mailto]", " $data[project]", "$data[total]", "deceased");
    }

     /**
     * send Email and SMS Confirmation
     *
     * @param [array] $data
     * @return void
     */
    public function sendNotfication($data, $type)
    {
        $data['type'] = $type;
        
        // store notify in app_notfiacrion 
        $this->storeNotfication($data);
        
        // send email, sms and notify by api in laravel application 
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => NOTIFICATION_DOMAIN. '/api/notfication',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Cookie: PHPSESSID=d27f437f16b9e581731a8a46da6e1832'
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
    }

    /**
     * store notify in app_notfiacrion
     *
     * @param [array] $data
     * @return void
     */
    public function storeNotfication($data)
    {
        $setting  = json_decode($this->getSettings('badal_notifications')->value);
        $optionSmsSetting = $data['type'] . '_sms';
        $sms_msgSetting = $data['type'] . '_sms_msg';
        require_once APPROOT . '/app/models/Notification.php';
        $model = new Notification();
        $orderInfo =  $model->getOrderInfo( $data['identifier']);
        if ($data['notify'] != null && $data['notify_id'] != null) {
            $notify['title']    = $data['notify'];
            $notify['donor_id'] = $data['notify_id'];
            $notify['type']     = $data['type'];
            $notify['badal_id'] = $orderInfo->badal_id;
            $notify['order_id'] = $orderInfo->order_id;
            $notify['msg'] =  $this->getMessage($setting->$sms_msgSetting, $data); 
            $model->storeNotify($notify);
        };
    }

    /**
     * get  message with data
     *
     * @param [array] $data
     * @return void
     */
    public function getMessage($message, $data)
    {
        $msg = str_replace('[[name]]', $data['donor'], $message); // replace name string with user name
        @$data['identifier'] ? $msg = str_replace('[[identifier]]', $data['identifier'], $msg) : ""; // replace identifier string with identifier
        @$data['total'] ? $msg = str_replace('[[total]]', $data['total'], $msg) : ""; // replace total string with order total
        @$data['project'] ? $msg = str_replace('[[project]]', $data['project'], $msg) : ""; // replace project string with project name
        @$data['substitute_name'] ? $msg = str_replace('[[substitute_name]]', @$data['substitute_name'], $msg) : " "; // replace substitute name string with substitute name
        @$data['behafeof'] ? $msg = str_replace('[[behafeof]]', @$data['behafeof'], $msg) : " "; // replace substitute name string with substitute name
        @$data['substitute_start'] ? $msg = str_replace('[[substitute_start]]',  date('Y/ m/ d | H:i a', @$data['substitute_start']), $msg) : ""; // replace start date string with start date name
        @$data['rate'] ? $msg = str_replace('[[rate]]', @$data['rate'], $msg) : " "; // replace start date string with start date name
        return $msg;
    }

    /**
     * Sending Push to Donor 
     * @param string $title
     * @param string $message
     * @param integer $donor_id
     * @return void
     */
    function sendDonor($title, $message, $donor_id)
    {
        $donor =  $this->countAll('WHERE donors.donor_id = ' . $donor_id, '', 'donors'); 
        if($donor == 1){
            sendPush($title, $message, $donor_id);
        }
    }
}
