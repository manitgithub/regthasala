<?php
use yii\db\Migration;
class m261103_000003_add_school_admissions extends Migration
{
    public function safeUp()
    {
        $this->addColumn('exam_round', 'registration_mode', $this->string(20)->notNull()->defaultValue('subjects'));
        $this->addColumn('exam_round', 'is_example', $this->boolean()->notNull()->defaultValue(false));
        $this->createTable('admission_program', [
            'id' => $this->primaryKey(), 'round_id' => $this->integer()->notNull(), 'code' => $this->string(30)->notNull(),
            'name' => $this->string(190)->notNull(), 'target_grade' => $this->string(10)->notNull(),
            'academic_year' => $this->integer()->notNull(), 'intake' => $this->integer()->notNull(),
            'gpa_basis' => $this->string(190)->notNull(), 'minimum_gpa' => $this->decimal(3, 2)->notNull()->defaultValue(0),
            'minimum_foreign_gpa' => $this->decimal(3, 2)->null(), 'application_fee' => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'enrollment_fee' => $this->decimal(10, 2)->null(), 'status' => $this->string(20)->notNull()->defaultValue('open'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        $this->createIndex('uq_program_round_code', 'admission_program', ['round_id', 'code'], true);
        $this->addForeignKey('fk_program_round', 'admission_program', 'round_id', 'exam_round', 'id', 'RESTRICT');
        $this->createTable('program_subject', ['program_id' => $this->integer()->notNull(), 'subject_id' => $this->integer()->notNull(), 'PRIMARY KEY ([[program_id]], [[subject_id]])']);
        $this->addForeignKey('fk_ps_program', 'program_subject', 'program_id', 'admission_program', 'id', 'CASCADE');
        $this->addForeignKey('fk_ps_subject', 'program_subject', 'subject_id', 'subject', 'id', 'RESTRICT');
        $this->addColumn('application', 'program_id', $this->integer()->null());
        $this->addColumn('application', 'application_fee', $this->decimal(10, 2)->notNull()->defaultValue(0));
        $this->addColumn('application', 'admission_snapshot', $this->json()->null());
        $this->addForeignKey('fk_application_program', 'application', 'program_id', 'admission_program', 'id', 'RESTRICT');
        $this->createTable('application_profile', [
            'id' => $this->primaryKey(), 'application_id' => $this->integer()->notNull()->unique(),
            'data' => $this->json()->notNull(),
        ]);
        $this->addForeignKey('fk_profile_application', 'application_profile', 'application_id', 'application', 'id', 'CASCADE');
        $this->createTable('application_document', [
            'id' => $this->primaryKey(), 'application_id' => $this->integer()->notNull(),
            'kind' => $this->string(40)->notNull(), 'stored_name' => $this->string(100)->notNull(),
            'original_name' => $this->string(255)->notNull(), 'mime_type' => $this->string(100)->notNull(), 'size' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey('fk_document_application', 'application_document', 'application_id', 'application', 'id', 'CASCADE');
    }
    public function safeDown()
    {
        $this->dropTable('application_document'); $this->dropTable('application_profile');
        $this->dropForeignKey('fk_application_program', 'application');
        foreach (['program_id', 'application_fee', 'admission_snapshot'] as $column) $this->dropColumn('application', $column);
        $this->dropTable('program_subject'); $this->dropTable('admission_program');
        $this->dropColumn('exam_round', 'registration_mode'); $this->dropColumn('exam_round', 'is_example');
    }
}
