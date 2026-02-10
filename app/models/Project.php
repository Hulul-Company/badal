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

class Project extends Model
{
    public function __construct()
    {
        parent::__construct('projects');
    }

    /**
     * get all pages from datatbase
     * @return object page data
     */
    public function getPagesTitle()
    {
        $results = $this->getFromTable('pages', 'page_id, title, alias', ['status' => 1]);
        return $results;
    }

    /**
     * getProjectsById
     *
     * @param  mixed $id
     *
     * @return object project
     */
    public function getProjectById($id)
    {
        // prepare Query
        $query = 'SELECT * FROM projects WHERE project_id = :project_id AND kafara <> "app" AND status = 1 LIMIT 1 ';
        $this->db->query($query);
        //bind values
        $this->db->bind(':project_id', $id);
        return $this->db->single();
    }

    /**
     * getProjectsById
     *
     * @param  mixed $id
     *
     * @return object project
     */
    public function getProjectByIdApp($id)
    {
        // prepare Query
        $query = 'SELECT * FROM projects WHERE project_id = :project_id AND status = 1 LIMIT 1 ';
        $this->db->query($query);
        //bind values
        $this->db->bind(':project_id', $id);
        return $this->db->single();
    }
    /**
     * get projects in the same category
     *
     * @param integer $category_id
     * @return object
     */
    public function moreProjects($category_id)
    {
        return $this->get('name, alias , project_id, secondary_image', ['category_id' => $category_id, 'status' => 1, 'hidden' => 0]);
    }

    /**
     * get collected project Traget by i
     *
     * @param integer $id
     * @return record
     */
    public function collectedTraget($id)
    {
        // prepare Query
        $query = 'SELECT SUM(total) as total FROM donations WHERE project_id = :project_id AND status = 1 LIMIT 1 ';
        $this->db->query($query);
        //bind values
        $this->db->bind(':project_id', $id);
        return (int) $this->db->single()->total;;
    }
    /**
     * projectsCount
     *
     * @param  mixed $id
     *
     * @return void
     */
    public function projectsCount($id)
    {
        return $this->countAll(['project_id' => $id, 'status' => 1, 'hidden' => 0], 'projects');
    }

    /**
     * get Supported Payment Methods
     *
     * @param  mixed $payments_ids
     *
     * @return void
     */
    public function getSupportedPaymentMethods($payments_ids)
    {
        $payments_ids = json_decode($payments_ids, true);
        $results = $this->getWhereInTable('payment_methods', 'payment_id', $payments_ids);
        return $results;
    }

    /**
     * get payment key by its id
     *
     * @param int $payment_id
     * @return object
     */
    public function getPaymentKey($payment_id)
    {
        return $results = $this->getWhereInTable('payment_methods', 'payment_id', [$payment_id]);
    }

    public function updateBadalOrderByOrderId($orderId, $status)
    {
        $this->db->query("UPDATE badal_orders SET status = :status, modified_date = :modified_date WHERE order_id = :order_id AND status <> 2");
        $this->db->bind(':status', $status);
        $this->db->bind(':modified_date', time());
        $this->db->bind(':order_id', $orderId);
        $this->db->excute();
        return $this->db->rowCount();
    }

    /**
     * get Donation By Hash code
     *
     * @param  mixed $hash
     *
     * @return void
     */
    public function getOrderByHash($hash)
    {
        return $this->getSingle('*', ['hash' => $hash], 'orders');
    }

