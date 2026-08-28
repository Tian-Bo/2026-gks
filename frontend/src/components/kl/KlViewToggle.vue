<template>
  <div class="kl-view-toggle" role="group">
    <button
      v-for="option in normalizedOptions"
      :key="option.value"
      type="button"
      class="kl-view-toggle__item"
      :class="{ 'is-active': option.value === modelValue }"
      :style="getItemStyle(option)"
      @click="handleChange(option.value)"
    >
      <slot :name="getItemSlotName(option)" :option="option" :active="option.value === modelValue">
        <span v-if="option.icon" class="kl-view-toggle__icon iconfont" :class="option.icon" />
        <span v-if="option.label" class="kl-view-toggle__label">{{ option.label }}</span>
      </slot>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
type ToggleValue = string | number

export type KlViewToggleOption = {
  label?: string
  value: ToggleValue
  /** iconfont class，例如 icon-liebiaoshitu1 / icon-kapianshitu */
  icon?: string
  /** 单个 item 宽度（数字按 px；字符串可传 87px/5rem 等） */
  width?: number | string
}

const props = withDefaults(defineProps<{
  modelValue?: ToggleValue
  options?: KlViewToggleOption[]
}>(), {
  modelValue: 'list',
  options: () => [
    { value: 'list', icon: 'icon-liebiaoshitu1' },
    { value: 'grid', icon: 'icon-kapianshitu' },
  ],
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: ToggleValue): void
  (e: 'change', value: ToggleValue): void
}>()

const normalizedOptions = computed(() => props.options)

function getItemStyle(option: KlViewToggleOption) {
  if (option.width == null) return undefined
  const width = typeof option.width === 'number' ? `${option.width}px` : option.width
  return { width }
}

function handleChange(value: ToggleValue) {
  if (value === props.modelValue) return
  emit('update:modelValue', value)
  emit('change', value)
}

function getItemSlotName(option: KlViewToggleOption) {
  return `item-${option.value}`
}
</script>

<style scoped lang="scss">
.kl-view-toggle {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 36px;
  padding: 3px;
  border-radius: 8px;
  background: #f5f6f7;
}

.kl-view-toggle__item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-width: 30px;
  height: 30px;
  padding: 5px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #0f182a;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  cursor: pointer;
  transition: background-color 0.2s ease, color 0.2s ease;

  &.is-active {
    background: #ffffff;
  }
}

.kl-view-toggle__icon {
  font-size: 20px;
  line-height: 1;
}

.kl-view-toggle__label {
  white-space: nowrap;
}
</style>
