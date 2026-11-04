<?php
use yii\db\Migration;

class m261104_000005_add_exam_number_sequence extends Migration
{
    public function safeUp()
    {
        $this->addColumn('exam_round', 'exam_number_prefix', $this->string(30)->null());
        $this->addColumn('exam_round', 'exam_number_digits', $this->integer()->notNull()->defaultValue(6));
        $this->addColumn('exam_round', 'exam_number_start', $this->integer()->notNull()->defaultValue(1));
        $this->addColumn('exam_round', 'exam_number_next', $this->integer()->notNull()->defaultValue(1));
    }
    public function safeDown()
    {
        foreach (['exam_number_prefix', 'exam_number_digits', 'exam_number_start', 'exam_number_next'] as $column) $this->dropColumn('exam_round', $column);
    }
}
