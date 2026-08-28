<template>
  <div class="activity-brief-form rounded-[18px] py-[16px]">
    <div
      v-if="showGoalSection"
      class="activity-brief-form__section"
      style="--activity-brief-reveal-delay: 0ms"
    >
      <div class="mb-[12px] flex items-center justify-between gap-[12px]">
        <div class="text-[16px] font-500 leading-[22px] text-[#0F172A]">
          {{ goalTitle }}
        </div>
        <button v-if="!readonly" type="button"
          class="inline-flex cursor-pointer items-center whitespace-nowrap border-none bg-transparent p-0 text-[14px] font-400 leading-[22px] text-[#0F172A] transition-opacity hover:opacity-70"
          @click="emit('skipGoal')">
          跳过
        </button>
      </div>

      <div v-if="readonly"
        class="rounded-[16px] bg-[#FFF7F7] px-[16px] py-[14px] text-[14px] font-600 leading-[20px] text-[#E62222]">
        {{ goalSummaryLabel }}
      </div>
      <div v-else class="grid grid-cols-4 gap-[12px]">
        <button v-for="item in goalOptions" :key="item.value" type="button" :disabled="readonly"
          class="flex h-[72px] w-full flex-col items-center justify-center rounded-[16px] border-2 border-solid px-[6px] text-[14px] font-600 transition-all disabled:cursor-not-allowed disabled:opacity-70"
          :class="item.value === goalValue
            ? '!border-[#E62222] !bg-[#FFF7F7] !text-[#E62222] shadow-none'
            : 'border-transparent bg-[#F2F4F7] text-[#0F172A]'" @click="emit('update:goalValue', item.value)">
          <span
            :class="item.value === goalValue ? '!text-[#E62222]' : '!text-[#0F182A]'"
          >
            {{ item.label }}
          </span>
          <span
            v-if="item.describe"
            class="mt-[6px] text-[12px] font-400"
            :class="item.value === goalValue ? '!text-[#E62222]' : '!text-[#64748B]'"
          >
            {{ item.describe }}
          </span>
        </button>
      </div>
    </div>

    <div
      v-if="showDateSection"
      class="activity-brief-form__section"
      :class="showGoalSection ? 'mt-[20px]' : ''"
      :style="getDateSectionRevealStyle()"
    >
      <div class="mb-[12px] flex items-center justify-between gap-[12px]">
        <div class="text-[16px] font-500 leading-[22px] text-[#0F172A]">
          {{ dateTitle }}
        </div>
        <button v-if="!readonly" type="button"
          class="inline-flex cursor-pointer items-center whitespace-nowrap border-none bg-transparent p-0 text-[14px] font-400 leading-[22px] text-[#0F172A] transition-opacity hover:opacity-70"
          @click="emit('skipDate')">
          跳过
        </button>
      </div>

      <div v-if="readonly"
        class="rounded-[16px] bg-[#FFF7F7] px-[16px] py-[14px] text-[14px] font-600 leading-[20px] text-[#E62222]">
        {{ dateSummaryLabel }}
      </div>
      <div v-else class="grid grid-cols-4 gap-[12px]">
        <button v-for="item in normalDateOptions" :key="item.value" type="button" :disabled="readonly"
          class="flex h-[56px] w-full items-center justify-center rounded-[16px] border-2 border-solid px-[12px] text-[14px] font-600 transition-all disabled:cursor-not-allowed disabled:opacity-70"
          :class="item.value === dateValue
            ? '!border-[#E62222] !bg-[#FFF7F7] !text-[#E62222] shadow-none'
            : 'border-transparent bg-[#F2F4F7] text-[#0F172A]'" @click="handleDateOptionSelect(item.value)">
          {{ item.label }}
        </button>

        <button v-if="customDateOption && !showCustomDatePicker" type="button"
          class="col-span-2 flex h-[56px] w-full items-center justify-center rounded-[16px] border-2 border-solid px-[12px] text-[14px] font-500 transition-all"
          :class="isCustomDateActive
            ? '!border-[#E62222] !bg-[#FFF7F7] !text-[#E62222] shadow-none'
            : 'border-transparent bg-[#F2F4F7] text-[#0F172A]'" @click="handleCustomDateTriggerClick">
          {{ customDateOption.label }}
        </button>

        <KlDateRangePicker v-else-if="customDateOption" ref="customDatePickerRef" :model-value="pickerValue"
          :allow-clear="false" :disabled="readonly" format="YYYY-MM-DD" width="100%" :placeholder="['开始时间', '结束时间']"
          :class="['activity-brief-form__date-picker', 'col-span-2 min-w-0 w-full', { 'is-active': isCustomDateActive }]"
          style="--kl-date-height: 56px; --kl-date-radius: 16px; --kl-date-gray-bg: #F2F4F7; --kl-date-input-size: 14px; --kl-date-input-weight: 500; --kl-date-separator-size: 14px; --kl-date-separator-weight: 500;"
          @update:model-value="handleDateRangeChange" @open-change="handleCustomDateOpenChange" />
      </div>
    </div>

    <KlButton v-if="(showGoalSection || showDateSection) && !readonly" size="lg" variant="primary" fill="solid"
      class="activity-brief-form__confirm mt-[28px] mb-[4px] w-full !justify-center !gap-[8px] !rounded-[16px]" :disabled="!canConfirm"
      :style="getConfirmRevealStyle()"
      @click="emit('confirm')">
      <span class="mr-[8px] text-[20px] leading-none">↗</span>
      <span>确认并继续</span>
    </KlButton>
  </div>
</template>

