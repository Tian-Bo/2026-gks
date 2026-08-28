<template>
  <a-pagination
    v-bind="paginationAttrs"
    v-model:current="mergedCurrent"
    v-model:page-size="mergedPageSize"
    :total="total"
    :show-size-changer="showSizeChanger"
  />
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<{
  current?: number
  pageSize?: number
  total: number
  showSizeChanger?: boolean
}>(), {
  current: undefined,
  pageSize: undefined,
  showSizeChanger: false,
})

const emit = defineEmits<{
  (e: 'update:current', value: number): void
  (e: 'update:pageSize', value: number): void
  (e: 'change', page: number, pageSize: number): void
  (e: 'showSizeChange', current: number, size: number): void
}>()

const attrs = useAttrs()

const mergedCurrent = computed({
  get: () => props.current ?? 1,
  set: (v: number) => {
    emit('update:current', v)
  },
})

const mergedPageSize = computed({
  get: () => props.pageSize ?? 10,
  set: (v: number) => {
    emit('update:pageSize', v)
  },
})

const paginationAttrs = computed(() => ({
  ...attrs,
  onChange: (page: number, size: number) => emit('change', page, size),
  onShowSizeChange: (current: number, size: number) => emit('showSizeChange', current, size),
}))
</script>

