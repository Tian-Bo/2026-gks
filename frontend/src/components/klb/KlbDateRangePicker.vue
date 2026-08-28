<template>
  <div
    class="klb-date-range-picker font-['PingFang_SC']"
    :class="{ 'is-disabled': disabled, 'has-clear': effectiveAllowClear }"
    :style="rootStyle"
  >
    <a-range-picker
      :value="modelValue ?? undefined"
      :allow-clear="effectiveAllowClear"
      :disabled="disabled"
      :disabled-date="disabledDate"
      :format="format"
      :show-time="antShowTime"
      :popup-class-name="popupClassName"
      :get-popup-container="getPopupContainer"
      separator="至"
      :placeholder="placeholder"
      class="klb-date-range-picker__control"
      @update:value="onUpdateValue"
      @change="onChange"
      @openChange="onOpenChange"
    >
      <template #suffixIcon>
        <i class="iconfont icon-rili klb-date-range-picker__icon" aria-hidden="true" />
      </template>
      <template #separator>
        <span class="klb-date-range-picker__separator">至</span>
      </template>
      <template #clearIcon>
        <span class="klb-date-range-picker__clear" aria-hidden="true">×</span>
      </template>
    </a-range-picker>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import dayjs, { type Dayjs } from "dayjs";
import type { StyleValue } from "vue";

type RangeValue = [Dayjs, Dayjs] | null;

const props = withDefaults(
  defineProps<{
    modelValue?: RangeValue;
    width?: number | string;
    format?: string;
    /** 为 true 时走 Ant 自带「选完需点确定」；为 false 时强制与 `showTime` 对象一致走原样。默认：仅在 `showTime === true`（布尔）时为 false，启用「两次点选日期即完成区间」并合并时分 */
    sequentialWithTime?: boolean;
    showTime?: boolean | Record<string, unknown>;
    placeholder?: [string, string];
    disabled?: boolean;
    allowClear?: boolean;
    disabledDate?: (current: Dayjs) => boolean;
    popupClassName?: string;
    getPopupContainer?: (triggerNode: HTMLElement) => HTMLElement;
  }>(),
  {
    modelValue: null,
    width: 282,
    format: "YYYY-MM-DD",
    sequentialWithTime: undefined,
    showTime: false,
    placeholder: () => ["开始时间", "结束时间"],
    disabled: false,
    allowClear: true,
    popupClassName: undefined,
    getPopupContainer: undefined,
  },
);

const emit = defineEmits<{
  (e: "update:modelValue", value: RangeValue): void;
  (e: "change", value: RangeValue): void;
  (e: "openChange", open: boolean): void;
}>();

const rootStyle = computed<StyleValue>(() => {
  const w = props.width;
  const width = typeof w === "number" ? `${w}px` : w;
  return { width };
});

const hasValue = computed(() => Boolean(props.modelValue?.[0] && props.modelValue?.[1]));
const effectiveAllowClear = computed(() => props.allowClear && hasValue.value);

/** 与 KlbTableFilterBar 一致：布尔 `showTime` 默认两次点日历即完成区间，不再用面板「确定」分步；对象 `showTime` 仍走 Ant 原逻辑（通常含确定） */
const useSequentialDatePick = computed(() => {
  if (props.sequentialWithTime === false) return false;
  if (props.sequentialWithTime === true) return true;
  return props.showTime === true;
});

const antShowTime = computed(() => {
  if (useSequentialDatePick.value) return false;
  return props.showTime;
});

function isValidDayjs(d: unknown): d is Dayjs {
  return dayjs.isDayjs(d) && d.isValid();
}

/** 日历只改日期时，把 previous 的时分秒套到新日期上；无 previous 时默认 12:00 */
function applyPreservedTime(datePart: Dayjs, timeSource: Dayjs | null | undefined): Dayjs {
  if (isValidDayjs(timeSource)) {
    return datePart
      .hour(timeSource.hour())
      .minute(timeSource.minute())
      .second(timeSource.second())
      .millisecond(0);
  }
  return datePart.hour(12).minute(0).second(0).millisecond(0);
}

