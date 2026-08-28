<template>
  <div class="w-full max-w-[720px]">
    <div class="activity-result-confirm-bar__description relative z-[1] mb-[28px] w-full whitespace-nowrap pl-[8px] text-[16px] font-500 leading-[22px] text-[#0F274A]" :class="{ 'is-animated': animate }">
      {{ description }}
    </div>

    <div class="activity-result-confirm-bar__actions grid grid-cols-2 gap-[16px]" :class="{ 'is-animated': animate }">
      <KlbButton size="lg" variant="secondary" fill="solid" pill class="!justify-center !text-[#0F182A]"
        @click="emit('adopt')">
        {{ adoptText }}
      </KlbButton>

      <KlbButton size="lg" variant="accent" fill="solid" pill class="!justify-center" @click="emit('publish')">
        <template #left>
          <span class="text-[18px] leading-none">↗</span>
        </template>
        {{ publishText }}
      </KlbButton>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  description?: string
  adoptText?: string
  publishText?: string
  animate?: boolean
}>(), {
  description: '活动已生成，点击进入活动编辑器查看并发布活动，也可采用该活动，在活动管理查阅',
  adoptText: '采用活动',
  publishText: '去发布活动',
})

const emit = defineEmits<{
  (e: 'adopt'): void
  (e: 'publish'): void
}>()
</script>

<style scoped>
.activity-result-confirm-bar__description.is-animated,
.activity-result-confirm-bar__actions.is-animated {
  opacity: 0;
  transform: translateY(8px);
  animation: activity-result-confirm-bar-reveal 380ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.activity-result-confirm-bar__actions.is-animated {
  animation-delay: 180ms;
}

@keyframes activity-result-confirm-bar-reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