<script setup lang="ts">
import dayjs, { type Dayjs } from 'dayjs'
import { computed, nextTick, ref, watch } from 'vue'

export type ActivityBriefOption = {
  value: string
  label: string
  describe?: string
}

const props = defineProps<{
  goalTitle: string
  dateTitle: string
  goalValue: string
  dateValue: string
  goalOptions: ActivityBriefOption[]
  dateOptions: ActivityBriefOption[]
  startValue?: string
  endValue?: string
  showGoalSection: boolean
  showDateSection: boolean
  readonly?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:goalValue', value: string): void
  (e: 'update:dateValue', value: string): void
  (e: 'update:startValue', value: string): void
  (e: 'update:endValue', value: string): void
  (e: 'skipGoal'): void
  (e: 'skipDate'): void
  (e: 'confirm'): void
}>()

type PickerValue = [Dayjs, Dayjs] | null
type DatePickerRef = {
  $el?: HTMLElement
} | null

const customDatePickerRef = ref<DatePickerRef>(null)
const shouldAutoOpenCustomDatePicker = ref(false)

const pickerValue = computed<PickerValue>(() => {
  if (!props.startValue || !props.endValue)
    return null
  return [dayjs(props.startValue), dayjs(props.endValue)]
})

const customDateOption = computed(() =>
  props.dateOptions.find(item => item.value === 'custom_range') || null,
)

const normalDateOptions = computed(() =>
  props.dateOptions.filter(item => item.value !== 'custom_range'),
)

const goalSummaryLabel = computed(() =>
  props.goalOptions.find(item => item.value === props.goalValue)?.label || '已跳过',
)

const showCustomDatePicker = computed(() =>
  props.dateValue === 'custom_range' || !!pickerValue.value,
)

const isCustomDateActive = computed(() =>
  props.dateValue === 'custom_range' || !!pickerValue.value,
)

const dateSummaryLabel = computed(() => {
  if (props.dateValue === 'custom_range' || pickerValue.value) {
    if (props.startValue && props.endValue)
      return `${props.startValue} - ${props.endValue}`
    return '已选择自定义时间'
  }
  return props.dateOptions.find(item => item.value === props.dateValue)?.label || '已跳过'
})

const canConfirm = computed(() => {
  if (props.showGoalSection && !props.goalValue)
    return false
  if (props.showDateSection) {
    if (!props.dateValue)
      return false
    if (props.dateValue === 'custom_range')
      return !!props.startValue && !!props.endValue
  }
  return props.showGoalSection || props.showDateSection
})

function getDateSectionRevealStyle() {
  return `--activity-brief-reveal-delay: ${props.showGoalSection ? 160 : 0}ms`
}

function getConfirmRevealStyle() {
  const visibleSectionCount = Number(props.showGoalSection) + Number(props.showDateSection)
  return `--activity-brief-reveal-delay: ${Math.max(visibleSectionCount - 1, 0) * 160 + 180}ms`
}

function handleDateOptionSelect(value: string) {
  emit('update:dateValue', value)
}

function handleCustomDateTriggerClick() {
  if (!customDateOption.value)
    return
  shouldAutoOpenCustomDatePicker.value = true
  emit('update:dateValue', customDateOption.value.value)
}

function handleCustomDateOpenChange(open: boolean) {
  if (open || !customDateOption.value)
    return
  shouldAutoOpenCustomDatePicker.value = false
}

function handleDateRangeChange(value: PickerValue) {
  const start = value?.[0]?.format('YYYY-MM-DD') || ''
  const end = value?.[1]?.format('YYYY-MM-DD') || ''
  if (customDateOption.value)
    emit('update:dateValue', customDateOption.value.value)
  emit('update:startValue', start)
  emit('update:endValue', end)
}

watch(showCustomDatePicker, async (visible) => {
  if (!visible || !shouldAutoOpenCustomDatePicker.value)
    return

  await nextTick()
  const pickerRoot = customDatePickerRef.value?.$el
  const trigger = pickerRoot?.querySelector('.ant-picker') as HTMLElement | null
  const input = pickerRoot?.querySelector('input') as HTMLInputElement | null

  trigger?.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
  trigger?.click()
  input?.focus()
  input?.click()
}, { flush: 'post' })
</script>

<style scoped>
.activity-brief-form__section,
.activity-brief-form__confirm {
  opacity: 0;
  transform: translateY(10px);
  animation: activity-brief-segment-reveal 460ms cubic-bezier(0.22, 1, 0.36, 1) var(--activity-brief-reveal-delay, 0ms) both;
  will-change: opacity, transform;
}

.activity-brief-form__date-picker :deep(.kl-date-range-picker__control) {
  border: 2px solid transparent !important;
  display: flex;
  align-items: center;
  width: 100%;
}

.activity-brief-form__date-picker.is-active :deep(.kl-date-range-picker__control),
.activity-brief-form__date-picker.is-active :deep(.kl-date-range-picker__control:hover),
.activity-brief-form__date-picker.is-active :deep(.kl-date-range-picker__control.ant-picker-focused) {
  border: 2px solid #E62222 !important;
  background: #FFF7F7 !important;
  box-shadow: none !important;
}

.activity-brief-form__date-picker :deep(.ant-picker-range) {
  display: flex;
  align-items: center;
  width: 100%;
}

.activity-brief-form__date-picker :deep(.ant-picker-input) {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.activity-brief-form__date-picker :deep(.ant-picker-input > input) {
  text-align: center;
}

@keyframes activity-brief-segment-reveal {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }

  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .activity-brief-form__section,
  .activity-brief-form__confirm {
    opacity: 1;
    transform: none;
    animation: none;
  }
}
</style>
