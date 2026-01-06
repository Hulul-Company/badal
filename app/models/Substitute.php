<?php

/**
 * Substitute Model - Updated Version
 * 
 * نموذج المنفذين مع دعم حفظ نسبة العمولة وقت الإسناد
 */

class Substitute extends Model
{
    public function __construct()
    {
        parent::__construct('substitutes');
    }

    /**
     * إسناد منفذ لطلب بدل مع حفظ نسبة العمولة
     * ✅ النسخة المحدثة بدون Transactions
     * 
     * @param array $data ['substitute_id', 'badal_id']
     * @return boolean
     */
    public function selectSubstitutes($data)
    {
        // 1️⃣ جلب نسبة العمولة الحالية للمنفذ
        $this->db->query('SELECT proportion FROM substitutes WHERE substitute_id = :substitute_id');
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $this->db->excute();
        $substitute = $this->db->single();

        // ✅ التحقق من وجود المنفذ
        if (!$substitute) {
            return false; // المنفذ غير موجود
        }

        $proportion = (float) $substitute->proportion;

        // 2️⃣ تحديث badal_orders مع حفظ النسبة
        $query = 'UPDATE `badal_orders` 
                  SET `substitute_id` = :substitute_id, 
                      `substitute_proportion` = :substitute_proportion,
                      `modified_date` = :modified_date  
                  WHERE `badal_id` = :badal_id';

        $this->db->query($query);
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $this->db->bind(':substitute_proportion', $proportion);
        $this->db->bind(':modified_date', time());
        $this->db->bind(':badal_id', $data['badal_id']);

        // ✅ تنفيذ التحديث
        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * الحصول على تفاصيل المنفذ مع حساب إجمالي العمولات
     * 
     * @param int $substitute_id
     * @return object|null
     */
    public function getSubstituteWithCommissions($substitute_id)
    {
        $query = 'SELECT 
            s.*,
            COUNT(bo.badal_id) AS total_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS total_commission_earned,
            SUM(CASE WHEN bo.complete_at IS NULL AND bo.start_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS pending_commission
        FROM substitutes s
        LEFT JOIN badal_orders bo ON s.substitute_id = bo.substitute_id AND bo.status = 1
        WHERE s.substitute_id = :substitute_id
        GROUP BY s.substitute_id';

        $this->db->query($query);
        $this->db->bind(':substitute_id', $substitute_id);
        $this->db->excute();
        return $this->db->single();
    }

    /**
     * إضافة منفذ جديد
     * 
     * @param array $data
     * @return boolean
     */
    public function addSubstitute($data)
    {
        $this->db->query('INSERT INTO `substitutes`(
            image, full_name, identity, phone, nationality, gender, 
            email, languages, proportion, status, modified_date, create_date
        ) VALUES (
            :image, :full_name, :identity, :phone, :nationality, :gender, 
            :email, :languages, :proportion, :status, :modified_date, :create_date
        )');

        // binding values
        $this->db->bind(':identity', $data['identity']);
        $this->db->bind(':image', $data['image']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':nationality', $data['nationality']);
        $this->db->bind(':gender', $data['gender']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':languages', implode(',', $data['languages']));
        $this->db->bind(':proportion', isset($data['proportion']) ? $data['proportion'] : 0);
        $this->db->bind(':status', '0');
        $this->db->bind(':create_date', time());
        $this->db->bind(':modified_date', time());

        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * تحديث نسبة عمولة المنفذ
     * 
     * @param int $substitute_id
     * @param float $proportion
     * @return boolean
     */
    public function updateSubstituteProportion($substitute_id, $proportion)
    {
        $query = 'UPDATE `substitutes` 
                  SET `proportion` = :proportion, 
                      `modified_date` = :modified_date 
                  WHERE `substitute_id` = :substitute_id';

        $this->db->query($query);
        $this->db->bind(':substitute_id', $substitute_id);
        $this->db->bind(':proportion', $proportion);
        $this->db->bind(':modified_date', time());

        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * الحصول على قائمة المنفذين مع إحصائيات العمولات
     * 
     * @return array
     */
    public function getSubstitutesWithStats()
    {
        $query = 'SELECT 
            s.substitute_id,
            s.full_name,
            s.gender,
            s.proportion,
            s.phone,
            s.email,
            s.create_date,
            COUNT(bo.badal_id) AS total_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN bo.complete_at IS NOT NULL THEN bo.total * bo.substitute_proportion / 100 ELSE 0 END) AS total_commission
        FROM `substitutes` s
        LEFT JOIN `badal_orders` bo ON s.substitute_id = bo.substitute_id AND bo.status = 1
        WHERE s.`status` <> 2
        GROUP BY s.substitute_id
        ORDER BY s.create_date DESC';

        $this->db->query($query);
        $this->db->excute();
        return $this->db->resultSet();
    }

    /**
     * الحصول على بيانات منفذ واحد
     * 
     * @param int $substitute_id
     * @return object|null
     */
    public function getSubstituteById($substitute_id)
    {
        $this->db->query('SELECT * FROM `substitutes` WHERE substitute_id = :substitute_id');
        $this->db->bind(':substitute_id', $substitute_id);
        $this->db->excute();
        return $this->db->single();
    }

    /**
     * تحديث بيانات منفذ
     * 
     * @param array $data
     * @return boolean
     */
    public function updateSubstitute($data)
    {
        $query = 'UPDATE `substitutes` 
                  SET full_name = :full_name,
                      identity = :identity,
                      phone = :phone,
                      nationality = :nationality,
                      gender = :gender,
                      email = :email,
                      languages = :languages,
                      proportion = :proportion,
                      modified_date = :modified_date';

        // إذا كان فيه صورة جديدة
        if (!empty($data['image'])) {
            $query .= ', image = :image';
        }

        $query .= ' WHERE substitute_id = :substitute_id';

        $this->db->query($query);
        $this->db->bind(':substitute_id', $data['substitute_id']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':identity', $data['identity']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':nationality', $data['nationality']);
        $this->db->bind(':gender', $data['gender']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':languages', implode(',', $data['languages']));
        $this->db->bind(':proportion', $data['proportion']);
        $this->db->bind(':modified_date', time());

        if (!empty($data['image'])) {
            $this->db->bind(':image', $data['image']);
        }

        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * حذف منفذ (Soft Delete)
     * 
     * @param int $substitute_id
     * @return boolean
     */
    public function deleteSubstitute($substitute_id)
    {
        $query = 'UPDATE `substitutes` 
                  SET status = 2, 
                      modified_date = :modified_date 
                  WHERE substitute_id = :substitute_id';

        $this->db->query($query);
        $this->db->bind(':substitute_id', $substitute_id);
        $this->db->bind(':modified_date', time());

        if ($this->db->excute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * الحصول على المنفذين المتاحين حسب الجنس واللغة
     * 
     * @param string $gender
     * @param string $language
     * @return array
     */
    public function getAvailableSubstitutes($gender, $language)
    {
        $query = 'SELECT * FROM `substitutes` 
                  WHERE status = 1 
                  AND gender = :gender 
                  AND FIND_IN_SET(:language, languages) > 0
                  ORDER BY proportion ASC, create_date DESC';

        $this->db->query($query);
        $this->db->bind(':gender', $gender);
        $this->db->bind(':language', $language);
        $this->db->excute();
        return $this->db->resultSet();
    }
    public function getActiveSubstitutes()
{
    $query = "
        SELECT s.*, d.donor_id, ft.fcm_token, d.email, d.mobile, d.full_name
        FROM substitutes s
        LEFT JOIN donors d ON s.phone = d.mobile AND d.is_substitute = 1
        LEFT JOIN fcm_tokens ft ON ft.donor_id = d.donor_id
        WHERE s.status = 1
        AND ft.fcm_token IS NOT NULL
    ";

    $this->db->query($query);
    return $this->db->resultSet();
}
}
