<template>
  <a-modal :open="modelValue" title="联系顾问" :footer="null" width="420px" @cancel="emit('update:modelValue', false)">
    <div class="service-modal">
      <div class="service-modal__avatar">快灵</div>
      <strong>专属顾问服务</strong>
      <p>独立展示版不包含商户客服系统。</p>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ (event: 'update:modelValue', value: boolean): void }>()
</script>

<style scoped>
.service-modal { display: grid; min-height: 180px; place-items: center; align-content: center; gap: 10px; text-align: center; color: #64748b; }
.service-modal__avatar { display: grid; width: 56px; height: 56px; place-items: center; border-radius: 50%; background: #fff1f1; color: #e62222; font-weight: 700; }
.service-modal strong { color: #0f182a; }
.service-modal p { margin: 0; font-size: 13px; }
</style>
