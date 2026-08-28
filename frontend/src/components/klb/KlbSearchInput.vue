<template>
  <div
    class="klb-search-input font-['PingFang_SC']"
    :class="[rootClass, `klb-search-input--${variant}`, sizeClass, { 'is-disabled': disabled }]"
    :style="mergedRootStyle"
  >
    <a-input-search
      ref="searchRef"
      v-bind="inputAttrs"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :loading="loading"
      :allow-clear="allowClear"
      class="klb-search-input__control"
      @change="onChange"
      @search="onSearch"
      @update:value="onUpdateValue"
    >
      <template #enterButton>
        <span class="klb-search-input__button-inner">
          <i class="iconfont icon-sousuo klb-search-input__icon" aria-hidden="true" />
          <span class="klb-search-input__button-text">{{ searchText }}</span>
        </span>
      </template>
    </a-input-search>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, useAttrs } from "vue";
import type { StyleValue } from "vue";

defineOptions({ inheritAttrs: false });

type KlbSearchInputVariant = "gray" | "white";
type KlbSearchInputSize = "sm" | "md" | "lg";
type AttrsRecord = Record<string, unknown>;

const props = withDefaults(
  defineProps<{
    modelValue?: string;
    /** 灰色 / 白色两种未聚焦样式 */
    variant?: KlbSearchInputVariant;
    /** 数字按 px；字符串可传 100%、20rem 等 */
    width?: number | string;
    /** 输入框提示文本 */
    placeholder?: string;
    /** 右侧搜索按钮文案 */
    searchText?: string;
    size?: KlbSearchInputSize;
    disabled?: boolean;
    loading?: boolean;
    allowClear?: boolean;
  }>(),
  {
    modelValue: "",
    variant: "gray",
    width: 400,
    placeholder: "搜索",
    searchText: "搜索",
    size: "md",
    disabled: false,
    loading: false,
    allowClear: false,
  }
);

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
  (e: "change", event: Event): void;
  (e: "search", value: string, event?: Event): void;
}>();

const attrs = useAttrs();
const searchRef = ref();

const rootClass = computed(() => (attrs as AttrsRecord).class);
const rootInlineStyle = computed(() => (attrs as AttrsRecord).style);

const inputAttrs = computed(() => {
  const { class: _class, style: _style, ...rest } = attrs as AttrsRecord;
  return rest;
});

const widthStyle = computed(() => {
  const width = typeof props.width === "number" ? `${props.width}px` : props.width;
  return { width };
});

const mergedRootStyle = computed<StyleValue>(() => [
  widthStyle.value,
  rootInlineStyle.value as StyleValue,
]);

const sizeClass = computed(() => `klb-search-input--${props.size}`);

function onUpdateValue(value: string) {
  emit("update:modelValue", value ?? "");
}

function onChange(event: Event) {
  emit("change", event);
}

function onSearch(value: string, event?: Event) {
  emit("search", value, event);
}

defineExpose({
  focus: (options?: { preventScroll?: boolean; cursor?: "start" | "end" | "all" }) =>
    searchRef.value?.focus?.(options),
  blur: () => searchRef.value?.blur?.(),
});
</script>

<style scoped lang="scss">
.klb-search-input {
  --klb-search-height: 44px;
  --klb-search-radius: 12px;
  --klb-search-button-width: 74px;
  --klb-search-gray-bg: #f5f6f7;
  --klb-search-border: #e3e9f1;
  --klb-search-focus-bg: var(--klb-search-gray-bg);
  --klb-search-placeholder: #99a7bb;
  --klb-search-text: #0f182a;
  --klb-search-muted: #64748b;
  --klb-search-red: #e62222;

  display: inline-block;
  max-width: 100%;
  line-height: 1;
}

.klb-search-input--md {
  --klb-search-height: 36px;
  --klb-search-radius: 8px;
}

.klb-search-input--sm {
  --klb-search-height: 32px;
  --klb-search-radius: 8px;
}

.klb-search-input__control {
  width: 100%;
}

:deep(.ant-input-search) {
  width: 100%;
  box-shadow: none !important;
}

:deep(.ant-input-wrapper.ant-input-group) {
  display: grid;
  grid-template-columns: minmax(0, 1fr) var(--klb-search-button-width);
  column-gap: 0;
  width: 100%;
  height: var(--klb-search-height);
  overflow: hidden;
  border: 1px solid transparent;
  border-radius: var(--klb-search-radius);
  box-shadow: none !important;
  transition: border-color 0.2s ease;
}

:deep(.ant-input-affix-wrapper) {
  min-width: 0;
  height: 100%;
  padding: 0 12px;
  border: 0 !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  background: transparent !important;
}

:deep(.ant-input) {
  height: 100%;
  color: var(--klb-search-text);
  font-size: 14px;
  line-height: 22px;
  background: transparent !important;
}

:deep(.ant-input::placeholder) {
  color: var(--klb-search-placeholder);
}

