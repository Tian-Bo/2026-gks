<template>
  <div class="ai-page-scroll relative h-screen overflow-y-auto">
    <div
      class="ai-page-background pointer-events-none absolute inset-0 bg-[radial-gradient(rgba(148,163,184,0.14)_0.8px,transparent_0.8px)] bg-[length:16px_16px]" />
    <header class="ai-page-header fixed inset-x-0 top-0 z-50 flex h-[72px] items-center justify-between px-[26px]">
      <div class="ai-logo-back-area">
        <img class="h-[28px] w-[105px]" :src="aiLogo" alt="快灵" />
        <div
          v-if="showWorkbenchReturn"
          class="ai-logo-back-button absolute left-0 top-[58px] cursor-pointer flex w-[160px] h-[40px] items-center px-[12px] gap-[8px] rounded-[8px] bg-[#ffffff] c-[#000]"
          @click="goBack"
        >
          <i class="iconfont icon-youjiantou rotate-180 text-[18px] c-[#000]"></i>
          <span>回到工作台</span>
        </div>
      </div>
      <div class="flex items-center gap-[12px]">
        <button
          v-if="generatingTaskCount"
          type="button"
          class="ai-generation-task-entry"
          aria-label="查看生成中任务"
          @click="goHistory"
        >
          <span class="ai-generation-task-entry__icon">
            <i class="iconfont icon-lishi1"></i>
          </span>
          <span class="ai-generation-task-entry__text">{{ generatingTaskText }}</span>
        </button>
        <KlHoverAction v-else icon-size="28px" @click="goHistory">
          <i class="iconfont icon-lishi1"></i>
        </KlHoverAction>
        <a-popover
          v-model:open="messagePanelOpen"
          trigger="click"
          placement="bottomRight"
          overlay-class-name="ai-message-popover"
        >
          <template #content>
            <div class="ai-message-panel">
              <div class="ai-message-panel__header">
                <div class="ai-message-panel__tabs">
                  <button
                    type="button"
                    class="ai-message-panel__tab"
                    :class="{ 'ai-message-panel__tab--active': messageTab === 'all' }"
                    @click="messageTab = 'all'"
                  >
                    全部消息
                  </button>
                  <button
                    type="button"
                    class="ai-message-panel__tab"
                    :class="{ 'ai-message-panel__tab--active': messageTab === 'unread' }"
                    @click="messageTab = 'unread'"
                  >
                    未读
                    <span v-if="hasUnreadMessages" class="ai-message-panel__tab-dot" />
                  </button>
                </div>
                <button
                  type="button"
                  class="ai-message-panel__clear iconfont icon-yijianqingchu"
                  title="一键清除"
                  aria-label="一键清除"
                  @click="clearUnreadMessages"
                />
              </div>

              <div v-if="!displayMessageList.length" class="ai-message-panel__empty">
                <img :src="messageEmptyIcon" alt="" class="ai-message-panel__empty-icon" />
                <span>暂无消息</span>
              </div>
              <div v-else class="ai-message-panel__list">
                <article
                  v-for="message in displayMessageList"
                  :key="message.id"
                  class="ai-message-panel__item"
                >
                  <div class="ai-message-panel__avatar">
                    <span class="iconfont" :class="message.icon" />
                  </div>
                  <div class="ai-message-panel__body">
                    <div class="ai-message-panel__meta">
                      <span class="ai-message-panel__sender">{{ message.sender }}</span>
                      <span v-if="message.unread" class="ai-message-panel__dot" />
                      <span class="ai-message-panel__time">{{ message.time }}</span>
                    </div>
                    <div class="ai-message-panel__content">{{ message.content }}</div>
                    <button
                      v-if="message.actionText"
                      type="button"
                      class="ai-message-panel__action"
                      @click="handleMessageAction(message)"
                    >
                      <span>{{ message.actionText }}</span>
                      <span class="iconfont icon-jinru ai-message-panel__action-icon" />
                    </button>
                    <div v-if="message.preview" class="ai-message-panel__preview">
                      {{ message.preview }}
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </template>
          <KlHoverAction icon-size="28px">
            <i class="iconfont icon-xiaoxi"></i>
          </KlHoverAction>
        </a-popover>
        <div
          class="cursor-pointer mx-[4px] box-border min-w-[79px] h-[28px] flex items-center justify-between gap-[6px] bg-[#0F182A] rounded-[8px] p-[3px]"
          @click="csModalOpen = true"
        >
          <span class="text-[12px] font-600 text-white ml-[3px]">升级</span>
          <div
            class="min-w-[40px] h-[22px] px-[4px] flex items-center rounded-[6px] justify-center text-[12px] gap-[3px] bg-[#ff5e56] text-white bg-[rgba(255,255,255,0.11)]">
            <img class="h-[11px] w-[6px]"
              src="https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778576253_EM1JkwfJ1h.png">
            {{ aiPointsBalanceText }}
          </div>
        </div>
        <KlUserAvatarDropdown placement="bottomRight" logout-only />
      </div>
    </header>

    <main class="relative pt-[88px]">
      <!-- <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="translate-y--2 opacity-0"
        enter-to-class="translate-y-0 opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y--2 opacity-0">
        <div v-if="statusBadge"
          class="mx-auto mb-5 w-fit flex items-center gap-2 rounded-full bg-[#ebfff3] px-4 text-3 text-[#1d9c55] font-700 leading-8.5 shadow-[0_14px_26px_rgba(37,179,91,0.12)]">
          <span class="inline-flex h-4 w-4 items-center justify-center" aria-hidden="true" v-html="statusBadgeIcon" />
          <span>{{ statusBadge }}</span>
        </div>
      </Transition> -->

      <section class="mx-auto w-[728px] flex justify-between pt-[8px] pr-[22px] pl-[16px]">
        <div class="pt-[8px]">
          <img class="block h-[43px] w-auto max-w-none" :src="aiGreet" width="786" height="70" alt="Hi，让活动在对话中形成" />
          <p class="mt-[8px] text-[14px] text-[#64748B]">告诉我你的诉求，帮你搞定一切，搭建活动快人一步</p>
        </div>

        <div class="flex justify-end">
          <img class="h-[128px] w-[132px]"
            src="https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1783910905_C0hp9xOdjV.png">
        </div>
      </section>

      <section class="ai-home-composer-section mx-auto w-[728px] mt-[-20px] relative z-[2]">
        <div class="rounded-[24px] bg-#E3D8FD p-[4px]" style="border-radius: 24px;
