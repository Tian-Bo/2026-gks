<template>
  <KlBottomDrawer
    v-model="open"
    :show-header="false"
    :show-close="true"
    height="calc(100vh - 72px)"
  >
    <div class="ai-inspiration-preview">
      <div v-if="item" class="ai-inspiration-preview__shell">
        <section class="ai-inspiration-preview__canvas">
          <div class="ai-inspiration-preview__preview-frame">
            <div v-if="isActivityPreview" class="ai-inspiration-preview__phone">
              <div class="ai-inspiration-preview__phone-screen">
                <PreviewMiniProgramHeader class="ai-inspiration-preview__mini-header" mode="status" />
                <iframe
                  class="ai-inspiration-preview__iframe"
                  :src="item.activityUrl"
                  title="活动预览"
                />
              </div>
            </div>
            <img v-else class="ai-inspiration-preview__image" :src="previewImage" :alt="item.typeLabel">
          </div>
        </section>

        <aside class="ai-inspiration-preview__side">
          <header class="ai-inspiration-preview__header">
            <div class="ai-inspiration-preview__brand">
              <span class="ai-inspiration-preview__brand-icon">
                <img :src="displayAuthorIcon" alt="">
              </span>
              <span class="ai-inspiration-preview__brand-name">{{ item.author || '快灵出品' }}</span>
            </div>

            <div class="ai-inspiration-preview__actions">
              <KlHoverAction
                class="ai-inspiration-preview__icon-btn"
                :class="{ 'is-liked': item.isLiked }"
                icon-size="16px"
                aria-label="收藏"
                @click="emit('toggleLike', item)"
              >
                <i class="iconfont icon-hongxin"></i>
              </KlHoverAction>
              <span class="ai-inspiration-preview__count">{{ item.likeCount ?? item.likes ?? 0 }}</span>
              <KlHoverAction class="ai-inspiration-preview__icon-btn" icon-size="16px" aria-label="分享">
                <i class="iconfont icon-fenxiang"></i>
              </KlHoverAction>
            </div>
          </header>

          <div class="ai-inspiration-preview__meta">
            <span>{{ item.publishDate || '-' }}</span>
            <i></i>
            <span>使用:{{ item.views || 0 }}次</span>
            <i></i>
            <span>{{ item.sourceLabel || sourceLabel }}</span>
          </div>

          <div class="ai-inspiration-preview__prompt-label">{{ promptLabel }}</div>
          <div class="ai-inspiration-preview__prompt">{{ displayPrompt }}</div>

          <button type="button" class="ai-inspiration-preview__adopt" @click="emit('adopt', item)">
            <i class="iconfont" :class="adoptIconClass"></i>
            <span>{{ adoptText }}</span>
          </button>
        </aside>
      </div>
    </div>
  </KlBottomDrawer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import KlHoverAction from '../kl/KlHoverAction.vue'
import KlBottomDrawer from '../kl/KlBottomDrawer.vue'
import PreviewMiniProgramHeader from '../preview/PreviewMiniProgramHeader.vue'

type AiInspirationPreviewItem = {
  id: string
  type: 'activity' | 'poster'
  typeLabel: '活动' | '海报'
  prompt: string
  image: string
  previewImage?: string
  activityUrl?: string
  author?: string
  authorIcon?: string
  publishDate?: string
  views?: string
  likes?: string
  likeCount?: number
  isLiked?: boolean
  sourceLabel?: string
  detail?: string
}

const open = defineModel<boolean>({ default: false })

const props = defineProps<{
  item: AiInspirationPreviewItem | null
}>()

const emit = defineEmits<{
  (e: 'adopt', item: AiInspirationPreviewItem): void
  (e: 'toggleLike', item: AiInspirationPreviewItem): void
}>()

const authorIcon = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778665743_pqhKFv1Ywb.png'

const previewImage = computed(() => props.item?.previewImage || props.item?.image || '')
const isActivityPreview = computed(() => props.item?.type === 'activity' && Boolean(props.item?.activityUrl))
const displayAuthorIcon = computed(() => props.item?.authorIcon || authorIcon)
const sourceLabel = computed(() => (props.item?.type === 'poster' ? '海报由AI生成' : '活动由AI生成'))
const promptLabel = computed(() => (props.item?.type === 'poster' ? '海报提示词' : '活动提示词'))
const displayPrompt = computed(() => props.item?.detail || props.item?.prompt || '')
const adoptText = computed(() => (props.item?.type === 'poster' ? '做同款海报' : '做同款活动'))
const adoptIconClass = computed(() => (props.item?.type === 'activity' ? 'icon-renqun' : 'icon-jinru'))
</script>

