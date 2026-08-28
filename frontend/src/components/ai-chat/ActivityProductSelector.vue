<template>
  <div class="rounded-[18px] py-[16px]">
    <div class="mb-[12px] flex items-center justify-between gap-[12px]" :class="{ 'activity-selector-reveal': animate }" style="--activity-selector-reveal-delay: 0ms">
      <div class="text-[16px] font-600 leading-[22px] text-[#0F172A]">
        {{ title }}
      </div>
      <button v-if="!readonly" type="button"
        class="inline-flex cursor-pointer items-center whitespace-nowrap border-none bg-transparent p-0 text-[14px] font-400 leading-[22px] text-[#0F172A] transition-opacity hover:opacity-70"
        @click="emit('skip')">
        跳过
      </button>
    </div>

    <div class="rounded-[18px] bg-[#F5F6F7] p-[12px]" :class="{ 'activity-selector-reveal': animate }" style="--activity-selector-reveal-delay: 110ms">
      <div v-if="readonly"
        class="rounded-[16px] bg-[#FFF7F7] px-[16px] py-[14px] text-[14px] leading-[22px] text-[#E62222]">
        <div class="font-600">
          {{ readonlySummaryText }}
        </div>
        <div v-if="readonlySummaryItems.length" class="mt-[8px] space-y-[4px]">
          <div v-for="item in readonlySummaryItems" :key="item">
            {{ item }}
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex min-h-[220px] items-center justify-center text-[14px] text-[#667085]">
        正在加载商品...
      </div>

      <div v-else-if="pagedProducts.length && !readonly" class="space-y-[10px]">
        <button v-for="(item, index) in pagedProducts" :key="item.id" type="button"
          class="grid w-full cursor-pointer grid-cols-[32px_minmax(0,1fr)_56px_116px_142px_24px] items-center gap-x-[16px] rounded-[16px] border-2 border-solid bg-white px-[14px] py-[14px] text-left transition-all"
          :style="animate ? { '--activity-selector-reveal-delay': `${180 + index * 70}ms` } : undefined"
          :class="[
            isSelected(item.id)
              ? '!border-[#E62222] !bg-[#FFF7F7] shadow-none'
              : 'border-transparent',
            { 'activity-selector-reveal': animate },
          ]"
          @click="toggleItem(item.id)">
          <img :src="item.image" :alt="item.name" class="block h-[32px] w-[32px] shrink-0 rounded-[8px] object-cover">

          <div class="min-w-0 truncate text-[15px] font-600 text-[#1C2537]">
            {{ item.name }}
          </div>
          <div>
            <span class="inline-flex h-[18px] min-w-0 items-center justify-center rounded-full px-[8px] text-[10px] font-500"
              :class="getTypeClass(item.typeTone)">
              {{ item.typeLabel }}
            </span>
          </div>
          <div class="flex min-w-0 items-center text-[14px] font-400 text-[#99A7BB]">
            <span>库存</span>
            <span class="ml-[8px] text-[16px] font-600 text-[#0F182A]">{{ item.stock }}</span>
          </div>
          <div class="flex min-w-0 items-center text-[14px] font-400 text-[#99A7BB]">
            <span>售价</span>
            <span class="ml-[8px] text-[16px] font-600 text-[#0F182A]">{{ item.price }}</span>
          </div>

          <div
            class="inline-flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[6px] border-2 border-solid"
            :class="isSelected(item.id) ? 'border-[#E62222] bg-[#E62222] text-white' : 'border-[#D9E0EA] bg-white text-transparent'">
            <i v-if="isSelected(item.id)" class="iconfont icon-zhengque !text-[16px] leading-none"></i>
          </div>
        </button>
      </div>

      <div v-else-if="!readonly"
        class="flex min-h-[220px] items-center justify-center text-center text-[14px] leading-[22px] text-[#98A2B3]">
        {{ emptyText || '暂无可选商品' }}
      </div>

      <div v-if="!loading && !readonly && totalPages > 1"
        class="mt-[12px] flex items-center justify-between gap-[12px] px-[4px]">
        <div class="text-[12px] text-[#667085]">
          第 {{ currentPage }} / {{ totalPages }} 页
        </div>
        <div class="flex items-center gap-[8px]">
          <KlButton size="sm" variant="secondary" fill="outline" class="!px-[12px]" :disabled="currentPage === 1"
            @click="currentPage--">
            上一页
          </KlButton>
          <KlButton size="sm" variant="secondary" fill="outline" class="!px-[12px]"
            :disabled="currentPage === totalPages" @click="currentPage++">
            下一页
          </KlButton>
        </div>
      </div>

    </div>

    <div v-if="!readonly" class="mt-[12px] rounded-[16px] bg-white py-[10px]" :class="{ 'activity-selector-reveal': animate }" style="--activity-selector-reveal-delay: 390ms">
      <div class="mb-[8px] text-[16px] font-600 leading-[22px] text-[#0F172A]">
        或者告诉快灵你的诉求（填写规则为商品名称+价格）
      </div>
      <div class="rounded-[16px] bg-[#F5F6F7] px-[14px] py-[10px]">
        <textarea :value="customRequirement || ''"
          class="min-h-[44px] w-full resize-none border-none bg-transparent p-0 text-[13px] leading-[22px] text-[#111827] outline-none placeholder:text-[#98A2B3]"
          placeholder="填写示例：水光针199元"
          @input="emit('update:customRequirement', (($event.target as HTMLTextAreaElement | null)?.value || ''))" />
      </div>
    </div>

    <KlButton v-if="!readonly" size="lg" variant="primary" fill="solid"
      class="activity-product-selector__confirm mt-[28px] mb-[4px] w-full !justify-center !gap-[8px] !rounded-[16px]"
      :class="{ 'activity-selector-reveal': animate }" style="--activity-selector-reveal-delay: 500ms"
      :disabled="loading || !canConfirm" @click="emit('confirm')">
      <span class="mr-[8px] text-[20px] leading-none">↗</span>
      <span>确认并继续</span>
    </KlButton>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

