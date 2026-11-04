<?php
namespace app\services;
class ThaiAddressCatalog
{
    private static ?array $rows = null;
    public static function find($code): ?array
    {
        if (!is_scalar($code) || !preg_match('/^[0-9]{6}$/D', (string) $code)) return null;
        if (self::$rows === null) {
            $data = json_decode(file_get_contents(\Yii::getAlias('@app/web/data/thai-address/address.json')), true, 512, JSON_THROW_ON_ERROR);
            $provinces = array_column($data['p'], 1, 0); $districts = array_column($data['d'], 1, 0);
            self::$rows = [];
            foreach ($data['s'] as $row) {
                $districtCode = intdiv($row[0], 100); $provinceCode = intdiv($districtCode, 100);
                self::$rows[(string) $row[0]] = ['subdistrict_code' => (string) $row[0], 'subdistrict' => $row[1], 'district' => $districts[$districtCode], 'province' => $provinces[$provinceCode], 'postal_code' => (string) $row[3]];
            }
        }
        return self::$rows[(string) $code] ?? null;
    }
    public static function label(array $row): string
    {
        $bangkok = str_starts_with($row['subdistrict_code'], '10');
        return ($bangkok ? 'แขวง' : 'ตำบล') . $row['subdistrict'] . ' ' . ($bangkok ? 'เขต' : 'อำเภอ') . $row['district'] . ' ' . ($bangkok ? '' : 'จังหวัด') . $row['province'] . ' ' . $row['postal_code'];
    }
}