background: linear-gradient(90deg, #EAE1FF 0%, #E5E8FF 100%);
box-shadow: 0 10px 30px 0 rgba(128, 144, 155, 0.20);">
          <div class="ai-mode-tabs">
            <div
              class="ai-mode-tab ai-mode-tab--activity"
              :class="{ 'is-active': activeMode === 'activity' }"
              @click="switchMode('activity')"
            >
              <img class="ai-mode-tab__bg" :src="aiActivityBg" alt="" draggable="false" @dragstart.prevent />
              <div class="ai-mode-tab__label">做活动</div>
              <img class="ai-mode-tab__asset ai-mode-tab__asset--activity" :src="aiActivityImage" alt="" draggable="false" @dragstart.prevent />
            </div>
            <div
              class="ai-mode-tab ai-mode-tab--poster"
              :class="{ 'is-active': activeMode === 'poster' }"
              @click="switchMode('poster')"
            >
              <img class="ai-mode-tab__bg" :src="aiPosterBg" alt="" draggable="false" @dragstart.prevent />
              <div class="ai-mode-tab__label">做海报</div>
              <img class="ai-mode-tab__asset ai-mode-tab__asset--poster mt-[-4px]" :src="aiPosterImage" alt="" draggable="false" @dragstart.prevent />
            </div>
          </div>

          <div ref="composerContainerRef"
            class="relative z-[12] flex min-h-[180px] w-[720px] cursor-text flex-col rounded-[0_20px_20px_20px] bg-white p-[16px]"
            @click="handleComposerInteract">
            <input ref="uploadInputRef" type="file" accept="image/*" multiple class="hidden"
              @change="handleUploadInputChange">
            <div v-if="pastedImages.length" class="mb-[12px] flex flex-wrap gap-[12px]">
              <div v-for="image in pastedImages" :key="image.id"
                class="relative h-[64px] w-[64px] overflow-hidden rounded-[12px] border border-[#E2E8F0] bg-[#F8FAFC]">
                <img :src="image.url" :alt="image.name" class="block h-full w-full object-cover">
                <div
                  class="absolute right-[4px] top-[4px] inline-flex h-[20px] w-[20px] cursor-pointer items-center justify-center rounded-full bg-[rgba(15,24,42,0.72)] text-white"
                  @click.stop="removePastedImage(image.id)">
                  <i class="iconfont icon-guanbi text-[12px]"></i>
                </div>
              </div>
            </div>
            <textarea ref="composerTextareaRef" v-model.trim="promptText"
              class="ai-composer-textarea mb-[16px] min-h-[56px] appearance-none resize-none overflow-y-hidden border-none bg-transparent p-0 shadow-none outline-none ring-0 placeholder:text-[#CBD5E1] focus:outline-none"
              @input="resizeComposerTextarea()" @paste="handlePromptPaste" @keydown="handlePromptKeydown" :placeholder="activePlaceholder" />

            <div class="mt-auto flex items-center">
              <div class="flex flex-wrap items-center gap-[8px]">
                <div
                  class="relative inline-flex h-[36px] w-[36px] cursor-pointer items-center justify-center rounded-full bg-[#F2F5FA] text-[#0F182A]"
                  title="上传图片" aria-label="上传图片" @click="openImageUpload">
                  <i class="iconfont icon-fujian text-[20px]"></i>
                  <span class="ai-thinking-tooltip">上传图片</span>
                </div>
                <div class="relative flex gap-[6px] rounded-full bg-[#F2F5FA] p-[4px]">
                  <div
                    class="pointer-events-none absolute top-[4px] h-[28px] w-[28px] rounded-full bg-[#fff] shadow-[0_1px_2px_rgba(15,24,42,0.08)] transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                    :class="selectedThinkingMode === 'deep' ? 'left-[4px]' : 'left-[38px]'">
                  </div>
                  <div
                    class="relative z-[1] inline-flex h-[28px] w-[28px] cursor-pointer items-center justify-center rounded-full text-[#0F182A] transition-all duration-200 ease-out"
                    :class="selectedThinkingMode === 'deep' ? 'text-[#0F182A] scale-100' : 'text-[#64748B] scale-[0.96]'"
                    title="专家模式" aria-label="深度思考" @click="selectThinkingMode('deep')">
                    <i class="iconfont icon-sikao text-[20px]"></i>
                    <span class="ai-thinking-tooltip">专家模式</span>
                  </div>
                  <div
                    class="relative z-[1] inline-flex h-[28px] w-[28px] cursor-pointer items-center justify-center rounded-full text-[#0F182A] transition-all duration-200 ease-out"
                    :class="selectedThinkingMode === 'quick' ? 'text-[#0F182A] scale-100' : 'text-[#64748B] scale-[0.96]'"
                    title="快捷模式" aria-label="快速思考" @click="selectThinkingMode('quick')">
                    <i class="iconfont icon-kuaisu text-[20px]"></i>
                    <span class="ai-thinking-tooltip">快捷模式</span>
                  </div>
                </div>
                <KlDropdown v-for="option in currentPromptOptions" :key="option.key"
                  :overlay-width="getPromptOptionOverlayWidth(option.key)" placement="bottomLeft"
                  overlay-class-name="ai-selector-dropdown">
                  <div
                    class="relative flex h-[36px] cursor-pointer items-center gap-[6px] rounded-full bg-[#F2F5FA] pl-[8px] pr-[16px] transition-colors"
                    :aria-label="getPromptOptionTooltip(option.key)"
                  >
                    <span
                      class="ai-composer-selected-option-icon"
                      :class="{ 'has-image': shouldShowPromptOptionImage(option.key, getPromptOptionSelectedItem(option.key)) }"
                    >
                      <img
                        v-if="shouldShowPromptOptionImage(option.key, getPromptOptionSelectedItem(option.key))"
                        :src="(getPromptOptionSelectedItem(option.key) as any)?.image"
                        :alt="getPromptOptionSelectedItem(option.key)?.label || option.label"
                        class="ai-composer-selected-option-icon__image"
                        @error="handlePromptOptionImageError(option.key, getPromptOptionSelectedItem(option.key))"
                      >
                      <i
                        v-else
                        class="iconfont text-[20px]"
                        :class="getPromptOptionSelectedIconClass(option.key)"
                      ></i>
                    </span>
                    <span class="max-w-[96px] truncate text-[14px] text-[#0F182A]">{{ getPromptOptionDisplayLabel(option.key) }}</span>
                    <span class="ai-thinking-tooltip">{{ getPromptOptionTooltip(option.key) }}</span>
                  </div>
                  <template #overlay>
                    <div class="ai-selector-panel" :class="`ai-selector-panel--${option.key}`">
                      <div class="ai-selector-panel__title">{{ getPromptOptionTitle(option.key) }}</div>
                      <button
                        v-for="item in getPromptOptionItems(option.key)"
                        :key="item.value"
                        type="button"
                        class="ai-selector-option"
                        :class="{ 'is-active': isPromptOptionSelected(option.key, item.value) }"
                        @click="selectSetting(option.key, item.value)"
                      >
                          <span class="ai-selector-option__icon" :class="{ 'has-image': shouldShowPromptOptionImage(option.key, item) }">
                            <img
                              v-if="shouldShowPromptOptionImage(option.key, item as any)"
                              :src="(item as any).image"
                              :alt="item.label"
                              class="ai-selector-option__image"
                              @error="handlePromptOptionImageError(option.key, item)"
                            />
                            <i class="iconfont" :class="getPromptOptionItemIconClass(option.key, item)"></i>
                          </span>
                        <span class="ai-selector-option__text">
                          <span class="ai-selector-option__label">{{ item.label }}</span>
                          <span class="ai-selector-option__desc">{{ item.desc }}</span>
                        </span>
                        <i
                          v-if="isPromptOptionSelected(option.key, item.value)"
                          class="iconfont icon-chenggong ai-selector-option__check"
                          aria-hidden="true"
                        ></i>
                      </button>
                    </div>
                  </template>
                </KlDropdown>
                <KlDropdown v-if="showAiModelSelector" :overlay-width="300" placement="bottomLeft" overlay-class-name="ai-selector-dropdown">
                  <div
                    class="relative flex h-[36px] cursor-pointer items-center gap-[6px] rounded-full bg-[#F2F5FA] pl-[8px] pr-[16px] transition-colors"
                    aria-label="选择模型"
                  >
                    <i class="iconfont icon-Vector !text-[15px] top-[1px] relative"></i>
                    <span class="max-w-[112px] truncate text-[14px] text-[#0F182A]">{{ selectedAiModelLabel }}</span>
                    <span class="ai-thinking-tooltip">选择模型</span>
                  </div>
                  <template #overlay>
                    <div class="ai-selector-panel ai-selector-panel--ai-model">
                      <div class="ai-selector-panel__title">选择AI模型</div>
                      <button
                      v-for="item in imageModelOptions"
                        :key="item.value"
                        type="button"
                        class="ai-selector-option"
                        :class="{ 'is-active': selectedAiModel === item.value }"
                        @click="selectAiModel(item.value)"
                      >
                        <span class="ai-selector-option__icon">
                          <i class="iconfont icon-Vector"></i>
                        </span>
                        <span class="ai-selector-option__text">
                          <span class="ai-selector-option__label">{{ item.label }}</span>
                          <span class="ai-selector-option__desc">{{ item.desc }}</span>
                        </span>
                        <i
                          v-if="selectedAiModel === item.value"
                          class="iconfont icon-chenggong ai-selector-option__check"
                          aria-hidden="true"
                        ></i>
                      </button>
                    </div>
                  </template>
                </KlDropdown>
              </div>

              <template v-if="isGenerating">
                <div
                  class="ai-send-button-wrap ai-send-button-wrap--generating absolute bottom-[16px] right-[16px] h-[36px] w-[36px]">
                  <span class="ai-send-button-glow" aria-hidden="true"></span>
                  <button
                    type="button"
                    class="ai-send-button inline-flex h-[36px] w-[36px] cursor-default items-center justify-center rounded-full bg-[#0F182A] text-[#fff] outline-none ring-0 focus:outline-none focus:ring-0"
                    disabled
                  >
                    <i class="iconfont icon-jiazaizhong ai-send-icon--loading text-[20px]"></i>
                  </button>
                </div>
              </template>
              <template v-else>
                <div
                  class="ai-send-button-wrap absolute bottom-[16px] right-[16px] h-[36px] w-[36px]">
                  <span class="ai-send-button-glow" aria-hidden="true"></span>
                  <button
                    type="button"
                    class="ai-send-button inline-flex h-[36px] w-[36px] cursor-pointer items-center justify-center rounded-full bg-[#0F182A] text-[#fff] outline-none ring-0 shadow-none transition-all duration-200 ease-out hover:bg-[#1B2942] focus:outline-none focus:ring-0"
                    @mousedown.prevent @click="handleGenerate"
                  >
                    <i class="iconfont icon-fasong text-[20px]"></i>
                  </button>
                </div>
              </template>
            </div>
          </div>
        </div>

        <div class="mt-[28px] flex items-center justify-center gap-[16px]">
          <div v-for="item in quickPrompts" :key="item.id"
            class="inline-flex cursor-pointer flex-shrink-0 p-[8px_16px_8px_12px] items-center rounded-full bg-[#fff] gap-[1px]"
            @click="applyQuickPrompt(item)">
            🎉
            <span>{{ item.content }}</span>
          </div>
        </div>
      </section>

      <section class="ai-home-inspiration-section mt-[160px] px-[81px] pb-[16px] box-border">
        <div class="mb-[16px]">
          <h2 class="text-[18px] font-600 text-[#0F182A]">灵感推荐</h2>
        </div>

        <div class="grid grid-cols-8 gap-[16px]">
          <template v-if="showInspirationGridSkeleton">
            <article
              v-for="index in inspirationSkeletonCount"
              :key="`inspiration-skeleton-${index}`"
              class="ai-inspiration-card-skeleton"
              aria-hidden="true"
            >
              <div class="ai-inspiration-card-skeleton__image">
                <span class="ai-inspiration-card-skeleton__label"></span>
                <span class="ai-inspiration-card-skeleton__action"></span>
              </div>
            </article>
          </template>
          <template v-else>
            <article v-for="item in inspirationCardList" :key="item.id"
              class="group overflow-hidden rounded-[16px] bg-[rgba(255,255,255,0.84)] shadow-[0_12px_30px_rgba(15,23,42,0.05)] ring-1 ring-transparent transition-all duration-220 ease cursor-pointer hover:shadow-[0_20px_36px_rgba(15,23,42,0.1)] hover:ring-[rgba(15,24,42,0.08)]"
              :class="selectedCardId === item.id ? 'shadow-[0_0_0_2px_rgba(104,104,255,0.18),0_18px_36px_rgba(77,85,255,0.1)]' : ''"
              @click="openInspirationPreview(item)">
            <div
              class="ai-inspiration-card__image-wrap relative aspect-[206/302] bg-[#f4f5f7]"
              :class="{ 'is-loading': !isInspirationImageLoaded(item) }"
            >
              <div v-if="!isInspirationImageLoaded(item)" class="ai-inspiration-card__image-skeleton" aria-hidden="true">
                <span class="ai-inspiration-card__image-skeleton-label"></span>
                <span class="ai-inspiration-card__image-skeleton-action"></span>
              </div>
              <img
                :src="item.image"
                :alt="item.typeLabel"
                class="ai-inspiration-card__image block h-full w-full object-cover saturate-90"
                :class="{ 'is-loaded': isInspirationImageLoaded(item) }"
                @load="markInspirationImageLoaded(item)"
                @error="markInspirationImageLoaded(item)"
              >
              <span
                class="absolute left-[12px] top-[12px] px-[4px] inline-flex h-[18px] items-center rounded-[4px] text-[12px] text-[#fff] bg-[rgba(0,0,0,0.50)]">
                {{ item.typeLabel }}
              </span>
              <button
                type="button"
                class="ai-inspiration-card__adopt"
                @click.stop="adoptInspiration(item)"
              >
                <span>一键同款</span>
                <span class="iconfont icon-a-Vector1" aria-hidden="true"></span>
              </button>
            </div>
            </article>
          </template>
        </div>
      </section>
    </main>

    <AiInspirationPreviewDrawer
      v-model="inspirationPreviewVisible"
      :item="activeInspiration"
      @adopt="adoptInspiration"
      @toggle-like="toggleInspirationLike"
    />
    <KlContactServiceModal v-model="csModalOpen" />
    <KlLoginGuideModal v-model="loginGuideOpen" @authenticated="handleLoginAuthenticated" />
  </div>
</template>

<script setup lang="ts">
const aiLogo = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298062_3ELdZZ4ftV.png';
const aiGreet = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298061_8MqOxrpmoE.png';
const aiActivityImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298061_xlfmtIl9jM.png';
const aiPosterImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298062_M7NJvh4H98.png';
const aiActivityBg = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298060_Zuso5NI6zJ.png';
const aiPosterBg = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298062_patdqnQSLL.png';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api, { hasAiAccessToken } from '../standalone/api'
import type { AiInspirationItem } from '../standalone/types'
import request from '../standalone/request'
import KlContactServiceModal from '../components/kl/KlContactServiceModal.vue'
import KlLoginGuideModal from '../components/kl/KlLoginGuideModal.vue'
import KlDropdown from '../components/kl/KlDropdown.vue'
import KlUserAvatarDropdown from '../components/kl/KlUserAvatarDropdown.vue'
import { getStore } from '../standalone/storage'
import { klbMessage } from '../standalone/klbMessage'
import { buildActivityPreviewUrl, buildActivityPreviewUrlSync } from '../standalone/activityPreviewUrl'
import AiInspirationPreviewDrawer from '../components/ai/AiInspirationPreviewDrawer.vue'
import {
  aiModelOptions,
  defaultAiPageConfig,
  getPosterSizeIconClass,
  getPromptOptionIconClass as getSharedPromptOptionIconClass,
  getPromptOptionItems as getSharedPromptOptionItems,
  getPromptOptionOverlayWidth,
  getPromptOptionTitle,
  getPromptOptionTooltip,
  normalizeAiPageConfig,
  promptOptionMap,
  type ModeKey,
  type PromptOptionKey,
  type SelectorItem,
} from '../shared/composerOptions'
import {
  getAiGenerationTasks,
  getAiGenerationTaskCount,
  replaceAiGenerationTasks,
  subscribeAiGenerationTasks,
  upsertAiGenerationTask,
  type AiGenerationTask,
} from '../shared/generationTaskStatus'

const router = useRouter()
type ModeTab = {
  key: ModeKey
  label: string
  theme: 'orange' | 'purple'
  icon: string
}

type InspirationCard = {
  id: string
  type: ModeKey
  typeLabel: '活动' | '海报'
  prompt: string
  image: string
  inspirationId?: number
  sourceType?: string
  sourceId?: number
  activityId?: number
  activityModelId?: number
  activityUrl?: string
  previewImage?: string
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

type QuickPromptOption = {
  id: number | string
  content: string
  prompt: string
}

type PastedImage = {
  id: string
  file: File
  url: string
  name: string
}

type MessageItem = {
  id: number
  sender: string
  time: string
  content: string
  icon: string
  unread?: boolean
  actionText?: string
  actionPath?: string
  preview?: string
}

function inlineIcon(content: string, viewBox = '0 0 20 20') {
  return `
    <svg viewBox="${viewBox}" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
      ${content}
    </svg>
  `
}

const iconRobotSmile = `
  <svg viewBox="0 0 72 72" width="42" height="42">
    <rect x="12" y="12" width="48" height="48" rx="16" fill="url(#robotGradient)" />
    <path d="M28 32c0 5.2 3.9 9.4 8.7 9.4 5.2 0 9.3-4 9.3-9.4 0-5.1-3.7-9.2-8.8-9.2H28V32Z" fill="#ffffff" />
    <circle cx="32" cy="29.5" r="2.4" fill="#182038" />
    <circle cx="42" cy="29.5" r="2.4" fill="#182038" />
    <defs>
      <linearGradient id="robotGradient" x1="12" y1="12" x2="60" y2="60" gradientUnits="userSpaceOnUse">
        <stop stop-color="#1f42ff" />
        <stop offset="0.48" stop-color="#784dff" />
        <stop offset="1" stop-color="#0f1c49" />
      </linearGradient>
    </defs>
  </svg>
`
const iconActivity = inlineIcon(`<path d="M4 10h12" /><path d="M7 6h6" /><path d="M7 14h6" /><rect x="3.4" y="4.3" width="13.2" height="11.4" rx="3" />`)
const iconPoster = inlineIcon(`<rect x="3.5" y="4" width="13" height="12" rx="3" /><circle cx="8" cy="8" r="1.3" fill="currentColor" stroke="none" /><path d="m6 13 2.6-2.6a1 1 0 0 1 1.4 0L12 12l1.3-1.2a1 1 0 0 1 1.4.1L16 12.8" />`)
const iconPalette = inlineIcon(`<path d="M10 4.2a5.8 5.8 0 1 0 0 11.6c.8 0 1.4-.6 1.4-1.4 0-.5-.3-.9-.3-1.3 0-.8.6-1.4 1.4-1.4h1a3 3 0 0 0 0-6H10Z" /><circle cx="7.1" cy="8.3" r=".8" fill="currentColor" stroke="none" /><circle cx="10" cy="6.9" r=".8" fill="currentColor" stroke="none" /><circle cx="13" cy="8.5" r=".8" fill="currentColor" stroke="none" />`)
const iconGift = inlineIcon(`<path d="M4 8h12v8H4z" /><path d="M10 8v8" /><path d="M4 11h12" /><path d="M7.5 8c-1.2 0-2-1-2-2 0-.8.6-1.5 1.4-1.5 1.7 0 3 3.5 3 3.5S11.2 4.5 13 4.5c.8 0 1.5.7 1.5 1.5 0 1-.8 2-2 2" />`)
const iconResize = inlineIcon(`<rect x="4" y="5" width="12" height="10" rx="2" /><path d="M7 8h6M7 12h4" />`)
const iconCheckCircle = inlineIcon(`<circle cx="10" cy="10" r="7" /><path d="m6.8 10.2 2.1 2.1 4.3-4.4" />`)
const iconTimer = inlineIcon(`<circle cx="10" cy="10" r="6.5" /><path d="M10 6.8v3.6l2.2 1.2" />`)
const inspirationDefaultPreviewImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778663651_nlAVosokfd.png'
const inspirationSkeletonCount = 8

const modeTabs: ModeTab[] = [
  { key: 'activity', label: '做活动', theme: 'orange', icon: iconActivity },
  { key: 'poster', label: '做海报', theme: 'purple', icon: iconPoster },
]


const placeholderMap: Record<ModeKey, string> = {
  activity: '告诉我你的想法，为您生成活动',
  poster: '描述你想要的海报主题、风格和目标人群',
}

const maxPastedImageCount = 5

const activeMode = ref<ModeKey>('activity')
const showWorkbenchReturn = false
const selectedThinkingMode = ref<'deep' | 'quick'>('deep')
const selectedAiModel = ref(aiModelOptions[0].value)
const showAiModelSelector = false
const aiPageConfig = ref(normalizeAiPageConfig(null))
const failedOptionImageValues = ref<Set<string>>(new Set())
const selectedSettingsByMode = ref<Record<ModeKey, Record<PromptOptionKey, string>>>({
  activity: {
    tone: '通用风格',
    activityModel: '',
    posterSize: '',
  },
  poster: {
    tone: '通用风格',
    activityModel: '',
    posterSize: '3:4',
  },
})
const promptText = ref('')
const pastedImages = ref<PastedImage[]>([])
const selectedCardId = ref('')
const quickPromptList = ref<QuickPromptOption[]>([])
const inspirationCardList = ref<InspirationCard[]>([])
const isInspirationCardsLoading = ref(false)
const inspirationLoadedImageKeys = ref<Set<string>>(new Set())
const inspirationPreviewVisible = ref(false)
const activeInspiration = ref<InspirationCard | null>(null)
const isInspirationDetailLoading = ref(false)
const csModalOpen = ref(false)
const loginGuideOpen = ref(false)
const messagePanelOpen = ref(false)
const unreadCleared = ref(false)
const messageTab = ref<'all' | 'unread'>('all')
const messageEmptyIcon = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1782959243_DOIxAu2HgN.png'
const messageList = ref<MessageItem[]>([])
const isGenerating = ref(false)
const aiPointsBalance = ref<number | null>(null)
const generatingTaskCount = ref(getAiGenerationTaskCount())
const uploadInputRef = ref<HTMLInputElement | null>(null)
const composerContainerRef = ref<HTMLElement | null>(null)
const composerTextareaRef = ref<HTMLTextAreaElement | null>(null)
const isComposerActive = ref(false)
const statusStage = ref<'idle' | 'submitted' | 'generating'>('idle')
const composerTextareaMaxRows = 8
let statusTimer: ReturnType<typeof setTimeout> | null = null
let unsubscribeGenerationTasks: (() => void) | null = null

const activePlaceholder = computed(() => placeholderMap[activeMode.value])
const quickPrompts = computed(() => quickPromptList.value)
// 快捷设置跟随当前创建模式切换，避免活动和海报混用无关配置。
const currentPromptOptions = computed(() => promptOptionMap[activeMode.value])
const selectedAiModelLabel = computed(() =>
  imageModelOptions.value.find(item => item.value === selectedAiModel.value)?.label || imageModelOptions.value[0]?.label || '',
)
const imageModelOptions = computed(() => aiPageConfig.value.models)
const activityModelOptions = computed<SelectorItem[]>(() => aiPageConfig.value.activityModels)
const normalizedMessageList = computed(() =>
  messageList.value.map(message => ({
    ...message,
    unread: unreadCleared.value ? false : message.unread,
  })),
)
const displayMessageList = computed(() => {
  if (messageTab.value === 'unread')
    return normalizedMessageList.value.filter(message => message.unread)

  return normalizedMessageList.value
})
const hasUnreadMessages = computed(() => normalizedMessageList.value.some(message => message.unread))
const aiPointsBalanceText = computed(() =>
  aiPointsBalance.value === null ? '--' : aiPointsBalance.value.toLocaleString('zh-CN'),
)
const statusBadge = computed(() => {
  if (statusStage.value === 'submitted')
    return '任务已提交，生成中'
  if (statusStage.value === 'generating')
    return '正在理解你的需求，准备活动草稿'
  return ''
})
const statusBadgeIcon = computed(() => (statusStage.value === 'submitted' ? iconCheckCircle : iconTimer))
const generatingTaskText = computed(() => `${generatingTaskCount.value}个任务生成中...`)
const showInspirationGridSkeleton = computed(() => isInspirationCardsLoading.value && !inspirationCardList.value.length)

onMounted(() => {
  document.addEventListener('pointerdown', handleComposerOutsidePointerDown)
  void nextTick(() => resizeComposerTextarea())
  updateGeneratingTaskCount()
  unsubscribeGenerationTasks = subscribeAiGenerationTasks(updateGeneratingTaskCount)
  void refreshGeneratingTaskStatus()
  void fetchAiPageConfig()
  void fetchAiPointsBalance()
  void fetchAiQuickPrompts()
  void fetchAiInspirationCards()
})

watch(activeMode, () => {
  void fetchAiQuickPrompts()
})

watch(inspirationCardList, (cards) => {
  const activeImageKeys = new Set(cards.map(getInspirationImageKey))
  inspirationLoadedImageKeys.value = new Set(
    [...inspirationLoadedImageKeys.value].filter(key => activeImageKeys.has(key)),
  )
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', handleComposerOutsidePointerDown)
  unsubscribeGenerationTasks?.()
  unsubscribeGenerationTasks = null
  if (statusTimer)
    clearTimeout(statusTimer)

  clearPastedImages()
})

function goBack() {
  router.push('/')
}

function goHistory() {
  router.push('/history')
}

function updateGeneratingTaskCount() {
  generatingTaskCount.value = getAiGenerationTaskCount()
}

function isGeneratingMessageStatus(status?: string | null) {
  return status === 'pending' || status === 'streaming'
}

function inferGenerationTaskMode(scene?: string | null, meta?: Record<string, any> | null): ModeKey {
  const rawScene = String(scene || '').toLowerCase()
  const configuredPosterScene = String(aiPageConfig.value.posterScene || '').toLowerCase()
  const metaMode = String(meta?.mode || '').toLowerCase()

  if (metaMode === 'poster' || rawScene === configuredPosterScene || /poster|海报|kv/.test(rawScene))
    return 'poster'

  return 'activity'
}

async function refreshGeneratingTaskStatus() {
  if (!hasAiAccessToken())
    return

  try {
    const result = await api.ai.getAiConversationList({
      shop_id: getCurrentShopId(),
      page: 1,
      per_page: 8,
    })
    const remoteTasks: Array<Omit<AiGenerationTask, 'updatedAt'> & { updatedAt?: number }> = []

    await Promise.all((result.items || []).map(async (conversation: Record<string, any>) => {
      const conversationId = conversation.conversation_id
      if (!conversationId)
        return

      try {
        const messageResult = await api.ai.getAiConversationMessages(conversationId, {
          page: 1,
          per_page: 30,
        })
        const pendingAssistantMessage = [...(messageResult.items || [])].reverse().find(item =>
          item.role === 'assistant' && isGeneratingMessageStatus(item.status),
        )

        if (!pendingAssistantMessage)
          return

        remoteTasks.push({
          conversationId,
          assistantMessageId: pendingAssistantMessage.message_id,
          mode: inferGenerationTaskMode(conversation.scene, conversation.meta),
          title: conversation.title || undefined,
        })
      }
      catch {
        // 单条会话同步失败时保留本地状态，避免顶部提示短暂消失。
      }
    }))

    const freshLocalTasks = getAiGenerationTasks().filter(task => Date.now() - task.updatedAt < 12 * 1000)
    const mergedTasks = [...remoteTasks]
    freshLocalTasks.forEach((task) => {
      if (!mergedTasks.some(item => item.conversationId === task.conversationId || item.assistantMessageId === task.assistantMessageId))
        mergedTasks.push(task)
    })

    replaceAiGenerationTasks(mergedTasks)
    updateGeneratingTaskCount()
  }
  catch {
    updateGeneratingTaskCount()
  }
}

function clearUnreadMessages() {
  unreadCleared.value = true
}

function handleMessageAction(message: MessageItem) {
  if (!message.actionPath)
    return

  messagePanelOpen.value = false
  void router.push(message.actionPath)
}

function getInspirationImageKey(item: InspirationCard) {
  return `${item.id}::${item.image}`
}

function isInspirationImageLoaded(item: InspirationCard) {
  return inspirationLoadedImageKeys.value.has(getInspirationImageKey(item))
}

function markInspirationImageLoaded(item: InspirationCard) {
  const imageKey = getInspirationImageKey(item)
  if (inspirationLoadedImageKeys.value.has(imageKey))
    return

  inspirationLoadedImageKeys.value = new Set([...inspirationLoadedImageKeys.value, imageKey])
}

function mapAiInspirationToCard(item: AiInspirationItem): InspirationCard {
  const type: ModeKey = item.type === 'poster' ? 'poster' : 'activity'
  const image = String(item.cover_img || item.preview_image || item.image_url || '').trim() || inspirationDefaultPreviewImage
  const createdAt = String(item.created_at || '').trim()
  const likeCount = Number(item.like_count || 0)
  const usedCount = Number(item.used_count || 0)
  const activityId = Number(item.activity_id || 0)
  const activityModelId = Number(item.activity_model_id || 0)

  return {
    id: `ai-inspiration-${item.id}`,
    inspirationId: Number(item.id || 0),
    type,
    typeLabel: type === 'poster' ? '海报' : '活动',
    prompt: String(item.prompt || '').trim(),
    image,
    activityId,
    activityModelId,
    activityUrl: type === 'activity' ? buildActivityPreviewUrlSync(activityId, activityModelId) : '',
    previewImage: image,
    author: String(item.author_name || item.shop_name || '').trim(),
    authorIcon: String(item.author_avatar || item.shop_logo || '').trim(),
    publishDate: createdAt ? createdAt.slice(0, 10).replace(/-/g, '/') : '',
    views: usedCount > 0 ? String(usedCount) : '0',
    likes: String(likeCount),
    likeCount,
    isLiked: Number(item.is_liked || 0) === 1,
    sourceLabel: type === 'poster' ? '海报由AI生成' : '活动由AI生成',
    detail: String(item.prompt || '').trim(),
  }
}

async function fetchAiQuickPrompts() {
  const mode = activeMode.value
  try {
    const result = await api.ai.getAiInspirations({ type: mode, page: 1, per_page: 1 })
    if (mode !== activeMode.value)
      return

    quickPromptList.value = Array.isArray(result.quick_prompts)
      ? result.quick_prompts
        .map((item: Record<string, any>) => ({
          id: item.id || `${item.type}-${item.content}`,
          content: String(item.content || '').trim(),
          prompt: String(item.prompt || item.content || '').trim(),
        }))
        .filter((item: QuickPromptOption) => item.content)
      : []
  }
  catch (error) {
    if (mode !== activeMode.value)
      return

    console.warn('获取 AI 快捷提示词失败:', error)
    quickPromptList.value = []
  }
}

async function fetchAiInspirationCards() {
  isInspirationCardsLoading.value = true
  try {
    const result = await api.ai.getAiInspirations({ type: 'all', page: 1, per_page: 16 })
    const cards = Array.isArray(result.items) ? result.items.map(mapAiInspirationToCard) : []
    inspirationCardList.value = cards
    selectedCardId.value = cards[0]?.id || ''
  }
  catch (error) {
    console.warn('获取 AI 灵感卡片失败:', error)
    inspirationCardList.value = []
    selectedCardId.value = ''
  }
  finally {
    isInspirationCardsLoading.value = false
  }
}

function switchMode(mode: ModeKey) {
  activeMode.value = mode
}

function openImageUpload() {
  uploadInputRef.value?.click()
}

function selectThinkingMode(mode: 'deep' | 'quick') {
  selectedThinkingMode.value = mode
}

function handleComposerInteract(event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (!target)
    return

  isComposerActive.value = true

  if (target.closest('button, input, textarea, a, [role="button"], [data-no-composer-focus="true"]'))
    return

  if (target.closest('.cursor-pointer'))
    return

  composerTextareaRef.value?.focus()
}

function handleComposerOutsidePointerDown(event: PointerEvent) {
  const target = event.target as Node | null
  if (!target)
    return

  if (composerContainerRef.value?.contains(target))
    return

  isComposerActive.value = false
}

function appendPastedImages(files: File[]) {
  const remainingCount = maxPastedImageCount - pastedImages.value.length
  if (remainingCount <= 0)
    return

  const nextImages = files
    .filter(file => file.type.startsWith('image/'))
    .slice(0, remainingCount)
    .map((file, index) => ({
      id: `${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`,
      file,
      url: URL.createObjectURL(file),
      name: file.name || 'clipboard-image',
    }))

  if (!nextImages.length)
    return

  pastedImages.value = [...pastedImages.value, ...nextImages]
}

function handleUploadInputChange(event: Event) {
  const input = event.target as HTMLInputElement | null
  const files = input?.files ? Array.from(input.files) : []
  if (files.length)
    appendPastedImages(files)

  if (input)
    input.value = ''
}

function handlePromptPaste(event: ClipboardEvent) {
  const items = event.clipboardData?.items
  if (!items)
    return

  const nextImages = Array.from(items)
    .filter(item => item.type.startsWith('image/'))
    .map((item, index) => {
      const file = item.getAsFile()
      if (!file)
        return null

      return {
        id: `${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`,
        file,
        url: URL.createObjectURL(file),
        name: file.name || 'clipboard-image',
      }
    })
    .filter((image): image is PastedImage => image !== null)

  if (!nextImages.length)
    return

  appendPastedImages(nextImages.map(image => image.file))
}

function handlePromptKeydown(event: KeyboardEvent) {
  if (event.key !== 'Enter')
    return
  if (event.shiftKey)
    return
  if (event.isComposing || event.keyCode === 229)
    return

  event.preventDefault()
  void handleGenerate()
}

function resizeComposerTextarea(target = composerTextareaRef.value) {
  if (!target)
    return

  const style = window.getComputedStyle(target)
  const lineHeight = Number.parseFloat(style.lineHeight) || 24
  const paddingTop = Number.parseFloat(style.paddingTop) || 0
  const paddingBottom = Number.parseFloat(style.paddingBottom) || 0
  const maxHeight = Math.ceil(lineHeight * composerTextareaMaxRows + paddingTop + paddingBottom)

  target.style.height = 'auto'
  const nextHeight = Math.min(target.scrollHeight, maxHeight)
  target.style.height = `${nextHeight}px`
  target.style.overflowY = target.scrollHeight > maxHeight ? 'auto' : 'hidden'
}

watch(promptText, () => {
  void nextTick(() => resizeComposerTextarea())
})

// 图片预览依赖 object URL，移除时同步释放，避免页面长期停留时泄漏。
function removePastedImage(id: string) {
  const targetImage = pastedImages.value.find(image => image.id === id)
  if (!targetImage)
    return

  URL.revokeObjectURL(targetImage.url)
  pastedImages.value = pastedImages.value.filter(image => image.id !== id)
}

function clearPastedImages() {
  pastedImages.value.forEach(image => URL.revokeObjectURL(image.url))
  pastedImages.value = []
}

function getCurrentShopId() {
  const rawShopId = getStore('shop_id')
  if (rawShopId == null)
    return null

  const shopId = Number(rawShopId)
  return Number.isFinite(shopId) && shopId > 0 ? shopId : null
}

function handleLoginAuthenticated() {
  window.location.reload()
}

async function fetchAiPointsBalance() {
  if (!hasAiAccessToken()) {
    aiPointsBalance.value = null
    return
  }

  try {
    const result = await api.ai.getAiPoints({ shop_id: getCurrentShopId() })
    const balance = Number(result.balance)
    aiPointsBalance.value = Number.isFinite(balance) ? balance : 0
  }
  catch (error) {
    console.warn('获取 AI 灵点余额失败:', error)
    aiPointsBalance.value = null
  }
}

function buildUserMessageId() {
  return `u_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`
}

async function uploadDraftAttachments(images: PastedImage[]) {
  if (!images.length)
    return []

  return await Promise.all(images.map(async (image) => {
    const formData = new FormData()
    formData.append('file', image.file)

    const response = await request.post<any, { url: string }>('/common/v1/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    const url = String((response as any)?.url || '').trim()
    if (!url)
      throw new Error('上传失败：未返回图片地址')

    return {
      url,
      name: image.name,
      type: image.file.type,
      size: image.file.size,
    }
  }))
}

function getSelectedActivityModelPayload() {
  const selectedValue = getSelectedSettingValue('activityModel')
  if (!selectedValue)
    return {}

  const selectedModel = activityModelOptions.value.find(item => item.value === selectedValue)
  return {
    activity_model_id: selectedValue,
    activity_model_label: selectedModel?.label || '',
  }
}

function buildLandingComponentResult() {
  return {
    think_mode: selectedThinkingMode.value,
    think_mode_label: selectedThinkingMode.value === 'deep' ? '深度思考' : '快速思考',
    mode: activeMode.value,
    source: 'landing',
    ...getSelectedActivityModelPayload(),
  }
}

function buildAiMessageOptions() {
  return {
    style: getSelectedSettingValue('tone') || null,
    aspect_ratio: activeMode.value === 'poster' ? getSelectedSettingValue('posterSize') || null : null,
    activity_model: activeMode.value === 'activity' ? getSelectedSettingValue('activityModel') || null : null,
    image_model: selectedAiModel.value || null,
    thinking_mode: selectedThinkingMode.value,
  }
}

async function fetchAiPageConfig() {
  try {
    const result = await api.ai.getAiPageConfig()
    const normalized = normalizeAiPageConfig(result)
    aiPageConfig.value = normalized
    selectedSettingsByMode.value.activity.tone = normalized.defaults.style
    selectedSettingsByMode.value.activity.activityModel = normalized.defaults.activityModel
    selectedSettingsByMode.value.poster.tone = normalized.defaults.style
    selectedSettingsByMode.value.poster.posterSize = normalized.defaults.aspectRatio
    selectedAiModel.value = normalized.defaults.imageModel
  }
  catch {
    aiPageConfig.value = normalizeAiPageConfig(null)
    selectedSettingsByMode.value.activity.tone = defaultAiPageConfig.styles[0]?.value || ''
    selectedSettingsByMode.value.activity.activityModel = defaultAiPageConfig.activityModels[0]?.value || 'auto'
    selectedSettingsByMode.value.poster.tone = defaultAiPageConfig.styles[0]?.value || ''
    selectedSettingsByMode.value.poster.posterSize = defaultAiPageConfig.sizes[0]?.value || ''
    selectedAiModel.value = defaultAiPageConfig.models[0]?.value || ''
  }
}

function getSelectedSettingValue(key: PromptOptionKey) {
  return selectedSettingsByMode.value[activeMode.value][key]
}

function getSelectedSettingLabel(key: PromptOptionKey) {
  if (key === 'activityModel') {
    return getPromptOptionSelectedItem(key)?.label || '活动模型'
  }
  return getPromptOptionSelectedItem(key)?.label || '请选择'
}

function getPromptOptionDisplayLabel(key: PromptOptionKey) {
  return getSelectedSettingLabel(key)
}

function selectSetting(key: PromptOptionKey, value: string) {
  selectedSettingsByMode.value[activeMode.value][key] = value
}

function selectAiModel(value: string) {
  selectedAiModel.value = value
}

function getPromptOptionIconClass(key: PromptOptionKey) {
  return getSharedPromptOptionIconClass(key)
}

function getPromptOptionItems(key: PromptOptionKey) {
  return getSharedPromptOptionItems(activeMode.value, key, aiPageConfig.value as any)
}

function getPromptOptionSelectedItem(key: PromptOptionKey) {
  const selectedValue = getSelectedSettingValue(key)
  return getPromptOptionItems(key).find(item => item.value === selectedValue)
}

function getOptionImageKey(key: PromptOptionKey, value: string) {
  return `${key}:${value}`
}

function shouldShowPromptOptionImage(key: PromptOptionKey, item: SelectorItem | undefined) {
  if (!item?.image)
    return false
  if (key === 'posterSize')
    return false
  if (key === 'tone' && (item.value === '通用风格' || item.label === '通用风格'))
    return false
  if (key === 'activityModel' && (item.isDefault || item.value === 'auto' || item.label === '活动模型'))
    return false
  return !failedOptionImageValues.value.has(getOptionImageKey(key, item.value))
}

function getPromptOptionItemIconClass(key: PromptOptionKey, item: SelectorItem | undefined) {
  if (key === 'posterSize' && item)
    return getPosterSizeIconClass(item)
  return item?.iconClass || getPromptOptionIconClass(key)
}

function getPromptOptionSelectedIconClass(key: PromptOptionKey) {
  return getPromptOptionItemIconClass(key, getPromptOptionSelectedItem(key))
}

function handlePromptOptionImageError(key: PromptOptionKey, item: SelectorItem | undefined) {
  if (!item)
    return

  failedOptionImageValues.value = new Set([
    ...failedOptionImageValues.value,
    getOptionImageKey(key, item.value),
  ])
}

function isPromptOptionSelected(key: PromptOptionKey, value: string) {
  return getSelectedSettingValue(key) === value
}

function applyQuickPrompt(item: QuickPromptOption) {
  promptText.value = item.prompt || item.content
}

async function openInspirationPreview(item: InspirationCard) {
  selectedCardId.value = item.id
  activeInspiration.value = await resolveActivityPreviewUrl(item)
  inspirationPreviewVisible.value = true

  if (!item.inspirationId)
    return

  isInspirationDetailLoading.value = true
  try {
    const detail = await api.ai.getAiInspirationDetail(item.inspirationId)
    const nextItem = await resolveActivityPreviewUrl(mapAiInspirationToCard(detail))
    activeInspiration.value = nextItem
    inspirationCardList.value = inspirationCardList.value.map(card => card.id === item.id ? nextItem : card)
  }
  catch (error) {
    console.warn('获取 AI 灵感模板详情失败:', error)
  }
  finally {
    isInspirationDetailLoading.value = false
  }
}

async function resolveActivityPreviewUrl(item: InspirationCard): Promise<InspirationCard> {
  if (item.type !== 'activity' || !item.activityId)
    return item

  try {
    const activityUrl = await buildActivityPreviewUrl(item.activityId, item.activityModelId || 0)
    return activityUrl ? { ...item, activityUrl } : item
  }
  catch {
    return item
  }
}

async function adoptInspiration(item: InspirationCard) {
  selectedCardId.value = item.id

  promptText.value = item.prompt
  activeMode.value = item.type
  inspirationPreviewVisible.value = false
}

async function toggleInspirationLike(item: InspirationCard) {
  if (!item.inspirationId) {
    klbMessage.warning('当前灵感信息不完整，暂时无法喜欢')
    return
  }

  try {
    const result = await api.ai.toggleContentReaction({
      target_type: 'ai_inspiration',
      target_id: item.inspirationId,
      reaction_type: 'like',
      shop_id: getCurrentShopId(),
    })
    const nextItem = {
      ...item,
      isLiked: Number(result.is_active || 0) === 1,
      likeCount: Number(result.count || 0),
      likes: String(Number(result.count || 0)),
    }
    activeInspiration.value = nextItem
    inspirationCardList.value = inspirationCardList.value.map(card => card.id === item.id ? nextItem : card)
  }
  catch (error: any) {
    klbMessage.error(error?.message || '操作失败，请稍后重试')
  }
}

async function handleGenerate() {
  if (isGenerating.value)
    return

  const content = promptText.value.trim()
  if (!content) {
    klbMessage.info('请先告诉快灵你的想法')
    void nextTick(() => composerTextareaRef.value?.focus())
    return
  }

  if (!hasAiAccessToken()) {
    loginGuideOpen.value = true
    return
  }

  isGenerating.value = true
  statusStage.value = 'submitted'

  try {
    const attachments = await uploadDraftAttachments([...pastedImages.value])
    statusStage.value = 'generating'
    const result = await api.ai.sendAiMessage({
      conversation_id: null,
      user_message_id: buildUserMessageId(),
      content,
      scene: activeMode.value === 'poster' ? aiPageConfig.value.posterScene : 'merchant_assistant',
      shop_id: getCurrentShopId(),
      attachments,
      component_result: buildLandingComponentResult(),
      options: buildAiMessageOptions(),
    })

    const conversationId = result.conversation?.conversation_id
    if (!conversationId)
      throw new Error('创建会话失败：未返回会话 ID')

    upsertAiGenerationTask({
      conversationId,
      assistantMessageId: result.assistant_message?.message_id,
      mode: activeMode.value,
      title: result.conversation?.title || undefined,
    })
    updateGeneratingTaskCount()
    clearPastedImages()
    await router.push({
      path: '/chat',
      query: {
        conversationId,
      },
    })
  }
  catch {
    klbMessage.error('创建 AI 会话失败，请稍后重试')
    isGenerating.value = false
    statusStage.value = 'idle'
  }
}
</script>

<style scoped>
.ai-page-scroll {
  min-width: 1440px;
  -ms-overflow-style: none;
  background: #f5f7fb;
  animation: ai-home-page-background-fade-in 520ms ease-out both;
  scrollbar-width: none;
}

.ai-page-scroll::-webkit-scrollbar {
  display: none;
}

.ai-page-background {
  background-color: #f5f7fb;
  background-image: radial-gradient(rgba(148, 163, 184, 0.14) 0.8px, transparent 0.8px);
  background-size: 16px 16px;
  animation: ai-home-background-fade-in 520ms ease-out both;
}

.ai-home-composer-section {
  animation: ai-home-composer-reveal 520ms cubic-bezier(0.22, 1, 0.36, 1) 120ms both;
  will-change: opacity, transform;
}

.ai-home-inspiration-section {
  animation: ai-home-inspiration-reveal 520ms cubic-bezier(0.22, 1, 0.36, 1) 380ms both;
  will-change: opacity, transform;
}

@keyframes ai-home-background-fade-in {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

@keyframes ai-home-page-background-fade-in {
  from {
    background-color: #ffffff;
  }

  to {
    background-color: #f5f7fb;
  }
}

@keyframes ai-home-composer-reveal {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes ai-home-inspiration-reveal {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.ai-send-button-wrap {
  isolation: isolate;
  overflow: visible;
}

.ai-send-button {
  position: relative;
  z-index: 1;
  border: 0;
}

.ai-send-button-glow {
  position: absolute;
  left: 3px;
  right: 3px;
  bottom: -4px;
  z-index: 0;
  height: 26px;
  border-radius: 26px;
  background: linear-gradient(
    270deg,
    #bee2c7 0%,
    #d3baeb 9.76%,
    #953aef 19.67%,
    #6e18c3 28.75%,
    #e62222 62.58%,
    #f7a589 83.94%,
    #f3c394 88.43%,
    #eeeea4 93.39%,
    #6de08a 100%
  );
  background-size: 260% 100%;
  filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  opacity: 0;
  pointer-events: none;
  transform: scaleX(0.9);
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
  animation: ai-gradient-blur-flow 3.2s ease-in-out infinite;
  will-change: background-position, filter, opacity, transform;
}

.ai-send-button-wrap:hover .ai-send-button-glow,
.ai-send-button-wrap:focus-within .ai-send-button-glow,
.ai-send-button-wrap--generating .ai-send-button-glow {
  opacity: 0.56;
  transform: scaleX(1.06);
}

@keyframes ai-gradient-blur-flow {
  0% {
    background-position: 0% 50%;
    filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  }

  50% {
    background-position: 100% 50%;
    filter: blur(7px) saturate(1.22) hue-rotate(-18deg);
  }

  100% {
    background-position: 0% 50%;
    filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  }
}

.ai-page-header {
  background-color: #f5f7fb;
  background-image: radial-gradient(rgba(148, 163, 184, 0.14) 0.8px, transparent 0.8px);
  background-size: 16px 16px;
  box-sizing: border-box;
  isolation: isolate;
}

.ai-generation-task-entry {
  position: relative;
  display: inline-flex;
  align-items: center;
  width: 155px;
  height: 36px;
  padding: 4px 12px 4px 4px;
  overflow: hidden;
  color: #0f182a;
  cursor: pointer;
  background: #fff;
  border: 0;
  border-radius: 12px;
  outline: none;
  transition:
    box-shadow 0.18s ease;
}

.ai-generation-task-entry:hover {
  box-shadow: 0 10px 24px rgba(15, 24, 42, 0.08);
}

.ai-generation-task-entry__icon {
  display: inline-flex;
  flex: 0 0 28px;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  margin-right: 10px;
  font-size: 28px;
  line-height: 1;
  color: #0f182a;
  animation: ai-generation-task-icon-breathe 2.4s ease-in-out infinite;
}

.ai-generation-task-entry__icon .iconfont {
  font-size: 28px;
  line-height: 1;
}

.ai-generation-task-entry__text {
  min-width: 0;
  overflow: hidden;
  font-size: 14px;
  line-height: 20px;
  color: transparent;
  text-align: left;
  text-overflow: ellipsis;
  white-space: nowrap;
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: ai-generation-task-text-shine var(--ai-working-text-shine-duration) linear infinite;
}

@keyframes ai-generation-task-text-shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: 0% 0;
  }
}

@keyframes ai-generation-task-icon-breathe {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.72;
    transform: scale(0.96);
  }
}
/* 
.ai-page-header::before {
  position: absolute;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-color: #f5f7fb;
  background-image: radial-gradient(rgba(117, 130, 160, 0.12) 0.8px, transparent 0.8px);
  background-size: 16px 16px;
  opacity: 0.56;
  content: '';
} */

@media (prefers-reduced-motion: reduce) {
  .ai-page-scroll,
  .ai-page-background,
  .ai-home-composer-section,
  .ai-home-inspiration-section {
    animation: none;
    opacity: 1;
    transform: none;
  }

  .ai-inspiration-card__image-skeleton::before,
  .ai-inspiration-card-skeleton__image::before {
    animation: none;
  }

  .ai-generation-task-entry__icon,
  .ai-generation-task-entry__text,
  .ai-send-button-glow,
  .ai-inspiration-card__image-wrap::after {
    animation: none !important;
  }

  .ai-generation-task-entry__text {
    color: #0f182a;
    background: none;
  }
}

.ai-logo-back-area {
  position: relative;
  display: flex;
  align-items: center;
  align-self: stretch;
  height: 100%;
  width: 160px;
  z-index: 2;
}

.ai-logo-back-area > img {
  position: relative;
  z-index: 1;
}

.ai-thinking-tooltip {
  position: absolute;
  left: 50%;
  bottom: calc(100% + 8px);
  z-index: 6;
  height: 28px;
  padding: 0 10px;
  border-radius: 6px;
  background: #0f182a;
  color: #ffffff;
  font-size: 12px;
  line-height: 28px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transform: translateX(-50%);
  transition:
    opacity 0.18s ease;
}

.ai-thinking-tooltip::after {
  content: "";
  position: absolute;
  left: 50%;
  bottom: -4px;
  width: 8px;
  height: 8px;
  background: #0f182a;
  transform: translateX(-50%) rotate(45deg);
}

.relative:hover > .ai-thinking-tooltip {
  opacity: 1;
}

.ai-inspiration-card__image-wrap {
  overflow: hidden;
}

.ai-inspiration-card__image {
  opacity: 0;
  transition:
    opacity 0.28s ease;
}

.ai-inspiration-card__image.is-loaded {
  opacity: 1;
}

.ai-inspiration-card__image-wrap.is-loading::after,
.ai-inspiration-card__image-wrap.is-loading .ai-inspiration-card__adopt {
  pointer-events: none;
  opacity: 0;
}

.ai-inspiration-card-skeleton {
  overflow: hidden;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.84);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
}

.ai-inspiration-card-skeleton__image,
.ai-inspiration-card__image-skeleton {
  position: relative;
  overflow: hidden;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.52), rgba(255, 255, 255, 0)),
    #eef2f7;
}

