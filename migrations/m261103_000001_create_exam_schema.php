<?php
use yii\db\Migration;

class m261103_000001_create_exam_schema extends Migration
{
    public function safeUp()
    {
        $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        $this->createTable('user', [
            'id' => $this->primaryKey(), 'email' => $this->string(190)->notNull()->unique(),
            'name' => $this->string(190)->notNull(), 'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string(64)->notNull(), 'role' => $this->string(32)->notNull()->defaultValue('applicant'),
            'created_at' => $this->dateTime()->notNull(),
        ], $options);
        $this->createTable('exam_type', [
            'id' => $this->primaryKey(), 'name' => $this->string(190)->notNull()->unique(),
        ], $options);
        $this->createTable('exam_round', [
            'id' => $this->primaryKey(), 'exam_type_id' => $this->integer()->notNull(),
            'name' => $this->string(190)->notNull(), 'description' => $this->text(),
            'opens_at' => $this->dateTime()->notNull(), 'closes_at' => $this->dateTime()->notNull(),
            'payment_due_at' => $this->dateTime()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('draft'),
            'required_documents' => $this->text(),
        ], $options);
        $this->addForeignKey('fk_round_type', 'exam_round', 'exam_type_id', 'exam_type', 'id', 'RESTRICT');
        $this->createTable('subject', [
            'id' => $this->primaryKey(), 'round_id' => $this->integer()->notNull(),
            'code' => $this->string(40)->notNull(), 'name' => $this->string(190)->notNull(),
            'starts_at' => $this->dateTime()->notNull(), 'ends_at' => $this->dateTime()->notNull(),
            'capacity' => $this->integer()->notNull(), 'fee' => $this->decimal(10, 2)->notNull(),
            'venue' => $this->string(190), 'status' => $this->string(20)->notNull()->defaultValue('open'),
        ], $options);
        $this->createIndex('uq_subject_round_code', 'subject', ['round_id', 'code'], true);
        $this->addForeignKey('fk_subject_round', 'subject', 'round_id', 'exam_round', 'id', 'RESTRICT');
        $this->createTable('milestone', [
            'id' => $this->primaryKey(), 'round_id' => $this->integer()->notNull(),
            'title' => $this->string(190)->notNull(), 'occurs_at' => $this->dateTime()->notNull(), 'note' => $this->text(),
        ], $options);
        $this->addForeignKey('fk_milestone_round', 'milestone', 'round_id', 'exam_round', 'id', 'CASCADE');
        $this->createTable('application', [
            'id' => $this->primaryKey(), 'reference' => $this->string(32)->notNull()->unique(),
            'user_id' => $this->integer()->notNull(), 'round_id' => $this->integer()->notNull(),
            'total' => $this->decimal(10, 2)->notNull(), 'status' => $this->string(32)->notNull()->defaultValue('pending_payment'),
            'created_at' => $this->dateTime()->notNull(),
        ], $options);
        $this->createIndex('uq_application_user_round', 'application', ['user_id', 'round_id'], true);
        $this->addForeignKey('fk_application_user', 'application', 'user_id', 'user', 'id', 'RESTRICT');
        $this->addForeignKey('fk_application_round', 'application', 'round_id', 'exam_round', 'id', 'RESTRICT');
        $this->createTable('application_subject', [
            'id' => $this->primaryKey(), 'application_id' => $this->integer()->notNull(),
            'subject_id' => $this->integer()->notNull(), 'fee' => $this->decimal(10, 2)->notNull(),
            'payment_status' => $this->string(20)->notNull()->defaultValue('unpaid'),
        ], $options);
        $this->createIndex('uq_application_subject', 'application_subject', ['application_id', 'subject_id'], true);
        $this->addForeignKey('fk_item_application', 'application_subject', 'application_id', 'application', 'id', 'CASCADE');
        $this->addForeignKey('fk_item_subject', 'application_subject', 'subject_id', 'subject', 'id', 'RESTRICT');
    }

    public function safeDown()
    {
        foreach (['application_subject', 'application', 'milestone', 'subject', 'exam_round', 'exam_type', 'user'] as $table) {
            $this->dropTable($table);
        }
    }
}
