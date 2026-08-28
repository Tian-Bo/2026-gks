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
      <h3>登录后即可开始生成</h3>
      <p>请先登录商家后台，登录完成后返回此页面继续创作。</p>
      <div class="login-guide__actions">
        <button type="button" class="login-guide__cancel" @click="close">暂不登录</button>
        <button type="button" class="login-guide__confirm" @click="goLogin">去登录</button>
      </div>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { getMerchantLoginUrl } from '../../standalone/api'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

function close() {
  emit('update:modelValue', false)
}

function goLogin() {
  const loginUrl = getMerchantLoginUrl()
  if (loginUrl)
    window.open(loginUrl, '_blank', 'noopener')
  else
    window.location.assign('/login')
}
</script>

<style scoped>
.login-guide { position: relative; display: flex; min-height: 260px; flex-direction: column; align-items: center; padding: 26px 20px 12px; text-align: center; }
.login-guide__close { position: absolute; top: 0; right: 0; display: grid; width: 28px; height: 28px; place-items: center; border: 0; border-radius: 4px; background: transparent; color: #94a3b8; cursor: pointer; }
.login-guide__close:hover { background: #f1f5f9; color: #475569; }
.login-guide__mark { display: grid; width: 52px; height: 52px; place-items: center; border-radius: 8px; background: #e62222; color: #fff; font-size: 15px; font-weight: 700; }
.login-guide h3 { margin: 18px 0 8px; color: #0f182a; font-size: 18px; line-height: 28px; font-weight: 700; }
.login-guide p { max-width: 280px; margin: 0; color: #64748b; font-size: 13px; line-height: 21px; }
.login-guide__actions { display: flex; width: 100%; gap: 12px; margin-top: 26px; }
.login-guide__actions button { height: 40px; flex: 1; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
.login-guide__cancel { border: 1px solid #e2e8f0; background: #fff; color: #475569; }
.login-guide__confirm { border: 1px solid #e62222; background: #e62222; color: #fff; }
.login-guide__confirm:hover { background: #c91818; }
:global(.kl-login-guide-modal-wrap .ant-modal-content) { border-radius: 8px; padding: 20px; }
:global(.kl-login-guide-modal-wrap .ant-modal-body) { padding: 0; }
</style>
