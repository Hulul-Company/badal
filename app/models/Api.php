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

class Api extends Model
{
    public function __construct()
    {
        parent::__construct('donations');
    }

    /**
     * get all donations
     *
     * @param integer $start
     * @param integer $count
     * @return object
     */
    public function getDonations($start = 0, $count = 20, $status, $donation_id, $project_id, $order_id, $API_status)
    {
        return $this->queryResult(
            'SELECT donations.*,orders.order_identifier as `order`, projects.name as project,
             from_unixtime(donations.create_date) as create_date, from_unixtime(donations.modified_date) as modified_date 
             FROM donations  ,projects, orders, donors
             WHERE donations.status <> 2 ' . $status . ' ' . $donation_id . ' ' . $project_id . ' ' . $order_id . ' ' . $API_status . ' 
                AND projects.project_id = donations.project_id AND orders.donor_id = donors.donor_id AND orders.order_id = donations.order_id
             ORDER BY donations.create_date LIMIT ' . $start . ' , ' . $count
        );
    }

    /**
     * Get orders with badal execution status and substitute details
     * النسخة المحدثة مع إضافة:
     * - فلتر حالة التنفيذ (execution_status)
     * - حالة التنفيذ على مستوى الطلب
     * - بيانات المنفذ الكاملة
     * - نسبة العمولة وقت الإسناد
     * 
     * @param int $start
     * @param int $count
     * @param string $status
     * @param string $order_identifier
     * @param string $order_id
     * @param string $API_status
     * @param string $API_odoo
     * @param string $custom_status_id
     * @param string $payment_method
     * @param string $store_id
     * @param string $start_date
     * @param string $end_date
     * @param string $execution_status - NEW: pending, in_progress, completed
     * @return array
     */
    public function getOrders(
        $start = 0,
        $count = 20,
        $status,
        $order_identifier,
        $order_id,
        $API_status,
        $API_odoo,
        $custom_status_id,
        $payment_method,
        $store_id,
        $start_date,
        $end_date,
        $execution_cond = '' // ✅ المتغير المهم
    )
    {
        $query = 'SELECT 
        ord.*,
        CONCAT("' . MEDIAURL . '/../files/banktransfer/", ord.banktransferproof) AS banktransferproof,
        
        -- بيانات طريقة الدفع
        pm.title AS payment_method,
        pm.payment_key,
        
        -- بيانات المتبرع
        donors.full_name AS donor,
        donors.mobile,
        donors.identity,
        donors.email,
        
        -- التواريخ
        FROM_UNIXTIME(ord.create_date) AS create_date,
        FROM_UNIXTIME(ord.modified_date) AS modified_date,
        
        -- الحالة المخصصة
        (SELECT st.name FROM statuses st WHERE st.status_id = ord.status_id) AS custom_status,
        ord.status_id AS custom_status_id,
        
        -- ✅ إحصائيات حالة التنفيذ على مستوى الطلب
        COUNT(DISTINCT bo.badal_id) AS total_badal_items,
        SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_items,
        SUM(CASE WHEN bo.start_at IS NOT NULL AND bo.complete_at IS NULL THEN 1 ELSE 0 END) AS in_progress_items,
        SUM(CASE WHEN bo.start_at IS NULL AND bo.complete_at IS NULL THEN 1 ELSE 0 END) AS pending_items,
        
        -- ✅ حالة التنفيذ الإجمالية للطلب
        CASE 
            WHEN COUNT(bo.badal_id) = 0 THEN "no_badal_orders"
            WHEN SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) = COUNT(bo.badal_id) THEN "completed"
            WHEN SUM(CASE WHEN bo.start_at IS NOT NULL THEN 1 ELSE 0 END) > 0 THEN "in_progress"
            ELSE "pending"
        END AS order_execution_status
        
    FROM orders ord USE INDEX (create_date)
    
    INNER JOIN donors ON donors.donor_id = ord.donor_id
    INNER JOIN payment_methods pm ON ord.payment_method_id = pm.payment_id
    LEFT JOIN badal_orders bo ON bo.order_id = ord.order_id AND bo.status = 1
    
    WHERE ord.status <> 2
    ' . $status . $order_identifier . $order_id . $API_status . $store_id .
            $custom_status_id . $payment_method . $start_date . $end_date . $API_odoo .
            $execution_cond . ' -- ✅ الفلتر يجي من Controller
    
    GROUP BY ord.order_id 
    ORDER BY ord.create_date DESC 
    LIMIT ' . (int)$start . ', ' . (int)$count;