:deep(.ant-input-group-addon) {
  position: relative;
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  width: var(--klb-search-button-width) !important;
  min-width: var(--klb-search-button-width) !important;
  height: 100%;
  padding: 0 !important;
  border: 0 !important;
  border-radius: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  overflow: hidden;
}

:deep(.ant-input-search-button) {
  position: relative;
  inset: 0;
  flex: 1 1 auto;
  display: flex !important;
  align-items: center;
  justify-content: center;
  width: 100% !important;
  max-width: var(--klb-search-button-width) !important;
  height: 100% !important;
  min-width: var(--klb-search-button-width);
  min-height: 100% !important;
  padding: 0 12px !important;
  margin: 0 !important;
  color: var(--klb-search-text) !important;
  border: 0 !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  background: transparent !important;
  overflow: hidden;
  cursor: pointer;
  transition:
    color 0.2s ease,
    border-color 0.2s ease,
    opacity 0.2s ease;
}

:deep(.ant-input-search-button::before) {
  position: absolute;
  top: 50%;
  left: 0;
  display: block;
  width: 1px;
  height: 10px;
  background: var(--klb-search-border);
  border-radius: 1px;
  transform: translateY(-50%);
  content: "";
}

:deep(.ant-input-search-button:disabled) {
  cursor: not-allowed;
}

:deep(.ant-input-search-button:hover),
:deep(.ant-input-search-button:focus),
:deep(.ant-input-search-button:focus-visible),
:deep(.ant-input-search-button:active) {
  color: inherit !important;
  background: transparent !important;
  border-color: transparent !important;
  box-shadow: none !important;
  outline: none !important;
}

.klb-search-input__button-inner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  height: 100%;
  font-size: 14px;
  font-weight: 500;
  line-height: 1;
  white-space: nowrap;
}

.klb-search-input__icon {
  display: block;
  flex-shrink: 0;
  font-size: 18px;
  line-height: 1;
}

.klb-search-input__button-text {
  font-size: 14px;
  font-weight: 500;
  line-height: 1.15;
}

.klb-search-input--gray {
  :deep(.ant-input-wrapper.ant-input-group) {
    border-color: var(--klb-search-gray-bg);
    background: var(--klb-search-gray-bg);
  }

  :deep(.ant-input-search-button) {
    color: var(--klb-search-text) !important;
  }

  :deep(.ant-input-search-button:hover),
  :deep(.ant-input-search-button:focus),
  :deep(.ant-input-search-button:focus-visible),
  :deep(.ant-input-search-button:active) {
    color: var(--klb-search-text) !important;
    background: transparent !important;
  }
}

.klb-search-input--white {
  --klb-search-focus-bg: #fff;

  :deep(.ant-input-wrapper.ant-input-group) {
    border-color: var(--klb-search-border);
    background: #fff;
  }

  :deep(.ant-input-search-button) {
    color: var(--klb-search-muted) !important;
  }

  :deep(.ant-input-search-button:hover),
  :deep(.ant-input-search-button:focus),
  :deep(.ant-input-search-button:focus-visible),
  :deep(.ant-input-search-button:active) {
    color: var(--klb-search-muted) !important;
    background: transparent !important;
  }
}

.klb-search-input:focus-within {
  :deep(.ant-input-wrapper.ant-input-group) {
    border-color: var(--klb-search-red) !important;
    background: linear-gradient(
      to right,
      var(--klb-search-focus-bg) 0,
      var(--klb-search-focus-bg) calc(100% - var(--klb-search-button-width)),
      var(--klb-search-red) calc(100% - var(--klb-search-button-width)),
      var(--klb-search-red) 100%
    ) !important;
    box-shadow: none !important;
  }

  :deep(.ant-input-affix-wrapper) {
    background: #fff !important;
  }

  :deep(.ant-input-search-button) {
    color: #fff !important;
    background: var(--klb-search-red) !important;
    border-color: var(--klb-search-red) !important;
  }

  :deep(.ant-input-search-button:hover),
  :deep(.ant-input-search-button:focus),
  :deep(.ant-input-search-button:focus-visible),
  :deep(.ant-input-search-button:active) {
    color: #fff !important;
    background: var(--klb-search-red) !important;
    border-color: var(--klb-search-red) !important;
    box-shadow: none !important;
    outline: none !important;
  }

  :deep(.ant-input-group-addon) {
    background: var(--klb-search-red) !important;
  }

  :deep(.ant-input-group-addon::after) {
    position: absolute;
    top: -1px;
    right: -1px;
    bottom: -1px;
    left: 0;
    background: var(--klb-search-red);
    content: "";
    pointer-events: none;
    border-top-right-radius: var(--klb-search-radius);
    border-bottom-right-radius: var(--klb-search-radius);
  }

  :deep(.ant-input-search-button::before) {
    display: none;
  }

  :deep(.ant-input-search-button) {
    position: relative;
    z-index: 1;
  }
}

.klb-search-input.is-disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
