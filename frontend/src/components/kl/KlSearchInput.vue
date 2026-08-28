<template>
  <div
    class="kl-search-input font-['PingFang_SC']"
    :class="[rootClass, `kl-search-input--${variant}`, sizeClass, { 'is-disabled': disabled }]"
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
      class="kl-search-input__control"
      @change="onChange"
      @search="onSearch"
      @update:value="onUpdateValue"
    >
      <template #enterButton>
        <span class="kl-search-input__button-inner">
          <i class="iconfont icon-sousuo kl-search-input__icon" aria-hidden="true" />
          <span class="kl-search-input__button-text">{{ searchText }}</span>
        </span>
      </template>
    </a-input-search>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, useAttrs } from "vue";
import type { StyleValue } from "vue";

defineOptions({ inheritAttrs: false });

type KlSearchInputVariant = "gray" | "white";
type KlSearchInputSize = "sm" | "md" | "lg";
type AttrsRecord = Record<string, unknown>;

const props = withDefaults(
  defineProps<{
    modelValue?: string;
    /** 灰色 / 白色两种未聚焦样式 */
    variant?: KlSearchInputVariant;
    /** 数字按 px；字符串可传 100%、20rem 等 */
    width?: number | string;
    /** 输入框提示文本 */
    placeholder?: string;
    /** 右侧搜索按钮文案 */
    searchText?: string;
    size?: KlSearchInputSize;
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

const sizeClass = computed(() => `kl-search-input--${props.size}`);

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
.kl-search-input {
  --kl-search-height: 44px;
  --kl-search-radius: 12px;
  --kl-search-button-width: 74px;
  --kl-search-gray-bg: #f5f6f7;
  --kl-search-border: #e3e9f1;
  --kl-search-focus-bg: var(--kl-search-gray-bg);
  --kl-search-placeholder: #99a7bb;
  --kl-search-text: #0f182a;
  --kl-search-muted: #64748b;
  --kl-search-red: #e62222;

  display: inline-block;
  max-width: 100%;
  line-height: 1;
}

.kl-search-input--md {
  --kl-search-height: 36px;
  --kl-search-radius: 8px;
}

.kl-search-input--sm {
  --kl-search-height: 32px;
  --kl-search-radius: 8px;
}

.kl-search-input__control {
  width: 100%;
}

:deep(.ant-input-search) {
  width: 100%;
  box-shadow: none !important;
}

:deep(.ant-input-wrapper.ant-input-group) {
  display: grid;
  grid-template-columns: minmax(0, 1fr) var(--kl-search-button-width);
  column-gap: 0;
  width: 100%;
  height: var(--kl-search-height);
  overflow: hidden;
  border: 1px solid transparent;
  border-radius: var(--kl-search-radius);
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
  color: var(--kl-search-text);
  font-size: 14px;
  line-height: 22px;
  background: transparent !important;
}

:deep(.ant-input::placeholder) {
  color: var(--kl-search-placeholder);
}

:deep(.ant-input-group-addon) {
  position: relative;
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  width: var(--kl-search-button-width) !important;
  min-width: var(--kl-search-button-width) !important;
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
  max-width: var(--kl-search-button-width) !important;
  height: 100% !important;
  min-width: var(--kl-search-button-width);
  min-height: 100% !important;
  padding: 0 12px !important;
  margin: 0 !important;
  color: var(--kl-search-text) !important;
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
  background: var(--kl-search-border);
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

.kl-search-input__button-inner {
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

.kl-search-input__icon {
  display: block;
  flex-shrink: 0;
  font-size: 18px;
  line-height: 1;
}

.kl-search-input__button-text {
  font-size: 14px;
  font-weight: 500;
  line-height: 1.15;
}

.kl-search-input--gray {
  :deep(.ant-input-wrapper.ant-input-group) {
    border-color: var(--kl-search-gray-bg);
    background: var(--kl-search-gray-bg);
  }

  :deep(.ant-input-search-button) {
    color: var(--kl-search-text) !important;
  }

  :deep(.ant-input-search-button:hover),
  :deep(.ant-input-search-button:focus),
  :deep(.ant-input-search-button:focus-visible),
  :deep(.ant-input-search-button:active) {
    color: var(--kl-search-text) !important;
    background: transparent !important;
  }
}

.kl-search-input--white {
  --kl-search-focus-bg: #fff;

  :deep(.ant-input-wrapper.ant-input-group) {
    border-color: var(--kl-search-border);
    background: #fff;
  }

  :deep(.ant-input-search-button) {
    color: var(--kl-search-muted) !important;
  }

  :deep(.ant-input-search-button:hover),
  :deep(.ant-input-search-button:focus),
  :deep(.ant-input-search-button:focus-visible),
  :deep(.ant-input-search-button:active) {
    color: var(--kl-search-muted) !important;
    background: transparent !important;
  }
}

.kl-search-input:focus-within {
  :deep(.ant-input-wrapper.ant-input-group) {
    border-color: var(--kl-search-red) !important;
    background: linear-gradient(
      to right,
      var(--kl-search-focus-bg) 0,
      var(--kl-search-focus-bg) calc(100% - var(--kl-search-button-width)),
      var(--kl-search-red) calc(100% - var(--kl-search-button-width)),
      var(--kl-search-red) 100%
    ) !important;
    box-shadow: none !important;
  }

  :deep(.ant-input-affix-wrapper) {
    background: #fff !important;
  }

  :deep(.ant-input-search-button) {
    color: #fff !important;
    background: var(--kl-search-red) !important;
    border-color: var(--kl-search-red) !important;
  }

  :deep(.ant-input-search-button:hover),
  :deep(.ant-input-search-button:focus),
  :deep(.ant-input-search-button:focus-visible),
  :deep(.ant-input-search-button:active) {
    color: #fff !important;
    background: var(--kl-search-red) !important;
    border-color: var(--kl-search-red) !important;
    box-shadow: none !important;
    outline: none !important;
  }

  :deep(.ant-input-group-addon) {
    background: var(--kl-search-red) !important;
  }

  :deep(.ant-input-group-addon::after) {
    position: absolute;
    top: -1px;
    right: -1px;
    bottom: -1px;
    left: 0;
    background: var(--kl-search-red);
    content: "";
    pointer-events: none;
    border-top-right-radius: var(--kl-search-radius);
    border-bottom-right-radius: var(--kl-search-radius);
  }

  :deep(.ant-input-search-button::before) {
    display: none;
  }

  :deep(.ant-input-search-button) {
    position: relative;
    z-index: 1;
  }
}

.kl-search-input.is-disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
