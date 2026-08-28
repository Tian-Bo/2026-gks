<template>
  <a-modal
    :open="modelValue"
    :footer="null"
    :closable="false"
    :mask-closable="true"
    :centered="true"
    :width="400"
    wrap-class-name="kl-login-guide-modal-wrap"
    @cancel="close"
  >
    <div class="login-guide">
      <button type="button" class="login-guide__close" aria-label="关闭登录提示" @click="close">
        <i class="iconfont icon-guanbi" />
      </button>
      <div class="login-guide__mark">快灵</div>
      <h3>{{ mode === 'register' ? '注册商家账号' : '登录后即可开始生成' }}</h3>
      <p>{{ mode === 'register' ? '注册完成后将自动进入默认店铺。' : '登录后将自动切换至当前店铺。' }}</p>
      <div class="login-guide__tabs">
        <button :class="{ active: mode === 'password' }" type="button" @click="mode = 'password'">密码登录</button>
        <button :class="{ active: mode === 'code' }" type="button" @click="mode = 'code'">验证码登录</button>
        <button :class="{ active: mode === 'register' }" type="button" @click="mode = 'register'">注册</button>
      </div>
      <form class="login-guide__form" @submit.prevent="submit">
        <input v-model.trim="phone" inputmode="numeric" autocomplete="tel" maxlength="11" placeholder="请输入手机号" />
        <div v-if="mode !== 'password'" class="login-guide__code-row">
          <input v-model.trim="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="请输入验证码" />
          <button type="button" :disabled="countdown > 0 || submitting" @click="sendCode">
            {{ countdown ? `${countdown}s 后重发` : '获取验证码' }}
          </button>
        </div>
        <input v-if="mode !== 'code'" v-model.trim="password" type="password" autocomplete="current-password" maxlength="16" placeholder="请输入6-16位字母和数字密码" />
        <label class="login-guide__agreement"><input v-model="agreed" type="checkbox" /> 我已阅读并同意用户协议和隐私政策</label>
        <button class="login-guide__submit" :disabled="submitting" type="submit">{{ submitting ? '处理中...' : mode === 'register' ? '注册并进入店铺' : '登录' }}</button>
      </form>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { onUnmounted, ref } from 'vue'
import api, { saveSelectedShopToken } from '../../standalone/api'
import { setStore } from '../../standalone/storage'
import { klbMessage } from '../../standalone/klbMessage'

defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean]; authenticated: [] }>()
type LoginMode = 'password' | 'code' | 'register'

const mode = ref<LoginMode>('password')
const phone = ref('')
const code = ref('')
const password = ref('')
const agreed = ref(true)
const submitting = ref(false)
const countdown = ref(0)
let countdownTimer: ReturnType<typeof setInterval> | null = null

const phonePattern = /^1\d{10}$/
const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,16}$/

function close() {
  emit('update:modelValue', false)
}

function validate(includePassword: boolean) {
  if (!phonePattern.test(phone.value)) {
    klbMessage.warning('请输入正确的手机号')
    return false
  }
  if (!agreed.value) {
    klbMessage.warning('请先阅读并同意用户协议')
    return false
  }
  if (mode.value !== 'password' && !code.value) {
    klbMessage.warning('请输入验证码')
    return false
  }
  if (includePassword && !passwordPattern.test(password.value)) {
    klbMessage.warning('请输入6-16位字母和数字组合密码')
    return false
  }
  return true
}

function startCountdown() {
  countdown.value = 60
  countdownTimer = setInterval(() => {
    if (countdown.value <= 1) {
      if (countdownTimer) clearInterval(countdownTimer)
      countdownTimer = null
      countdown.value = 0
      return
    }
    countdown.value -= 1
  }, 1000)
}

async function sendCode() {
  if (!phonePattern.test(phone.value)) {
    klbMessage.warning('请输入正确的手机号')
    return
  }
  try {
    await api.auth.sendSmsCode({ phone: phone.value, cms_type: mode.value === 'register' ? 1 : 2 })
    startCountdown()
    klbMessage.success('验证码已发送')
  }
  catch (error: any) {
    klbMessage.error(error?.message || '验证码发送失败')
  }
}

