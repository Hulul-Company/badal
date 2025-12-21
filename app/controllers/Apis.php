<?php

class Apis extends Controller
{
    private $apiModel;
    public $donorModel;
    public $badalorderModel;


    public function __construct()
    {
        $this->apiModel = $this->model('Api');
        $this->badalorderModel = $this->model('Badalorder'); 

        // إذا كان فيه models تانية تحتاجها
        // $this->substituteModel = $this->model('Substitute');
        // $this->orderModel = $this->model('Order');
    }

    public function index()
    {
        $data = [
            'status' => 'error',
            'code' => 000,
            'msg' => 'bad request',
        ];
        echo json_encode($data);
    }
    /**
     * donation API 
     *
     * @return json
     */
    public function donations()
    {
        
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (isset($_POST['api_key']) && isset($_POST['api_user'])) { // check if credential is sent
            $auth = $this->apiModel->auth($_POST['api_user'], $_POST['api_key']); // load API settings
            if ($auth['enable']) {
                //validate credential
                if ($auth['authorized']) {
                    isset($_POST['start']) ? $start = (int) $_POST['start'] : $start = 0;
                    isset($_POST['count']) ? $count = (int) $_POST['count'] : $count = 20;
                    isset($_POST['status']) ? $status = ' AND donations.status =' . (int) $_POST['status'] : $status = '';
                    isset($_POST['donation_id']) ? $donation_id = ' AND donations.donation_id =' . (int) $_POST['donation_id'] : $donation_id = '';
                    isset($_POST['project_id']) ? $project_id = ' AND donations.project_id =' . (int) $_POST['project_id'] : $project_id = '';
                    isset($_POST['order_id']) ? $order_id = ' AND donations.order_id =' . (int) $_POST['order_id'] : $order_id = '';
                    isset($_POST['API_status']) ? $API_status = ' AND donations.API_status ="' . $_POST['API_status'] . '"' : $API_status = '';

                    $donations = $this->apiModel->getDonations($start, $count, $status, $donation_id, $project_id, $order_id, $API_status);
                    $data = [
                        'status' => 'success',
                        'code' => 100,
                        'msg' => 'Successfully connected',
                        'count' => count($donations),
                        'donations' => $donations,
                    ];
                } else { // wrong user or key
                    $data = [
                        'status' => 'error',
                        'code' => 102,
                        'msg' => 'Wrong Credential check user and API key',
                    ];
                }
            } else { // API not enabled
                $data = [
                    'status' => 'error',
                    'code' => 103,
                    'msg' => 'API not enabled',
                ];
            }
        } else { // no credential
            $data = [
                'status' => 'error',
                'code' => 101,
                'msg' => 'Invalid Credential',
            ];
        }
        echo json_encode($data);
    }

