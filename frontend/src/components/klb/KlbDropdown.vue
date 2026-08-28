<template>
  <a-dropdown
    v-bind="dropdownProps"
    @open-change="handleOpenChange"
    @visible-change="handleVisibleChange"
  >
    <slot />

    <template v-if="$slots.overlay" #overlay>
      <div class="klb-dropdown-overlay__content" :style="overlayContentStyle">
        <slot name="overlay" />
      </div>
    </template>
    <template v-else-if="menuProps" #overlay>
      <div class="klb-dropdown-overlay__content" :style="overlayContentStyle">
        <AMenu v-bind="menuProps" />
      </div>
    </template>
  </a-dropdown>
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue'
import { Menu as AMenu } from 'ant-design-vue'

defineOptions({ inheritAttrs: false })

type DropdownTrigger = 'click' | 'hover' | 'contextmenu'
type DropdownPlacement = 'bottomLeft' | 'bottom' | 'bottomRight' | 'topLeft' | 'top' | 'topRight'
type AttrsRecord = Record<string, unknown>
type MenuConfig = Record<string, any> & { items?: any[] }

defineSlots<{
  default?: () => unknown
  overlay?: () => unknown
}>()

const props = withDefaults(defineProps<{
  menu?: MenuConfig
  trigger?: DropdownTrigger[]
  placement?: DropdownPlacement
  arrow?: boolean | Record<string, unknown>
  disabled?: boolean
  open?: boolean
  visible?: boolean
  overlayClassName?: string
  overlayWidth?: string | number
  destroyPopupOnHide?: boolean
  getPopupContainer?: (triggerNode: HTMLElement) => HTMLElement
}>(), {
  menu: undefined,
  trigger: () => ['click'],
  placement: 'bottomLeft',
  arrow: false,
  disabled: false,
  open: undefined,
  visible: undefined,
  overlayClassName: '',
  overlayWidth: undefined,
  destroyPopupOnHide: undefined,
  getPopupContainer: undefined,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'openChange', value: boolean): void
  (e: 'visibleChange', value: boolean): void
}>()

const attrs = useAttrs()
const mergedOverlayClassName = computed(() =>
  ['klb-dropdown-overlay', props.overlayClassName].filter(Boolean).join(' '),
)
const overlayContentStyle = computed(() => {
  if (props.overlayWidth === undefined)
    return undefined
  const width = typeof props.overlayWidth === 'number' ? `${props.overlayWidth}px` : props.overlayWidth
  return { '--klb-dropdown-width': width, '--klb-dropdown-min-width': width }
})
const menuProps = computed<Record<string, any> | undefined>(() =>
  props.menu?.items?.length ? props.menu : undefined,
)
const dropdownProps = computed(() => {
  const {
    overlayClassName: _overlayClassName,
    trigger: _trigger,
    placement: _placement,
    arrow: _arrow,
    disabled: _disabled,
    menu: _menu,
    open: _open,
    visible: _visible,
    overlayWidth: _overlayWidth,
    destroyPopupOnHide: _destroyPopupOnHide,
    getPopupContainer: _getPopupContainer,
    ...restAttrs
  } = attrs as AttrsRecord

  return {
    ...restAttrs,
    trigger: props.trigger,
    placement: props.placement,
    arrow: props.arrow,
    disabled: props.disabled,
    overlayClassName: mergedOverlayClassName.value,
    ...(props.open !== undefined ? { open: props.open } : {}),
    ...(props.visible !== undefined ? { visible: props.visible } : {}),
    ...(props.destroyPopupOnHide !== undefined ? { destroyPopupOnHide: props.destroyPopupOnHide } : {}),
    ...(props.getPopupContainer ? { getPopupContainer: props.getPopupContainer } : {}),
  }
})

function handleOpenChange(value: boolean) {
  emit('update:open', value)
  emit('openChange', value)
}

function handleVisibleChange(value: boolean) {
  emit('visibleChange', value)
}
</script>

<style lang="scss">
.klb-dropdown-overlay {
  font-family: 'PingFang SC', sans-serif;

  .klb-dropdown-overlay__content {
    width: var(--klb-dropdown-width, auto);
    min-width: var(--klb-dropdown-min-width, auto);
  }
}
</style>
