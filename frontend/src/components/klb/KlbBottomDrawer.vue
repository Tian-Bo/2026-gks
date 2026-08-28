<template>
  <a-drawer
    :open="modelValue"
    :placement="'bottom'"
    :height="height"
    :closable="false"
    :mask-closable="maskClosable"
    :keyboard="keyboard"
    class="klb-bottom-drawer"
    :style="drawerCssVars"
    :content-wrapper-style="drawerContentWrapperStyle"
    @close="handleCancel"
    @update:open="handleOpenChange"
  >
    <div
      class="klb-bottom-drawer__close iconfont icon-guanbi"
      v-if="showClose"
      @click="handleCancel"
    ></div>

    <div
      class="klb-bottom-drawer__panel"
      :class="{ 'is-headerless': !showHeader }"
    >
      <header v-if="showHeader" class="klb-bottom-drawer__header">
        <slot name="title">
          <div class="klb-bottom-drawer__title">{{ title }}</div>
        </slot>
      </header>

      <div class="klb-bottom-drawer__body">
        <slot />
      </div>
    </div>
  </a-drawer>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue?: boolean;
    title?: string;
    showHeader?: boolean;
    showClose?: boolean;
    height?: number | string;
    maskClosable?: boolean;
    keyboard?: boolean;
    animationDuration?: number;
    closeTop?: number;
    closeRight?: number;
  }>(),
  {
    modelValue: false,
    title: "标题",
    showHeader: true,
    showClose: true,
    height: "calc(100vh - 70px)",
    maskClosable: true,
    keyboard: true,
    animationDuration: 300,
    closeTop: 18,
    closeRight: 26,
  },
);

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
  (e: "cancel"): void;
}>();

const drawerCssVars = computed(() => ({
  "--klb-bottom-drawer-duration": `${props.animationDuration}ms`,
  "--klb-bottom-drawer-close-top": `${props.closeTop}px`,
  "--klb-bottom-drawer-close-right": `${props.closeRight}px`,
}));

/** 内联到 .ant-drawer-content-wrapper，覆盖 AntD :where+hash 的 box-shadow，比样式表更稳 */
const drawerContentWrapperStyle = { boxShadow: "none" } as const;

function handleOpenChange(value: boolean) {
  emit("update:modelValue", value);
}

function handleCancel() {
  emit("update:modelValue", false);
  emit("cancel");
}
</script>

<style lang="scss">

.klb-bottom-drawer {
  border-radius: 32px 32px 0 0;
  overflow: hidden;
  box-shadow: none !important;
  .ant-drawer-mask {
    background: rgba(0, 0, 0, 0.5);
    transition-duration: var(--klb-bottom-drawer-duration, 300ms) !important;
  }


  .ant-drawer-content {
    background: transparent;
    box-shadow: none;
  }

  .ant-drawer-header {
    display: none;
  }

  .ant-drawer-body {
    height: 100%;
    padding: 0;
    overflow: visible;
  }

  .klb-bottom-drawer__panel {
    display: flex;
    height: 100%;
    flex-direction: column;
    background: #ffffff;
  }

  .klb-bottom-drawer__panel.is-headerless {
    .klb-bottom-drawer__body {
      height: 100%;
    }
  }

  .klb-bottom-drawer__header {
    flex-shrink: 0;
    padding: 24px 32px 24px;
  }

  .klb-bottom-drawer__title {
    color: #0f182a;
    font-size: 18px;
    font-weight: 600;
    line-height: 25px;
  }

  .klb-bottom-drawer__body {
    flex: 1;
    min-height: 0;
    overflow: auto;
  }

  .klb-bottom-drawer__close {
    position: absolute;
    right: 26px;
    top: -52px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    font-size: 24px;
    color: #fff;
    cursor: pointer;
  }
}
</style>
