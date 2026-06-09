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

        // check if project exists
        $project = $this->model->getProject($data['project_id']);

        if (!$project) {
            $this->error("Project not found");
        }

        // check if amount is possible
        if ($data['amount'] < $project->min_price) {
            $this->error("الحد الادني : " . $project->min_price . ' ريال ');
        }

        $data['offer_time'] = json_decode($this->model->getSettings('badal')->value)->offer_time;

        $checkexist = $this->model->checkPossibleTime($data);

        if (count($checkexist)) {
            $this->error("لا يمكن اضافه العرض في هذا الوقت ,  يجب ان يكون مضي " .  $data['offer_time'] .  " ساعات علي اخر العرض ");
        }

        $offer = $this->model->addOffer($data);

        if (!$offer) {
            $this->error("There is a problem .. Please try again");
        }

        // get offer data
        $offerData = $this->model->getOfferByIdWithRelationsNotiftAdd($offer);

        if (!$offerData) {
            $this->error("Offer data not found");
        }

        // get order donor with fcm token
        $order = $this->model->getOrderByProjectIDWithToken($data['project_id']);

        if (!$order) {
            $this->error("Order donor token not found");
        }

        $messaging = $this->model('Messaging');

        $internalStartTimestamp = (int) $offerData->start_at;
        $externalStartTimestamp = $internalStartTimestamp + (3 * 60 * 60);
        $offerStartText = date('Y/m/d | h:i a', $internalStartTimestamp);

        $body = "لديك عرض جديد من {$offerData->full_name} على طلب {$offerData->project_name} بقيمة {$offerData->amount} في موعد {$offerStartText}";

        $sendData = [
            'mailto'            => $order->email ?? '',
            'mobile'            => $order->mobile ?? '',

            'identifier'        => $order->order_identifier,

            'total'             => $offerData->amount,
            'project'           => $offerData->project_name,
            'donor'             => $order->behafeof ?? $order->donor_name,
            'substitute_name'   => $offerData->full_name,

            'substitute_start'      => $externalStartTimestamp,
            'app_substitute_start'  => $internalStartTimestamp,

            'notify_id'         => $order->donor_id,
            'notify'            => "تم إضافة عرض جديد على طلبكم",
            'type'              => 'newOffer',

            'body'              => $body,
            'msg'               => $body,
        ];

        $messaging->sendNotfication($sendData, 'newOffer');

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
        if ($offer == null) $this->error("Offer not found");
        // if($offer->status == 0)$this->error("Already canceled");
        $offer = $this->model->cancelOffer($offer_id);
        if ($offer == true) $this->response("Offer cancel successfully");
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