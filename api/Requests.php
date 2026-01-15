<?php
class Requests extends ApiController
{

    private $messaging, $badalOrder;

    public function __construct()
    {
        $this->model = $this->model('Request');
        $this->badalOrder = $this->model('Badalorder');
        $this->messaging = $this->model('Messaging');
    }

    /**
     * Subsitude requests
     * @return response
     */
    public function myRequests()
    {
        $subsitute_id = $this->required('subsitute_id');
        $requests = $this->model->getRequestBysubsituteID($subsitute_id, 30);
        foreach ($requests as $key => $req) {
            if ($req->status == 1 && $req->is_selected == 1) {
                $requests[$key]->status = "مقبول";
            } elseif ($req->status == 1 && $req->is_selected == 0 && $req->badal_selected == null) {
                $requests[$key]->status = "في الانتظار";
            } elseif ($req->status == 1 && $req->is_selected == 0 && $req->badal_selected != null) {
                $requests[$key]->status = "مرفوض";
            } elseif ($req->status == 0) {
                $requests[$key]->status = "تم الالغاء";
            }
        }
        $this->response($requests);
    }


    /**
     * list new request
     * @return response
     */
    public function list()
    {
        $badal_id = $this->required('badal_id');
        $badal = $this->badalOrder->getBadalById($badal_id);
        if (@$badal->substitute_id != null) $this->response('لقد تم اختيار المتقدمين مسبقًا');
        // if($badal->substitute_id != null) $this->response('The applicants have been pre-selected');
        $request = $this->model->getRequestByBadalId($badal_id);
        $response = [];
        foreach ($request as $req) {
            $req->start_at =  date('Y-m-d | h:i a', $req->start_at);
            $response[] = $req;
        }

        if (count($response) == 1 && $response[0]->is_selected == 1) {
            $this->error("subsitutes already selected");
        }
        $this->response($request);
    }

    /**
     * add new request
     *@param integar $badal_id
     *@param integar $substitute_id
     *@param integar $start_at
     * @return response
     */
    public function add()
    {
        $data = $this->requiredArray(['badal_id', 'substitute_id', 'start_at']);
        $order = $this->model->getOrderByBadalID($data['badal_id']); // get orderb badal id
        if (!$order) {
            $this->error("Order not found");
        }

        $validDateRequest = $this->model->checkValidDateRequest($data['badal_id']); //get start day and last day


        if ((strtotime($data['start_at']) < $validDateRequest->start_date) || (strtotime($data['start_at']) > $validDateRequest->end_date)) {
            $this->error(" يجب تقديم الطلب في الموعد من " . date("Y-m-d", $validDateRequest->start_date) . " الي " . date("Y-m-d", $validDateRequest->end_date));
        }
        $lastrequests = $this->model->getLastsRequestOfSubstitute($data['badal_id'], $data['substitute_id']); // get the last request of subsitute
        if (count($lastrequests) > 0) {
            $this->error("لقد قمت بالتسجيل من قبل");
        }
        $checkSameDate = $this->model->checkAcceptSameDate($data); // get the last request of subsitute
        if (count($checkSameDate) > 0) {
            $this->error("يوجد لديك عمره ف هذا اليوم");
        }
        $request = $this->model->addBadalRequest($data); // add request 
        $substitute = $this->model->getrequestByIdWithSubstitute($request); //get Selected substitute
        if ($substitute->status != 1) {
            $this->error("الحساب متوقف عن الخدمة");
        }
        // send messages  (email - sms - whatsapp)
        if ($request == true) {
            $sendData = [
                'mailto'                => $order->email,
                'mobile'                => $order->mobile,
                'identifier'            => $order->order_identifier,
                'total'                 => $order->total,
                'project'               => $order->projects,
                'donor'                 => $order->behafeof,
                'substitute_name'       => $substitute->full_name,
                'substitute_start'      => $substitute->start_at,
                'notify_id'             => $order->donor_id,
                'notify'                => "يرغب في تنفيذ طلبكم",
                'type'                  => 'newRequest',
            ];

            $this->messaging->sendNotfication($sendData, 'newRequest');


            $this->response("Request added successfully");
        } else {
            $this->error("There is a problem .. Please try again");
        }
    }


