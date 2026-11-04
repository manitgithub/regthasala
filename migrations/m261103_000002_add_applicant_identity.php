<?php
use yii\db\Migration;

class m261103_000002_add_applicant_identity extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('user', 'email', $this->string(190)->null());
        $this->addColumn('user', 'national_id', $this->string(13)->null());
        $this->addColumn('user', 'birth_date_hash', $this->string()->null());
        $this->createIndex('uq_user_national_id', 'user', 'national_id', true);
    }

    public function safeDown()
    {
        echo "Applicant identity migration cannot be reverted without losing account credentials.\n";
        return false;
    }
}