async function persistShopToken(auth: { access_token: string; shop_id?: number; default_shop_id?: number }) {
  const merchantToken = String(auth.access_token || '').trim()
  if (!merchantToken)
    throw new Error('登录成功但未返回令牌')

  const shops = await api.auth.getShops(merchantToken)
  // `shop_id` is the merchant's last selected shop returned by the login API.
  // The list endpoint echoes it as `current_shop_id`; use the default shop only
  // when the account has no remembered shop yet.
  const lastLoginShopId = Number(auth.shop_id || shops.current_shop_id || 0)
  const selectedShopId = lastLoginShopId || Number(auth.default_shop_id || shops.items?.[0]?.id || 0)
  if (!selectedShopId)
    throw new Error('当前账号暂无可用店铺')

  const shopAuth = await api.auth.selectShop(selectedShopId, merchantToken)
  const shopToken = String(shopAuth.access_token || '').trim()
  if (!shopToken)
    throw new Error('店铺令牌获取失败')

  saveSelectedShopToken(shopToken)
  setStore('shop_id', Number(shopAuth.shop_id || selectedShopId))
}

async function submit() {
  if (submitting.value || !validate(mode.value !== 'code'))
    return

  submitting.value = true
  try {
    const auth = mode.value === 'password'
      ? await api.auth.loginByPassword({ phone: phone.value, password: password.value })
      : mode.value === 'code'
        ? await api.auth.loginByCode({ phone: phone.value, code: code.value })
        : await api.auth.register({ phone: phone.value, password: password.value, code: code.value })
    await persistShopToken(auth)
    klbMessage.success(mode.value === 'register' ? '注册成功，已进入店铺' : '登录成功')
    close()
    emit('authenticated')
  }
  catch (error: any) {
    klbMessage.error(error?.message || (mode.value === 'register' ? '注册失败' : '登录失败'))
  }
  finally {
    submitting.value = false
  }
}

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer)
})
</script>

<style scoped>
.login-guide { position: relative; display: flex; min-height: 410px; flex-direction: column; align-items: center; padding: 10px 14px 8px; text-align: center; }
.login-guide__close { position: absolute; top: 0; right: 0; display: grid; width: 28px; height: 28px; place-items: center; border: 0; border-radius: 4px; background: transparent; color: #94a3b8; cursor: pointer; }
.login-guide__close:hover { background: #f1f5f9; color: #475569; }
.login-guide__mark { display: grid; width: 52px; height: 52px; place-items: center; border-radius: 8px; background: #e62222; color: #fff; font-size: 15px; font-weight: 700; }
.login-guide h3 { margin: 14px 0 5px; color: #0f182a; font-size: 18px; line-height: 28px; font-weight: 700; }
.login-guide p { max-width: 280px; margin: 0; color: #64748b; font-size: 13px; line-height: 21px; }
.login-guide__tabs { display: flex; width: 100%; margin-top: 20px; border-bottom: 1px solid #e2e8f0; }
.login-guide__tabs button { flex: 1; height: 34px; border: 0; border-bottom: 2px solid transparent; background: transparent; color: #64748b; cursor: pointer; font-size: 13px; }
.login-guide__tabs button.active { border-bottom-color: #e62222; color: #e62222; font-weight: 600; }
.login-guide__form { display: grid; width: 100%; gap: 12px; padding-top: 18px; }
.login-guide__form input { box-sizing: border-box; width: 100%; height: 40px; border: 1px solid #dbe2ea; border-radius: 6px; outline: none; padding: 0 12px; color: #0f182a; font-size: 13px; }
.login-guide__form input:focus { border-color: #e62222; box-shadow: 0 0 0 2px rgba(230, 34, 34, 0.1); }
.login-guide__code-row { display: flex; gap: 8px; }
.login-guide__code-row button { flex: 0 0 96px; border: 0; border-radius: 6px; background: #fff1f1; color: #d51c1c; cursor: pointer; font-size: 12px; }
.login-guide__code-row button:disabled, .login-guide__submit:disabled { cursor: not-allowed; opacity: .55; }
.login-guide__agreement { display: flex; align-items: center; gap: 6px; color: #94a3b8; font-size: 11px; text-align: left; }
.login-guide__agreement input { width: 13px; height: 13px; accent-color: #e62222; }
.login-guide__submit { height: 42px; border: 1px solid #e62222; border-radius: 6px; background: #e62222; color: #fff; cursor: pointer; font-size: 14px; font-weight: 600; }
.login-guide__submit:hover:not(:disabled) { background: #c91818; }
:global(.kl-login-guide-modal-wrap .ant-modal-content) { border-radius: 8px; padding: 20px; }
:global(.kl-login-guide-modal-wrap .ant-modal-body) { padding: 0; }
</style>