.ai-inspiration-card-skeleton__image {
  aspect-ratio: 206 / 302;
}

.ai-inspiration-card__image-skeleton {
  position: absolute;
  inset: 0;
  z-index: 4;
}

.ai-inspiration-card-skeleton__image::before,
.ai-inspiration-card__image-skeleton::before {
  position: absolute;
  inset: 0;
  z-index: 1;
  background: linear-gradient(
    105deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.72) 46%,
    rgba(255, 255, 255, 0) 64%
  );
  content: "";
  transform: translateX(-100%);
  animation: ai-inspiration-skeleton-shimmer 1.35s ease-in-out infinite;
}

.ai-inspiration-card-skeleton__label,
.ai-inspiration-card__image-skeleton-label,
.ai-inspiration-card-skeleton__action,
.ai-inspiration-card__image-skeleton-action {
  position: absolute;
  z-index: 2;
  display: block;
  background: rgba(255, 255, 255, 0.72);
  box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.74);
}

.ai-inspiration-card-skeleton__label,
.ai-inspiration-card__image-skeleton-label {
  top: 12px;
  left: 12px;
  width: 42px;
  height: 18px;
  border-radius: 4px;
}

.ai-inspiration-card-skeleton__action,
.ai-inspiration-card__image-skeleton-action {
  right: 12px;
  bottom: 16px;
  left: 12px;
  height: 32px;
  border-radius: 16px;
}