    /**
     * prepar bind and excute search query
     *
     * @param string $colomns
     * @param array $bind
     * @param integer $start
     * @param integer $perPage
     * @param string $table
     * @param string $orderBy
     * @param string $order
     * @param array $searchBind
     * @return object
     */
    public function search($colomns, $bind = '', $start = 1, $perPage = '', $table = null, $orderBy = 'create_date', $order = 'DESC', $searchBind = null)
    {
        $table ?: $table = $this->table;
        //check for pagination
        if (!empty($perPage)) {
            $limit = ' LIMIT :start, :perpage';
        } else {
            $limit = '';
        }
        //prepar condation for binding
        $cond = '';
        if (!empty($bind)) {
            $cond = ' WHERE ';
            foreach ($bind as $key => $value) {
                $cond .= "$key =:$key AND ";
            }
            $cond = rtrim($cond, 'AND ');
        }
        if ($searchBind) {
            empty($bind) ? $cond .= ' WHERE ' : $cond .= ' AND ';
            foreach ($searchBind as $key => $value) {
                $cond .= "$key  LIKE :$key AND ";
            }
            $cond = rtrim($cond, 'AND ');
        }
        // prepare Query
        if ($table == 'projects') $cond .= ' AND kafara <> "app" AND `hidden` = 0 ';
        $query = 'SELECT ' . $colomns . ' FROM ' . $table . ' ' . $cond . ' ORDER BY ' . $orderBy . ' ' . $order . ' ' . $limit;
        $this->db->query($query);
        //bind values
        if (!empty($bind)) {
            foreach ($bind as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }
        }
        //bind search
        if ($searchBind) {
            foreach ($searchBind as $key => $value) {
                $this->db->bind(':' . $key, "%$value%");
            }
        }
        // bind pagination LIMIT values
        if (!empty($perPage)) {
            $this->db->bind(':start', ($start - 1) * $perPage);
            $this->db->bind(':perpage', $perPage);
        }
        return $this->db->resultSet();
    }