<style scoped>
.ai-inspiration-preview {
  height: 100%;
  background: #ffffff;
}

.ai-inspiration-preview__shell {
  display: flex;
  width: min(1200px, calc(100vw - 64px));
  height: 100%;
  margin: 0 auto;
  background: #f5f6f7;
}

.ai-inspiration-preview__canvas {
  width: 800px;
  min-width: 0;
  height: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow-x: hidden;
  overflow-y: auto;
  background: #f5f6f7;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.ai-inspiration-preview__canvas::-webkit-scrollbar {
  display: none;
}

.ai-inspiration-preview__preview-frame {
  width: 375px;
  flex: 0 0 375px;
  margin-top: 64px;
  display: flex;
  justify-content: center;
}

.ai-inspiration-preview__phone {
  width: 316px;
  height: 666px;
  flex: 0 0 316px;
  padding: 8px;
  border-radius: 32px;
  background: #0f182a;
  box-sizing: border-box;
}

.ai-inspiration-preview__phone-screen {
  position: relative;
  width: 300px;
  height: 650px;
  overflow: hidden;
  border-radius: 24px;
  background: #ffffff;
}

.ai-inspiration-preview__mini-header {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 2;
  transform: scale(0.8);
  transform-origin: left top;
}

.ai-inspiration-preview__image {
  display: block;
  width: 100%;
  height: auto;
  object-fit: contain;
}

.ai-inspiration-preview__iframe {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  display: block;
  width: 100%;
  height: calc(100% - 37px);
  border: 0;
  background: #ffffff;
}

.ai-inspiration-preview__side {
  width: 400px;
  flex: 0 0 400px;
  display: flex;
  flex-direction: column;
  padding: 22px 32px;
  box-sizing: border-box;
  border-left: 1px solid #f2f5fa;
  background: #ffffff;
}

.ai-inspiration-preview__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.ai-inspiration-preview__brand {
  flex: 1 1 auto;
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #0f182a;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
}

.ai-inspiration-preview__brand-name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ai-inspiration-preview__brand-icon {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 50%;
  background: #f5f6f7;
}

.ai-inspiration-preview__brand-icon img {
  display: block;
  width: 22px;
  height: 22px;
  object-fit: contain;
}

.ai-inspiration-preview__actions {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

.ai-inspiration-preview__icon-btn {
  color: #0f182a;
}

.ai-inspiration-preview__icon-btn.is-liked {
  color: #e62222;
}

.ai-inspiration-preview__icon-btn .iconfont {
  font-size: 16px;
  line-height: 1;
}

.ai-inspiration-preview__count {
  margin: 0 16px 0 4px;
  color: #0f182a;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
}

.ai-inspiration-preview__meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  color: #99a7bb;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
}

.ai-inspiration-preview__meta i {
  width: 1px;
  height: 12px;
  background: #e3e9f1;
}

.ai-inspiration-preview__prompt-label {
  margin-top: 31px;
  color: #99a7bb;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
}

.ai-inspiration-preview__prompt {
  margin-top: 8px;
  color: #0f182a;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
  text-align: justify;
  white-space: pre-wrap;
  word-break: break-word;
}

.ai-inspiration-preview__adopt {
  position: relative;
  width: 336px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 32px;
  padding: 0;
  border: 0;
  border-radius: 12px;
  background: #0f182a;
  color: #ffffff;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  line-height: 20px;
  transition:
    background-color 0.18s ease,
    box-shadow 0.18s ease;
}

.ai-inspiration-preview__adopt:hover {
  background: #111c30;
  box-shadow: 0 10px 24px rgba(15, 24, 42, 0.18);
}

.ai-inspiration-preview__adopt:active {
  box-shadow: 0 6px 16px rgba(15, 24, 42, 0.14);
}

.ai-inspiration-preview__adopt .iconfont {
  font-size: 16px;
  line-height: 1;
}

@media (max-width: 1180px) {
  .ai-inspiration-preview__shell {
    width: min(1000px, calc(100vw - 48px));
  }

  .ai-inspiration-preview__canvas {
    width: calc(100% - 360px);
  }

  .ai-inspiration-preview__side {
    width: 360px;
    flex-basis: 360px;
    padding-right: 24px;
    padding-left: 24px;
  }

  .ai-inspiration-preview__adopt {
    width: 100%;
  }
}
</style>
