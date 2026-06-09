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

class Offer extends Model
{
    public function __construct()
    {
        parent::__construct('badal_offers');
    }

    /**
     * insert new offer
     * @param array $data
     * @return boolean
     */
    public function addOffer($data)
    {
        $this->db->query('INSERT INTO `badal_offers` ( substitute_id, project_id,  amount, start_at, status, modified_date, create_date)'
            . ' VALUES (:substitute_id, :project_id, :amount, :start_at, :status, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $this->db->bind(':project_id', $data['project_id']);
        $this->db->bind(':amount', $data['amount']);
        $offerDate = new DateTime($data['start_at'], new DateTimeZone('Asia/Riyadh'));
        $this->db->bind(':start_at', $offerDate->getTimestamp());
        $this->db->bind(':status', 0);
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
     * cancel offer by id 
     * @param Array $ids
     */
    public function cancelOffer($id)
    {
        $query = 'UPDATE `badal_offers` SET  status = 2,  `modified_date` = :modified_date WHERE `offer_id`= :offer_id';
        $this->db->query($query);
        $this->db->bind(':offer_id', $id);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * get offers by substitute 
     * @param Array $ids
     */
    public function getOffersBySubstitute($substitute_id)
    {
        $query = "SELECT badal_offers.*, 
            projects.`name` AS project_name, 
            substitutes.full_name, 
            substitutes.nationality, 
            substitutes.gender,
            from_unixtime(badal_offers.start_at) as start_at,
            (SELECT  round(AVG(`badal_review`.rate) * 2 , 0) / 2
                FROM `badal_review`, `badal_orders`
                WHERE `badal_orders`.`substitute_id` = `badal_offers`.`substitute_id`
                AND `badal_review`.`badal_id` = `badal_orders`.`badal_id`
            ) AS rate
        FROM badal_offers INNER JOIN substitutes ON badal_offers.substitute_id = substitutes.substitute_id
            INNER JOIN projects ON  badal_offers.project_id = projects.project_id
        WHERE  badal_offers.substitute_id = :substitute_id 
        AND badal_offers.status IN ( 1 , 0 )
        ORDER BY badal_offers.create_date DESC ";

        $this->db->query($query);
        $this->db->bind(':substitute_id', $substitute_id);
        return $this->db->resultSet();
    }

    /**
     * get offers 
     * @param Array $ids
     */
    public function getOffers()
    {
        $query = " SELECT `badal_offers`.*, 
                    `projects`.`name` AS project_name, 
                    `substitutes`.full_name, 
                    CONCAT('" . MEDIAURL .  "/../files/substitutes/', substitutes.image ) AS substitute_image,
                    `substitutes`.nationality, 
                    `substitutes`.gender,
                    `substitutes`.languages,
                    from_unixtime(badal_offers.start_at) as start_at,
                    (SELECT  round(AVG(`badal_review`.rate) * 2 , 0) / 2
                        FROM `badal_review`, `badal_orders`
                        WHERE `badal_orders`.`substitute_id` = `badal_offers`.`substitute_id`
                        AND `badal_review`.`badal_id` = `badal_orders`.`badal_id`
                    ) AS rate
                    FROM `badal_offers` INNER JOIN `substitutes` ON `badal_offers`.substitute_id = `substitutes`.substitute_id
                    INNER JOIN `projects` ON  `badal_offers`.project_id = `projects`.project_id
                    WHERE `badal_offers`.`status` = 1 AND `badal_offers`.start_at > " . time() . "
                    AND `substitutes`.status = 1
                    ORDER BY `badal_offers`.create_date DESC ";

        $this->db->query($query);
        // $this->db->bind(':now', time());
        return $this->db->resultSet();
    }

    /**
     * get Request by id 
     * @param Array $id
     */
    public function getOfferById($id)
    {
        $query = 'SELECT * FROM  `badal_offers`  WHERE `offer_id` = ' . $id . '   AND  status <> 2 ';
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * get Request by id 
     * @param Array $id
     */
    public function getOfferByIdWithRelations($id)
    {
        $query = 'SELECT `badal_offers`.*, `projects`.name AS project_name, `substitutes`.full_name FROM  `badal_offers`, `projects`, `substitutes`  WHERE `badal_offers`.`offer_id` = ' . $id . '   AND  `badal_offers`.status NOT IN (2, 0)  ';
        $this->db->query($query);
        return $this->db->single();
    }

    /**
     * get Request by id 
     * @param Array $id
     */
    public function getOfferByIdWithRelationsNotiftAdd($id)
    {
        $query = 'SELECT `badal_offers`.*, `projects`.name AS project_name, `substitutes`.full_name FROM  `badal_offers`, `projects`, `substitutes`  WHERE `badal_offers`.`offer_id` = ' . $id . '   AND  `badal_offers`.status NOT IN (2)  ';
        $this->db->query($query);
        return $this->db->single();
    }
    public function getOrderByProjectID($project_id)
    {
        $query = '
        SELECT 
            badal_orders.order_id,
            badal_orders.badal_id,
            badal_orders.behafeof,
            orders.*,
            donors.mobile,
            donors.full_name,
            donors.email
        FROM badal_orders, orders, donors
        WHERE badal_orders.project_id = :project_id
        AND badal_orders.order_id = orders.order_id
        AND orders.donor_id = donors.donor_id
        ORDER BY badal_orders.create_date DESC
        LIMIT 1
    ';

        $this->db->query($query);
        $this->db->bind(':project_id', $project_id);
        return $this->db->single();
    }

    public function getOrderByProjectIDWithToken($project_id)
    {
        $query = "
        SELECT 
            badal_orders.order_id,
            badal_orders.badal_id,
            badal_orders.behafeof,
            orders.*,
            donors.donor_id,
            donors.mobile,
            donors.full_name,
            donors.email,
            fcm_tokens.fcm_token
        FROM badal_orders
        INNER JOIN orders 
            ON badal_orders.order_id = orders.order_id
        INNER JOIN donors 
            ON orders.donor_id = donors.donor_id
        INNER JOIN fcm_tokens 
            ON fcm_tokens.donor_id = donors.donor_id
            AND fcm_tokens.fcm_token IS NOT NULL
        WHERE badal_orders.project_id = :project_id
        AND donors.status <> 2
        ORDER BY fcm_tokens.modified_date DESC
        LIMIT 1
    ";

        $this->db->query($query);
        $this->db->bind(':project_id', $project_id);

        return $this->db->single();
    }
    /**
     * get All Donors 
     */
    public function getAllDonors()
    {
        $query = 'SELECT * FROM  `donors`  WHERE  status <> 2 ';
        $this->db->query($query);
        return $this->db->resultSet();
    }


    /**
     * check Possible Time to create offer
     * @param Array $data
     */
    public function checkPossibleTime($data)
    {
        $query = '  SELECT * 
                    FROM  `badal_offers`  
                    WHERE `substitute_id` = :substitute_id 
                    AND `start_at` > :start_at 
                    AND `start_at` < :end_at 
                    AND `status` <> 2 ';
        $this->db->query($query);
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $offerDate = new DateTime($data['start_at'], new DateTimeZone('Asia/Riyadh'));
        $offerTimestamp = $offerDate->getTimestamp();

        $this->db->bind(':start_at', $offerTimestamp - ($data['offer_time'] * 60 * 60));
        $this->db->bind(':end_at', $offerTimestamp + ($data['offer_time'] * 60 * 60));
        return $this->db->resultSet();
    }

    /**
     * get project by project_id
     * @param Array $data
     */
    public function getProject($project_id)
    {
        $query = 'SELECT `min_price` FROM  `projects`  WHERE `project_id` = ' . $project_id . ' AND  `status` <> 2 ';
        $this->db->query($query);
        return $this->db->single();
    }
    /**
     * get offers by substitute (paginated)
     */
    public function getOffersBySubstitutePaginated($substitute_id, $offset = 0, $limit = 5)
    {
        $query = "SELECT badal_offers.*, 
        projects.`name` AS project_name, 
        substitutes.full_name, 
        substitutes.nationality, 
        substitutes.gender,
        FROM_UNIXTIME(badal_offers.start_at) AS start_at,
        (SELECT ROUND(AVG(`badal_review`.rate) * 2 , 0) / 2
            FROM `badal_review`, `badal_orders`
            WHERE `badal_orders`.`substitute_id` = `badal_offers`.`substitute_id`
            AND `badal_review`.`badal_id` = `badal_orders`.`badal_id`
        ) AS rate
    FROM badal_offers
        INNER JOIN substitutes ON badal_offers.substitute_id = substitutes.substitute_id
        INNER JOIN projects ON badal_offers.project_id = projects.project_id
    WHERE badal_offers.substitute_id = :substitute_id
        AND badal_offers.status IN (0,1)
    ORDER BY badal_offers.create_date DESC
    LIMIT :offset, :limit";

        $this->db->query($query);
        $this->db->bind(':substitute_id', (int)$substitute_id);
        $this->db->bind(':offset', (int)$offset);
        $this->db->bind(':limit', (int)$limit);

        return $this->db->resultSet();
    }
    /**
     * count offers by substitute
     */
    public function getOffersBySubstituteCount($substitute_id)
    {
        $query = "
        SELECT COUNT(*) AS total
        FROM badal_offers
        WHERE substitute_id = :substitute_id
        AND status IN (0,1)
    ";

        $this->db->query($query);
        $this->db->bind(':substitute_id', (int)$substitute_id);
        return $this->db->single();
    }
    public function getOffersPaginated($offset = 0, $limit = 10)
    {
        $query = "SELECT badal_offers.*, 
        projects.name AS project_name, 
        substitutes.full_name, 
        CONCAT('" . MEDIAURL . "/../files/substitutes/', substitutes.image ) AS substitute_image,
        substitutes.nationality, 
        substitutes.gender,
        substitutes.languages,
        FROM_UNIXTIME(badal_offers.start_at) AS start_at,
        (
            SELECT ROUND(AVG(badal_review.rate) * 2, 0) / 2
            FROM badal_review, badal_orders
            WHERE badal_orders.substitute_id = badal_offers.substitute_id
            AND badal_review.badal_id = badal_orders.badal_id
        ) AS rate
    FROM badal_offers
        INNER JOIN substitutes ON badal_offers.substitute_id = substitutes.substitute_id
        INNER JOIN projects ON badal_offers.project_id = projects.project_id
    WHERE badal_offers.status = 1
        AND badal_offers.start_at > :now
        AND substitutes.status = 1
    ORDER BY badal_offers.create_date DESC
    LIMIT :offset, :limit";

        $this->db->query($query);
        $this->db->bind(':now', time());
        $this->db->bind(':offset', (int)$offset);
        $this->db->bind(':limit', (int)$limit);

        return $this->db->resultSet();
    }

    public function getOffersCount()
    {
        $query = "SELECT COUNT(*) AS total
        FROM badal_offers
        INNER JOIN substitutes ON badal_offers.substitute_id = substitutes.substitute_id
        WHERE badal_offers.status = 1
        AND badal_offers.start_at > :now
        AND substitutes.status = 1";

        $this->db->query($query);
        $this->db->bind(':now', time());

        return $this->db->single();
    }
}
