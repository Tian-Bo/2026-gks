<template>
  <KlbDropdown :trigger="trigger" :placement="placement" :overlay-width="120" overlay-class-name="klb-user-avatar-dropdown-popover">
    <div class="klb-user-avatar-dropdown__avatar">A</div>
    <template #overlay>
      <div class="klb-user-avatar-dropdown__menu">
        <button type="button" class="klb-user-avatar-dropdown__menu-item">退出登录</button>
      </div>
    </template>
  </KlbDropdown>
</template>

<script setup lang="ts">
import KlbDropdown from './KlbDropdown.vue'

withDefaults(defineProps<{
  trigger?: Array<'click' | 'hover' | 'contextmenu'>
  placement?: 'bottomLeft' | 'bottom' | 'bottomRight' | 'topLeft' | 'top' | 'topRight'
  logoutOnly?: boolean
}>(), {
  trigger: () => ['hover'],
  placement: 'bottomRight',
  logoutOnly: false,
})
</script>

<style scoped lang="scss">
.klb-user-avatar-dropdown__avatar { display: inline-flex; width: 36px; height: 36px; align-items: center; justify-content: center; border-radius: 50%; background: #b5c3d6; color: #fff; cursor: pointer; font-size: 12px; font-weight: 700; }
.klb-user-avatar-dropdown__menu { width: 120px; }
.klb-user-avatar-dropdown__menu-item { width: 100%; height: 52px; border: 0; background: transparent; color: #0f182a; cursor: pointer; font-size: 14px; font-weight: 600; }
.klb-user-avatar-dropdown__menu-item:hover { background: #f5f6f7; }
:global(.klb-user-avatar-dropdown-popover .klb-dropdown-overlay__content) { overflow: hidden; border-radius: 8px; background: #fff; box-shadow: 0 4px 12px 4px rgba(47, 48, 49, .1); }
</style>
