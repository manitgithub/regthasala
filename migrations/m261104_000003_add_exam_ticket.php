<?php
use yii\db\Migration;
class m261104_000003_add_exam_ticket extends Migration
{
    public function safeUp()
    {
        $this->createTable('exam_ticket', [
            'application_id' => $this->integer()->notNull(), 'round_id' => $this->integer()->notNull(),
            'exam_number' => $this->string(50)->null(), 'venue' => $this->string(190)->null(),
            'room' => $this->string(100)->null(), 'seat' => $this->string(50)->null(),
            'note' => $this->text()->null(), 'published' => $this->boolean()->notNull()->defaultValue(false),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk_exam_ticket', 'exam_ticket', 'application_id');
        $this->createIndex('uq_exam_number_round', 'exam_ticket', ['round_id', 'exam_number'], true);
        $this->addForeignKey('fk_ticket_application', 'exam_ticket', 'application_id', 'application', 'id', 'CASCADE');
        $this->addForeignKey('fk_ticket_round', 'exam_ticket', 'round_id', 'exam_round', 'id', 'CASCADE');
    }
    public function safeDown() { $this->dropTable('exam_ticket'); }
}
