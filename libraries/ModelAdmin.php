<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class ModelAdmin
{
    protected $db;
    protected $table;
    /**
     * calling database object and setting table name
     */
    public function __construct($table)
    {
        $this->table = $table;
        $this->db = new Database;
        $this->log();
    }

    public function log()
    {
        include_once APPROOT . '/helpers/Log.php';

        $log = new Helpers\Log;
        if ($log->action && $log->user) {
            $query = "INSERT INTO logs SET 
            user_id = '{$log->user->user_id}'
            , user_name ='{$log->user->name}'
            , action ='$log->action'
            , records = '$log->records'
            , model = '$log->model'
            , url = '" . implode('/', $log->url) . "'
            , create_date  =" . time();
            $this->db->query($query);
            $this->db->excute();
        };
    }
    /**
     * Delete one or more records by id
     * @param Array $ids
     * @param string colomn id
     * @return boolean or row count
     */
    public function deleteById($ids, $where)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($ids); $index++) {
            $id_num[] = ":id" . $index;
        }
        //setting the query
        $this->db->query('UPDATE ' . $this->table . ' SET status = 2 WHERE ' . $where . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        foreach ($ids as $key => $id) {
            $this->db->bind(':id' . ($key + 1), $id);
        }
        if ($this->db->excute()) {
            return $this->db->rowCount();
        } else {
            return false;
        }
    }

    /**
     * publish one or more records by id
     * @param Array $ids
     * @param string colomn id
     * @return boolean or row count
     */
    public function publishById($ids, $where)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($ids); $index++) {
            $id_num[] = ":id" . $index;
        }
        //setting the query
        $this->db->query('UPDATE ' . $this->table . ' SET status = 1 WHERE ' . $where . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        foreach ($ids as $key => $id) {
            $this->db->bind(':id' . ($key + 1), $id);
        }
        if ($this->db->excute()) {
            return $this->db->rowCount();
        } else {
            return false;
        }
    }

    /**
     * unpublish one or more records by id
     * @param Array $ids
     * @param string colomn id
     * @return boolean or row count
     */
    public function unpublishById($ids, $where)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($ids); $index++) {
            $id_num[] = ":id" . $index;
        }
        //setting the query
        $this->db->query('UPDATE ' . $this->table . ' SET status = 0 WHERE ' . $where . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        foreach ($ids as $key => $id) {
            $this->db->bind(':id' . ($key + 1), $id);
        }
        if ($this->db->excute()) {
            return $this->db->rowCount();
        } else {
            return false;
        }
    }

    /**
     * featured one or more records by id
     * @param Array $ids
     * @param string colomn id
     * @return boolean or row count
     */
    public function featuredById($ids, $where)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($ids); $index++) {
            $id_num[] = ":id" . $index;
        }
        //setting the query
        $this->db->query('UPDATE ' . $this->table . ' SET featured = 1 WHERE ' . $where . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        foreach ($ids as $key => $id) {
            $this->db->bind(':id' . ($key + 1), $id);
        }
        if ($this->db->excute()) {
            return $this->db->rowCount();
        } else {
            return false;
        }
    }

    /**
     * unfeatured one or more records by id
     * @param Array $ids
     * @param string colomn id
     * @return boolean or row count
     */
    public function unfeaturedById($ids, $where)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($ids); $index++) {
            $id_num[] = ":id" . $index;
        }
        //setting the query
        $this->db->query('UPDATE ' . $this->table . ' SET featured = 0 WHERE ' . $where . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        foreach ($ids as $key => $id) {
            $this->db->bind(':id' . ($key + 1), $id);
        }
        if ($this->db->excute()) {
            return $this->db->rowCount();
        } else {
            return false;
        }
    }

    /**
     * get By Id
     *
     * @param  string $id
     * @param  string $where
     *
     * @return void
     */
    public function getById($id, $where, $cod = null)
    {
        $this->db->query('SELECT * FROM `' . $this->table . '` WHERE ' . $where . '= :' . $where . $cod);
        $this->db->bind(':' . $where, $id);
        $row = $this->db->single();
        return $row;
    }

    /**
     * get WHERE In
     *
     * @param  array $in values
     * @param  string $colomn
     *
     * @return void
     */
    public function getWhereIn($colomn, $in)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($in); $index++) {
            $id_num[] = ":in" . $index;
        }
        //setting the query
        $this->db->query('SELECT * FROM  ' . $this->table . '  WHERE ' . $colomn . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        foreach ($in as $key => $value) {
            $this->db->bind(':in' . ($key + 1), $value);
        }
        if ($this->db->excute()) {
            return $this->db->resultSet();
        } else {
            return false;
        }
    }

    /**
     * Set multiable value 
     *
     * @param string $target
     * @param string $value
     * @param string $colomn
     * @param array $in
     * @return void
     */
    public function setWhereIn($target, $value, $colomn, $in)
    {
        //get the id in PDO form @Example :id1,id2
        for ($index = 1; $index <= count($in); $index++) {
            $id_num[] = ":in" . $index;
        }
        //setting the query
        $this->db->query('UPDATE ' . $this->table . ' SET ' . $target . ' = :target WHERE ' . $colomn . ' IN (' . implode(',', $id_num) . ')');
        //loop through the bind function to bind all the IDs
        $this->db->bind(':target', $value);
        foreach ($in as $key => $val) {
            $this->db->bind(':in' . ($key + 1), $val);
        }
        if ($this->db->excute()) {
            return $this->db->rowCount();
        } else {
            return false;
        }
    }
    /**
     * searchHandling
     *
     * @param  array $searchColomns ['name','status']
     * @return array $cond $bind
     */
    public function searchHandling($searchColomns, $current = '')
    {
        // if user make a search
        if (isset($_POST['search'])) {
            // return to first
            $current = 1;
            return $this->handlingSearchCondition($searchColomns);
        } else {
            // if user didn't search
            // look for pagenation if not clear seassion
            if (empty($current)) {
                unset($_SESSION['search']);
                // if there is pagenation and value stored into session get it and prepare Condition and bind
            } else {
                return $this->handlingSearchSessionCondition($searchColomns);
            }
        }
        return ['cond' => '', 'bind' => ''];
    }

    /**
     * handling Search Condition, creating bind array and handling search session
     *
     * @param  array $searches
     * @return array of condation and bind array
     */
    public function handlingSearchCondition($searches)
    {
        //reset search session
        unset($_SESSION['search']);
        $cond = '';
        $bind = [];
        if (!empty($searches)) {
            foreach ($searches as $keyword) {
                $cond .= ' AND ' . $this->table . '.' . $keyword . ' LIKE :' . $keyword . ' ';
                $bind[':' . $keyword] = $_POST['search'][$keyword];
                $_SESSION['search'][$keyword] = $_POST['search'][$keyword];
            }
        }
        return $data = ['cond' => $cond, 'bind' => $bind];
    }

    /**
     * handling Search Condition on the stored session, creating bind array and handling search session
     *
     * @param  array $searches
     * @return array of condation and bind array
     */
    public function handlingSearchSessionCondition($searches)
    {
        $cond = '';
        $bind = [];
        foreach ($searches as $keyword) {
            if (isset($_SESSION['search'][$keyword])) {
                $cond .= ' AND ' . $this->table . '.' . $keyword . ' LIKE :' . $keyword;
                $bind[':' . $keyword] = $_SESSION['search'][$keyword];
            }
        }
        return $data = ['cond' => $cond, 'bind' => $bind];
    }
    /**
     * getAll data from database
     *
     * @param  string $cond
     * @param  array $bind
     * @param  string $limit
     * @param  array $bindLimit
     *
     * @return Object
     */
    public function getAll($query, $bind = '', $limit = '', $bindLimit = '')
    {
        $this->db->query($query . $limit);
        if (!empty($bind)) {
            foreach ($bind as $key => $value) {
                $this->db->bind($key, '%' . $value . '%');
            }
        }
        if (!empty($bindLimit)) {
            foreach ($bindLimit as $key => $value) {
                $this->db->bind($key, $value);
            }
        }
        return $this->db->resultSet();
    }

    /**
     * get count of all records
     * @param string $cond
     * @return array $bind
     */
    public function countAll($cond = '', $bind = '', $table = NULL)
    {
        if (!$table) $table = $this->table;
        $this->db->query('SELECT count(*) as count FROM ' . $table . ' ' . $cond);
        if (!empty($bind)) {
            foreach ($bind as $key => $value) {
                $this->db->bind($key, '%' . $value . '%');
            }
        }
        $this->db->excute();
        return $this->db->single();
    }

    /**
     * clear HTML string with html purifier
     * @param type $stringHTML
     * @return string HTML
     */
    public function cleanHTML($stringHTML)
    {
        if (!empty($stringHTML)) {
            require_once '../helpers/htmlpurifier/HTMLPurifier.auto.php';
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^(https?:)?(\/\/www\.youtube(?:-nocookie)?\.com\/embed\/|\/\/player\.vimeo\.com\/)%');
            $purifier = new HTMLPurifier($config);
            return $purifier->purify($stringHTML);
        } else {
            return null;
        }
    }

    /**
     * export sql result array to excel sheet
     *
     * @param array $result
     * @param string $fileName
     * @param string $title
     * @return void
     */
    public function exportToExcel($result, $fileName = 'report', $title = "التقرير")
    {
        if (!empty($result)) {
            require_once '../helpers/excel_export.php';
            $excel = new Excel_XML('UTF-8', false, $fileName);
            $excel->addArray($result);
            $excel->generateXML($title);
        } else {
            return null;
        }
    }

    /**
     * validateImage
     *
     * @param  string $imageName
     * @return array name or error
     */
    public function validateImage($imageName, $pass =  MEDIAPATH . '/')
    {
        $uploadErrors = array(
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        );
        if ($_FILES[$imageName]['error'] == 0) {
            $image = uploadImage($imageName, $pass, 5000000, true);
            if (empty($image['error'])) {
                return [true, $image['filename']];
            } else {
                if (!isset($image['error']['nofile'])) {
                    return [false, implode(',', $image['error'])];
                }
            }
        } else {
            return [true, $uploadErrors[$_FILES[$imageName]['error']]];
        }
    }
    /**
     * loading setting
     *
     * @param string $settingType
     * @return object
     */
    public function getSettings($settingType = null)
    {
        if ($settingType) {
            return $this->getAll('SELECT * FROM settings WHERE settings.alias = "' . $settingType . '"')[0];
        } else {
            return $this->getAll('SELECT * FROM settings');
        }
    }

    /**
     * sending Html Email
     *
     * @param string $to
     * @param string $subject
     * @param string $msg
     * @param string|boolen $attachment
     * @param boolen $debug
     * @return boolen
     */
    public function Email($to, $subject, $msg, $attachment = false, $debug = false)
    {
        $emailSettings = $this->getSettings('email');
        $email = json_decode($emailSettings->value);
        if ($email->smtp) {
            $to = explode(',', $to);          // load email setting 
            $mail = new PHPMailer(true);                                // load php mailer 
            try {
                //Server settings
                $mail->CharSet = 'UTF-8';
                if ($debug) $mail->SMTPDebug = 4;                                //Enable verbose debug output
                $mail->isSMTP();                                        //Send using SMTP
                $mail->Host       = $email->host;                       //Set the SMTP server to send through
                $mail->SMTPAuth   = true;                               //Enable SMTP authentication
                $mail->AuthType = 'LOGIN';
                $mail->Username   = $email->user;                       //SMTP username
                $mail->Password   = $email->password;                   //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;     //Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS encouraged
                $mail->Port       = $email->port;                       //TCP port to connect to, use 465 for PHPMailer::ENCRYPTION_SMTPS above

                //Recipients
                $mail->setFrom($email->sending_email, $email->sending_name);
                foreach ($to as $index => $reciver) {
                    if ($index == 0) {
                        $mail->addAddress($reciver);
                    } else {
                        $mail->addCC($reciver);
                    }
                }
                //Add a recipient
                // $mail->addAddress('ellen@example.com');               //Name is optional
                $mail->addReplyTo($email->sending_email, $email->sending_name);
                // $mail->addCC('cc@example.com');
                // $mail->addBCC('bcc@example.com');

                // //Attachments
                if ($attachment) $mail->addAttachment($attachment);         //Add attachments

                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body    = $msg;

                return $mail->send();
            } catch (Exception $e) {
                if ($debug) echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $separator = md5(time());   // carriage return type (RFC)
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= 'From: ' . $email->sending_name . '<' . $email->sending_email . '>' . "\r\n";
            $headers .= 'Reply-To: ' . $email->sending_email . '' . "\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . "\r\n";
            $headers .= "Content-Transfer-Encoding: 7bit" . "\r\n";
            $headers .= "This is a MIME encoded message." . "\r\n";

            // message
            $body = "--" . $separator . "\r\n";
            $body .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            // $body .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . "\r\n";
            $body .= "Content-Transfer-Encoding: 8bit" . "\r\n";
            $body .= $msg . "\r\n";

            // attachment
            if ($attachment) {
                $filename = basename($attachment);
                $attachment = file_get_contents($attachment);
                $attachment = chunk_split(base64_encode($attachment));  // a random hash will be necessary to send mixed content
                // attachment
                $body .= "--" . $separator . "\r\n";
                $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . "\r\n";
                $body .= "Content-Transfer-Encoding: base64" . "\r\n";
                $body .= "Content-Disposition: attachment" . "\r\n";
                $body .= $attachment . "\r\n";
                $body .= "--" . $separator . "--";
            }
            return mail($to, $subject, $body, $headers); // sending Email

        }
    }

    /**
     * Sending SMS message
     *
     * @param string $to
     * @param string $msg
     * @return void
     */
    public function SMS($to, $msg)
    {
        $smsSettings = $this->getSettings('sms'); // load sms setting 
        $sms = json_decode($smsSettings->value);
        if (!$sms->smsenabled) {
            flash('order_msg', 'هناك خطأ ما بوابة الارسال غير مفعلة', 'alert alert-danger');
            redirect('orders');
        }
        return sendSMS($sms->sms_username, $sms->sms_password, $msg, $to, $sms->sender_name, $sms->gateurl, $sms->gateway);
    }
    
    /**
     * Sending whatsapp message
     *
     * @param string $to
     * @param string $msg
     * @param string $order number
     * @param string $amount
     * @param string $placeholder (link of confirm order)
     * @return response 
     */
    public function ReciveOrdersApp($to, $name, $order, $amount, $placeholder)
    {
        $whatsAppSettings = $this->getSettings('whatsapp'); // load whatsapp setting 
        $whatsAppSettings = json_decode($whatsAppSettings->value);
        if (!$whatsAppSettings->whatsappenabled) {
            return false;
        }
        $parameters = [
            ["name" => "name", "value" => $name],
            ["name" => "order", "value" => $order],
            ["name" => "amount", "value" => $amount],
            ["name" => "placeholder", "value" => $placeholder]
        ];
        return sendWhatsAppParameter($whatsAppSettings->gateurl, $whatsAppSettings->accessToken, $whatsAppSettings->template_name_receive_order, $whatsAppSettings->broadcast_name_receive_order, $to, $parameters);
    }

    /**
     * Sending whatsapp message
     *
     * @param string $to
     * @param string $msg
     * @param string $order number
     * @param string $amount
     * @return Response
     */
    public function ConfirmedOrdersApp($to, $name, $order)//, $amount, $project)
    {

        $whatsAppSettings = $this->getSettings('whatsapp'); // load whatsapp setting 
        $whatsAppSettings = json_decode($whatsAppSettings->value);
        if (!$whatsAppSettings->whatsappenabled) {
            return false;
        }
       
        $parameters = [
            ["name" => "name", "value" => $name],
            ["name" => "order", "value" => $order->identifier],
            ["name" => "amount", "value" => $order->total],
            //["name" => "project", "value" => $order->projects??""],
            ["name" => "project", "value" => $order->project??""],
            ["name" => "link", "value" => URLROOT .'/invoices/show/' . orderIdentifier($order->order_id)??""],
        ];
        return sendWhatsAppParameter($whatsAppSettings->gateurl, $whatsAppSettings->accessToken, $whatsAppSettings->template_name_confirm_order, $whatsAppSettings->broadcast_name_confirm_order, $to, $parameters);
    }

    /**
     * Sending whatsapp message
     *
     * @param string $to
     * @param string $msg
     * @param string $order number
     * @param string $amount
     * @param string $name whatsapp
     * @param string $type settings
     * @return Response
     */
    public function NotficationWhatssApp($to, $name, $order, $amount, $type, $otherdata = [])
    {
        $whatsAppSettings = $this->getSettings('whatsapp'); // load whatsapp setting 
        $whatsAppSettings = json_decode($whatsAppSettings->value);
        if (!$whatsAppSettings->whatsappenabled) {
            return false;
        }
        $broadcast =  "broadcast_name_" . $type;
        $template =  "template_name_" . $type;
        if (!$whatsAppSettings->whatsappenabled) {
            return false;
        }

        if (@$whatsAppSettings->$broadcast == null || @$whatsAppSettings->$template == null) {
            return "messing data";
        }

        
        $parameters = [
            ["name" => "name", "value" => $name],
            ["name" => "order", "value" => $order],
            ["name" => "amount", "value" => $amount],
        ];
      
        if(in_array($type , ["new_request", "select_request", "new_offer", "start_order", "notify_order" ,'complete_order', "arrive_order"] ) ){
            $parameters[] = ["name" => "substitute_name", "value" => @$otherdata['substitute_name']];
            $parameters[] = ["name" => "substitute_start", "value" =>  date('Y/ m/ d | H:i a',  @$otherdata['substitute_start'])];
            $parameters[] = ["name" => "identifier", "value" => @$order];
            $parameters[] = ["name" => "projects", "value" => @$otherdata['project']];
            $parameters[] = ["name" => "behafeof", "value" => @$otherdata['behafeof']];
        }
        if(in_array($type , ["review"] ) ){
            $parameters[] = ["name" => "rate", "value" => $otherdata['rate']];
        }
        return sendWhatsAppParameter($whatsAppSettings->gateurl, $whatsAppSettings->accessToken, $whatsAppSettings->$template, $whatsAppSettings->$broadcast , $to, $parameters);
    }

    /**
     * Notfy  whatsapp message
     *
     * @param string $to
     * @param string $name
     * @return Response
     */
    public function NotficationsWhatsApp($to, $data, $type)
    {
        $whatsAppSettings = $this->getSettings('whatsapp'); // load whatsapp setting 
        $whatsAppSettings = json_decode($whatsAppSettings->value);
        if (!$whatsAppSettings->whatsappenabled) {
            return false;
        }
        $broadcast =  "broadcast_name_" . $type;
        $template =  "template_name_" . $type;
        $templateTemp = @$whatsAppSettings->$template;
        $broadcastTemp = @$whatsAppSettings->$broadcast;
        if (!$whatsAppSettings->whatsappenabled) {
            return false;
        }

        $parameters = [];
        if($type == "special_confirm"){
            $specialSettings = $this->getSettings('specialMessages'); // load whatsapp setting 
            $specialSettings = json_decode($specialSettings->value);
            if (!$specialSettings->specialenabled) {
                return false;
            }
            $templateTemp = $specialSettings->$template;
            $broadcastTemp = $specialSettings->$broadcast;
            $parameters = [
                ["name" => "name", "value" =>  $data['donor']],
                ["name" => "order", "value" =>  $data['identifier']],
                ["name" => "amount", "value" =>  $data['total']],
                ["name" => "projects", "value" =>  $data['projects']],
                ["name" => "link", "value" =>  $data['link']],
            ];
        }
        if($type == "gift_confirm"){
            $store_url = "";
            if($_SESSION['store']){
                $store_url = "stores/" . $_SESSION['store']->alias;
            }
            $parameters = [
                ["name" => "name", "value" => $data['from_name']],
                ["name" => "donor", "value" => $data['donor_name']],
                ["name" => "imageLink", "value" => $data['card']],
                ["name" => "project", "value" =>  $data['project']],
                ["name" => "store", "value" =>  $store_url ],
                ["name" => "url", "value" =>  $whatsAppSettings->template_url_gift_confirm],
            ];
        }
        return sendWhatsAppParameter($whatsAppSettings->gateurl, $whatsAppSettings->accessToken, $templateTemp, $broadcastTemp , $to, $parameters);
    }


    /**
     * Sending whatsapp message without params
     *
     * @param string $to
     * @param string $msg
     * @param string $order number
     * @param string $amount
     * @return Response
     */
    public function sendWhatsApp($to, $tamplate)//, $amount, $project)
    {

        $whatsAppSettings = $this->getSettings('whatsapp'); // load whatsapp setting 
        $whatsAppSettings = json_decode($whatsAppSettings->value);
       
        return sendWhatsAppParameter($whatsAppSettings->gateurl, $whatsAppSettings->accessToken, $tamplate, $whatsAppSettings->broadcast_name_confirm_order, $to, []);
    }

}