    /**
     * select request
     *@param integar $request_id
     * @return response
     */
    public function select()
    {
        $request_id = $this->required('request_id');
        // get request  
        $request = $this->model->getRequestById($request_id);
        if ($request == null) $this->error("request not found");
        // if ($request->is_selected == 1) $this->response($request, 200, 'Already selected');
        else {
            // select request
            $updateRequest = $this->model->selectRequest($request_id);
            // update BadalOrder Substiute_id
            $updateRequest = $this->model->updateBadalOrder($request);
            if (!$updateRequest) {
                $this->error("There is a problem .. Please try again");
            }
            // delete all request with same day 
            $deleteRequest = $this->model->deleteSameDateRequest($request);

            $order = $this->model->getOrderByBadalID($request->badal_id); // get orderb badal id
            // get subsitude with its  donor id
            $substitute = $this->model->getSubstituteByID($request->substitute_id); //get Selected substitute
            // send messages  (email - sms - whatsapp)
            $sendData = [
                'mailto'            => $substitute->email,
                'mobile'            => $substitute->phone,
                'identifier'        => $order->order_identifier,
                'total'             => $order->total,
                'project'           => $order->projects,
                'donor'             => $order->donor_name,
                'substitute_name'   => $substitute->full_name,
                'behafeof'          => $order->behafeof,
                'notify_id'          => $substitute->subsitude_donor_id,
                'notify'            => "لقد تم اختيارك",
            ];
            $this->messaging->sendNotfication($sendData, 'selectRequest');

            $this->updateQueueNotify($order);

            if ($request == true) $this->response($request, 200, 'selected Sucessfully');
            else {
                $this->error("There is a problem .. Please try again");
            }
        }
    }

    /**
     * cancel request
     *@param integar $request_id
     * @return response
     */
    public function cancel()
    {
        $request_id = $this->required('request_id');
        $request = $this->model->getRequestById($request_id); // get request
        if ($request == null) $this->error("Request not found");
        if ($request->status == 0) $this->error("Already canceled");
        if ($request->is_selected == 1) $this->error("Unfortunately, the request cannot be canceled ... The request is approved "); //check if not selected
        else {
            $request = $this->model->cancelRequest($request_id); // cancel badal order
            if ($request) $this->response("Request Canceled successfully");
            else {
                $this->error("There is a problem .. Please try again");
            }
        }
    }

    /**
     * cancel request
     *@param integar $request_id
     * @return response
     */
    public function cancelOrderRequest()
    {
        $request_id = $this->required('request_id');
        $request = $this->model->getRequestById($request_id); // get request
        if ($request == null) $this->error("Request not found");
        if ($request->status == 0) $this->error("Already canceled");

        //  update substuite to null in badal order
        $cancelRequest = $this->model->cancelRequestBadal($request->badal_id);
        if (!$cancelRequest) {
            $this->error("There is a problem .. while cancel Request");
        }
        //  cancel request of badal
        $cancelOrder = $this->model->updateCancelRequest($request->badal_id);
        if (!$cancelOrder) {
            $this->error("There is a problem .. while cancel Request");
        }
        // get all subsitutes to send (sms - whatsapp - email)
        $order = $this->model->getOrderByBadalID($request->badal_id); // get get badal by id
        $messaging = $this->model('Messaging');
        $subsitues =  $this->model->getAllSubstitutesByDonors();

        if ($subsitues) {
            foreach ($subsitues as $subsitue) {
                $subsitueData = [
                    'mailto' => $subsitue->email,
                    'mobile' => $subsitue->phone,
                    'identifier' => $order->order_identifier,
                    'total' => $order->total,
                    'project' => $order->projects,
                    'donor' => $subsitue->full_name,
                    'subject' => 'تم تسجيل طلب جديد ',
                    'msg' => "تم تسجيل طلب جديد بمشروع : {$order->projects} <br/> بقيمة : " . $order->total,
                    'notify_id' => @$subsitue->donor_id,
                    'notify' => "تم تسجيل طلب جديد بمشروع : {$order->projects} <br/> بقيمة : " . $order->total,
                ];
                $messaging->sendNotfication($subsitueData, 'newOrder');
            }
        }
        $this->response("Request of Order Cancel sucessfully");
    }

