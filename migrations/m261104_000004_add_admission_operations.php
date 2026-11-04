<?php
use yii\db\Migration;
class m261104_000004_add_admission_operations extends Migration
{
    public function safeUp()
    {
        $this->addColumn('user', 'active', $this->boolean()->notNull()->defaultValue(true));
        foreach (['qualification_status' => $this->string(24)->notNull()->defaultValue('pending'), 'qualification_reason' => $this->text()->null(), 'reviewed_by' => $this->integer()->null(), 'reviewed_at' => $this->dateTime()->null()] as $key => $type) $this->addColumn('application', $key, $type);
        foreach (['review_status' => $this->string(24)->notNull()->defaultValue('pending'), 'review_reason' => $this->text()->null(), 'reviewed_by' => $this->integer()->null(), 'reviewed_at' => $this->dateTime()->null(), 'is_current' => $this->boolean()->notNull()->defaultValue(true)] as $key => $type) $this->addColumn('application_document', $key, $type);
        foreach (['review_reason' => $this->text()->null(), 'reviewed_by' => $this->integer()->null(), 'reviewed_at' => $this->dateTime()->null()] as $key => $type) $this->addColumn('application_payment', $key, $type);
        $this->addColumn('subject', 'max_score', $this->decimal(7, 2)->notNull()->defaultValue(100));
        $this->addColumn('exam_round', 'results_published_at', $this->dateTime()->null());
        $this->createTable('exam_room', ['id' => $this->primaryKey(), 'round_id' => $this->integer()->notNull(), 'venue' => $this->string(190)->notNull(), 'name' => $this->string(100)->notNull(), 'capacity' => $this->integer()->notNull()]);
        $this->addForeignKey('fk_room_round', 'exam_room', 'round_id', 'exam_round', 'id', 'CASCADE');
        $this->createIndex('uq_room_name', 'exam_room', ['round_id', 'venue', 'name'], true);
        $this->addColumn('exam_ticket', 'room_id', $this->integer()->null());
        $this->addForeignKey('fk_ticket_room', 'exam_ticket', 'room_id', 'exam_room', 'id', 'RESTRICT');
        $this->createIndex('uq_room_seat', 'exam_ticket', ['room_id', 'seat'], true);
        $this->createTable('exam_score', ['item_id' => $this->integer()->notNull(), 'score' => $this->decimal(7, 2)->null(), 'attendance' => $this->string(20)->notNull(), 'updated_by' => $this->integer()->notNull(), 'updated_at' => $this->dateTime()->notNull()]);
        $this->addPrimaryKey('pk_exam_score', 'exam_score', 'item_id');
        $this->addForeignKey('fk_score_item', 'exam_score', 'item_id', 'application_subject', 'id', 'CASCADE');
        $this->createTable('application_result', ['application_id' => $this->integer()->notNull(), 'outcome' => $this->string(20)->notNull(), 'note' => $this->text()->null(), 'updated_by' => $this->integer()->notNull(), 'updated_at' => $this->dateTime()->notNull()]);
        $this->addPrimaryKey('pk_application_result', 'application_result', 'application_id');
        $this->addForeignKey('fk_result_application', 'application_result', 'application_id', 'application', 'id', 'CASCADE');
        $this->createTable('audit_log', ['id' => $this->bigPrimaryKey(), 'actor_id' => $this->integer()->null(), 'actor_name' => $this->string(190)->notNull(), 'action' => $this->string(80)->notNull(), 'entity' => $this->string(80)->notNull(), 'entity_id' => $this->string(64)->null(), 'details' => $this->json()->null(), 'created_at' => $this->dateTime()->notNull()]);
        $this->createIndex('idx_audit_time', 'audit_log', 'created_at');
        $this->createIndex('idx_audit_action', 'audit_log', 'action');
    }
    public function safeDown() { echo "This migration retains review and audit history; rollback is not supported.\n"; return false; }
}