@keyframes ai-inspiration-skeleton-shimmer {
  to {
    transform: translateX(100%);
  }
}

.ai-inspiration-card__image-wrap::after {
  position: absolute;
  left: 50%;
  bottom: 10px;
  z-index: 1;
  width: calc(100% - 32px);
  height: 34px;
  border-radius: 999px;
  background: linear-gradient(
    270deg,
    #bee2c7 0%,
    #d3baeb 9.76%,
    #953aef 19.67%,
    #6e18c3 28.75%,
    #e62222 62.58%,
    #f7a589 83.94%,
    #f3c394 88.43%,
    #eeeea4 93.39%,
    #6de08a 100%
  );
  background-size: 260% 100%;
  filter: blur(6px) saturate(1.08) hue-rotate(0deg);
  content: "";
  opacity: 0;
  pointer-events: none;
  transform: translate(-50%, 8px) scaleX(0.88);
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
  animation: ai-gradient-blur-flow 3.2s ease-in-out infinite;
  will-change: background-position, filter, opacity, transform;
}

.ai-inspiration-card__adopt {
  position: absolute;
  left: 50%;
  bottom: 15px;
  z-index: 2;
  width: calc(100% - 30px);
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 0 16px;
  border: 0;
  border-radius: 20px;
  background: #0f182a;
  color: #ffffff;
  cursor: pointer;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  line-height: 20px;
  opacity: 0;
  pointer-events: none;
  isolation: isolate;
  transform: translate(-50%, 8px);
  transition:
    opacity 0.2s ease,
    background-color 0.18s ease,
    transform 0.22s ease;
}

