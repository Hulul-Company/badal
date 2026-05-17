<?php
class Reviews extends ApiController
{

    public $messaging;

    public function __construct()
    {
        $this->model = $this->model('Review');
    }

    /**
     * list Reviews
     * @return response
     */
    public function list()
    {
        $request = [
            "5" => "راضي تماما",
            "4" => "راضي نوعا ما ",
            "3" => "محايد",
            "2" => "غير راضي نوعا ما",
            "1" => "غير راضي",
        ];
        
        $this->response($request);
    }

    public function add()
    {
        $data = $this->requiredArray(['badal_id', 'rate', 'description']);

        $checkReviewExist = $this->model->checkeview($data);

        if ($checkReviewExist) {
            $this->error('Review is already exist');
        }

        $review = $this->model->addReview($data);

        if ($review == true) {
            $substitute = $this->model->getSubstitute($data['badal_id']);

            if (!$substitute) {
                $this->error("Substitute order data not found");
            }

            // get substitute donor with fcm token
            $notifyData = $this->model->getSubstituteNewWithToken($data['badal_id']);

            if (!$notifyData) {
                $this->error("Substitute donor token not found");
            }

            $messaging = $this->model('Messaging');


            $body = "تم تقييم تنفيذك لطلب {$substitute->projects} بتقييم {$data['rate']} من 5";

            $sendData = [
                'mailto'        => $notifyData->email ?? '',
                'mobile'        => $notifyData->mobile ?? '',
                'identifier'    => $substitute->order_identifier,
                'total'         => $substitute->total,
                'project'       => $substitute->projects,
                'donor'         => $substitute->donor_name,
                'rate'          => $data['rate'],

                'notify_id'     => $notifyData->donor_id,
                'notify'        => "تم تقييم طلبك",
                'type'          => 'review',

                'body'          => $body,
                'msg'           => $body,
            ];

            $messaging->sendNotfication($sendData, 'review');

            $this->response("Review sent successfully");
        } else {
            $this->error("There is a problem .. Please try again");
        }
    }
}