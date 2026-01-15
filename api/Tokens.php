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

        $data = [
            'title'    => $title,
            'body'     => $body,
            'donor_id' => $_POST['donor_id']
        ];

        $jsonData = json_encode($data);


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => NOTIFICATION_DOMAIN . '/api/sendPush',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: PHPSESSID=d27f437f16b9e581731a8a46da6e1832'
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $this->response($data);
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
