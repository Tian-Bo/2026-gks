<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiMerchant;
use App\Models\AiShop;
use App\Services\MerchantAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MerchantAuthController extends Controller
{
    public function __construct(private MerchantAuthService $auth)
    {
    }

    public function sendSmsCode(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'regex:/^1\\d{10}$/'], 'cms_type' => ['required', 'integer']]);
        $demoCode = trim((string) env('AI_DEMO_SMS_CODE', ''));
        $code = $demoCode !== '' ? $demoCode : (string) random_int(100000, 999999);
        Cache::put($this->codeKey($data['phone'], (int) $data['cms_type']), $code, now()->addMinutes(5));

        $payload = ['message' => '验证码已发送', 'expires_in' => 300];
        if (config('app.debug')) {
            $payload['debug_code'] = $code;
        }
        return response()->json($payload);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'regex:/^1\\d{10}$/', 'unique:ai_merchants,phone'],
            'password' => ['required', 'regex:/^(?=.*[A-Za-z])(?=.*\\d)[A-Za-z\\d]{6,16}$/'],
            'code' => ['required', 'string'],
        ]);
        $this->assertCode($data['phone'], 1, $data['code']);
        [$merchant, $shop] = $this->auth->register($data['phone'], $data['password']);
        Cache::forget($this->codeKey($data['phone'], 1));

        return response()->json(array_merge($this->auth->issueToken($merchant, $shop), ['default_shop_id' => $shop->id]));
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'regex:/^1\\d{10}$/'], 'password' => ['required', 'string']]);
        $merchant = AiMerchant::query()->where('phone', $data['phone'])->first();
        if (!$merchant || !$this->auth->verifyPassword($merchant, $data['password'])) {
            abort(401, '手机号或密码错误');
        }
        return $this->loginResponse($merchant);
    }

    public function loginByCode(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'regex:/^1\\d{10}$/'], 'code' => ['required', 'string']]);
        $this->assertCode($data['phone'], 2, $data['code']);
        $merchant = AiMerchant::query()->where('phone', $data['phone'])->first();
        if (!$merchant) {
            abort(401, '该手机号尚未注册');
        }
        Cache::forget($this->codeKey($data['phone'], 2));
        return $this->loginResponse($merchant);
    }

    private function loginResponse(AiMerchant $merchant): JsonResponse
    {
        $shop = AiShop::query()->where('merchant_id', $merchant->id)
            ->where('id', $merchant->last_shop_id ?: 0)->first()
            ?: AiShop::query()->where('merchant_id', $merchant->id)->orderByDesc('id')->first();
        $merchant->update(['last_login_at' => now(), 'last_shop_id' => $shop?->id]);
        return response()->json($this->auth->issueToken($merchant->fresh(), $shop));
    }

    private function assertCode(string $phone, int $type, string $code): void
    {
        if ((string) Cache::get($this->codeKey($phone, $type)) !== $code) {
            abort(422, '验证码错误或已过期');
        }
    }

    private function codeKey(string $phone, int $type): string
    {
        return 'kl_ai_sms:' . $type . ':' . $phone;
    }
}
