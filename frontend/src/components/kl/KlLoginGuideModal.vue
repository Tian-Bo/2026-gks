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
      <img class="login-guide__mark" :src="aiLogo" alt="快灵 AI" />
      <h3>登录后即可开始生成</h3>
      <p>登录后即可使用快灵 AI，生成专属活动方案。</p>
      <form class="login-guide__form" @submit.prevent="submit">
        <input v-model.trim="phone" inputmode="numeric" autocomplete="tel" maxlength="11" placeholder="请输入手机号" />
        <input v-model.trim="password" type="password" autocomplete="current-password" maxlength="16" placeholder="请输入6-16位字母和数字密码" />
        <label class="login-guide__agreement"><input v-model="agreed" type="checkbox" /> 我已阅读并同意用户协议和隐私政策</label>
        <button class="login-guide__submit" :disabled="submitting" type="submit">{{ submitting ? '登录中...' : '立即登录' }}</button>
      </form>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import api, { saveSelectedShopToken } from '../../standalone/api'
import { setStore } from '../../standalone/storage'
import { klbMessage } from '../../standalone/klbMessage'

const aiLogo = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298062_3ELdZZ4ftV.png'

defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean]; authenticated: [] }>()
const phone = ref('')
const password = ref('')
const agreed = ref(true)
const submitting = ref(false)

const phonePattern = /^1\d{10}$/
const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,16}$/

function close() {
  emit('update:modelValue', false)
}

function validate() {
  if (!phonePattern.test(phone.value)) {
    klbMessage.warning('请输入正确的手机号')
    return false
  }
  if (!agreed.value) {
    klbMessage.warning('请先阅读并同意用户协议')
    return false
  }
  if (!passwordPattern.test(password.value)) {
    klbMessage.warning('请输入6-16位字母和数字组合密码')
    return false
  }
  return true
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
  if (submitting.value || !validate())
    return

  submitting.value = true
  try {
    const auth = await api.auth.loginByPassword({ phone: phone.value, password: password.value })
    await persistShopToken(auth)
    klbMessage.success('登录成功')
    close()
    emit('authenticated')
  }
  catch (error: any) {
    klbMessage.error(error?.message || '登录失败')
  }
  finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.login-guide { position: relative; display: flex; min-height: 320px; flex-direction: column; align-items: center; padding: 10px 14px 8px; text-align: center; }
.login-guide__close { position: absolute; top: 0; right: 0; display: grid; width: 28px; height: 28px; place-items: center; border: 0; border-radius: 4px; background: transparent; color: #94a3b8; cursor: pointer; }
.login-guide__close:hover { background: #f1f5f9; color: #475569; }
.login-guide__mark { width: 105px; height: 28px; object-fit: contain; }
.login-guide h3 { margin: 14px 0 5px; color: #0f182a; font-size: 18px; line-height: 28px; font-weight: 700; }
.login-guide p { max-width: 280px; margin: 0; color: #64748b; font-size: 13px; line-height: 21px; }
.login-guide__form { display: grid; width: 100%; gap: 12px; padding-top: 24px; }
.login-guide__form input { box-sizing: border-box; width: 100%; height: 40px; border: 1px solid #dbe2ea; border-radius: 6px; outline: none; padding: 0 12px; color: #0f182a; font-size: 13px; }
.login-guide__form input:focus { border-color: #e62222; box-shadow: 0 0 0 2px rgba(230, 34, 34, 0.1); }
.login-guide__submit:disabled { cursor: not-allowed; opacity: .55; }
.login-guide__agreement { display: flex; align-items: center; gap: 6px; color: #94a3b8; font-size: 11px; text-align: left; }
.login-guide__agreement input { width: 13px; height: 13px; accent-color: #e62222; }
.login-guide__submit { height: 42px; border: 1px solid #e62222; border-radius: 6px; background: #e62222; color: #fff; cursor: pointer; font-size: 14px; font-weight: 600; }
.login-guide__submit:hover:not(:disabled) { background: #c91818; }
:global(.kl-login-guide-modal-wrap .ant-modal-content) { border-radius: 8px; padding: 20px; }
:global(.kl-login-guide-modal-wrap .ant-modal-body) { padding: 0; }
</style>
