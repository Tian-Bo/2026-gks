<template>
  <div class="rounded-[18px] py-[16px]">
    <div class="mb-[12px] flex items-center justify-between gap-[12px]" :class="{ 'activity-style-selector__reveal': animate }" style="--activity-style-selector-reveal-delay: 0ms">
      <div class="text-[16px] font-500 leading-[22px] text-[#0F172A]">
        {{ title }}
      </div>
      <button v-if="!readonly" type="button"
        class="inline-flex cursor-pointer items-center whitespace-nowrap border-none bg-transparent p-0 text-[14px] font-400 leading-[22px] text-[#0F172A] transition-opacity hover:opacity-70"
        @click="emit('skip')">
        跳过
      </button>
    </div>

    <div v-if="readonly"
      class="rounded-[18px] bg-[#FFF7F7] px-[16px] py-[14px] text-[14px] leading-[22px] text-[#E62222]">
      <div class="font-600">
        {{ readonlySummaryText }}
      </div>
      <div v-if="readonlySummaryItems.length" class="mt-[8px] space-y-[4px]">
        <div v-for="item in readonlySummaryItems" :key="item">
          {{ item }}
        </div>
      </div>
    </div>

    <div v-else class="rounded-[18px] bg-[#F5F6F7] p-[12px]" :class="{ 'activity-style-selector__reveal': animate }" style="--activity-style-selector-reveal-delay: 110ms">
      <div class="grid grid-cols-6 gap-[10px]">
        <button v-for="(item, index) in options" :key="item.value" type="button"
          class="cursor-pointer rounded-[16px] border-2 border-solid bg-[#F7F8FA] p-[8px] text-center transition-all"
          :style="animate ? { '--activity-style-selector-reveal-delay': `${160 + index * 45}ms` } : undefined"
          :class="[
            item.value === selectedValue
              ? '!border-[#E62222] !bg-[#FFF7F7] shadow-none'
              : 'border-transparent',
            { 'activity-style-selector__reveal': animate },
          ]"
          @click="emit('update:selectedValue', item.value)">
          <img :src="item.image" :alt="item.label" class="block w-full rounded-[12px] object-cover">
          <div class="mt-[8px] text-[13px] font-600 text-[#0F172A]">
            {{ item.label }}
          </div>
        </button>
      </div>

    </div>

    <div v-if="!readonly" class="mt-[12px] rounded-[16px] bg-white py-[10px]" :class="{ 'activity-style-selector__reveal': animate }" style="--activity-style-selector-reveal-delay: 500ms">
      <div class="mb-[8px] text-[16px] font-500 leading-[22px] text-[#0F172A]">
        或者告诉快灵你的诉求
      </div>
      <div class="rounded-[16px] bg-[#F5F6F7] px-[14px] py-[10px]">
        <textarea :value="customRequirement || ''"
          class="min-h-[44px] w-full resize-none border-none bg-transparent p-0 text-[13px] leading-[22px] text-[#111827] outline-none placeholder:text-[#98A2B3]"
          placeholder="请输入对活动风格的诉求"
          @input="emit('update:customRequirement', (($event.target as HTMLTextAreaElement | null)?.value || ''))" />
      </div>
    </div>

    <KlbButton v-if="!readonly" size="lg" variant="primary" fill="solid"
      class="mt-[28px] mb-[4px] w-full !justify-center !gap-[8px] !rounded-[16px]" :class="{ 'activity-style-selector__reveal': animate }" style="--activity-style-selector-reveal-delay: 600ms" :disabled="!canConfirm"
      @click="emit('confirm')">
      <span class="text-[20px] leading-none">↗</span>
      <span>确认并继续</span>
    </KlbButton>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

export type ActivityStyleOption = {
  value: string
  label: string
  image: string
}

const props = defineProps<{
  title: string
  options: ActivityStyleOption[]
  selectedValue: string
  customRequirement?: string
  readonly?: boolean
  readonlySummaryText?: string
  readonlySummaryItems?: string[]
  animate?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:selectedValue', value: string): void
  (e: 'update:customRequirement', value: string): void
  (e: 'skip'): void
  (e: 'confirm'): void
}>()

const readonlySummaryText = computed(() => props.readonlySummaryText || '已完成风格选择')
const readonlySummaryItems = computed(() => props.readonlySummaryItems || [])
const canConfirm = computed(() => Boolean(props.selectedValue))
</script>

<style scoped>
.activity-style-selector__reveal {
  opacity: 0;
  transform: translateY(8px);
  animation: activity-style-selector-reveal 340ms cubic-bezier(0.22, 1, 0.36, 1) var(--activity-style-selector-reveal-delay, 0ms) both;
}

@keyframes activity-style-selector-reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