export type ActivityProductItem = {
  id: string
  name: string
  image: string
  price: string
  stock: string
  typeLabel: string
  typeTone: 'red' | 'orange' | 'green'
}

const props = defineProps<{
  title: string
  products: ActivityProductItem[]
  selectedIds: string[]
  pageSize?: number
  loading?: boolean
  emptyText?: string
  customRequirement?: string
  readonly?: boolean
  readonlySummaryText?: string
  readonlySummaryItems?: string[]
  animate?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:selectedIds', value: string[]): void
  (e: 'update:customRequirement', value: string): void
  (e: 'skip'): void
  (e: 'confirm'): void
}>()

const currentPage = ref(1)
const resolvedPageSize = computed(() => props.pageSize || 3)
const totalPages = computed(() => Math.max(1, Math.ceil(props.products.length / resolvedPageSize.value)))
const pagedProducts = computed(() => {
  const start = (currentPage.value - 1) * resolvedPageSize.value
  return props.products.slice(start, start + resolvedPageSize.value)
})
const readonlySummaryText = computed(() => props.readonlySummaryText || '已完成项目选择')
const readonlySummaryItems = computed(() => props.readonlySummaryItems || [])
const canConfirm = computed(() => props.selectedIds.length > 0 || Boolean((props.customRequirement || '').trim()))

watch(() => props.products.length, () => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

function isSelected(id: string) {
  return props.selectedIds.includes(id)
}

function toggleItem(id: string) {
  const nextSelectedIds = isSelected(id)
    ? props.selectedIds.filter(itemId => itemId !== id)
    : [...props.selectedIds, id]
  emit('update:selectedIds', nextSelectedIds)
}

function getTypeClass(typeTone: ActivityProductItem['typeTone']) {
  if (typeTone === 'green')
    return 'bg-[#EAFBF0] text-[#16A34A]'
  if (typeTone === 'orange')
    return 'bg-[#FFF3E8] text-[#F08C35]'
  return 'bg-[#FFF1F1] text-[#EB433B]'
}
</script>

<style scoped>
.activity-selector-reveal {
  opacity: 0;
  transform: translateY(8px);
  animation: activity-selector-reveal 360ms cubic-bezier(0.22, 1, 0.36, 1) var(--activity-selector-reveal-delay, 0ms) both;
}

@keyframes activity-selector-reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

:deep(.activity-product-selector__confirm.ant-btn:disabled),
:deep(.activity-product-selector__confirm.ant-btn[disabled]),
:deep(.activity-product-selector__confirm.ant-btn:disabled:hover),
:deep(.activity-product-selector__confirm.ant-btn[disabled]:hover) {
  background: #b7b9bf !important;
  border-color: #b7b9bf !important;
  color: #ffffff !important;
}
</style>
