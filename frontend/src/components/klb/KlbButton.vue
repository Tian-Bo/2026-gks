<template>
  <a-button
    :type="antType"
    :size="antSize"
    :disabled="disabled || loading"
    :loading="loading"
    :ghost="false"
    :danger="false"
    class="klb-button font-['PingFang_SC'] border-solid!"
    :class="customClass"
    @click="onClick"
  >
    <template v-if="useIconSlot" #icon>
      <span class="klb-button__left inline-flex items-center justify-center" aria-hidden="true">
        <template v-if="variant === 'icon'">
          <slot name="icon">
            <slot />
          </slot>
        </template>
        <template v-else>
          <slot name="left">
            <slot name="leftIcon">
              <slot name="icon" />
            </slot>
          </slot>
        </template>
      </span>
    </template>
    <template v-if="variant !== 'icon'">
      <span
        v-if="$slots.right || $slots.rightIcon"
        class="klb-button__row inline-flex min-w-0 max-w-full flex-1 items-center justify-center gap-x-[6px]"
      >
        <span
          class="klb-button__label min-w-0 flex items-center [font-size:inherit] [line-height:1.15] [-webkit-font-smoothing:antialiased]"
        >
          <slot />
        </span>
        <span class="klb-button__right inline-flex shrink-0 items-center" aria-hidden="true">
          <slot name="right">
            <slot name="rightIcon" />
          </slot>
        </span>
      </span>
      <template v-else>
        <slot />
      </template>
    </template>
  </a-button>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'

const slots = useSlots()

type KlbButtonSize = 'sm' | 'md' | 'lg'
type KlbButtonVariant = 'accent' | 'primary' | 'secondary' | 'icon' | 'text'
type KlbButtonFill = 'solid' | 'outline'

/**
 * 设计目标：
 * - 对外只暴露 size / variant / fill / disabled / loading
 * - 非 icon 形态：#left 或 #leftIcon 或 #icon（左）；#right 或 #rightIcon（右），文案为默认插槽
 * - variant=icon：默认插槽为图标，仍放在 AntD 的 icon 区域
 * - 内部使用 Ant Design Vue Button 作为基础交互与无障碍支持
 * - 通过类名覆盖实现产品侧统一风格（不依赖 AntD 的主题变量）
 */
const props = withDefaults(defineProps<{
  size?: KlbButtonSize
  variant?: KlbButtonVariant
  fill?: KlbButtonFill
  /** 胶囊按钮：border-radius 200px */
  pill?: boolean
  disabled?: boolean
  loading?: boolean
}>(), {
  size: 'md',
  variant: 'primary',
  fill: 'solid',
  pill: false,
  disabled: false,
  loading: false
})

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>()

/** 使用 AntD #icon 区域：纯图标按钮；或带左图标（#left / #leftIcon / #icon） */
const useIconSlot = computed(
  () =>
    props.variant === 'icon'
    || Boolean(slots.left)
    || Boolean(slots.leftIcon)
    || Boolean(slots.icon)
)

function onClick(e: MouseEvent) {
  // 这里再兜底一次：即使外部绑定了 @click，也不让 disabled/loading 触发
  if (props.disabled || props.loading) return
  emit('click', e)
}

const antSize = computed(() => {
  // KlbButton 的 size 与 AntD 的 size 做最小映射
  if (props.size === 'sm') return 'small'
  if (props.size === 'lg') return 'large'
  return 'middle'
})

const antType = computed(() => {
  // AntD 的 type 只用于承载基础行为，视觉由 customClass 控制
  if (props.variant === 'text') return 'text'
  if (props.variant === 'icon') return 'text'
  return 'default'
})

