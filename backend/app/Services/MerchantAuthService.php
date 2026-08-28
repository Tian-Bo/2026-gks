<?php

namespace App\Services;

use App\Models\AiItem;
use App\Models\AiConversation;
use App\Models\AiMerchant;
use App\Models\AiPointAccount;
use App\Models\AiShop;
use App\Models\MerchantAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantAuthService
{
    public function register(string $phone, string $password): array
    {
        $merchant = new AiMerchant([
            'phone' => $phone,
            'name' => '我的门店',
            'password' => Hash::make($password),
        ]);
        // Keep generated identities above existing numeric ranges to avoid overlap.
        $merchant->id = $this->nextMerchantId();
        $merchant->save();

        $shop = new AiShop([
            'merchant_id' => $merchant->id,
            'name' => '我的门店',
            'is_default' => true,
        ]);
        $shop->id = $this->nextShopId();
        $shop->save();
        $merchant->update(['last_shop_id' => $shop->id]);
        AiPointAccount::firstOrCreate(['shop_id' => $shop->id], [
            'merchant_id' => $merchant->id,
            'balance' => 0,
            'monthly_grant_remaining' => 0,
            'trial_activity_remaining' => 1,
            'trial_poster_remaining' => 1,
        ]);
        $this->createDefaultItems($merchant, $shop);

        return [$merchant->fresh(), $shop];
    }

    public function verifyPassword(AiMerchant $merchant, string $password): bool
    {
        return Hash::check($password, $merchant->password);
    }

    public function issueToken(int $merchantId, ?int $shopId = null): array
    {
        $token = Str::random(64);
        MerchantAccessToken::query()->where('merchant_id', $merchantId)->where('expires_at', '<=', now())->delete();
        MerchantAccessToken::create([
            'merchant_id' => $merchantId,
            'shop_id' => $shopId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token' => $token,
            'expires_in' => 30 * 24 * 60 * 60,
            'shop_id' => $shopId,
        ];
    }

    private function createDefaultItems(AiMerchant $merchant, AiShop $shop): void
    {
        $items = [
            ['type' => 'package', 'title' => '新客体验套餐', 'base_price' => 99, 'stock' => null],
            ['type' => 'single', 'title' => '门店招牌单品', 'base_price' => 199, 'stock' => 100],
            ['type' => 'stored_value', 'title' => '会员储值卡', 'base_price' => 500, 'stock' => null],
        ];
        foreach ($items as $item) {
            AiItem::create(array_merge($item, [
                'merchant_id' => $merchant->id,
                'shop_id' => $shop->id,
                'status' => 1,
            ]));
        }
    }

    private function nextMerchantId(): int
    {
        return max(
            1000000,
            (int) AiMerchant::query()->max('id') + 1,
            (int) AiConversation::query()->max('merchant_id') + 1,
            (int) AiPointAccount::query()->max('merchant_id') + 1,
        );
    }

    private function nextShopId(): int
    {
        return max(
            1000000,
            (int) AiShop::query()->max('id') + 1,
            (int) AiConversation::query()->max('shop_id') + 1,
            (int) AiPointAccount::query()->max('shop_id') + 1,
        );
    }
}
