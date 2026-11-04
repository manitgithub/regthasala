<?php
use yii\db\Migration;
class m261104_000002_require_payment_with_application extends Migration
{
    public function safeUp()
    {
        $this->update('application', ['status' => 'incomplete'], ['status' => 'pending_payment']);
        $this->alterColumn('application', 'status', $this->string(32)->notNull()->defaultValue('incomplete'));
    }
    public function safeDown()
    {
        $this->update('application', ['status' => 'pending_payment'], ['status' => 'incomplete']);
        $this->alterColumn('application', 'status', $this->string(32)->notNull()->defaultValue('pending_payment'));
    }
}
