<?php
use yii\db\Migration;
class m261103_000004_extend_milestone_details extends Migration
{
    public function safeUp()
    {
        $this->addColumn('milestone', 'ends_at', $this->dateTime()->null());
        $this->addColumn('milestone', 'venue', $this->string(190)->null());
    }
    public function safeDown()
    {
        $this->dropColumn('milestone', 'venue');
        $this->dropColumn('milestone', 'ends_at');
    }
}
