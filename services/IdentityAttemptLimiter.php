<?php
namespace app\services;

class IdentityAttemptLimiter
{
    public function assertAllowed(string $nationalId): void
    {
        foreach ($this->keys($nationalId) as $key) {
            if ((int) \Yii::$app->cache->get($key) >= 10) throw new \DomainException('ลองเข้าสู่ระบบหลายครั้ง กรุณาลองใหม่ใน 15 นาที');
        }
    }
    public function failure(string $nationalId): void
    {
        foreach ($this->keys($nationalId) as $key) {
            \Yii::$app->cache->set($key, (int) \Yii::$app->cache->get($key) + 1, 900);
        }
    }
    private function keys(string $nationalId): array
    {
        $key = \Yii::$app->request->cookieValidationKey;
        return ['identity-ip:' . hash_hmac('sha256', \Yii::$app->request->userIP, $key), 'identity-id:' . hash_hmac('sha256', $nationalId, $key)];
    }
}
