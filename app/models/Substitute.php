<?php

/*
 * Copyright (C) 2018 Easy CMS Framework Ahmed Elmahdy
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License
 * @license    https://opensource.org/licenses/GPL-3.0
 *
 * @package    Easy CMS MVC framework
 * @author     Ahmed Elmahdy
 * @link       https://ahmedx.com
 *
 * For more information about the author , see <http://www.ahmedx.com/>.
 */

class Substitute extends Model
{

    /**
     * setting table name
     */
    public function __construct()
    {
        parent::__construct('substitutes');
    }



    /**
     * insert new Substitute
     * @param array $data
     * @return boolean
     */
    public function addSubstitute($data)
    {
        
        $this->db->query('INSERT INTO `substitutes`( image, full_name, identity, phone, nationality, gender, email, languages, status, modified_date, create_date)'
            . ' VALUES ( :image, :full_name, :identity, :phone, :nationality, :gender, :email, :languages, :status, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':identity', $data['identity']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':nationality', $data['nationality']);
        $this->db->bind(':gender', $data['gender']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':languages', implode(',', $data['languages']) );
        $this->db->bind(':status', '0');
        $this->db->bind(':create_date', time());
        $this->db->bind(':modified_date', time());

        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }  $this->db->query('INSERT INTO `substitutes`( image, full_name, identity, phone, nationality, gender, email, languages, status, modified_date, create_date)'
            . ' VALUES ( :image, :full_name, :identity, :phone, :nationality, :gender, :email, :languages, :status, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':identity', $data['identity']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':nationality', $data['nationality']);
        $this->db->bind(':gender', $data['gender']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':languages', implode(',', $data['languages']) );
        $this->db->bind(':status', '0');
        $this->db->bind(':create_date', time());
        $this->db->bind(':modified_date', time());

        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * list all Substitute
     * @param array $data
     * @return boolean
     */
    public function getSubstitutes() {
        $query = 'SELECT `substitute_id`, `full_name`, `gender`, `create_date` FROM `substitutes`  WHERE `status` <> 2 ';
        $this->db->query($query);
        return ($this->db->resultSet());
    }

    /**
     * select all Substitute
     * @param array $data
     * @return boolean
     */
    public function selectSubstitutes($data) {
        $query = 'UPDATE `badal_orders` SET `substitute_id` = :substitute_id, `modified_date` = :modified_date  WHERE `badal_id`= :badal_id';
        $this->db->query($query);
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $this->db->bind(':modified_date', time());
        $this->db->bind(':badal_id', $data['badal_id']);
          // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * select all Substitute has order totday
     * @param array $data
     * @return boolean
     */
    public function gettSubstitutesHasOrderToday() {
        $query = 'SELECT `requests`.*, `substitutes`.phone, `substitutes`.email, `substitutes`.full_name,
                         `orders`.total, `orders`.order_identifier, `projects`.`name` AS project_name
                  FROM `requests`, `substitutes`, `badal_orders`, `orders`, `projects`
                  WHERE `requests`.substitute_id  = `substitutes`.substitute_id  
                  AND `requests`.badal_id = `badal_orders`.badal_id
                  AND `badal_orders`.order_id = `orders`.order_id
                  AND `projects`.project_id = `badal_orders`.project_id
                  AND `requests`.is_selected = 1 
                  AND `requests`.`status` = 1
                  AND `badal_orders`.`status` = 1
                  AND `substitutes`.`status` = 1
                  AND DATE( FROM_UNIXTIME(`requests`.`start_at`)) = CURDATE(); ';
        $this->db->query($query);
        return ($this->db->resultSet());
    }
    /**
     * get substitutes with their badal_orders (paginated)
     *
     * @param int $page
     * @param int $per_page
     * @return array ['data'=>[], 'total'=>int]
     */
    public function getSubstitutesWithOrders($page = 1, $per_page = 20)
    {
        $page = max(1, (int)$page);
        $per_page = max(1, (int)$per_page);
        $offset = ($page - 1) * $per_page;

        // total count
        $this->db->query('SELECT COUNT(*) AS total FROM `substitutes` WHERE `status` <> 2');
        $totalRow = $this->db->single();
        $total = isset($totalRow->total) ? (int)$totalRow->total : 0;

        // fetch substitutes with their orders (left join)
        // note: we inject offset/limit as integers to avoid PDO limit binding issues
        $query = 'SELECT s.substitute_id, s.full_name, s.gender, s.proportion, s.create_date,
                     b.badal_id, b.order_id, b.amount, b.quantity, b.start_at, b.complete_at, b.status AS badal_status
              FROM `substitutes` s
              LEFT JOIN `badal_orders` b ON b.substitute_id = s.substitute_id
              WHERE s.`status` <> 2
              ORDER BY s.create_date DESC
              LIMIT ' . (int)$offset . ', ' . (int)$per_page;

        $this->db->query($query);
        $rows = $this->db->resultSet();

        // aggregate rows into substitutes with orders array
        $result = [];
        foreach ($rows as $r) {
            $sid = $r->substitute_id;
            if (!isset($result[$sid])) {
                $result[$sid] = (object)[
                    'substitute_id' => $sid,
                    'full_name' => $r->full_name,
                    'gender' => $r->gender,
                    'proportion' => isset($r->proportion) ? $r->proportion : 0,
                    'create_date' => $r->create_date,
                    'orders' => []
                ];
            }

            // if there is an order (LEFT JOIN might produce nulls)
            if (!empty($r->badal_id)) {
                $order = (object)[
                    'badal_id' => $r->badal_id,
                    'order_id' => $r->order_id,
                    'amount' => $r->amount,
                    'quantity' => $r->quantity,
                    'start_at' => $r->start_at,
                    'complete_at' => $r->complete_at,
                    'status' => $r->badal_status,
                ];
                $result[$sid]->orders[] = $order;
            }
        }

        // reindex to numeric array
        $data = array_values($result);

        return ['data' => $data, 'total' => $total];
    }



}