    /**
     * update Donation  add bank transfere file
     *
     * @param  array $data
     *
     * @return void
     */
    public function updateOrderHash($data)
    {
        $query = 'UPDATE orders SET banktransferproof = :banktransferproof, payment_method_key = :payment_method_key, modified_date = :modified_date';
        
        $query .= ' WHERE hash = :hash';
        $this->db->query($query);
        // binding values
        $this->db->bind(':banktransferproof', $data['image']);
        $this->db->bind(':payment_method_key', $data['payment_key']);
        $this->db->bind(':hash', $data['hash']->hash);
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Update order bank transfer proof
     * @param int $order_id
     * @param string $filename
     * @return boolean
     */
    public function updateOrderBankProof($order_id, $filename)
    {
        $query = 'UPDATE orders SET banktransferproof = :banktransferproof, modified_date = :modified_date WHERE order_id = :order_id';

        $this->db->query($query);
        $this->db->bind(':order_id', $order_id);
        $this->db->bind(':banktransferproof', $filename);
        $this->db->bind(':modified_date', time());

        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }
    public function updateDonationStatus($order_id, $status)
    {
        $query = 'UPDATE donations SET status = :status, modified_date = :modified_date WHERE order_id = :order_id ';
        $this->db->query($query);
        $this->db->bind(':status', $status);
        $this->db->bind(':modified_date', time());
        $this->db->bind(':order_id', $order_id);
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    public function updateOrderMeta($data)
    {
        $query = 'UPDATE orders SET meta = :meta, status = :status, modified_date = :modified_date';
        if (isset($data['app']) && !empty($data['app'])) $query .= ', app =:app ';
        $query .= ' WHERE hash = :hash';
        $this->db->query($query);
        // binding values
        $this->db->bind(':meta', $data['meta']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':hash', $data['hash']);
        $this->db->bind(':modified_date', time());
        if (isset($data['app']) && !empty($data['app'])) $this->db->bind(':app', $data['app']);

        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    public function updateOrderMetaAuthorization($data)
    {
        $query = 'UPDATE orders SET 
              meta = :meta, 
              status = :status, 
              modified_date = :modified_date';

        if (isset($data['payment_method_id'])) {
            $query .= ', payment_method_id = :payment_method_id';
        }
        if (isset($data['payment_method_key'])) {
            $query .= ', payment_method_key = :payment_method_key';
        }
        if (isset($data['banktransferproof'])) {
            $query .= ', banktransferproof = :banktransferproof';
        }
        if (isset($data['app'])) {
            $query .= ', app = :app';
        }

        $query .= ' WHERE order_id = :order_id';

        $this->db->query($query);

        $this->db->bind(':order_id', $data['order_id']);
        $this->db->bind(':meta', $data['meta']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':modified_date', time());

        if (isset($data['payment_method_id'])) $this->db->bind(':payment_method_id', $data['payment_method_id']);
        if (isset($data['payment_method_key'])) $this->db->bind(':payment_method_key', $data['payment_method_key']);
        if (isset($data['banktransferproof'])) $this->db->bind(':banktransferproof', $data['banktransferproof']);
        if (isset($data['app'])) $this->db->bind(':app', $data['app']);

        return $this->db->excute();
    }

    /**
     * addDonation
     *
     * @param  array $data
     *
     * @return void
     */
    public function addDonation($data)
    {
        $this->db->query('INSERT INTO donations (amount, total, quantity, donation_type, order_id, project_id, status, modified_date, create_date)'
            . ' VALUES (:amount, :total, :quantity, :donation_type, :order_id, :project_id, :status, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':total', $data['total']);
        $this->db->bind(':quantity', $data['quantity']);
        $this->db->bind(':donation_type', $data['donation_type']);
        $this->db->bind(':project_id', $data['project_id']);
        $this->db->bind(':order_id', $data['order_id']);
        $this->db->bind(':status', $data['status']);
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
     * saving order data
     *
     * @param array $data
     * @return boolean
     */
    public function addOrder($data)
    {
        $this->db->query('INSERT INTO orders (order_identifier, projects, total, quantity, gift, gift_data, payment_method_id, payment_method_key, banktransferproof, hash, projects_id, donor_id, donor_name, store_id, deceased_id, status, modified_date, create_date)'
            . ' VALUES (:order_identifier, :projects, :total, :quantity, :gift, :gift_data, :payment_method_id, :payment_method_key, :banktransferproof, :hash, :projects_id, :donor_id, :donor_name, :store_id, :deceased_id, :status, :modified_date, :create_date)');
        // binding values
        if (!isset($data['deceased_id'])) $data['deceased_id'] = null;
        $this->db->bind(':order_identifier', $data['order_identifier']);
        $this->db->bind(':gift', $data['gift']);
        $this->db->bind(':gift_data', $data['gift_data']);
        $this->db->bind(':projects', $data['projects']);
        $this->db->bind(':total', $data['total']);
        $this->db->bind(':quantity', $data['quantity']);
        $this->db->bind(':hash', $data['hash']);
        $this->db->bind(':payment_method_id', $data['payment_method_id']);
        $this->db->bind(':payment_method_key', $data['payment_method_key']);
        $this->db->bind(':banktransferproof', @$data['banktransferproof']);
        $this->db->bind(':projects_id', $data['projects_id']);
        $this->db->bind(':donor_id', $data['donor_id']);
        $this->db->bind(':donor_name', $data['donor_name']);
        $this->db->bind(':store_id', $data['store_id']);
        $this->db->bind(':deceased_id', $data['deceased_id']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':create_date', time());
        $this->db->bind(':modified_date', time());
        // excute
        if ($this->db->excute()) {
            $order_id = $this->db->lastId();
            if (isset($data['app'])) $this->updateOrderApp($order_id, $data['app']);
            return $order_id;
        } else {
            return false;
        }
    }

    /**
     * generat uniqe order_identifier
     *
     * @return int
     */
    public function uniqNum($x = false)
    {
        $num = (time() - 580000000) . rand(1111, 9999);
        if ($x) $num = $x;
        $rec =  $this->getSingle('*', ['order_identifier' => $num], 'orders');
        if ($rec) {
            $num = $this->uniqNum();
        }
        return $num;
    }

    /**
     * get projects By Category
     *
     * @param  int  $id
     *
     * @return object
     */
    public function projectsByCategory($id)
    {
        $query = "SELECT pj.*, 
            CONCAT('". MEDIAURL ."/', `pj`.`secondary_image` ) AS secondary_image, 
            CONCAT('". MEDIAURL ."/', `pj`.`background_image` ) AS background_image, 
            CONCAT('". MEDIAURL ."/', `pj`.`image` ) AS image, 
            (SELECT SUM(total) FROM donations WHERE pj.project_id = donations.project_id AND status = 1 LIMIT 1 ) as total
        FROM `projects` pj , project_categories cat
        WHERE (cat.kafara= 'app' OR cat.kafara= 'both') AND (pj.kafara= 'app' OR pj.kafara= 'both') AND pj.status =1 AND pj.category_id = :category_id 
        AND pj.category_id =cat.category_id AND pj.start_date <= " . time() . " AND pj.end_date >= " . time() . " ORDER BY pj.arrangement ASC";
        $this->db->query($query);
        $this->db->bind(":category_id", $id);
        return ($this->db->resultSet());
    }

    /**
     * get kafara App Categories
     *
     * @return object
     */
    public function getAppCategories()
    {
        $query = "SELECT * FROM project_categories WHERE kafara='app' OR kafara='both' AND status =1 ";
        $this->db->query($query);
        return ($this->db->resultSet());
    }


    public function updateOrderApp($order_id, $app)
    {
        $query = 'UPDATE orders SET app = :app  WHERE order_id = :order_id';
        $this->db->query($query);
        // binding values
        $this->db->bind(':app', $app);
        $this->db->bind(':order_id', $order_id);
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * check if the order donor has been notified or not
     *
     * @param  mixed $order_id
     *
     * @return void
     */
    public function notified($order_id)
    {
        $query = 'UPDATE orders SET notified = 1  WHERE order_id = :order_id';
        $this->db->query($query);
        // binding values
        $this->db->bind(':order_id', $order_id);
        // excute
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Badal Projects with Pagination
     * 
     * @param array $types - أنواع المشاريع
     * @param int $start - نقطة البداية
     * @param int $count - عدد السجلات
     * @return array
     */
    public function getTableEdits($types, $start = 0, $count = 20)
    {
        $query = 'SELECT *,
                CONCAT("' . MEDIAURL .  '/", `secondary_image` ) AS secondary_image, 
                CONCAT("' . MEDIAURL .  '/", `background_image` ) AS background_image, 
                CONCAT("' . MEDIAURL .  '/", `image` ) AS image, 
                FROM_UNIXTIME(`start_date`) AS start_date, 
                FROM_UNIXTIME(`end_date`) AS end_date
            FROM `projects` 
            WHERE `badal` = 1
            AND `status` = 1
            AND `badal_type` IN ( ' . implode(",", $types) . ')
            AND CURDATE() BETWEEN FROM_UNIXTIME(`start_date`) AND FROM_UNIXTIME(`end_date`)
            ORDER BY create_date DESC
            LIMIT ' . (int)$start . ', ' . (int)$count;

        $this->db->query($query);
        return $this->db->resultSet();
    }

    /**
     * Get Total Count of Badal Projects
     * 
     * @param array $types - أنواع المشاريع
     * @return int
     */
    public function getTableEditsCount($types)
    {
        $query = 'SELECT COUNT(*) AS total
            FROM `projects` 
            WHERE `badal` = 1
            AND `status` = 1
            AND `badal_type` IN ( ' . implode(",", $types) . ')
            AND CURDATE() BETWEEN FROM_UNIXTIME(`start_date`) AND FROM_UNIXTIME(`end_date`)';

        $this->db->query($query);
        $result = $this->db->single();

        return $result ? (int)$result->total : 0;
    }
    /**
     * get selected projects
     * 
     * @param  mixed $project_ids
     * 
     * @return response
     */
    public function getSelectedProjects($project_ids)
    {
        $ids = str_replace(',', '","', $project_ids);
        $query = 'SELECT  `name`,  CONCAT("' . URLROOT .  '/projects/show/", `project_id` ) AS url, 
        CONCAT("' . MEDIAURL .  '/", `secondary_image` ) AS secondary_image,
        target_price, unit_price, fake_target
                    FROM `projects` WHERE  `project_id` IN ("' . $ids . '") AND status <> 2 ORDER BY create_date DESC ';
        $this->db->query($query);
        return ($this->db->resultSet());
    }


    /**
     * umrah projects
     *
     * @return object projects
     */
    public function getUmrah()
    {
        // prepare Query
        $query = 'SELECT *,
                    CONCAT("' . MEDIAURL .  '/", `secondary_image` ) AS secondary_image, 
                    CONCAT("' . MEDIAURL .  '/", `background_image` ) AS background_image, 
                    CONCAT("' . MEDIAURL .  '/", `background_image` ) AS image, 
                    from_unixtime(`start_date`) AS start_date , 
                    from_unixtime(`end_date`) AS end_date
            FROM `projects`
            WHERE `status` = 1 
            AND `badal` = 1
            AND `badal_type` = "umrah"
            AND CURDATE() BETWEEN FROM_UNIXTIME(`start_date`) AND FROM_UNIXTIME(`end_date`);
            ';

        $this->db->query($query);
        //bind values
        return $this->db->resultSet();
    }

    /**
     * hajj projects
     *
     * @return object projects
     */
    public function getHajj()
    {
        // prepare Query
        $query = 'SELECT *,
                CONCAT("' . MEDIAURL .  '/", `secondary_image` ) AS secondary_image, 
                CONCAT("' . MEDIAURL .  '/", `background_image` ) AS background_image, 
                CONCAT("' . MEDIAURL .  '/", `background_image` ) AS image, 
                from_unixtime(`start_date`) AS start_date , 
                from_unixtime(`end_date`) AS end_date
            FROM `projects`
            WHERE `status` = 1 
            AND `badal` = 1
            AND `badal_type` = "hajj"
            AND CURDATE() BETWEEN FROM_UNIXTIME(`start_date`) AND FROM_UNIXTIME(`end_date`);
            ';

        $this->db->query($query);
        //bind values
        return $this->db->resultSet();
    }



    /**
     * get all Bank accounts
     *
     * @return response
     */
    public function getBankAccounts()
    {
        // prepare Query
        $query = 'SELECT `payment_methods`.meta FROM `payment_methods`
            WHERE `status` = 1 And `payment_id` = 1';
        $this->db->query($query);
        return  $this->db->single();
    }
    /**
     * check if account is closed
     *
     * @return response
     */
    public function checkAccountClose($mobile)
    {
        $query = 'SELECT `donor_id` FROM `donors`
            WHERE `status` <> 2 And `mobile` = ' . $mobile;
        $this->db->query($query);
        return  $this->db->single();
    }


    // load project store hits + 1 where ip is new
    public function storeHits($id, $store_id)
    {
        if( $store_id == null){
            $query = 'SELECT * FROM stores_projects WHERE project_id = :id AND store_id  Is NULL ';
            $this->db->query($query);
      
        }
        else{
            $query = 'SELECT * FROM stores_projects WHERE project_id = :id AND store_id  = :store_id ';
            $this->db->query($query);
            $this->db->bind(':store_id', $store_id);
        }
        $this->db->bind(':id', $id);
        $store_hits = $this->db->single();
        if ($store_hits && $store_id == null) {
            $query = 'UPDATE stores_projects SET hits = hits + 1 WHERE project_id = :id AND store_id IS NULL ';
            $this->db->query($query);
            $this->db->bind(':id', $id);
            $this->db->excute();
        } else if ($store_hits) {
            $query = 'UPDATE stores_projects SET hits = hits + 1 WHERE project_id = :id AND store_id  = :store_id ';
            $this->db->query($query);
            $this->db->bind(':id', $id);
            $this->db->bind(':store_id', $store_id);
            $this->db->excute();
        } else {
            $query = 'INSERT INTO stores_projects (project_id, store_id, hits) VALUES (:id, :store_id, 1)';
            $this->db->query($query);
            $this->db->bind(':id', $id);
            $this->db->bind(':store_id', $store_id);
            $this->db->excute();
        }
    }


    /**
     * saving order data
     *
     * @param array $data
     * @return boolean
     */
    public function saveOrder($data)
    {
        $this->db->query('INSERT INTO orders (order_identifier, projects, total, quantity, payment_method_id, payment_method_key, projects_id, donor_id, donor_name, hash, app, status, modified_date, create_date)'
            . ' VALUES (:order_identifier, :projects, :total, :quantity, :payment_method_id, :payment_method_key, :projects_id, :donor_id, :donor_name, :hash, :app, :status, :modified_date, :create_date)');
        // binding values
        $this->db->bind(':order_identifier', $data['order_identifier']);
        $this->db->bind(':projects', $data['projects']);
        $this->db->bind(':total', $data['total']);
        $this->db->bind(':quantity', $data['quantity']);
        $this->db->bind(':payment_method_id', $data['payment_method_id']);
        $this->db->bind(':payment_method_key', $data['payment_method_key']);
        $this->db->bind(':projects_id', $data['projects_id']);
        $this->db->bind(':donor_id', $data['donor_id']);
        $this->db->bind(':donor_name', $data['donor_name']);
        $this->db->bind(':hash', $data['hash']);
        $this->db->bind(':app', $data['app']);
        $this->db->bind(':status', $data['status']);
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
     * Mark order as processed by webhook
     * @param int $order_id
     * @return bool
     */
    public function markWebhookProcessed($order_id)
    {
        $this->db->query("UPDATE orders SET webhook_processed = 1 WHERE order_id = :id");
        $this->db->bind(':id', $order_id);
        return $this->db->excute();
    }
    /**
     * get order By id
     *
     * @param  mixed $hash
     *
     * @return void
     */
    public function getOrderById($order_id)
    {
        return $this->getSingle('*', ['order_id' => $order_id], 'orders');
    }
}