.ai-inspiration-card__adopt .iconfont {
  font-size: 16px;
  line-height: 1;
}

.ai-inspiration-card__adopt:hover {
  background: #111c30;
}

.ai-inspiration-card__image-wrap:not(.is-loading):hover::after,
.group:hover .ai-inspiration-card__image-wrap:not(.is-loading)::after,
.ai-inspiration-card__image-wrap:not(.is-loading):focus-within::after {
  opacity: 0.58;
  transform: translate(-50%, 0) scaleX(1.02);
}

.ai-inspiration-card__image-wrap:not(.is-loading):hover .ai-inspiration-card__adopt,
.group:hover .ai-inspiration-card__image-wrap:not(.is-loading) .ai-inspiration-card__adopt,
.ai-inspiration-card__image-wrap:not(.is-loading):focus-within .ai-inspiration-card__adopt {
  opacity: 1;
  pointer-events: auto;
  transform: translate(-50%, 0);
}

.ai-mode-tabs {
  position: relative;
  display: flex;
  align-items: flex-start;
  height: 46px;
  padding-left: 0;
  overflow: visible;
}

.ai-mode-tab {
  position: relative;
  height: 64px;
  width: 240px;
  cursor: pointer;
  overflow: visible;
  transition:
    opacity 0.24s ease,
    transform 0.24s ease,
    filter 0.24s ease;
}

