<?php
use yii\db\Migration;
class m261104_000001_add_application_payments extends Migration
{
    public function safeUp()
    {
        foreach (['payment_bank', 'payment_account', 'payment_account_name'] as $column) $this->addColumn('exam_round', $column, $this->string(190)->null());
        $this->addColumn('exam_round', 'payment_instructions', $this->text()->null());
        $this->createTable('application_payment', [
            'application_id' => $this->integer()->notNull(), 'document_id' => $this->integer()->notNull(),
            'amount' => $this->decimal(10, 2)->notNull(), 'transferred_at' => $this->dateTime()->notNull(),
            'transfer_reference' => $this->string(190)->null(), 'status' => $this->string(32)->notNull()->defaultValue('pending_review'),
            'account_snapshot' => $this->json()->notNull(), 'created_at' => $this->dateTime()->notNull(),
        ]);
        $this->addPrimaryKey('pk_application_payment', 'application_payment', 'application_id');
        $this->addForeignKey('fk_payment_application', 'application_payment', 'application_id', 'application', 'id', 'CASCADE');
        $this->addForeignKey('fk_payment_document', 'application_payment', 'document_id', 'application_document', 'id', 'CASCADE');
    }
    public function safeDown()
    {
        $this->dropTable('application_payment');
        foreach (['payment_bank', 'payment_account', 'payment_account_name', 'payment_instructions'] as $column) $this->dropColumn('exam_round', $column);
    }
}
