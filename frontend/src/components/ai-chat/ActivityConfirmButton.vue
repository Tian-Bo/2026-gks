<template>
  <div class="rounded-[18px] py-[16px]">
    <KlButton
      size="lg"
      variant="primary"
      fill="solid"
      class="mt-[28px] mb-[4px] w-full !justify-center !rounded-[16px]"
      :disabled="disabled"
      :loading="loading"
      @click="emit('confirm')"
    >
      <span class="inline-flex items-center justify-center gap-[8px]">
        <span class="mr-[8px] text-[20px] leading-none">↗</span>
        <span>{{ buttonText }}</span>
      </span>
    </KlButton>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  buttonText?: string
  disabled?: boolean
  loading?: boolean
}>(), {
  buttonText: '确认并继续',
  disabled: false,
  loading: false,
})

const emit = defineEmits<{
  (e: 'confirm'): void
}>()
</script>
