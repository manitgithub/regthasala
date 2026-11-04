<?php
use app\services\ThaiAddressCatalog;
use yii\helpers\Html;
$id = Html::getInputId($model, 'subdistrict_code') . '-search';
$selected = ThaiAddressCatalog::find($model->subdistrict_code);
?>
<div class="field thai-address-field col-span-full" data-thai-address>
<?= Html::label('ตำบล / อำเภอ / จังหวัด / รหัสไปรษณีย์', $id) ?>
<div class="address-combobox">
<?= Html::input('search', null, $selected ? ThaiAddressCatalog::label($selected) : '', ['id' => $id, 'class' => 'input', 'placeholder' => 'ค้นหาตำบล อำเภอ จังหวัด หรือรหัสไปรษณีย์', 'autocomplete' => 'off', 'required' => true, 'role' => 'combobox', 'aria-expanded' => 'false', 'aria-controls' => $id . '-list', 'aria-autocomplete' => 'list', 'aria-describedby' => $id . '-status', 'data-address-query' => '', 'maxlength' => 300]) ?>
<ul id="<?= $id ?>-list" class="address-suggestions" role="listbox" aria-label="พื้นที่ที่ตรงกับคำค้น" hidden></ul>
</div>
<?php foreach (['subdistrict_code', 'subdistrict', 'district', 'province', 'postal_code'] as $attribute): ?><?= Html::activeHiddenInput($model, $attribute, ['data-address-part' => $attribute]) ?><?php endforeach ?>
<div class="address-feedback"><p id="<?= $id ?>-status" data-address-status role="status" class="text-sm text-muted">กำลังโหลดข้อมูลพื้นที่...</p><button type="button" class="button-quiet" data-address-retry hidden>โหลดข้อมูลอีกครั้ง</button></div>
<?= Html::error($model, 'subdistrict_code', ['class' => 'field-error']) ?>
</div>