.ai-mode-tab--poster {
  margin-left: -60px;
}

.ai-mode-tab__bg {
  position: absolute;
  top: 12px;
  left: 0;
  z-index: 1;
  width: 100%;
  height: 48px;
  display: block;
  object-fit: fill;
}

.ai-mode-tab.is-active {
  z-index: 4;
  opacity: 1;
  transform: translateY(-12px);
  filter: none;
}

.ai-mode-tab:not(.is-active) {
  z-index: 1;
  opacity: 0.42;
  transform: translateY(0);
  filter: saturate(0.72);
}

.ai-mode-tab__label {
  position: absolute;
  z-index: 2;
  top: 26px;
  left: 16px;
  font-size: 16px;
  font-weight: bold;
  line-height: 20px;
  color: #0f182a;
}

.ai-mode-tab:not(.is-active) .ai-mode-tab__label {
  top: 20px;
  font-size: 14px;
}

.ai-mode-tab--poster .ai-mode-tab__label {
  left: 67px;
}

.ai-mode-tab__asset {
  position: absolute;
  z-index: 3;
  object-fit: contain;
  transition:
    opacity 0.24s ease,
    transform 0.24s ease;
}

.ai-mode-tab__asset--activity {
  right: 24px;
  top: 0;
  width: 60.57px;
  height: 64px;
}