function normalizeSequentialRange(
  incoming: RangeValue,
  previous: RangeValue,
): RangeValue {
  if (!incoming?.[0] || !incoming?.[1]) return incoming;
  const [a, b] = incoming;
  const [pa, pb] = previous ?? [null, null];
  return [applyPreservedTime(a, pa), applyPreservedTime(b, pb)];
}

function maybeNormalize(value: unknown): RangeValue {
  const v = (value ?? null) as RangeValue;
  if (!useSequentialDatePick.value) return v;
  if (!v?.[0] || !v?.[1]) return v;
  return normalizeSequentialRange(v, props.modelValue ?? null);
}

function onUpdateValue(value: unknown) {
  emit("update:modelValue", maybeNormalize(value));
}

function onChange(value: unknown) {
  emit("change", maybeNormalize(value));
}

function onOpenChange(open: boolean) {
  emit("openChange", open);
}
</script>

<style scoped lang="scss">
.klb-date-range-picker {
  --klb-date-height: 36px;
  --klb-date-radius: 8px;
  --klb-date-gray-bg: #f5f6f7;
  --klb-date-border: #e3e9f1;
  --klb-date-placeholder: #99a7bb;
  --klb-date-text: #0f182a;
  --klb-date-muted: #64748b;
  --klb-date-red: #e62222;
  --klb-date-input-size: 12px;
  --klb-date-input-weight: 400;
  --klb-date-separator-size: 14px;
  --klb-date-separator-weight: 500;

  display: inline-block;
  max-width: 100%;
  height: var(--klb-date-height);
  line-height: 1;
}

.klb-date-range-picker__control {
  width: 100%;
  height: var(--klb-date-height);
  min-height: var(--klb-date-height);
  padding: 0 12px;
  border: 0 !important;
  border-radius: var(--klb-date-radius);
  background: var(--klb-date-gray-bg) !important;
  box-shadow: none !important;
  display: flex;
  align-items: center;
}

.klb-date-range-picker__icon {
  font-size: 18px;
  line-height: 1;
  color: var(--klb-date-text);
}

.klb-date-range-picker__control:hover,
.klb-date-range-picker__control.ant-picker-focused {
  background: var(--klb-date-gray-bg) !important;
  border-color: transparent !important;
  box-shadow: none !important;
}

.klb-date-range-picker__clear {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  color: var(--klb-date-muted);
  font-size: 16px;
  line-height: 1;
}

:deep(.ant-picker-range) {
  display: flex;
  align-items: center;
}

:deep(.ant-picker-input) {
  display: flex;
  align-items: center;
  height: 100%;
}

:deep(.ant-picker-input:first-of-type) {
  padding-right: 10px;
}

:deep(.ant-picker-input:last-of-type) {
  padding-left: 10px;
}

:deep(.ant-picker-range-separator + .ant-picker-input) {
  margin-left: 10px;
}

:deep(.ant-picker-input > input) {
  height: 100%;
  padding: 0;
}

:deep(.ant-picker:hover),
:deep(.ant-picker-focused) {
  box-shadow: none !important;
}

:deep(.ant-picker-input > input) {
  color: var(--klb-date-text);
  font-size: var(--klb-date-input-size);
  font-weight: var(--klb-date-input-weight);
  line-height: 1;
}

:deep(.ant-picker-input > input::placeholder) {
  color: var(--klb-date-placeholder);
}

.klb-date-range-picker__separator {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 0 8px;
  color: var(--klb-date-text);
  font-size: var(--klb-date-separator-size);
  font-weight: var(--klb-date-separator-weight);
  line-height: 1;
}

:deep(.ant-picker-separator) {
  height: 100%;
}

:deep(.ant-picker-range-separator) {
  padding: 0;
  margin: 0;
}

:deep(.ant-picker-suffix) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  margin-left: 8px;
  color: var(--klb-date-text);
}

:deep(.ant-picker-clear) {
  right: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  background: var(--klb-date-gray-bg) !important;
  color: var(--klb-date-muted);
  z-index: 2;
}

.klb-date-range-picker.has-clear :deep(.ant-picker:hover .ant-picker-suffix) {
  opacity: 0;
}

.klb-date-range-picker.is-disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