        return $this->queryResult($query);
    }

    /**
     * Get badal orders with full substitute details for a specific order
     * يجلب جميع سطور البدل مع بيانات المنفذ الكاملة ونسبة العمولة
     * 
     * @param int $order_id
     * @return array
     */
    public function getBadalOrdersByOrderId($order_id)
    {
        $query = 'SELECT 
            bo.badal_id,
            bo.order_id,
            bo.amount,
            bo.total,
            bo.quantity,
            bo.substitute_id,
            bo.substitute_proportion,
            bo.project_id,
            bo.behafeof,
            bo.relation,
            bo.language,
            bo.gender,
            bo.is_offer,
            bo.offer_id,
            FROM_UNIXTIME(bo.start_at) AS start_at,
            FROM_UNIXTIME(bo.complete_at) AS complete_at,
            FROM_UNIXTIME(bo.create_date) AS create_date,
            FROM_UNIXTIME(bo.modified_date) AS modified_date,
            bo.status,
            
            -- *** حالة التنفيذ لكل سطر ***
            CASE 
                WHEN bo.complete_at IS NOT NULL THEN "completed"
                WHEN bo.start_at IS NOT NULL THEN "in_progress"
                ELSE "pending"
            END AS execution_status,
            
            -- *** بيانات المنفذ الكاملة ***
            s.substitute_id AS substitute_details_id,
            s.full_name AS substitute_name,
            s.email AS substitute_email,
            s.phone AS substitute_phone,
            s.identity AS substitute_identity,
            s.languages AS substitute_languages,
            s.gender AS substitute_gender,
            s.proportion AS substitute_current_proportion,
            s.nationality AS substitute_nationality,
            
            -- بيانات المشروع
            p.name AS project_name,
            p.project_id AS project_details_id,
            p.beneficiary AS project_beneficiary,
            p.project_number AS project_ax_id
            
        FROM badal_orders bo
        LEFT JOIN substitutes s ON bo.substitute_id = s.substitute_id
        LEFT JOIN projects p ON bo.project_id = p.project_id
        WHERE bo.order_id = ' . (int)$order_id . '
        AND bo.status = 1
        ORDER BY bo.badal_id';

        return $this->queryResult($query);
    }

    /**
     * Update orders API status
     *
     * @param array $filters
     * @param string $set_status
     * @return int
     */
    public function updatetOrders($filters, $set_status)
    {
        $cond = '';
        foreach ($filters as $key => $value) {
            $cond .= " AND $key = :$key";
        }
        $query = 'UPDATE orders SET API_status = :API_status WHERE orders.status <> 2 ' . $cond;

        $this->db->query($query);
        $this->db->bind(':API_status', $set_status);
        foreach ($filters as $key => $value) {
            $this->db->bind(":" . $key, $value);
        }
        $this->db->excute();
        return $this->db->rowCount();
    }

    /**
     * Update orders Odoo status
     *
     * @param array $filters
     * @param string $odooStatus
     * @return int
     */
    public function updatetOrdersOdoo($filters, $odooStatus)
    {
        $cond = '';
        foreach ($filters as $key => $value) {
            $cond .= " AND $key = :$key";
        }
        $query = 'UPDATE orders SET API_odoo = :API_odoo2 WHERE orders.status <> 2 ' . $cond;
        $this->db->query($query);
        $this->db->bind(':API_odoo2', $odooStatus);
        foreach ($filters as $key => $value) {
            $this->db->bind(":" . $key, $value);
        }
        $this->db->excute();
        return $this->db->rowCount();
    }

    /**
     * Update odoo status with date range
     *
     * @param array $filters
     * @param string $odooStatus
     * @param string $start_date
     * @param string $end_date
     * @return int
     */
    public function updatetOrdersOdooWithDate($filters, $odooStatus, $start_date = null, $end_date = null)
    {
        $cond = '';
        foreach ($filters as $key => $value) {
            $cond .= " AND $key = :$key";
        }

        $query = "UPDATE orders SET API_odoo = :API_odoo2 WHERE orders.status <> 2 " . $cond;

        if ($start_date != null) {
            $query .= " AND from_unixtime( `create_date`, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "'";
        }
        if ($end_date != null) {
            $query .= " AND from_unixtime( `create_date`, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "'";
        }

        $this->db->query($query);
        $this->db->bind(':API_odoo2', $odooStatus);
        foreach ($filters as $key => $value) {
            $this->db->bind(":" . $key, $value);
        }

        $this->db->excute();
        return $this->db->rowCount();
    }

    /**
     * Check user API authentication
     *
     * @param string $user
     * @param string $key
     * @return array
     */
    public function auth($user, $key)
    {
        $api_settings = json_decode($this->getSettings('api')->value);

        if ($api_settings->api_user == $user && $api_settings->api_key == $key) {
            return ['enable' => $api_settings->api_enable, 'authorized' => true];
        } else {
            return ['enable' => $api_settings->api_enable, 'authorized' => false];
        }
    }

    /**
     * Get donations by order id
     *
     * @param int $order_id
     * @return array
     */
    public function getDonationByOrderId($order_id)
    {
        return $this->queryResult('SELECT donations.*, projects.beneficiary, projects.project_number AS AX_ID FROM donations, projects WHERE projects.project_id = donations.project_id AND order_id = ' . (int)$order_id);
    }

    /**
     * Get Store by id
     *
     * @param int $store_id
     * @return object|null
     */
    public function getStore($store_id)
    {
        if (!$store_id) {
            $store_id = 0;
        }
        $results = $this->queryResult('SELECT * FROM stores WHERE store_id = ' . (int)$store_id);
        if (count($results) > 0) {
            return $results[0];
        }
        return null;
    }

    /**
     * Get list of stores
     *
     * @param string $cond
     * @return array
     */
    public function storesList($cond)
    {
        return $this->queryResult('SELECT * FROM stores ' . $cond);
    }

    /**
     * Get substitute statistics
     * احصائيات المنفذ مع حساب العمولات
     *
     * @param int $substitute_id
     * @return object|null
     */
    public function getSubstituteStats($substitute_id)
    {
        $query = 'SELECT 
            s.substitute_id,
            s.full_name,
            s.phone,
            s.email,
            s.identity,
            s.gender,
            s.languages,
            s.proportion AS current_proportion,
            COUNT(bo.badal_id) AS total_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN bo.start_at IS NOT NULL AND bo.complete_at IS NULL THEN 1 ELSE 0 END) AS in_progress_orders,
            SUM(CASE WHEN bo.start_at IS NULL THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS total_commission_earned,
            SUM(CASE WHEN bo.complete_at IS NULL AND bo.start_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS pending_commission
        FROM substitutes s
        LEFT JOIN badal_orders bo ON s.substitute_id = bo.substitute_id AND bo.status = 1
        WHERE s.substitute_id = ' . (int)$substitute_id . '
        GROUP BY s.substitute_id';

        $results = $this->queryResult($query);
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Get all substitutes with their statistics
     * جميع المنفذين مع احصائياتهم
     *
     * @return array
     */
    public function getAllSubstitutesStats()
    {
        $query = 'SELECT 
            s.substitute_id,
            s.full_name,
            s.phone,
            s.email,
            s.gender,
            s.languages,
            s.proportion AS current_proportion,
            COUNT(bo.badal_id) AS total_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS total_commission
        FROM substitutes s
        LEFT JOIN badal_orders bo ON s.substitute_id = bo.substitute_id AND bo.status = 1
        WHERE s.status <> 2
        GROUP BY s.substitute_id
        ORDER BY total_commission DESC';

        return $this->queryResult($query);
    }

    /**
     * Get execution status summary for a date range
     * ملخص حالة التنفيذ لفترة زمنية
     *
     * @param string $start_date
     * @param string $end_date
     * @return object
     */
    public function getExecutionSummary($start_date = null, $end_date = null)
    {
        $date_condition = '';
        if ($start_date) {
            $date_condition .= " AND FROM_UNIXTIME(ord.create_date) >= '" . date('Y-m-d 00:00:00', strtotime($start_date)) . "'";
        }
        if ($end_date) {
            $date_condition .= " AND FROM_UNIXTIME(ord.create_date) <= '" . date('Y-m-d 23:59:59', strtotime($end_date)) . "'";
        }

        $query = 'SELECT 
            COUNT(DISTINCT ord.order_id) AS total_orders,
            COUNT(DISTINCT bo.badal_id) AS total_badal_items,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_items,
            SUM(CASE WHEN bo.start_at IS NOT NULL AND bo.complete_at IS NULL THEN 1 ELSE 0 END) AS in_progress_items,
            SUM(CASE WHEN bo.start_at IS NULL THEN 1 ELSE 0 END) AS pending_items,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS total_commissions,
            SUM(ord.total) AS total_amount
        FROM orders ord
        LEFT JOIN badal_orders bo ON bo.order_id = ord.order_id AND bo.status = 1
        WHERE ord.status = 1 ' . $date_condition;

        $results = $this->queryResult($query);
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Get Total Count of Orders
     * 
     * @param string $status
     * @param string $order_identifier
     * @param string $order_id
     * @param string $API_status
     * @param string $API_odoo
     * @param string $custom_status_id
     * @param string $payment_method
     * @param string $store_id
     * @param string $start_date
     * @param string $end_date
     * @param string $execution_cond
     * @return int
     */
    public function getOrdersCount(
        $status,
        $order_identifier,
        $order_id,
        $API_status,
        $API_odoo,
        $custom_status_id,
        $payment_method,
        $store_id,
        $start_date,
        $end_date,
        $execution_cond = ''
    ) {
        $query = 'SELECT COUNT(DISTINCT ord.order_id) AS total
    FROM orders ord
    INNER JOIN donors ON donors.donor_id = ord.donor_id
    INNER JOIN payment_methods pm ON ord.payment_method_id = pm.payment_id
    LEFT JOIN badal_orders bo ON bo.order_id = ord.order_id AND bo.status = 1
    WHERE ord.status <> 2
    ' . $status . $order_identifier . $order_id . $API_status . $store_id .
            $custom_status_id . $payment_method . $start_date . $end_date . $API_odoo .
            $execution_cond;

        $result = $this->queryResult($query);
        return $result && count($result) > 0 ? (int)$result[0]->total : 0;
    }
}
