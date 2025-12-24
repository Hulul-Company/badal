<?php


class Substitutes extends ApiController
{
    public $model;
    public $messaging;

    public function __construct()
    {
        $this->model = $this->model('Substitute');
        $this->messaging = $this->model('Messaging');

    }



    /**
     * get all Substitutes
     *@param integar $id
     *
     * @return response Data Substitutes
     */
    public function list()
    {
        $substitutes =  $this->model->getSubstitutes();
        if($substitutes != null){
            $this->response($substitutes);
        }
        else{
            $this->error('Not found');
        }
    }

    /**
     * select Substitute all Substitutes
     *@param integar $id
     *
     * @return response Data Substitutes
     */
    public function selectSubstitute()
    {
        $data = $this->requiredArray(['substitute_id', 'badal_id']);
        $substitutes =  $this->model->selectSubstitutes($data);
        if($substitutes){
            $this->response("The agent has been selected successfully");
        }
        else{
            $this->error('Please try again');
        }
    }



    //  cron jobs --------
    /**
     * sen notify to all Substitutes which has order today
     *@param integar $id
     *
     * @return response Data Substitutes
     */
    public function substituteNotify()
    {
        $substitutes =  $this->model->gettSubstitutesHasOrderToday();
        if($substitutes){
            foreach($substitutes as $substitute){
                $sendData = [
                    'mailto'            => $substitute->email,
                    'mobile'            => $substitute->phone,
                    'total'             => $substitute->total,
                    'donor'             => $substitute->full_name,
                    'identifier'        => $substitute->order_identifier, 
                    'project'           => $substitute->project_name,
                    'substitute_start'  => $substitute->start_at,
                ];
                // send messages  (email - sms - whatsapp)
                $this->messaging->sendNotfication($sendData, 'notify_order');
            }
        }
        $this->response("The Notify has been send successfully" );
    }

    /**
     * list substitutes with their orders (paginated)
     * GET params: page, per_page
     */
    public function listWithOrders()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        // load substitute model (already in $this->model from __construct)
        $subModel = $this->model; // in your __construct you set $this->model = $this->model('Substitute');
        // or if not, do: $subModel = $this->model('Substitute');

        $res = $subModel->getSubstitutesWithOrders($page, $per_page);

        // prepare meta
        $total = isset($res['total']) ? (int)$res['total'] : 0;
        $pages = $per_page > 0 ? (int)ceil($total / $per_page) : 0;

        $payload = [
            'data' => $res['data'],
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'pages' => $pages
            ]
        ];

        // use your controller response helper
        $this->response($payload);
    }

}