const customClass = computed(() => {
  const classes: string[] = [
    // 统一过渡反馈（不走 AntD 默认阴影/动效）
    'transition-all',
    'duration-200',
    'ease-out',
    'shadow-none!',
    // 以设计规范为准，统一 padding（与 AntD 默认 padding 脱钩）
    '!px-[24px]',
    'box-border',
    // 统一字重
    '!font-semibold',
  ]

  // 图标 + 文字：与 AntD 左侧 icon 区同一行内垂直居中对齐（纯 icon 由下方 variant=icon 单独处理）
  if (props.variant !== 'icon') {
    classes.push('!inline-flex', '!items-center', '!gap-0')
    if (props.variant !== 'text') classes.push('!justify-center')
  }

  // size 规范：
  // - lg：height 44px，font-size 14px，radius 12px
  // - md：height 36px，font-size 14px，radius 8px
  // - sm：height 32px，font-size 12px，radius 8px
  if (props.size === 'lg') {
    classes.push('!h-[44px]', '!text-[14px]')
    classes.push(props.pill ? '!rounded-[200px]' : '!rounded-[12px]')
  } else if (props.size === 'sm') {
    classes.push('!h-[32px]', '!text-[12px]')
    classes.push(props.pill ? '!rounded-[200px]' : '!rounded-[8px]')
  } else {
    classes.push('!h-[36px]', '!text-[14px]')
    classes.push(props.pill ? '!rounded-[200px]' : '!rounded-[8px]')
  }

  if (props.fill === 'solid') {
    if (props.variant === 'accent') {
      // 主强调色（红）
      classes.push('!bg-[#E62222]', '!text-white', '!border-[#E62222]', 'hover:!bg-[#D91C1C]', 'hover:!border-[#D91C1C]')
    } else if (props.variant === 'primary') {
      // 主色（深色）
      classes.push('!bg-[#0F182A]', '!text-white', '!border-[#0F182A]', 'hover:!bg-[#1B2942]', 'hover:!border-[#1B2942]')
    } else if (props.variant === 'secondary') {
      // 次要（浅底）
      classes.push('!bg-[#F1F3F5]', '!text-[#0F182A]', '!border-[#F1F3F5]', 'hover:!bg-[#E5E4E7]', 'hover:!border-[#E5E4E7]')
    }
  } else {
    if (props.variant === 'accent') {
      // 描边强调色（红）
      classes.push('!bg-white', '!text-[#E62222]', '!border-[#E62222]', 'hover:!bg-[#FFF5F5]', 'hover:!text-[#E62222]', 'hover:!border-[#E62222]')
    } else if (props.variant === 'primary') {
      // 描边主色（深色）
      classes.push('!bg-white', '!text-[#0F182A]', '!border-[#0F182A]', 'hover:!bg-[#F8F9FA]', 'hover:!text-[#0F182A]', 'hover:!border-[#0F182A]')
    } else if (props.variant === 'secondary') {
      // 描边次要（灰）
      classes.push('!bg-white', '!text-[#64748B]', '!border-[#D9D9D9]', 'hover:!bg-[#F8F9FA]', 'hover:!text-[#64748B]', 'hover:!border-[#D9D9D9]')
    }
  }

  if (props.variant === 'icon') {
    // icon 形态：不显示文本，交互仍由 AntD Button 承载
    classes.push(
      'flex',
      'items-center',
      'justify-center',
      // icon 按钮不需要左右 padding
      '!px-[0px]',
      '!text-[#0F182A]',
      'hover:!text-[#E62222]',
    )
  }

  if (props.variant === 'text') {
    // text 形态：与链接按钮一致，但保持当前字体与 hover 色
    classes.push('!text-[#0F182A]', 'hover:!text-[#E62222]')
  }

  return classes.join(' ')
})
</script>

<style scoped>
/* 左侧/右侧内联 SVG 去基线间隙；右侧列 line-height:0 避免多出一层行高盒，便于与文字竖直居中 */
.klb-button :deep(.klb-button__left svg),
.klb-button :deep(.klb-button__row svg) {
  display: block;
  flex-shrink: 0;
}
.klb-button :deep(.klb-button__right) {
  line-height: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
/* 有右图标时，中间文字列与图标本征高度对齐 */
.klb-button :deep(.klb-button__row) {
  display: inline-flex;
  align-items: center;
}
</style>
