<?php


class Offers extends ApiController
{
    public $model;

    public function __construct()
    {
        $this->model = $this->model('Offer');
    }

    /**
     * add new offer
     *@param integar $substitute_id
     *@param integar $project_id
     *@param integar $amount
     *@param integar $start_at
     * @return response
     */
    public function add()
    {
        $data = $this->requiredArray(['substitute_id', 'project_id', 'amount', 'start_at']);
        // check if amount is possible
        $project = $this->model->getProject($data['project_id']);
        if( $data['amount'] < $project->min_price ){
            $this->error("الحد الادني : " . $project->min_price . ' ريال '); 
        }
        $data['offer_time'] = json_decode($this->model->getSettings('badal')->value)->offer_time;
        $checkexist = $this->model->checkPossibleTime($data);
        if(count($checkexist)){
            $this->error("لا يمكن اضافه العرض في هذا الوقت ,  يجب ان يكون مضي " .  $data['offer_time'] .  " ساعات علي اخر العرض ");
        }
        $offer = $this->model->addOffer($data);
        if(!$offer) $this->error("There is a problem .. Please try again"); 
        // send messages  (email - sms - whatsapp)
        // $offerData = $this->model->getOfferByIdWithRelationsNotiftAdd($offer); // get offer
         
        // $donors = $this->model->getAllDonors();   // get all donors to notify
        // $messaging = $this->model('Messaging');
        // foreach($donors as $donor){
        //     $sendData = [
        //         'mailto'            => $donor->email,
        //         'mobile'            => $donor->mobile,
        //         'total'             => $offerData->amount,
        //         'donor'             => $donor->full_name,
        //         'identifier'        => $offerData->project_name, //name of project 
        //         'project'           => $offerData->project_name,
        //         'substitute_name'   => $offerData->full_name,
        //         'substitute_start'  => $offerData->start_at,
           
        //     ];
        //     $messaging->sendNotfication($sendData, 'newOffer');
        // }
        $this->response("Offer sent successfully");
    }

    /**
     * cancel  offer from substitutes
     *@param integar $substitute_id
     *@param integar $project_id
     *@param integar $amount
     *@param integar $start_at
     * @return response
     */
    public function cancel()
    {
        $offer_id = $this->required('offer_id');
        $offer = $this->model->getOfferById($offer_id); // get offer
        if($offer == null)$this->error("Offer not found");
        // if($offer->status == 0)$this->error("Already canceled");
        $offer = $this->model->cancelOffer($offer_id);
        if($offer == true) $this->response("Offer cancel successfully");
        else {
            $this->error("There is a problem .. Please try again");
        }
    }

    
    /**
     * list offers by substitute
     *@param integar $substitute_id
     * @return response
     */
    public function substitute()
    {
        // required param
        $data = $this->requiredArray(['substitute_id']);
        $substitute_id = (int) $data['substitute_id'];

        // pagination params (from GET to match your example)
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 5;

        // validation and safety
        if ($page < 1) $page = 1;
        if ($per_page < 1 || $per_page > 200) $per_page = 5;

        $offset = ($page - 1) * $per_page;

        // call model paginated method and count
        $rows = $this->model->getOffersBySubstitutePaginated($substitute_id, $offset, $per_page);
        $totalRecordsRow = $this->model->getOffersBySubstituteCount($substitute_id);
        $totalRecords = $totalRecordsRow ? (int)$totalRecordsRow->total : 0;

        // build meta
        $totalPages = $per_page ? (int) ceil($totalRecords / $per_page) : 0;

        $response = [
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_more' => ($offset + $per_page) < $totalRecords,
            ],
        ];

        $this->response($response);
    }


    /**
     * list offers 
     * @return response
     */
    public function list()
    {
        // pagination params
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;

        if ($page < 1) $page = 1;
        if ($per_page < 1 || $per_page > 200) $per_page = 10;

        $offset = ($page - 1) * $per_page;

        // get data
        $offers = $this->model->getOffersPaginated($offset, $per_page);
        $totalRow = $this->model->getOffersCount();
        $totalRecords = $totalRow ? (int) $totalRow->total : 0;

        $totalPages = ceil($totalRecords / $per_page);

        $response = [
            'data' => $offers,
            'meta' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_more' => ($offset + $per_page) < $totalRecords,
            ],
        ];

        $this->response($response);
    }
}