.ai-mode-tab__asset--poster {
  right: 14px;
  top: 0;
  width: 72px;
  height: 72px;
}

.ai-mode-tab:not(.is-active) .ai-mode-tab__asset {
  opacity: 0.2;
  transform: translateY(14px);
}

.ai-message-panel {
  width: 410px;
  max-height: min(640px, calc(100vh - 32px));
  display: flex;
  flex-direction: column;
  background: #ffffff;
  overflow: hidden;
}

.ai-message-panel__header {
  height: 60px;
  flex: 0 0 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  border-bottom: 1px solid #e3e9f1;
  box-sizing: border-box;
}

.ai-message-panel__tabs {
  display: flex;
  align-items: center;
  gap: 24px;
}

.ai-message-panel__tab {
  position: relative;
  height: 28px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #64748b;
  font-size: 14px;
  line-height: 20px;
  font-weight: 400;
  cursor: pointer;
}

.ai-message-panel__tab--active {
  color: #0f182a;
  font-weight: 500;
}

.ai-message-panel__tab-dot,
.ai-message-panel__dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #e62222;
  display: inline-block;
}

.ai-message-panel__tab-dot {
  position: absolute;
  top: 9px;
  right: -8px;
}

.ai-message-panel__clear {
  width: 24px;
  height: 24px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #0f182a;
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
}

