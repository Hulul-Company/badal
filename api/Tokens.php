<?php
class Tokens extends ApiController
{

    public $messaging;


    public function __construct()
    {
        $this->model = $this->model('Token');
    }


    public function send()
    {
        $fields = $this->requiredArray(['title', 'body']);

        $title = $fields['title'];
        $body = $fields['body'];
        $donor_id = isset($_POST['donor_id']) ? $_POST['donor_id'] : null;

        if (!$donor_id) {
            $this->error('donor_id is required');
            return;
        }

        $data = [
            'title'    => $title,
            'body'     => $body,
            'donor_id' => $donor_id
        ];

        $jsonData = json_encode($data);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => NOTIFICATION_DOMAIN . '/api/sendPush',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $jsonData,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        $this->response([
            'success'      => $httpCode === 200,
            'http_code'    => $httpCode,
            'sent_data'    => $data,
            'fcm_response' => json_decode($response, true),
            'curl_error'   => $err ?: null
        ]);
    }


    public function save()
    {
        //validate all data
        $fields = $this->requiredArray(['device_id', 'fcm_token']);

        if ($this->model->getToken($_POST['device_id'])) {

            $data = [
                'device_id' => $_POST['device_id'],
                'fcm_token' => $_POST['fcm_token'],
                'donor_id' => $_POST['donor_id'],
            ];

            //save donor
            $data['token_id'] = $this->model->updateToken($data);
            $this->response($data);
        }
        //prepar data for save
        $data = [
            'device_id' => $_POST['device_id'],
            'fcm_token' => $_POST['fcm_token'],
            'donor_id' => $_POST['donor_id'],
        ];

        //save donor
        $data['token_id'] = $this->model->addToken($data);
        
        $this->response($data);
    }

    // public function save()
    // {
    //     //validate all data
    //     $fields = $this->requiredArray(['device_id', 'fcm_token']);

    //     if ($this->model->getToken($_POST['device_id'])) {

    //         $data = [
    //             'device_id' => $_POST['device_id'],
    //             'fcm_token' => $_POST['fcm_token'],
    //             'donor_id' => $_POST['donor_id'],
    //         ];

    //         //save donor
    //         $data['token_id'] = $this->model->updateToken($data);
    //         $this->response($data);
    //     }
    //     //prepar data for save
    //     $data = [
    //         'device_id' => $_POST['device_id'],
    //         'fcm_token' => $_POST['fcm_token'],
    //         'donor_id' => $_POST['donor_id'],
    //     ];

    //     //save donor
    //     $data['token_id'] = $this->model->addToken($data);


    //     $this->response($data);
    // }
}