    // /**
    //  * cancel request of order
    //  *@param integar $order_id
    //  * @return response
    //  */
    // public function cancelRequestOfOrder()
    // {
    //     $badal_id = $this->required('badal_id');
    //     // get get request by badal  
    //     $request = $this->model->getSelectedRequestByOrderId($badal_id);
    //     if ($request->start_at >  time()) {
    //         $this->error("Unfortunately, this request cannot be canceled because time does not pass");
    //     }
    //     //  update substuite to null in badal order
    //     $cancelRequest = $this->model->cancelRequestBadal($badal_id);
    //     if (!$cancelRequest) {
    //         $this->error("There is a problem .. while cancel Request");
    //     }
    //     //  cancel request of badal
    //     $cancelOrder = $this->model->updateCancelRequest($badal_id);
    //     if (!$cancelOrder) {
    //         $this->error("There is a problem .. while cancel Request");
    //     }
    //     // get all subsitutes to send (sms - whatsapp - email)
    //     $order = $this->model->getOrderByBadalID($badal_id); // get get badal by id
    //     $messaging = $this->model('Messaging');
    //     $subsitues =  $this->model->getAllSubstitutes();
    //     if ($subsitues) {
    //         foreach ($subsitues as $subsitue) {
    //             $subsitueData = [
    //                 'mailto' => $subsitue->email,
    //                 'mobile' => $subsitue->phone,
    //                 'identifier' => $order->order_identifier,
    //                 'total' => $order->total,
    //                 'project' => $order->projects,
    //                 'donor' => $subsitue->full_name,
    //                 'subject' => 'تم تسجيل طلب جديد ',
    //                 'msg' => "تم تسجيل طلب جديد بمشروع : {$order->projects} <br/> بقيمة : " . $order->total,

    //             ];
    //             $messaging->sendNotfication($subsitueData, 'newOrder');
    //         }
    //     }
    //     $this->response("Request of Order Cancel sucessfully");
    // }



    //corn job ----------------------------------------

    /**
     * cancel request of order
     *@param integar $order_id
     * @return response
     */
    public function cancelLateRequests()
    {
        // grt all badal pending selected Order 
        $Badalorders = $this->badalOrder->getBadalOrders();
        if ($Badalorders) {
            foreach ($Badalorders as $Badalorder) {
                $badal_id = $Badalorder->badal_id;
                // get get badal by id
                $badal = $this->badalOrder->getBadalOrderById($badal_id);
                // get get request by badal  
                $request = $this->model->getSelectedRequestByOrderId($badal_id);
                if (!$request) {
                    continue;
                    // $this->error("There is a problem .. Please try again");
                }
                // get max dealy hours 
                $timeLateSetting = json_decode($this->model->getLateTimeSetting()->value)->late_time;
                // get current  dealy hours 
                $currentdelay = (strtotime(date("Y-m-d H:i:s")) -  @$request->start_at) / 3600;
                if ($currentdelay < $timeLateSetting) {
                    continue;
                    // $this->error("Unfortunately, this request cannot be canceled because time does not pass");
                }
                //  update substuite to null in badal order
                $cancelRequest = $this->model->cancelRequestBadal($badal_id);
                if (!$cancelRequest) {
                    continue;
                    // $this->error("There is a problem .. while cancel Request");
                }
                //  cancel request of badal
                $cancelOrder = $this->model->updateCancelRequest($badal_id);
                if (!$cancelOrder) {
                    continue;
                    // $this->error("There is a problem .. while cancel Request");
                }
                // send messages  (email - sms - whatsapp)
                $order = $this->model->getOrder($badal->order_id); // get order
                $substitute = $this->model->getSubstituteByID($request->substitute_id); //get Selected substitute 
                // send messages  (email - sms - whatsapp)
                $sendData = [
                    'mailto'        => $substitute->email,
                    'mobile'        => $substitute->phone,
                    'identifier'    => $order->order_identifier,
                    'total'         => $order->total,
                    'project'       => $order->projects,
                    'donor'         => $substitute->full_name,
                    'notify_id'     => $substitute->subsitude_donor_id,
                    'notify'        => "تم الغاء الطلب ",
                ];
                // send messages
                $this->messaging->sendNotfication($sendData, 'lateRequest');
            }
            $this->response("Request for late Orders Canceled sucessfully");
        }
        $this->response("No Request Late");
    }

    public function updateQueueNotify($order)
    {
        $pendingOrders = $this->badalOrder->getBadalOrderPending();
        if (empty($pendingOrders)) {
            require_once APPROOT . '/admin/models/QueueTable.php';
            $queueTable = new QueueTable();
            $queueTable->updateStatus($order->order_id, 0);
        }
    }
}
