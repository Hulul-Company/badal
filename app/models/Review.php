<?php

class Review extends Model
{
    public function __construct()
    {
        parent::__construct('badal_review');
    }

    /**
     * insert new review
     * @param array $data
     * @return boolean
     */
    public function addReview($data)
    {
        $this->db->query('INSERT INTO `badal_review` ( badal_id, rate, description, status, modified_date, create_date)'
            . ' VALUES ( :badal_id, :rate, :description, :status, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':badal_id', $data['badal_id']);
        $this->db->bind(':rate', $data['rate']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':status', 1);
        $this->db->bind(':modified_date', time());
        $this->db->bind(':create_date', time());
        // excute
        if ($this->db->excute()) {
            return $this->db->lastId();
        } else {
            return false;
        }
    }
    public function getSubstituteNewWithToken($badal_id)
    {
        $query = "
        SELECT 
            donors.donor_id,
            donors.full_name,
            donors.email,
            donors.mobile,
            fcm_tokens.fcm_token
        FROM badal_orders
        INNER JOIN substitutes 
            ON badal_orders.substitute_id = substitutes.substitute_id
        INNER JOIN donors 
            ON donors.mobile = substitutes.phone
            AND donors.status = 1
        INNER JOIN fcm_tokens 
            ON fcm_tokens.donor_id = donors.donor_id
            AND fcm_tokens.fcm_token IS NOT NULL
        WHERE badal_orders.badal_id = :badal_id
        ORDER BY fcm_tokens.modified_date DESC
        LIMIT 1
    ";

        $this->db->query($query);
        $this->db->bind(':badal_id', $badal_id);

        return $this->db->single();
    }

    /**
     * insert new review
     * @param array $data
     * @return boolean
     */
    public function getSubstitute($badal_id)
    {
        $this->db->query('
        SELECT
            badal_orders.substitute_id,
            substitutes.*,
            orders.donor_id,
            orders.order_id,
            orders.order_identifier,
            orders.total,
            orders.projects,
            orders.donor_name
        FROM badal_orders
        INNER JOIN orders 
            ON orders.order_id = badal_orders.order_id
        INNER JOIN substitutes 
            ON substitutes.substitute_id = badal_orders.substitute_id
        WHERE badal_orders.badal_id = :badal_id
        LIMIT 1
    ');

        $this->db->bind(':badal_id', $badal_id);

        return $this->db->single();
    }

    /**
     * insert new review
     * @param array $data
     * @return boolean
     */
    public function getSubstituteNew($badal_id){
        $this->db->query('
        SELECT
        `donors`.* 
        FROM `substitutes`, `donors` , `badal_orders`
        WHERE `badal_orders`.badal_id = ' . $badal_id.'
        AND `substitutes`.substitute_id = `badal_orders`.substitute_id 
        AND `substitutes`.phone  =  `donors`.mobile
        AND `substitutes`.status  !=  0'
        );
       return $this->db->single();
    }

    /**
     * check if review is exist 
     * @param array $data
     * @return boolean
     */
    public function checkeview($data){
        $this->db->query('SELECT `badal_id` FROM `badal_review` WHERE `badal_id` = :badal_id ');
        $this->db->bind(':badal_id', $data['badal_id']);
       return $this->db->single();
    }

}