    /**
     * Order API 
     *
     * @return json
     */
    public function orders()
    {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (isset($_POST['api_key']) && isset($_POST['api_user'])) {
            $auth = $this->apiModel->auth($_POST['api_user'], $_POST['api_key']);

            if ($auth['enable']) {
                if ($auth['authorized']) {

                    // ✅ معالجة Pagination Parameters
                    isset($_POST['start']) ? $start = (int) $_POST['start'] : $start = 0;
                    isset($_POST['count']) ? $count = (int) $_POST['count'] : $count = 20;

                    // ✅ التحقق من القيم
                    if ($start < 0) $start = 0;
                    if ($count < 1 || $count > 100) $count = 20;

                    // معالجة المدخلات الأخرى
                    isset($_POST['status']) ? $status = ' AND ord.status =' . (int) $_POST['status'] : $status = '';
                    isset($_POST['order_identifier']) ? $order_identifier = ' AND ord.order_identifier =' . (int) $_POST['order_identifier'] : $order_identifier = '';
                    isset($_POST['order_id']) ? $order_id = ' AND ord.order_id =' . (int) $_POST['order_id'] : $order_id = '';
                    isset($_POST['custom_status_id']) ? $custom_status_id = ' AND ord.status_id =' . (int) $_POST['custom_status_id'] : $custom_status_id = '';
                    isset($_POST['API_status']) ? $API_status = ' AND ord.API_status ="' . $_POST['API_status'] . '"' : $API_status = '';
                    isset($_POST['API_odoo']) ? $API_odoo = ' AND ord.API_odoo ="' . $_POST['API_odoo'] . '"' : $API_odoo = '';
                    isset($_POST['payment_method']) ? $payment_method = ' AND ord.payment_method_id =' . (int) $_POST['payment_method'] : $payment_method = '';
                    isset($_POST['store_id']) ? $store_id = ' AND ord.store_id =' . (int) $_POST['store_id'] : $store_id = '';
                    isset($_POST['start_date']) ? $start_date = " AND ord.`create_date` >= UNIX_TIMESTAMP('" . date('Y-m-d', strtotime($_POST['start_date'])) . " 00:00:00') " : $start_date = '';
                    isset($_POST['end_date']) ? $end_date = " AND ord.`create_date` <= UNIX_TIMESTAMP('" . date('Y-m-d', strtotime($_POST['end_date'])) . " 23:59:59')" : $end_date = '';

                    // بناء فلتر حالة التنفيذ
                    $execution_status = isset($_POST['execution_status']) ? $_POST['execution_status'] : '';
                    $execution_cond = '';

                    if (!empty($execution_status)) {
                        switch ($execution_status) {
                            case 'not_started':
                            case '0':
                                $execution_cond = " AND EXISTS (SELECT 1 FROM badal_orders bo WHERE bo.order_id = ord.order_id AND bo.start_at IS NULL AND bo.complete_at IS NULL)";
                                break;
                            case 'in_progress':
                            case '1':
                                $execution_cond = " AND EXISTS (SELECT 1 FROM badal_orders bo WHERE bo.order_id = ord.order_id AND bo.start_at IS NOT NULL AND bo.complete_at IS NULL)";
                                break;
                            case 'completed':
                            case '2':
                                $execution_cond = " AND EXISTS (SELECT 1 FROM badal_orders bo WHERE bo.order_id = ord.order_id AND bo.complete_at IS NOT NULL)";
                                break;
                        }
                    }

                    // ✅ تحميل الطلبات
                    $orders = $this->apiModel->getOrders(
                        $start,
                        $count,
                        $status,
                        $order_identifier,
                        $order_id,
                        $API_status,
                        $API_odoo,
                        $custom_status_id,
                        $payment_method,
                        $store_id,
                        $start_date,
                        $end_date,
                        $execution_cond
                    );

                    // ✅ جلب إجمالي العدد
                    $total = $this->apiModel->getOrdersCount(
                        $status,
                        $order_identifier,
                        $order_id,
                        $API_status,
                        $API_odoo,
                        $custom_status_id,
                        $payment_method,
                        $store_id,
                        $start_date,
                        $end_date,
                        $execution_cond
                    );

                    $newOrders = [];
                    foreach ($orders as $index => $order) {
                        $newOrders[$index] = $order;
                        $newOrders[$index]->meta = json_decode($order->meta);

                        // donations & store
                        $newOrders[$index]->donations = $this->apiModel->getDonationByOrderId($order->order_id);
                        $newOrders[$index]->store_id  = $this->apiModel->getStore($order->store_id);

                        $badal = $this->badalorderModel->getBadalOrderByOrderID($order->order_id);

                        if ($badal) {
                            // execution status
                            if (empty($badal->start_at) && empty($badal->complete_at)) {
                                $newOrders[$index]->execution_status_id = 0;
                                $newOrders[$index]->execution_status = 'not_started';
                            } elseif (!empty($badal->start_at) && empty($badal->complete_at)) {
                                $newOrders[$index]->execution_status_id = 1;
                                $newOrders[$index]->execution_status = 'in_progress';
                            } elseif (!empty($badal->complete_at)) {
                                $newOrders[$index]->execution_status_id = 2;
                                $newOrders[$index]->execution_status = 'completed';
                            }

                            $newOrders[$index]->badal = $badal;

                            // executor (substitute)
                            if (!empty($badal->substitute_id)) {
                                $executor = $this->badalorderModel->getSsubstituteFromOrder($order->order_id);

                                if ($executor) {
                                    $newOrders[$index]->executor = (object) [
                                        'substitute_id' => $executor->substitute_id,
                                        'full_name'     => $executor->full_name,
                                        'email'         => $executor->email,
                                        'mobile'        => $executor->phone,
                                        'identity'      => $executor->identity,
                                        'languages'     => $executor->languages,
                                        'gender'        => $executor->gender,
                                        'proportion'    => isset($executor->proportion) ? $executor->proportion : 0
                                    ];

                                    $newOrders[$index]->line_commission =
                                        !empty($badal->substitute_proportion)
                                        ? $badal->substitute_proportion
                                        : ($executor->proportion ?? 0);
                                }
                            } else {
                                $newOrders[$index]->executor = null;
                                $newOrders[$index]->line_commission = 0;
                            }
                        } else {
                            $newOrders[$index]->execution_status_id = null;
                            $newOrders[$index]->execution_status = null;
                            $newOrders[$index]->badal = null;
                            $newOrders[$index]->executor = null;
                            $newOrders[$index]->line_commission = 0;
                        }
                    }

                    $data = [
                        'status' => 'success',
                        'code' => 100,
                        'msg' => 'Successfully connected',
                        'orders' => $newOrders,
                        'pagination' => [
                            'start' => $start,
                            'count' => $count,
                            'total' => $total,
                            'current_page' => $total > 0 ? floor($start / $count) + 1 : 0,
                            'total_pages' => $total > 0 ? ceil($total / $count) : 0,
                            'has_more' => ($start + $count) < $total
                        ],
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 102,
                        'msg' => 'Wrong Credential check user and API key',
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'code' => 103,
                    'msg' => 'API not enabled',
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 101,
                'msg' => 'Invalid Credential',
            ];
        }

        echo json_encode($data);
    }


    /**
     * update order status
     *
     * @return JSON
     */
    public function orderupdate()
    {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (isset($_POST['api_key']) && isset($_POST['api_user'])) { // check if credential is sent
            $auth = $this->apiModel->auth($_POST['api_user'], $_POST['api_key']); // load API settings
            if ($auth['enable']) {
                //validate credential
                if ($auth['authorized']) {
                    $filter = [];
                    isset($_POST['status']) ?           $filter['status'] =  (int) $_POST['status']                         : '';
                    isset($_POST['order_identifier']) ? $filter['order_identifier'] =  (int) $_POST['order_identifier']     : '';
                    isset($_POST['order_id']) ?         $filter['order_id'] =  (int) $_POST['order_id']                     : '';
                    isset($_POST['custom_status_id']) ? $filter['status_id'] =  (int) $_POST['custom_status_id']            : '';
                    isset($_POST['API_status']) ?       $filter['API_status'] =  $_POST['API_status']                       : '';
                    isset($_POST['API_odoo']) ?         $filter['API_odoo'] =  $_POST['API_odoo']                           : '';
                    isset($_POST['payment_method']) ?   $filter['payment_method_id'] = (int) $_POST['payment_method']       : '';
                    $orders = false;
                    if (isset($_POST['set_status'])) {
                                            // dd( $_POST['set_status']);
                        switch ($_POST['set_status']) {
                            case 'read':
                                $orders = $this->apiModel->updatetOrders($filter, 'read');
                                break;
                            case 'unread':
                                $orders = $this->apiModel->updatetOrders($filter, 'unread');
                                break;
                            default:
                                break;
                        }
                    }
                    // update orders
                    if ($orders) {
                        $msg = 'Successfully updated ' . $orders . ' record';
                    } else {
                        $msg = 'Not updated there are an error or ther is nothing to update. please try again later';
                    }
                    $data = [
                        'status' => 'success',
                        'code' => 100,
                        'msg' => $msg,
                        'orders' => $orders,
                    ];
                } else { // wrong user or key
                    $data = [
                        'status' => 'error',
                        'code' => 102,
                        'msg' => 'Wrong Credential check user and API key',
                    ];
                }
            } else { // API not enabled
                $data = [
                    'status' => 'error',
                    'code' => 103,
                    'msg' => 'API not enabled',
                ];
            }
        } else { // no credential
            $data = [
                'status' => 'error',
                'code' => 101,
                'msg' => 'Invalid Credential',
            ];
        }
        echo json_encode($data);
    }
    /**
     * update order status
     *
     * @return JSON
     */
    public function orderupdate_odoo()
    {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (isset($_POST['api_key']) && isset($_POST['api_user'])) { // check if credential is sent
            $auth = $this->apiModel->auth($_POST['api_user'], $_POST['api_key']); // load API settings
            if ($auth['enable']) {
                //validate credential
                if ($auth['authorized']) {
                    $filter = [];
                    isset($_POST['status']) ?           $filter['status'] =  (int) $_POST['status']                       : '';
                    isset($_POST['order_identifier']) ? $filter['order_identifier'] =  (int) $_POST['order_identifier']   : '';
                    isset($_POST['order_id']) ?         $filter['order_id'] =  (int) $_POST['order_id']                   : '';
                    isset($_POST['custom_status_id']) ? $filter['status_id'] =  (int) $_POST['custom_status_id']          : '';
                    isset($_POST['API_status']) ?       $filter['API_status'] =  (string) $_POST['API_status']                : '';
                    isset($_POST['API_odoo']) ?         $filter['API_odoo'] =  (int) $_POST['API_odoo']                  : '';
                    isset($_POST['payment_method']) ?   $filter['payment_method_id'] = (int) $_POST['payment_method']     : '';
                    $orders = false;
                    if (isset($_POST['set_odoo_status'])) {
                        switch ($_POST['set_odoo_status']) {
                            case 1:
                                $orders = $this->apiModel->updatetOrdersOdoo($filter, 1);
                                break;
                            case 0:
                                $orders = $this->apiModel->updatetOrdersOdoo($filter, 0);
                                break;
                            default:
                                break;
                        }
                    }
                    // update orders
                    if ($orders) {
                        $msg = 'Successfully updated ' . $orders . ' record';
                    } else {
                        $msg = 'Not updated there are an error or ther is nothing to update. please try again later';
                    }
                    $data = [
                        'status' => 'success',
                        'code' => 100,
                        'msg' => $msg,
                        'orders' => $orders,
                    ];
                } else { // wrong user or key
                    $data = [
                        'status' => 'error',
                        'code' => 102,
                        'msg' => 'Wrong Credential check user and API key',
                    ];
                }
            } else { // API not enabled
                $data = [
                    'status' => 'error',
                    'code' => 103,
                    'msg' => 'API not enabled',
                ];
            }
        } else { // no credential
            $data = [
                'status' => 'error',
                'code' => 101,
                'msg' => 'Invalid Credential',
            ];
        }
        echo json_encode($data);
    }
    public function stores()
    {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (isset($_POST['api_key']) && isset($_POST['api_user'])) { // check if credential is sent
            $auth = $this->apiModel->auth($_POST['api_user'], $_POST['api_key']); // load API settings
            if ($auth['enable']) {
                //validate credential
                if ($auth['authorized']) {

                    $stores = $this->apiModel->storesList('');
                    $data = [
                        'status' => 'success',
                        'code' => 100,
                        'msg' => 'Successfully connected',
                        'count' => count($stores),
                        'stores' => $stores,
                    ];
                } else { // wrong user or key
                    $data = [
                        'status' => 'error',
                        'code' => 102,
                        'msg' => 'Wrong Credential check user and API key',
                    ];
                }
            } else { // API not enabled
                $data = [
                    'status' => 'error',
                    'code' => 103,
                    'msg' => 'API not enabled',
                ];
            }
        } else { // no credential
            $data = [
                'status' => 'error',
                'code' => 101,
                'msg' => 'Invalid Credential',
            ];
        }
        echo json_encode($data);
    }
    
    
    
     /**
     * update order odoo by order_identifier
     *
     * @return JSON
     */
    public function updateOrderOdoo()
    {
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (isset($_POST['api_key']) && isset($_POST['api_user'])) { // check if credential is sent
            $auth = $this->apiModel->auth($_POST['api_user'], $_POST['api_key']); // load API settings
            if ($auth['enable']) {
                //validate credential
                if ($auth['authorized']) {
                    $filter = [];
                    isset($_POST['order_identifier']) ? $filter['order_identifier'] =  (int) $_POST['order_identifier']   : '';
                    $orders = false;
                    if (isset($_POST['API_odoo'])) {
                        switch ($_POST['API_odoo']) {
                            case 1:
                                $orders = $this->apiModel->updatetOrdersOdooWithDate($filter, 1, $_POST['start_date'], $_POST['end_date']);
                                break;
                            case 0:
                                $orders = $this->apiModel->updatetOrdersOdooWithDate($filter, 0, $_POST['start_date'], $_POST['end_date']);
                                break;
                            default:
                                break;
                        }
                    }
                    // update orders
                    if ($orders) {
                        $msg = 'Successfully updated ' . $orders . ' record';
                    } else {
                        $msg = 'Not updated there are an error or ther is nothing to update. please try again later';
                    }
                    $data = [
                        'status' => 'success',
                        'code' => 100,
                        'msg' => $msg,
                        'orders' => $orders,
                    ];
                } else { // wrong user or key
                    $data = [
                        'status' => 'error',
                        'code' => 102,
                        'msg' => 'Wrong Credential check user and API key',
                    ];
                }
            } else { // API not enabled
                $data = [
                    'status' => 'error',
                    'code' => 103,
                    'msg' => 'API not enabled',
                ];
            }
        } else { // no credential
            $data = [
                'status' => 'error',
                'code' => 101,
                'msg' => 'Invalid Credential',
            ];
        }
        echo json_encode($data);
    }
}