.ai-message-panel__list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: none;
}

.ai-message-panel__list::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.ai-message-panel__empty {
  flex: 1;
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #99a7bb;
  font-size: 14px;
  line-height: 20px;
}

.ai-message-panel__empty-icon {
  display: block;
  width: 96px;
  height: 96px;
  object-fit: contain;
}

.ai-message-panel__item {
  display: grid;
  grid-template-columns: 32px minmax(0, 1fr);
  column-gap: 8px;
  padding: 24px;
  border-bottom: 1px solid #e3e9f1;
  box-sizing: border-box;
}

.ai-message-panel__item:last-child {
  border-bottom: 0;
}

.ai-message-panel__avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f1f3f5;
  color: #0f182a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-message-panel__avatar .iconfont {
  font-size: 18px;
  line-height: 1;
}

.ai-message-panel__body {
  min-width: 0;
}

.ai-message-panel__meta {
  min-height: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-message-panel__sender {
  color: #0f182a;
  font-size: 14px;
  line-height: 20px;
  font-weight: 400;
}

.ai-message-panel__time {
  color: #64748b;
  font-size: 11px;
  line-height: 15px;
  font-weight: 400;
}

.ai-message-panel__content {
  margin-top: 10px;
  color: #0f182a;
  font-size: 16px;
  line-height: 28px;
  font-weight: 400;
  white-space: pre-line;
  word-break: break-word;
}

.ai-message-panel__action {
  margin-top: 10px;
  height: 28px;
  padding: 0;
  border: 0;
  background: transparent;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  color: #0f182a;
  font-size: 16px;
  line-height: 28px;
  font-weight: 500;
  cursor: pointer;
}

.ai-message-panel__action-icon {
  font-size: 16px;
  line-height: 1;
}

.ai-message-panel__preview {
  width: 322px;
  max-width: 100%;
  height: 108px;
  margin-top: 12px;
  border-radius: 4px;
  background: #e3e9f1;
  color: #64748b;
  font-size: 14px;
  line-height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  box-sizing: border-box;
}

:global(.ai-message-popover) {
  padding-top: 8px;
}

:global(.ai-message-popover .ant-popover-inner) {
  padding: 0;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 4px 12px 4px rgba(47, 48, 49, 0.1);
  overflow: hidden;
}

:global(.ai-message-popover .ant-popover-inner-content) {
  padding: 0;
}

:global(.ai-message-popover .ant-popover-arrow) {
  display: none;
}

:global(.ai-selector-dropdown) {
  padding-top: 8px;
}

:global(.ai-selector-dropdown .kl-dropdown-overlay__content) {
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 4px 12px 4px rgba(47, 48, 49, 0.1);
  overflow: hidden;
}

:global(.ai-selector-panel) {
  width: 100%;
  max-height: min(360px, calc(100vh - 160px));
  overflow-y: auto;
  background: #ffffff;
  scrollbar-width: none;
}

:global(.ai-selector-panel--tone) {
  max-height: min(372px, calc(100vh - 160px));
}

:global(.ai-selector-panel--activityModel) {
  max-height: min(328px, calc(100vh - 160px));
}

:global(.ai-selector-panel--ai-model),
:global(.ai-selector-panel--posterSize) {
  max-height: min(292px, calc(100vh - 160px));
}

:global(.ai-selector-panel::-webkit-scrollbar) {
  display: none;
}

:global(.ai-selector-panel__title) {
  height: 30px;
  padding: 9px 12px 0;
  box-sizing: border-box;
  overflow: hidden;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:global(.ai-selector-option) {
  position: relative;
  width: 100%;
  height: 64px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 44px 12px 12px;
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
  box-sizing: border-box;
  transition: background-color 0.18s ease;
}

:global(.ai-selector-option:hover),
:global(.ai-selector-option.is-active) {
  background: #f5f6f7;
}

:global(.ai-selector-option__icon) {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 4.465px;
  background: #f5f6f7;
  color: #0f182a;
}

:global(.ai-selector-option:hover .ai-selector-option__icon),
:global(.ai-selector-option.is-active .ai-selector-option__icon) {
  background: #ffffff;
}

:global(.ai-selector-option__icon .iconfont) {
  font-size: 20px;
  line-height: 1;
}

:global(.ai-selector-option__icon.has-image) {
  overflow: hidden;
  background: #ffffff;
}

:global(.ai-selector-option__icon.has-image .iconfont) {
  display: none;
}

:global(.ai-selector-option__image) {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

:global(.ai-selector-option__text) {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1 1 auto;
}

:global(.ai-selector-option__label) {
  overflow: hidden;
  color: #0f182a;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:global(.ai-selector-option__desc) {
  overflow: hidden;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
  white-space: nowrap;
  text-overflow: ellipsis;
}

:global(.ai-selector-option__check) {
  position: absolute;
  top: 19px;
  right: 14px;
  color: #0f182a;
  font-size: 18px;
}

.ai-composer-selected-option-icon {
  display: inline-flex;
  width: 20px;
  height: 20px;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  color: #0f182a;
}

.ai-composer-selected-option-icon.has-image {
  border-radius: 50%;
}

.ai-composer-selected-option-icon__image {
  display: block;
  width: 20px;
  height: 20px;
  object-fit: cover;
}

.ai-send-icon--loading {
  display: inline-block;
  animation: ai-send-loading-spin 0.85s linear infinite;
}

@keyframes ai-send-loading-spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}
</style>
