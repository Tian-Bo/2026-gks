<template>
  <div class="activity-selected-result" :class="`activity-selected-result--${type}`">
    <div class="activity-selected-result__header">
      <span class="activity-selected-result__check iconfont icon-zhengque" aria-hidden="true" />
      <span>{{ title }}</span>
    </div>

    <div v-if="type === 'product'" class="activity-selected-result__products">
      <div v-for="item in products" :key="`${item.name}-${item.price}`" class="activity-selected-result__product">
        <img :src="item.image" :alt="item.name" class="activity-selected-result__product-image">
        <div class="activity-selected-result__product-name">{{ item.name }}</div>
        <div>
          <span
            class="activity-selected-result__product-tag"
            :class="getProductTagClass(item)"
          >{{ item.typeLabel || '套餐' }}</span>
        </div>
        <div class="activity-selected-result__product-meta">
          <span>库存</span>
          <strong>{{ item.stock || '--' }}</strong>
        </div>
        <div class="activity-selected-result__product-meta activity-selected-result__product-meta--price">
          <span>售价</span>
          <strong>{{ item.price || '--' }}</strong>
        </div>
      </div>
    </div>

    <div v-if="requirement" class="activity-selected-result__requirement">
      <span>补充诉求：</span>
      <strong>{{ requirement }}</strong>
    </div>
  </div>
</template>

<script setup lang="ts">
type SelectedProduct = {
  name: string
  image: string
  typeLabel?: string
  typeTone?: 'red' | 'orange' | 'green'
  stock?: string
  price?: string
}

defineProps<{
  type: 'text' | 'product'
  title: string
  products?: SelectedProduct[]
  requirement?: string
}>()

function getProductTagClass(item: SelectedProduct) {
  if (item.typeTone === 'green' || item.typeLabel === '套餐')
    return 'activity-selected-result__product-tag--green'
  if (item.typeTone === 'orange' || item.typeLabel === '储值卡')
    return 'activity-selected-result__product-tag--orange'
  return 'activity-selected-result__product-tag--red'
}
</script>

<style scoped>
.activity-selected-result {
  width: 100%;
  box-sizing: border-box;
  border-radius: 12px;
  background: #f5f6f7;
  text-align: left;
}

.activity-selected-result--text {
  min-height: 48px;
  padding: 14px 12px;
}

.activity-selected-result--product {
  padding: 14px 12px 18px;
}

.activity-selected-result__header {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #0f182a;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
}

.activity-selected-result__check {
  display: inline-flex;
  width: 13.333px;
  height: 13.333px;
  flex: 0 0 auto;
  flex-basis: 13.333px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #2EB450;
  color: #ffffff;
  font-size: 9px;
  line-height: 1;
}

.activity-selected-result__products {
  margin-top: 18px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding-left: 22px;
}

.activity-selected-result__product {
  display: grid;
  grid-template-columns: 32px minmax(0,1fr) 76px 116px 142px;
  align-items: center;
  column-gap: 16px;
}

.activity-selected-result__product-image {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  object-fit: cover;
  background: #f5f6f7;
}

.activity-selected-result__product-name {
  overflow: hidden;
  color: #0f182a;
  font-size: 14px;
  font-weight: 600;
  line-height: 20px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.activity-selected-result__product-tag {
  display: inline-block;
  height: 18px;
  align-items: center;
  justify-content: center;
  border-radius: 19px;
  line-height: 18px;
  padding: 0 6px;
  font-size: 10px;
  font-weight: 500;
}

.activity-selected-result__product-tag--green {
  background: #EAFBF0;
  color: #16A34A;
}

.activity-selected-result__product-tag--orange {
  background: #FFF3E8;
  color: #F08C35;
}

.activity-selected-result__product-tag--red {
  background: #FFF1F1;
  color: #EB433B;
}

.activity-selected-result__product-meta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #99a7bb;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
  white-space: nowrap;
}

.activity-selected-result__product-meta span {
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
}

.activity-selected-result__product-meta strong {
  color: #0f182a;
  font-size: 16px;
  font-weight: 600;
  line-height: 20px;
}

.activity-selected-result__requirement {
  margin-top: 14px;
  color: #64748b;
  font-size: 14px;
  font-weight: 400;
  line-height: 22px;
}

.activity-selected-result__requirement strong {
  color: #0f182a;
  font-weight: 500;
}
</style>
