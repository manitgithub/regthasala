<?php
namespace app\models;
class PaymentForm extends \yii\base\Model
{
    public $slip;
    public $amount;
    public $transferred_at;
    public $transfer_reference;
    public function rules() { return [
        [['amount', 'transferred_at', 'transfer_reference'], 'trim'],
        [['amount', 'transferred_at', 'slip'], 'required'],
        ['amount', 'match', 'pattern' => '/^[0-9]+(?:\.[0-9]{1,2})?$/D'],
        ['amount', 'number', 'min' => 0.01, 'max' => 99999999.99],
        ['transferred_at', 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ['transferred_at', function ($attribute) { if (strtotime($this->$attribute) > time()) $this->addError($attribute, 'วันเวลาโอนต้องไม่อยู่ในอนาคต'); }],
        ['transfer_reference', 'string', 'max' => 190],
        ['slip', 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'pdf'], 'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'], 'maxSize' => 5 * 1024 * 1024],
    ]; }
    public function hasSubmission(): bool { return $this->slip !== null || $this->amount !== null && $this->amount !== '' || $this->transferred_at !== null && $this->transferred_at !== '' || $this->transfer_reference !== null && $this->transfer_reference !== ''; }
    public function attributeLabels() { return ['slip' => 'หลักฐานการโอนเงิน', 'amount' => 'ยอดเงินที่โอน (บาท)', 'transferred_at' => 'วันและเวลาโอน', 'transfer_reference' => 'เลขอ้างอิงการโอน (ถ้ามี)']; }
}
