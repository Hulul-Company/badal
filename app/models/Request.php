<?php

class Request extends Model
{
    public function __construct()
    {
        parent::__construct('requests');
    }

    /**
     * list Request by badal_id 
     * @param Array $id
     */
    public function getRequestByBadalId($badal_id)
    {
        // $query = 'SELECT `requests`.*,   from_unixtime(`requests`.start_at) as start_at,
        $query = 'SELECT `requests`.*, 
                 `substitutes`.full_name, `substitutes`.nationality, `substitutes`.gender,  `substitutes`.languages,
                 CONCAT("' . MEDIAURL . '/../files/substitutes/", `substitutes`.`image` ) AS image,
                (SELECT  round(AVG(`badal_review`.rate) * 2 , 0) / 2
                    FROM `badal_review`, `badal_orders`
                    WHERE `badal_orders`.`substitute_id` = `requests`.`substitute_id`
                    AND `badal_review`.`badal_id` = `badal_orders`.`badal_id`
                ) AS rate
                FROM  `requests` , `substitutes`
                WHERE `requests`.`badal_id` = :badal_id
                AND `requests`.substitute_id =  `substitutes`.substitute_id 
                AND  `requests`.status NOT IN (2, 0)  
                AND  `substitutes`.status = 1 
                ORDER BY `requests`.`create_date` DESC';
        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);
        return $this->db->resultSet();
    }

    /**
     * get Request by id 
     * @param Array $id
     */
    public function getRequestById($id)
    {
        $query = 'SELECT * FROM  `requests`  WHERE `request_id` = ' . $id . '   AND  status NOT IN (2, 0)  ';
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * get order by badal id
     * @param Array $id
     */
    public function getOrderByBadalID($badal_id)
    {
        $query = 'SELECT `badal_orders`.order_id,`badal_orders`.behafeof, `orders`.*, `donors`.mobile, `donors`.full_name, `donors`.email
                  FROM  `badal_orders`, `orders`, `donors`
                  WHERE `badal_orders`.badal_id = :badal_id
                  AND  `badal_orders`.order_id = `orders`.order_id
                  AND  `orders`.donor_id = `donors`.donor_id';
        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);
        return $this->db->single();
    }

    /**
     * get request by id with substitute
     * @param integer $id
     */
    public function getrequestByIdWithSubstitute($request_id)
    {
        $query = ' SELECT `requests`.*, `substitutes`.full_name ,  `substitutes`.status As substitute_status
                   FROM  `requests`, `substitutes`  
                   WHERE `requests`.request_id = :request_id 
                   AND `requests`.substitute_id = `substitutes`.substitute_id';
        $this->db->query($query);
        $this->db->bind(':request_id', $request_id);

        return $this->db->single();
    }

    /**
     * update select Request  by id 
     * @param Array $id
     */
    public function selectRequest($id)
    {
        $query = 'UPDATE `requests` SET  is_selected = 1,  `modified_date` = :modified_date WHERE `request_id`= :request_id';
        $this->db->query($query);
        $this->db->bind(':request_id', $id);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * insert new request
     * @param array $data
     * @return boolean
     */
    public function addBadalRequest($data)
    {
        $this->db->query('INSERT INTO `requests` ( badal_id, substitute_id, start_at, is_selected, status, type, modified_date, create_date)'
            . ' VALUES (:badal_id, :substitute_id, :start_at, :is_selected, :status, :type, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':badal_id', $data['badal_id']);
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $dt = new DateTime($data['start_at'], new DateTimeZone('Asia/Riyadh'));
        $dt->setTimezone(new DateTimeZone('UTC'));

        $this->db->bind(':start_at', $dt->getTimestamp());
        $this->db->bind(':is_selected', 0);
        $this->db->bind(':status', 1);
        $this->db->bind(':type', "badal");
        $this->db->bind(':modified_date', time());
        $this->db->bind(':create_date', time());
        // excute
        if ($this->db->excute()) {
            return $this->db->lastId();
        } else {
            return false;
        }
    }

    /**
     * update badal order by id 
     * @param Array $request
     * @param Array $substitute_id
     */
    public function updateBadalOrder($data)
    {
        $query = 'UPDATE `badal_orders` SET  substitute_id = :substitute_id,  start_at = :start_at,
         `modified_date` = :modified_date WHERE `badal_id`= :badal_id';
        $this->db->query($query);
        $this->db->bind(':badal_id', $data->badal_id);
        $this->db->bind(':substitute_id', $data->substitute_id);
        $this->db->bind(':start_at', null);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * get substitutes by id
     * @param Array $id
     */
    public function getSubstituteByID($id)
    {
        $query = 'SELECT * , (SELECT `donors`.donor_id FROM `donors` WHERE `donors`.mobile = `substitutes`.phone AND `status` = 1 )  AS subsitude_donor_id
        FROM  `substitutes`  WHERE `substitute_id` = ' . $id;
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * others Requests by badal_id 
     * @param Array $id
     */
    public function getOtherRequest($badal_id)
    {
        $query = 'SELECT * FROM  `requests`  WHERE `badal_id` = ' . $badal_id . '  AND is_selected <> 1 AND  status NOT IN (2, 0)  ';
        $this->db->query($query);
        return $this->db->resultSet();
    }

    /**
     * cancel Request by id 
     * @param Array $id
     */
    public function cancelRequest($id)
    {
        $query = 'UPDATE `requests` SET  status = 0,  `modified_date` = :modified_date WHERE `request_id`= :request_id';
        $this->db->query($query);
        $this->db->bind(':request_id', $id);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * get selected request by Order id 
     * @param Array $id
     */
    public function getSelectedRequestByOrderId($badal_id)
    {
        $query = 'SELECT * FROM  `requests`  WHERE `badal_id` = ' . $badal_id . '   AND  status = 1  AND is_selected = 1 ORDER BY `create_date` DESC';
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * get order by id
     * @param Array $id
     */
    public function getOrder($id)
    {
        $query = 'SELECT * FROM  `orders`  WHERE `order_id` = ' . $id;
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * cancel Order Request by order id 
     * @param Array $id
     */
    public function updateCancelRequest($badal_id)
    {
        $query = 'UPDATE `requests` SET  `status` = 0, `is_selected` = 0 ,`modified_date` = :modified_date  WHERE `badal_id` = :badal_id AND `is_selected` = 1 ';
        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * cancel Order Request by order id 
     * @param Array $id
     */
    public function cancelRequestBadal($badal_id)
    {
        $query = 'UPDATE `badal_orders` SET  substitute_id = NULL,  start_at = NULL,  `modified_date` = :modified_date WHERE `badal_id`= :badal_id';
        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }
    public function getOrderByBadalIDWithToken($badal_id)
    {
        $query = "
        SELECT 
            badal_orders.badal_id,
            badal_orders.order_id,
            badal_orders.behafeof,

            orders.order_identifier,
            orders.total,
            orders.projects,
            orders.donor_name,

            donors.donor_id,
            donors.email,
            donors.mobile,
            donors.full_name,

            fcm_tokens.fcm_token

        FROM badal_orders
        INNER JOIN orders 
            ON orders.order_id = badal_orders.order_id
        INNER JOIN donors 
            ON donors.donor_id = orders.donor_id
        INNER JOIN fcm_tokens 
            ON fcm_tokens.donor_id = donors.donor_id
            AND fcm_tokens.fcm_token IS NOT NULL
            AND fcm_tokens.fcm_token != ''

        WHERE badal_orders.badal_id = :badal_id
        AND donors.status <> 2

        ORDER BY fcm_tokens.modified_date DESC
        LIMIT 1
    ";

        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);

        return $this->db->single();
    }
    /**
     * get late time from setting
     * @param Array $id
     */
    public function getLateTimeSetting()
    {
        $query = 'SELECT `value` FROM  `settings`  WHERE `alias` = "badal"';
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * get all Substitutes
     *
     * @param [int] $id
     * @return object
     */
    public function getAllSubstitutes()
    {
        $query = 'SELECT * FROM `substitutes` WHERE `status` <> 2 ORDER BY create_date DESC ';
        $this->db->query($query);
        $results = $this->db->resultSet();
        return $results;
    }
    public function getSubstituteByIDWithToken($id)
    {
        $query = "
        SELECT 
            substitutes.*,
            donors.donor_id AS subsitude_donor_id,
            donors.email AS donor_email,
            donors.mobile AS donor_mobile,
            donors.full_name AS donor_full_name,
            fcm_tokens.fcm_token
        FROM substitutes
        INNER JOIN donors 
            ON donors.mobile = substitutes.phone
            AND donors.status = 1
        INNER JOIN fcm_tokens 
            ON fcm_tokens.donor_id = donors.donor_id
            AND fcm_tokens.fcm_token IS NOT NULL
        WHERE substitutes.substitute_id = :substitute_id
        LIMIT 1
    ";

        $this->db->query($query);
        $this->db->bind(':substitute_id', $id);

        return $this->db->single();
    }
    /**
     * get all Substitutes with donor
     *
     * @param [int] $id
     * @return object
     */
    public function getAllSubstitutesByDonors()
    {
        $query = 'SELECT substitutes.*, donors.is_substitute, donors.donor_id
                FROM substitutes
                JOIN donors ON substitutes.phone = donors.mobile  
                WHERE substitutes.status <> 2 AND donors.is_substitute = 1 AND donors.status = 1 
                ORDER BY substitutes.create_date DESC; ';
        $this->db->query($query);
        $results = $this->db->resultSet();
        return $results;
    }

    /**
     * get th request of this badal 
     * @param mixed $badal_id
     * @param mixed $substitute_id
     * 
     * @return [type]
     */
    public function getLastsRequestOfSubstitute($badal_id, $substitute_id)
    {
        $query = 'SELECT * FROM `requests` WHERE `badal_id` = ' . $badal_id . ' AND substitute_id = ' . $substitute_id . ' AND `status` != 2 ';
        $this->db->query($query);
        $results = $this->db->resultSet();
        return $results;
    }

    /**
     * get th request of this badal 
     * @param mixed $badal_id
     * @param mixed $substitute_id
     * 
     * @return [type]
     */
    public function checkAcceptSameDate($data)
    {
        $query = " SELECT *  FROM `requests`  , `badal_orders`
            WHERE `requests`.`badal_id` != :badal_id 
            AND `requests`.substitute_id = :substitute_id 
            AND `requests`.`status` != 2 
            AND  `badal_orders`.badal_id =  `requests`.badal_id
            AND `badal_orders`.status = 1
            AND from_unixtime( `requests`.`start_at`, '%Y-%m-%d') = :start_at 
            AND `requests`.is_selected = 1 ";

        $this->db->query($query);
        $this->db->bind(':start_at', date('Y-m-d', strtotime($data['start_at'])));
        $this->db->bind(':badal_id',  $data['badal_id']);
        $this->db->bind(':substitute_id',  $data['substitute_id']);
        $results = $this->db->resultSet();
        return $results;
    }

    /**
     * delete all Request with the same day by Request id 
     * @param Array $id
     */
    public function deleteSameDateRequest($request)
    {

        $query = "UPDATE `requests` SET `status` = 2, `modified_date` = :modified_date 
                   WHERE from_unixtime(`start_at`, '%d-%m-%Y') = :start_at AND `request_id` != :request_id AND `substitute_id` = :substitute_id AND `is_selected` != 1 ";
        // Bind the parameters to the query
        $this->db->query($query);
        $this->db->bind(':start_at', date('d-m-Y', $request->start_at));
        $this->db->bind(':request_id',  $request->request_id);
        $this->db->bind(':substitute_id',  $request->substitute_id);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * delete all Request with the same day by Request id 
     * @param Array $id
     */
    public function getRequestBysubsituteID($substitute_id)
    {

        $query = 'select
                    `requests`.request_id
                    ,( select name from projects p where p.project_id  = bo.project_id) as project_name
                    ,bo.behafeof 
                    ,`requests`.start_at
                    ,`requests`.status
                    ,`requests`.is_selected
                    ,bo.substitute_id as badal_selected
                from
                    `requests`, badal_orders bo 
                where
                    `requests`.substitute_id = :substitute_id
                    and `requests`.badal_id  =  bo.badal_id  
                    and `requests`.status <> 2
                order by
                    `requests`.`create_date` desc
                limit 30';

        $this->db->query($query);
        $this->db->bind(':substitute_id', $substitute_id);
        return $this->db->resultSet();
    }

    /**
     * check valid date Request of project
     * @param Array $id
     */
    public function checkValidDateRequest($badal_id)
    {

        $query = "select p.start_date , p.end_date 
                    from badal_orders bo , projects p 
                    where bo.badal_id = :badal_id
                    and bo.project_id = p.project_id";

        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);
        return $this->db->single();
    }
}
