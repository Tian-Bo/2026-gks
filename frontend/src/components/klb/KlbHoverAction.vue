<template>
  <button
    type="button"
    class="klb-hover-action font-['PingFang_SC']"
    :class="[
      `klb-hover-action--${size}`,
      {
        'is-filled': filled,
        'is-danger': danger,
        'is-disabled': disabled,
      },
    ]"
    :style="customStyle"
    :disabled="disabled"
    @click="handleClick"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type HoverActionSize = 'sm' | 'md'

const props = withDefaults(defineProps<{
  size?: HoverActionSize
  filled?: boolean
  danger?: boolean
  disabled?: boolean
  containerWidth?: number | string
  containerHeight?: number | string
  containerRadius?: number | string
  width?: number | string
  height?: number | string
  radius?: number | string
  iconSize?: number | string
}>(), {
  size: 'md',
  filled: false,
  danger: false,
  disabled: false,
  containerWidth: undefined,
  containerHeight: undefined,
  containerRadius: undefined,
  width: undefined,
  height: undefined,
  radius: undefined,
  iconSize: undefined,
})

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>()

function handleClick(event: MouseEvent) {
  if (props.disabled) return
  emit('click', event)
}

const customStyle = computed(() => ({
  '--klb-hover-action-width': resolveUnit(props.containerWidth ?? props.width),
  '--klb-hover-action-height': resolveUnit(props.containerHeight ?? props.height),
  '--klb-hover-action-radius': resolveUnit(props.containerRadius ?? props.radius),
  '--klb-hover-action-icon-size': props.iconSize ? normalizeUnit(props.iconSize) : undefined,
}))

function normalizeUnit(value: string | number) {
  return typeof value === 'number' ? `${value}px` : value
}

function resolveUnit(value?: string | number) {
  if (value == null) return undefined
  return normalizeUnit(value)
}
</script>

<style scoped lang="scss">
.klb-hover-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #0f172a;
  cursor: pointer;
  transition: background-color 0.18s ease, color 0.18s ease, opacity 0.18s ease;

  &:hover {
    background: #f1f3f5;
  }
}

.klb-hover-action--md {
  width: var(--klb-hover-action-width, 32px);
  height: var(--klb-hover-action-height, 32px);
  border-radius: var(--klb-hover-action-radius, 8px);
}

.klb-hover-action--sm {
  width: var(--klb-hover-action-width, 32px);
  height: var(--klb-hover-action-height, 32px);
  border-radius: var(--klb-hover-action-radius, 8px);
}

.klb-hover-action.is-filled {
  background: #f1f3f5;
}

.klb-hover-action.is-danger:hover,
.klb-hover-action.is-danger.is-filled {
  color: #e62222;
}

.klb-hover-action.is-disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

:deep(.iconfont),
:deep(svg) {
  font-size: var(--klb-hover-action-icon-size, inherit);
}

:deep(svg) {
  width: var(--klb-hover-action-icon-size, inherit);
  height: var(--klb-hover-action-icon-size, inherit);
}
</style>
