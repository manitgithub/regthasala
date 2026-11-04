<?php
namespace app\helpers;
use yii\helpers\{Html, Url};

class Ui
{
    public const APPLICATION_STATUSES = ['payment_review' => 'รอตรวจสอบการชำระเงิน', 'qualification_review' => 'รอตรวจคุณสมบัติ', 'eligible' => 'มีสิทธิ์สอบ', 'correction_required' => 'ส่งกลับแก้ไข', 'rejected' => 'ไม่อนุมัติ', 'incomplete' => 'ใบสมัครเดิมยังไม่สมบูรณ์'];
    public static function icon(string $name): string { return '<i data-lucide="' . Html::encode($name) . '" aria-hidden="true"></i>'; }
    public static function link(string $label, array $url, string $icon = '', string $class = 'button-secondary'): string
    {
        $route = $url[0] ?? '';
        if (($route === 'edit' && \Yii::$app->controller->id === 'admin' || $route === '/admin/edit') && !\Yii::$app->user->isGuest && !\Yii::$app->user->identity->can('rounds.manage')) return '';
        return Html::a(($icon ? self::icon($icon) : '') . Html::encode($label), $url, ['class' => $class, 'aria-current' => in_array('is-active', explode(' ', $class), true) ? 'page' : null]);
    }
    public static function date(?string $value, bool $time = true): string
    {
        if (!$value) return '-';
        $months = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $t = strtotime($value);
        return date('j', $t) . ' ' . $months[(int) date('n', $t)] . ' ' . ((int) date('Y', $t) + 543) . ($time ? ' · ' . date('H:i', $t) : '');
    }
    public static function money($value): string { return number_format((float) $value, 2); }
    public static function select($model, string $attribute, string $label, array $items): string
    {
        $id = Html::getInputId($model, $attribute);
        return '<div class="field">' . Html::label($label, $id) . Html::activeDropDownList($model, $attribute, $items, ['id' => $id, 'class' => 'input', 'prompt' => 'เลือก' . $label, 'required' => $model->isAttributeRequired($attribute)]) . Html::error($model, $attribute, ['class' => 'field-error']) . '</div>';
    }
    public static function status(string $value): string
    {
        $labels = self::APPLICATION_STATUSES + ['draft' => 'แบบร่าง', 'published' => 'เผยแพร่แล้ว', 'closed' => 'ปิดรอบแล้ว', 'ended' => 'สิ้นสุดรับสมัคร', 'scheduled' => 'ยังไม่ถึงวันรับสมัคร', 'setup' => 'ยังไม่พร้อมรับสมัคร', 'open' => 'เปิดรับสมัคร', 'approved' => 'ผ่านคุณสมบัติ', 'pending' => 'รอตรวจสอบ', 'accepted' => 'เอกสารถูกต้อง', 'verified' => 'ตรวจรับชำระแล้ว', 'pending_review' => 'รอตรวจสลิป', 'correction' => 'ส่งกลับแก้ไข'];
        $tone = in_array($value, ['published', 'open', 'approved', 'eligible', 'accepted', 'verified']) ? 'green' : (in_array($value, ['payment_review', 'qualification_review', 'setup', 'correction', 'correction_required', 'pending_review']) ? 'amber' : (in_array($value, ['scheduled', 'pending']) ? 'blue' : ($value === 'rejected' ? 'red' : 'neutral')));
        return Html::tag('span', Html::encode($labels[$value] ?? $value), ['class' => 'badge badge-' . $tone]);
    }
    public static function roundState(\app\models\ExamRound $round): string
    {
        if ($round->status !== 'published') return $round->status;
        if (date('Y-m-d H:i:s') > $round->closes_at) return 'ended';
        if (!$round->hasPaymentAccount()) return 'setup';
        return $round->isOpen() ? 'open' : 'scheduled';
    }
    public static function field($model, string $attribute, string $label, string $type = 'text', array $options = []): string
    {
        $id = Html::getInputId($model, $attribute);
        $opts = array_merge(['id' => $id, 'class' => 'input', 'required' => $model->isAttributeRequired($attribute)], $options);
        $value = $model->$attribute;
        if ($type === 'datetime-local' && $value && strtotime($value)) $value = date('Y-m-d\\TH:i', strtotime($value));
        $input = $type === 'textarea' ? Html::textarea(Html::getInputName($model, $attribute), $value, $opts) : Html::input($type, Html::getInputName($model, $attribute), $value, $opts);
        return '<div class="field">' . Html::label($label, $id) . $input . Html::error($model, $attribute, ['class' => 'field-error']) . '</div>';
    }
}
