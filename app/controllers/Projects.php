<?php

class Projects extends Controller
{
    private $projectsModel;
    public $donorModel;
    public $testMode = TEST_MODE;

    public function __construct()
    {
        $this->projectsModel = $this->model('Project');
    }


    public function PurchaseResponse($fortParams = null)
    { // filter get respond
        if ($fortParams == null) {
            require_once APPROOT . '/helpers/PayfortIntegration.php';
            $objFort = new PayfortIntegration();
            $fortParams = $objFort->processResponse();
        }
        unset($fortParams['url'], $fortParams['r'], $fortParams['access_code'], $fortParams['return_url'], $fortParams['language'], $fortParams['merchant_identifier']);
        $order = $this->projectsModel->getSingle('*', ['order_identifier' => $fortParams['merchant_reference']], 'orders');

        if ($order->webhook_processed && $order->status == 1) {
            unset($_SESSION['cart']);
            $_SESSION['payment']['msg'] = ' شكرا لتبرعك لدي ' . SITENAME;
            flashRedirect('pages/thankyou/' . $order->hash . '/' . $order->total, 'msg', $_SESSION['payment']['msg'], 'alert alert-success');
            return;
        }

        $meta = json_encode($fortParams);
        ($fortParams['status'] == 14) ? $status = 1 : $status = 0;
        //load order data by merchant_reference/order_identifier
        $order = $this->projectsModel->getSingle('*', ['order_identifier' => $fortParams['merchant_reference']], 'orders');
        $donor = $this->projectsModel->getSingle('*', ['donor_id' => $order->donor_id], 'donors');
        $this->donorModel = $this->model('Donor');
        $activate = $this->donorModel->activeCard($fortParams['merchant_reference']);
        $data = [
            'meta' => $meta,
            'hash' => $order->hash,
            'status' => $status,
        ];
        //updating donation status in donation table
        $this->projectsModel->updateDonationStatus($order->order_id, $status);
        $this->projectsModel->updateOrderMeta($data);
        $this->projectsModel->updateBadalOrderByOrderId($order->order_id, $status);
        //update donation meta and set status on order table
        if ($status == 1) {
            //send Email and SMS confirmation
            $messaging = $this->model('Messaging');
            $sendData = [
                'mailto' => $donor->email,
                'mobile' => $donor->mobile,
                'identifier' => $order->order_identifier,
                'order_id' => $order->order_id,
                'total' => $order->total,
                'project' => $order->projects,
                'donor' => $order->donor_name,
            ];

            if (!$order->notified) {
                $this->projectsModel->notified($order->order_id);
                $messaging->sendConfirmation($sendData);
                $messaging->sendGiftCard($order); // send message of gift card 
            }
            unset($_SESSION['cart']);
            $_SESSION['payment']['msg'] = ' شكرا لتبرعك لدي ' . SITENAME;
            flashRedirect('pages/thankyou/' . $order->hash . '/' . $order->total, 'msg', $_SESSION['payment']['msg'], 'alert alert-success');
        } else {
            $order = $this->projectsModel->getSingle('*', ['order_identifier' => $fortParams['merchant_reference']], 'orders');
            $projects_id  = str_replace(")", "", $order->projects_id);
            $projects_id  = str_replace("(", "", $projects_id);
            echo "the status is 0";
        }
    }


    
}
