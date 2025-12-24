<?php


class Notifications extends ApiController
{
    public $model;

    public function __construct()
    {
        $this->model = $this->model('Notification');
    }

    public function list()
    {
        $donor_id = $this->required('donor_id');

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 5;

        if ($page < 1) $page = 1;
        if ($per_page < 1) $per_page = 5;

        $offset = ($page - 1) * $per_page;

        $notfications = $this->model->getNotficationsPaginate($donor_id, $offset, $per_page);

        $totalRecordsObj = $this->model->getCountNotfication($donor_id);
        $totalRecords = $totalRecordsObj ? (int) $totalRecordsObj->count : 0;

        $totalPages = $per_page > 0 ? (int) ceil($totalRecords / $per_page) : 0;

        $response = [
            'data' => $notfications,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_more' => ($page < $totalPages)
            ],
        ];

        $this->response($response);
    }


    public function listPaginate(){
        $donor_id = $this->required('donor_id');

        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? $_GET['per_page'] : 5;
        $offset = ($page - 1) * $per_page;

        $notfications = $this->model->getNotficationsPaginate($donor_id, $offset, $per_page);
        $totalRecords = @$this->model->getCountNotfication($donor_id)->count;
        $totalPages = ceil($totalRecords / (int)$per_page);
        $response = [
            'data' => $notfications,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
            ],
        ];
        $this->response($response);
    }
    
    public function read(){
        $donor_id = $this->required('donor_id');
        $notfications = $this->model->readNotfications($donor_id);
        $this->response($notfications);
    }
    
    
     public function readNotfication(){
        $donor_id = $this->required('notfication_id');
        $notfications = $this->model->readNotficationByID($donor_id);
        $this->response($notfications);
    }
    
   
      public function setting(){
        $donor_id = $this->required('donor_id');
        $unread = $this->model->unreadNotfication($donor_id);
        $notfications = [
            'unread' =>  count($unread),
        ];
        $this->response($notfications);
    }
    
    
}