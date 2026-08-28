<template>
  <div
    class="ai-chat-page w-full overflow-x-hidden"
    :class="{ 'ai-chat-page--entered': isPageEntered }"
  >
    <div
      class="ai-chat-canvas mx-auto flex flex-col px-[16px] pb-[40px] pt-[72px]"
    >
      <header
        class="ai-chat-header fixed inset-x-0 top-0 z-50 flex h-[72px] shrink-0 items-center justify-between px-[24px]"
      >
        <div class="ai-chat-brand-bar">
          <button type="button" class="ai-chat-brand-back" @click="goBack">
            <i class="iconfont icon-youjiantou rotate-180"></i>
            <span>返回</span>
          </button>
          <span class="ai-chat-brand-divider"></span>
          <img class="ai-chat-brand-logo" :src="aiLogo" alt="快灵 AI">
        </div>
        <div class="flex items-center gap-[12px]">
          <a-popover
            v-model:open="messagePanelOpen"
            trigger="click"
            placement="bottomRight"
            overlay-class-name="ai-chat-message-popover"
          >
            <template #content>
              <div class="ai-chat-message-panel">
                <div class="ai-chat-message-panel__header">
                  <div class="ai-chat-message-panel__tabs">
                    <button
                      type="button"
                      class="ai-chat-message-panel__tab"
                      :class="{ 'ai-chat-message-panel__tab--active': messageTab === 'all' }"
                      @click="messageTab = 'all'"
                    >
                      全部消息
                    </button>
                    <button
                      type="button"
                      class="ai-chat-message-panel__tab"
                      :class="{ 'ai-chat-message-panel__tab--active': messageTab === 'unread' }"
                      @click="messageTab = 'unread'"
                    >
                      未读
                      <span v-if="hasUnreadMessages" class="ai-chat-message-panel__tab-dot" />
                    </button>
                  </div>
                  <button
                    type="button"
                    class="ai-chat-message-panel__clear iconfont icon-yijianqingchu"
                    title="一键清除"
                    aria-label="一键清除"
                    @click="clearUnreadMessages"
                  />
                </div>

                <div v-if="!displayMessageList.length" class="ai-chat-message-panel__empty">
                  <img :src="messageEmptyIcon" alt="" class="ai-chat-message-panel__empty-icon" />
                  <span>暂无消息</span>
                </div>
                <div v-else class="ai-chat-message-panel__list">
                  <article
                    v-for="message in displayMessageList"
                    :key="message.id"
                    class="ai-chat-message-panel__item"
                  >
                    <div class="ai-chat-message-panel__avatar">
                      <span class="iconfont" :class="message.icon" />
                    </div>
                    <div class="ai-chat-message-panel__body">
                      <div class="ai-chat-message-panel__meta">
                        <span class="ai-chat-message-panel__sender">{{ message.sender }}</span>
                        <span v-if="message.unread" class="ai-chat-message-panel__dot" />
                        <span class="ai-chat-message-panel__time">{{ message.time }}</span>
                      </div>
                      <div class="ai-chat-message-panel__content">{{ message.content }}</div>
                      <button
                        v-if="message.actionText"
                        type="button"
                        class="ai-chat-message-panel__action"
                        @click="handleMessageAction(message)"
                      >
                        <span>{{ message.actionText }}</span>
                        <span class="iconfont icon-jinru ai-chat-message-panel__action-icon" />
                      </button>
                      <div v-if="message.preview" class="ai-chat-message-panel__preview">
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
            @click="csModalOpen = true">
            <span class="text-[12px] font-600 text-white ml-[3px]">升级</span>
            <div
              class="min-w-[40px] h-[22px] px-[4px] flex items-center rounded-[6px] justify-center text-[12px] gap-[3px] bg-[#ff5e56] text-white bg-[rgba(255,255,255,0.11)]">
              <img class="h-[11px] w-[6px]"
                src="https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778576253_EM1JkwfJ1h.png">
              {{ aiPointsBalanceText }}
            </div>
          </div>
          <span class="ai-chat-header-divider"></span>
          <div
            class="transition-all duration-200 ease-out hover:shadow-[0_8px_20px_rgba(15,24,42,0.08)]"
            style="width: 106px; height: 36px; color:#0F182A; font-weight: 600;
             background-color: #fff; border-radius: 8px; cursor: pointer;
             display: flex; align-items: center; justify-content: center;" @click="router.push('/history')">
            历史生成({{ historyConversationTotal }})
          </div>
          <button v-if="showAdoptActivityButton" type="button"
            class="h-[36px] w-[88px] rounded-[8px] border-none text-[14px] font-600 transition-all duration-200 ease-out"
            :class="getHeaderActionButtonClass(canAdoptCurrentResult)"
            :disabled="!canAdoptCurrentResult" @click="adoptCurrentResult">
            {{ isActivityReleaseSubmitting ? '采用中' : '采用活动' }}
          </button>
          <button v-if="showPublishActivityButton" type="button"
            class="h-[36px] w-[88px] rounded-[8px] border-none text-[14px] font-600 transition-all duration-200 ease-out"
            :class="getHeaderActionButtonClass(canPublishCurrentResult)"
            :disabled="!canPublishCurrentResult" @click="publishCurrentResult()">
            去发布
          </button>
          <button v-if="showExportPosterButton" type="button"
            class="h-[36px] w-[74px] rounded-[8px] border-none text-[14px] font-600 transition-all duration-200 ease-out"
            :class="getHeaderActionButtonClass(canExportCurrentResult, 'dark')" :disabled="!canExportCurrentResult"
            @click="exportCurrentResult">
            导出
          </button>
        </div>
      </header>


      <section
        class="ai-chat-main flex flex-1 overflow-hidden"
        :class="{ 'ai-chat-main--fullscreen': isChatFullscreenMode }"
      >
        <div
          class="ai-chat-dialog-column z-20 min-w-0 rounded-[24px] bg-[#fff]"
        >
          <div class="flex h-full min-h-0 flex-col rounded-[22px] bg-white">
            <div class="flex items-center justify-between h-[56px]">
              <div class="text-[16px] text-[#0F182A] font-600 pl-[24px]">
                {{ activeMode === 'poster' ? '海报对话生成器' : '活动对话生成器' }}
              </div>
              <button
                type="button"
                class="ai-chat-layout-toggle"
                :class="{ 'ai-chat-layout-toggle--fullscreen': isChatFullscreenMode }"
                :title="isChatFullscreenMode ? '切换折叠模式' : '切换全屏模式'"
                :aria-label="isChatFullscreenMode ? '切换折叠模式' : '切换全屏模式'"
                @click="toggleChatLayoutMode"
              >
                <i class="iconfont icon-shouqi1"></i>
              </button>
            </div>

            <ChatMessageWindow :active-mode="activeMode"
              :is-mock-preview-mode="isMockPreviewMode"
              :is-generating="isMessageWorking || isPosterGenerating"
              :should-show-activity-brief-form="shouldShowActivityBriefForm"
              :selected-activity-goal="selectedActivityGoal" :selected-activity-duration="selectedActivityDuration"
              :activity-goal-options="activityGoalOptions" :activity-duration-options="activityDurationOptions"
              :activity-date-range="activityDateRange" :show-activity-goal-selector="showActivityGoalSelector"
              :show-activity-date-selector="showActivityDateSelector"
              :is-activity-brief-readonly="isActivityBriefReadonly"
              :should-show-activity-product-selector="shouldShowActivityProductSelector"
              :activity-product-options="activityProductOptions"
              :activity-product-requirement="activityProductRequirement"
              :selected-activity-product-ids="selectedActivityProductIds"
              :is-activity-products-loading="isActivityProductsLoading"
              :should-show-activity-style-selector="shouldShowActivityStyleSelector"
              :activity-style-options="activityStyleOptions" :selected-activity-style="selectedActivityStyle"
              :activity-style-requirement="activityStyleRequirement"
              :should-show-activity-result-confirm-bar="shouldShowActivityResultConfirmBar"
              :should-show-thinking-process-card="shouldShowThinkingProcessCard"
              :thinking-process-status="thinkingProcessStatus"
              :thinking-process-summary-items="thinkingProcessSummaryItems" :current-prompt="currentPrompt"
              :poster-aspect-ratio="getSelectedSettingValue('posterSize') || '3:4'"
              :chat-messages="chatMessages" :get-message-display-content="getMessageDisplayContent"
              :get-message-image-attachments="getMessageImageAttachments" @update:goal-value="handleActivityGoalSelect"
              @update:date-value="handleActivityDurationSelect" @update:start-value="handleActivityStartDateChange"
              @update:end-value="handleActivityEndDateChange" @skip-goal="handleActivityGoalSkip"
              @skip-date="handleActivityDateSkip" @confirm-brief="handleActivityBriefConfirm"
              @update:selected-product-ids="selectedActivityProductIds = $event"
              @update:product-requirement="activityProductRequirement = $event"
              @skip-product="handleActivityProductSkip" @confirm-product="handleActivityProductConfirm"
              @update:selected-style="selectedActivityStyle = $event"
              @update:style-requirement="activityStyleRequirement = $event" @skip-style="handleActivityStyleSkip"
              @confirm-style="handleActivityStyleConfirm"
              @confirm-activity-deep="handleActivityDeepConfirm"
              @confirm-poster-deep="handlePosterDeepConfirm"
              @adopt="adoptCurrentResult" @publish="publishCurrentResult"
              @preview-image="openImagePreview" @regenerate-message="handleChatMessageRegenerate"
              @regenerate-image="handleImageRegenerate" @reselect-items="handleReselectActivityItems"
              @download-poster="handlePosterMessageDownload" />

            <ChatComposer :active-mode="activeMode" :current-mode-label="currentModeLabel" :draft-message="draftMessage"
              :pasted-images="pastedImages" :selected-thinking-mode="selectedThinkingMode"
              :current-prompt-options="currentPromptOptions" :current-model="currentModel"
              :current-model-value="selectedModel"
              :composer-summary-text="composerModelText"
              :image-model-options="imageModelOptions"
              :is-message-working="isMessageWorking" :send-state="composerSendState"
              :generation-timing-text="composerGenerationTimingText"
              :get-prompt-option-items="getPromptOptionItems"
              :get-prompt-option-display-label="getPromptOptionDisplayLabel"
              :get-prompt-option-title="getPromptOptionTitle"
              :get-prompt-option-overlay-width="getPromptOptionOverlayWidth"
              :get-prompt-option-icon-class="getPromptOptionIconClass"
              :get-prompt-option-selected-item="getPromptOptionSelectedItem"
              :is-prompt-option-selected="isPromptOptionSelected"
              @update:draft-message="draftMessage = $event" @upload-change="handleUploadInputChange"
              @prompt-paste="handlePromptPaste" @preview-image="openImagePreview" @remove-image="removePastedImage"
              @select-thinking-mode="selectThinkingMode" @select-setting="selectSetting"
              @select-model="selectModel" @send="sendMessage" @stop="handleStopMessage" />

          </div>
        </div>

        <div
          class="ai-chat-preview-column col-start-2 flex flex-col items-center justify-center overflow-x-hidden"
          :aria-hidden="isChatFullscreenMode"
          :inert="isChatFullscreenMode"
        >
          <template v-if="shouldShowGuideSuggestionPreview">
            <div class="w-full max-w-[720px]">
              <div class="ai-activity-generating-status ai-activity-generating-status--guide">
                <LottieStar class="ai-activity-generating-status__star" :size="28" />
                <TextType
                  :key="activeMode"
                  :text="currentGenerationLoadingTexts"
                  as="span"
                  class-name="ai-activity-generating-status__text"
                  :typing-speed="80"
                  :deleting-speed="38"
                  :pause-duration="6000"
                  cursor-character="_"
                  cursor-class-name="text-type-underline-cursor"
                  :cursor-blink-duration="0.55"
                  random
                />
              </div>
              <div class="mt-[10px] text-[24px] font-700 leading-[34px] text-[#0F182A]">
                活动已开始生成，在等待的同时，享受这些建议吧
              </div>

              <div
                class="ai-activity-suggestion-carousel"
                :class="{ 'is-switching': isActivitySuggestionAnimating }">
                <TransitionGroup name="ai-activity-suggestion-card" tag="div" class="ai-activity-suggestion-card-layer">
                  <div
                    v-for="card in activitySuggestionDisplayCards"
                    :key="card.key"
                    class="ai-activity-suggestion-card"
                    :class="[
                      `ai-activity-suggestion-card--position-${card.position}`,
                      card.position === 0 ? 'is-active' : '',
                    ]">
                    <div class="ai-activity-suggestion-card-content">
                      <div class="ai-activity-suggestion-copy">
                        <div class="ai-activity-suggestion-title">{{ card.suggestion.title }}</div>
                        <div class="ai-activity-suggestion-content">
                          <div
                            v-for="(line, lineIndex) in getActivitySuggestionContentLines(card.suggestion)"
                            :key="`${card.key}-content-${lineIndex}`"
                            class="ai-activity-suggestion-content-line">
                            <span v-if="line.label" class="ai-activity-suggestion-content-label">{{ line.label }}：</span>
                            <span>{{ line.text }}</span>
                          </div>
                        </div>
                      </div>

                      <button
                        v-if="card.position === 0"
                        type="button"
                        class="ai-activity-suggestion-add-action"
                        @click="handleActivitySuggestionAdd">
                        <i class="iconfont icon-jinru text-[18px] font-600"></i>
                        <span class="ml-[4px] c-[#0F182A] font-600">添加到对话</span>
                      </button>

                      <div class="ai-activity-suggestion-illustration-wrap">
                        <img :src="card.suggestion.image"
                          class="ai-activity-suggestion-illustration">
                      </div>
                    </div>
                  </div>
                </TransitionGroup>

              </div>
            </div>
          </template>

          <template v-else-if="activeMode === 'activity'">
            <div class="ai-activity-preview-shell">
              <div class="ai-activity-preview-screen">
                <div
                  v-if="latestGeneratedActivityCoverImage"
                  class="h-full w-full overflow-y-auto bg-[#F8FAFC]"
                >
                  <img
                    :src="latestGeneratedActivityCoverImage"
                    :alt="generatedActivityTitle"
                    referrerpolicy="no-referrer"
                    class="block h-auto w-full"
                  >
                </div>
                <div
                  v-else-if="!displayedActivityPreviewUrl"
                  class="flex h-full flex-col items-center justify-center bg-[#F8FAFC] px-[32px] text-center"
                >
                  <div class="ai-activity-generating-status text-[14px] font-600 text-[#475467]">
                    快灵正在加载活动预览
                  </div>
                </div>
                <iframe
                  v-if="displayedActivityPreviewUrl"
                  :key="displayedActivityPreviewKey"
                  :src="displayedActivityPreviewUrl"
                  class="ai-activity-preview-frame"
                  scrolling="yes"
                  title="AI 生成活动预览"
                />
              </div>
            </div>
            <iframe
              v-if="pendingActivityPreviewUrl"
              :key="pendingActivityPreviewKey"
              :src="pendingActivityPreviewUrl"
              class="ai-activity-preview-frame ai-activity-preview-frame--pending"
              scrolling="no"
              title="AI 生成活动预览加载中"
              @load="handlePendingActivityPreviewLoad"
            />
          </template>

          <template v-else>
            <div
              class="group relative w-[400px] shrink-0 cursor-pointer overflow-hidden rounded-[4px] bg-white shadow-[0_18px_42px_rgba(15,23,42,0.12)]">
              <img v-if="!shouldShowPosterGeneratingPreview" :src="currentPosterPreviewImage" class="block w-full">
              <div
                v-else
                class="ai-poster-generating-preview"
                aria-hidden="true"
              >
                <LottieStar class="ai-poster-generating-preview__star" :size="64" />
              </div>
              <div
                class="pointer-events-none absolute right-[12px] top-[12px] flex flex-col gap-[8px] opacity-0 transition-all duration-200 ease-out group-hover:pointer-events-auto group-hover:opacity-100">
                <button type="button"
                  class="h-[36px] min-w-[96px] rounded-[10px] border border-[rgba(15,24,42,0.08)] bg-white px-[14px] text-[13px] font-600 shadow-[0_10px_24px_rgba(15,24,42,0.12)] transition-all duration-200 ease-out"
                  :class="isPosterGenerating ? 'cursor-not-allowed text-[#CBD5E1] opacity-80' : 'cursor-pointer text-[#0F182A] hover:bg-[#F8FAFC] hover:shadow-[0_14px_28px_rgba(15,24,42,0.14)]'"
                  :disabled="isPosterGenerating"
                  @click="handlePreviewAction('重新生成')">
                  重新生成
                </button>
                <button type="button"
                  class="h-[36px] min-w-[96px] cursor-pointer rounded-[10px] border border-[rgba(15,24,42,0.08)] bg-white px-[14px] text-[13px] text-[#0F182A] font-600 shadow-[0_10px_24px_rgba(15,24,42,0.12)] transition-all duration-200 ease-out hover:bg-[#F8FAFC] hover:shadow-[0_14px_28px_rgba(15,24,42,0.14)]"
                  @click="handlePreviewAction('导出')">
                  导出主图
                </button>
              </div>
            </div>
          </template>
        </div>
      </section>
    </div>

    <KlContactServiceModal v-model="csModalOpen" />
    <KlLoginGuideModal v-model="loginGuideOpen" @authenticated="handleLoginAuthenticated" />
    <a-modal
      v-model:open="activitySuccessModalOpen"
      :width="400"
      :footer="null"
      :closable="false"
      :mask-closable="true"
      :centered="true"
      wrap-class-name="ai-activity-success-modal-wrap"
      @cancel="activitySuccessModalOpen = false"
    >
      <div class="ai-activity-success-modal">
        <button
          type="button"
          class="ai-activity-success-modal__close"
          aria-label="关闭弹窗"
          @click="activitySuccessModalOpen = false"
        >
          <i class="iconfont icon-guanbi"></i>
        </button>

        <section class="ai-activity-success-modal__body">
          <div class="ai-activity-success-modal__icon">
            <span class="ai-activity-success-modal__icon-bg"></span>
            <span class="ai-activity-success-modal__icon-mark">
              <i class="iconfont icon-chenggong"></i>
            </span>
          </div>
          <h3 class="ai-activity-success-modal__title">您的专属活动已定制完成！</h3>
          <p class="ai-activity-success-modal__desc">
            恭喜您！快灵已为您完整打造【{{ generatedActivityTitle }}】活动方案，核心配置均已按您的需求落地，完全符合平台发布规范：
          </p>
          <p class="ai-activity-success-modal__tip">
            您可进入编辑器完成最终效果预览与确认，一键发布活动。
          </p>
        </section>

        <footer class="ai-activity-success-modal__footer">
          <button type="button" class="ai-activity-success-modal__secondary" @click="activitySuccessModalOpen = false">
            我再看看
          </button>
          <button
            type="button"
            class="ai-activity-success-modal__primary"
            :disabled="isActivityReleaseSubmitting"
            @click="handleSuccessModalPublish"
          >
            {{ isActivityReleaseSubmitting ? '发布中...' : '去编辑器发布' }}
          </button>
        </footer>
      </div>
    </a-modal>
    <a-image v-if="previewImageUrl" :src="previewImageUrl" :preview="{
      visible: previewVisible,
      onVisibleChange: handlePreviewVisibleChange,
    }" style="display: none" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type {
  AiConversation,
  AiGeneratedActivity,
  AiMessage,
  AiMessageStatus,
  AiGeneratedPoster,
  AiPosterImagePreviewCard,
  AiPromptTipItem,
  AiStreamActivityGeneratedEvent,
  AiStreamActivityImageProgressEvent,
  AiStreamCompletedEvent,
  AiStreamDoneEvent,
  AiStreamErrorEvent,
  AiStreamEventBase,
  AiStreamMessageCardEvent,
  AiStreamPosterGeneratedEvent,
  AiStreamPosterProgressEvent,
} from './standalone/types'
import type { MerchantItemType, UnifiedItem } from './standalone/types'
import ChatComposer from './components/ai-chat/ChatComposer.vue'
import ChatMessageWindow from './components/ai-chat/ChatMessageWindow.vue'
import LottieStar from './components/ai-chat/LottieStar.vue'
import TextType from './components/ai-chat/TextType.vue'

import api, { hasAiAccessToken } from './standalone/api'
import request from './standalone/request'
const aiLogo = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1784298062_3ELdZZ4ftV.png';
import KlContactServiceModal from './components/kl/KlContactServiceModal.vue'
import KlLoginGuideModal from './components/kl/KlLoginGuideModal.vue'
import { resolveMainImageBackgroundColors } from './standalone/mainImageBackgroundColor'
import { buildActivityPreviewUrl, buildActivityPreviewUrlSync } from './standalone/activityPreviewUrl'
import { getStore } from './standalone/storage'
import { klbMessage } from './standalone/klbMessage'
import {
  aiModelOptions,
  defaultAiPageConfig,
  getImageModelDisplayName,
  getPromptOptionIconClass as getSharedPromptOptionIconClass,
  getPromptOptionItems as getSharedPromptOptionItems,
  getPromptOptionOverlayWidth,
  getPromptOptionTitle,
  normalizeAiPageConfig,
  promptOptionMap,
  type PromptOption,
  type PromptOptionKey,
  type SelectorItem,
} from './shared/composerOptions'
import {
  activityGenerationLoadingTexts,
  posterGenerationLoadingTexts,
} from './shared/generationLoadingCopy'
import {
  removeAiGenerationTask,
  upsertAiGenerationTask,
} from './shared/generationTaskStatus'

const router = useRouter()
const route = useRoute()
const AI_SCENE = 'merchant_assistant'
const deepConfirmSubmitText = '确认并开始生成'

type ModeKey = 'activity' | 'poster'
type ThinkingMode = 'deep' | 'quick'
type ActivityUiStage = 'brief' | 'product' | 'style' | 'result'
type ChatAttachment = {
  url: string
  name?: string
  type?: string
  size?: number
}
type ChatMessage = {
  id: string
  messageId?: string
  role: 'assistant' | 'user'
  content: string
  status?: AiMessageStatus
  errorMessage?: string | null
  createdAt?: string | null
  isSystem?: boolean
  attachments?: ChatAttachment[]
  cards?: ActivityAssistantCard[]
  activity?: AiGeneratedActivity | null
  poster?: AiGeneratedPoster | null
  componentResult?: Record<string, any> | any[] | null
  meta?: Record<string, any> | null
  seq?: number
}
type ChatMessageRegeneratePayload = {
  id: string
  role: string
  content: string
}
type PosterProgressState = {
  step: string
  message: string
  progress: number
  elapsed?: string
  estimated?: string
}
type ThinkingProcessStatus = 'thinking' | 'completed'
type ThinkingModeOption = {
  value: ThinkingMode
  label: string
}
type ActivityGoalDurationCardOption = {
  label: string
  value: string
  describe?: string
  action?: 'open_date_picker'
}
type ActivityGoalDurationCardSection = {
  section_key: string
  title: string
  required?: boolean
  selection_mode?: string
  options?: ActivityGoalDurationCardOption[]
}
type ActivityGoalDurationCard = {
  card_id: string
  type: 'activity_goal_duration_selector'
  version?: number
  title?: string
  sub_title?: string
  can_skip?: boolean
  skip_button_text?: string
  submit_mode?: string
  submit_button_text?: string
  step_key?: string
  scene?: string
  sections?: ActivityGoalDurationCardSection[]
}
type ActivityItemSelectorType = 'package' | 'voucher' | 'mixed_items'
type ActivityItemSelectorCard = {
  card_id: string
  type: 'activity_item_selector'
  version?: number
  title?: string
  sub_title?: string
  selector_type?: ActivityItemSelectorType
  selection_mode?: string
  min_select_count?: number
  max_select_count?: number
  can_skip?: boolean
  skip_button_text?: string
  submit_mode?: string
  submit_button_text?: string
  step_key?: string
  scene?: string
}
type ActivityStyleCardOption = {
  id?: string
  label: string
  value: string
  title?: string
  describe?: string
  img?: string | null
  image?: string | null
  image_url?: string | null
  cover_img?: string | null
  thumbnail?: string | null
}
type ActivityStyleSelectorCard = {
  card_id: string
  type: 'activity_style_selector'
  version?: number
  title?: string
  sub_title?: string
  selection_mode?: string
  can_skip?: boolean
  skip_button_text?: string
  submit_mode?: string
  submit_button_text?: string
  step_key?: string
  scene?: string
  options?: ActivityStyleCardOption[]
}
type ActivityDeepConfirmCard = {
  card_id: string
  type: 'activity_deep_confirm'
  version?: number
  title?: string
  step_key?: string
  scene?: string
  submit_button_text?: string
  thinking?: string | null
  summary?: string | string[] | null
  plan?: Record<string, any> | null
}
type PosterImagePreviewCard = AiPosterImagePreviewCard
type PosterDeepConfirmCard = {
  card_id: string
  type: 'poster_deep_confirm'
  version?: number
  title?: string
  step_key?: string
  scene?: string
  submit_button_text?: string
  thinking?: string | null
  summary?: string | string[] | null
  plan?: Record<string, any> | null
}
type ActivityAssistantCard = Record<string, any> & {
  type: string
  card_id?: string
}
type ActivityProductOption = {
  id: string
  name: string
  image: string
  price: string
  stock: string
  typeLabel: string
  typeTone: 'red' | 'orange' | 'green'
  rawItem: UnifiedItem
}
type ActivitySuggestion = {
  id: string
  title: string
  content: string
  image: string
}
type ActivitySuggestionContentLine = {
  label?: string
  text: string
}
type ActivitySuggestionStackCard = {
  key: string
  suggestion: ActivitySuggestion
  position: 0 | 1 | 2
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

const posterPreviewImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778685865_9Ez3vzr1I9.png'
const messageEmptyIcon = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1782959243_DOIxAu2HgN.png'

function inlineIcon(content: string, viewBox = '0 0 20 20') {
  return `
    <svg viewBox="${viewBox}" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
      ${content}
    </svg>
  `
}

const fallbackActivityGoalOptions: SelectorItem[] = [
  { value: '拉新获客', label: '拉新获客' },
  { value: '老客复购', label: '老客复购' },
  { value: '提升客单价', label: '提升客单价' },
  { value: '会员储值', label: '会员储值' },
]
const fallbackActivityDurationOptions: SelectorItem[] = [
  { value: '最近10天', label: '最近 10 天' },
  { value: '近期五一劳动节', label: '近期五一劳动节' },
]
const fallbackActivityStyleOptions = [
  {
    value: 'trend_3d',
    label: '3D潮玩',
    image: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222827_QEqJdnJEPA.png',
  },
  {
    value: 'cute_plush',
    label: '毛绒卡通',
    image: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222911_yogqgOPrry.png',
  },
  {
    value: 'soft_3d',
    label: '3D毛绒',
    image: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222930_Cq3TbNTP6B.png',
  },
  {
    value: 'light_luxury',
    label: '轻奢高级',
    image: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222827_QEqJdnJEPA.png',
  },
  {
    value: 'fresh_natural',
    label: '清新自然',
    image: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222911_yogqgOPrry.png',
  },
  {
    value: 'festive_hot',
    label: '节日热销',
    image: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222930_Cq3TbNTP6B.png',
  },
]

function normalizeQueryValue(value: unknown) {
  if (Array.isArray(value))
    return String(value[0] || '')
  return typeof value === 'string' ? value : ''
}

function normalizeThinkingModeQuery(value: unknown): ThinkingMode {
  return normalizeQueryValue(value) === 'quick' ? 'quick' : 'deep'
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

function normalizeActivityCards(cards: unknown): ActivityAssistantCard[] {
  if (!Array.isArray(cards))
    return []

  return cards
    .filter((card): card is ActivityAssistantCard =>
      Boolean(card && typeof card === 'object' && typeof (card as ActivityAssistantCard).type === 'string'),
    )
}

function isPosterImagePreviewCard(card: unknown): card is PosterImagePreviewCard {
  return Boolean(card && typeof card === 'object' && (card as Record<string, any>).type === 'poster_image_preview')
}

function isActivityImagePreviewCard(card: unknown): boolean {
  const type = card && typeof card === 'object' ? (card as Record<string, any>).type : ''
  return type === 'activity_cover_preview' || type === 'activity_detail_preview'
}

function normalizeGeneratedActivity(activity: unknown): AiGeneratedActivity | null {
  if (!activity || typeof activity !== 'object')
    return null

  const rawActivity = activity as Record<string, any>
  const activityId = Number(rawActivity.activity_id)
  if (!Number.isFinite(activityId) || activityId <= 0)
    return null

  const activityModelId = Number(rawActivity.activity_model_id || rawActivity.activityModelId || rawActivity.model_id || 0)
  const coverImg = String(
    rawActivity.cover_img
    || rawActivity.coverImg
    || rawActivity.cover
    || rawActivity.image_url
    || rawActivity.main_image
    || rawActivity.mainImage
    || '',
  ).trim()

  return {
    activity_id: activityId,
    ...(Number.isFinite(activityModelId) && activityModelId > 0 ? { activity_model_id: activityModelId } : {}),
    title: String(rawActivity.title || ''),
    status: String(rawActivity.status || ''),
    ...(coverImg ? { cover_img: coverImg } : {}),
    preview_url: typeof rawActivity.preview_url === 'string' ? rawActivity.preview_url : null,
  }
}

function normalizeGeneratedPoster(poster: unknown): AiGeneratedPoster | null {
  if (!poster || typeof poster !== 'object')
    return null

  const rawPoster = poster as Record<string, any>
  const url = String(rawPoster.url || '').trim()
  if (!url)
    return null

  return {
    url,
    width: Number.isFinite(Number(rawPoster.width)) ? Number(rawPoster.width) : null,
    height: Number.isFinite(Number(rawPoster.height)) ? Number(rawPoster.height) : null,
    size: typeof rawPoster.size === 'string' ? rawPoster.size : null,
    aspect_ratio: typeof rawPoster.aspect_ratio === 'string' ? rawPoster.aspect_ratio : null,
    style: typeof rawPoster.style === 'string' ? rawPoster.style : null,
    style_title: typeof rawPoster.style_title === 'string' ? rawPoster.style_title : null,
    image_model: typeof rawPoster.image_model === 'string' ? rawPoster.image_model : null,
    image_model_title: getImageModelDisplayName(
      typeof rawPoster.image_model_title === 'string'
        ? rawPoster.image_model_title
        : (typeof rawPoster.image_model === 'string' ? rawPoster.image_model : null),
    ) || null,
    provider_model: typeof rawPoster.provider_model === 'string' ? rawPoster.provider_model : null,
    prompt: typeof rawPoster.prompt === 'string' ? rawPoster.prompt : null,
    revised_prompt: typeof rawPoster.revised_prompt === 'string' ? rawPoster.revised_prompt : null,
    status: typeof rawPoster.status === 'string' ? rawPoster.status : null,
    mock: Boolean(rawPoster.mock),
  }
}

function extractGeneratedPosterFromCards(cards: unknown) {
  const normalizedCards = normalizeActivityCards(cards)
  for (let index = normalizedCards.length - 1; index >= 0; index -= 1) {
    const card = normalizedCards[index]
    if (!isPosterImagePreviewCard(card))
      continue

    const poster = normalizeGeneratedPoster(card.poster)
    if (poster)
      return poster

    const imageUrl = String(card.image_url || '').trim()
    if (!imageUrl)
      continue

    return normalizeGeneratedPoster({
      url: imageUrl,
      width: card.width,
      height: card.height,
      aspect_ratio: card.aspect_ratio,
      status: card.status === 'completed' ? 'created' : card.status,
    })
  }

  return null
}

function extractGeneratedActivity(source: { activity?: unknown, meta?: unknown } | null | undefined) {
  const directActivity = normalizeGeneratedActivity(source?.activity)
  if (directActivity)
    return directActivity

  const meta = source?.meta
  if (!meta || typeof meta !== 'object')
    return null

  return normalizeGeneratedActivity((meta as Record<string, any>).activity)
}

function extractGeneratedPoster(source: { poster?: unknown, meta?: unknown, components?: unknown, cards?: unknown } | null | undefined) {
  const directPoster = normalizeGeneratedPoster(source?.poster)
  if (directPoster)
    return directPoster

  const cardPoster = extractGeneratedPosterFromCards(source?.components ?? source?.cards)
  if (cardPoster)
    return cardPoster

  const meta = source?.meta
  if (!meta || typeof meta !== 'object')
    return null

  const metaPoster = normalizeGeneratedPoster((meta as Record<string, any>).poster)
  if (metaPoster)
    return metaPoster

  return extractGeneratedPosterFromCards((meta as Record<string, any>).components)
}

function mergeMessageMetaWithActivity(
  meta: Record<string, any> | null | undefined,
  activity: AiGeneratedActivity | null | undefined,
) {
  if (!activity)
    return meta ?? null

  return {
    ...(meta && typeof meta === 'object' ? meta : {}),
    activity,
  }
}

function mergeMessageMetaWithPoster(
  meta: Record<string, any> | null | undefined,
  poster: AiGeneratedPoster | null | undefined,
) {
  if (!poster)
    return meta ?? null

  return {
    ...(meta && typeof meta === 'object' ? meta : {}),
    poster,
  }
}

function getChatMessageGeneratedActivity(message?: Pick<ChatMessage, 'activity' | 'meta'> | null) {
  if (!message)
    return null

  return extractGeneratedActivity(message)
}

function getChatMessageGeneratedPoster(message?: Pick<ChatMessage, 'poster' | 'meta'> | null) {
  if (!message)
    return null

  return extractGeneratedPoster(message)
}

function getActivityGoalDurationCardSection(
  card: ActivityGoalDurationCard | null | undefined,
  sectionKey: 'goal' | 'duration',
) {
  return card?.sections?.find(section => section.section_key === sectionKey)
}

function findCardOption(
  options: ActivityGoalDurationCardOption[] | undefined,
  value: string,
) {
  return options?.find(item => item.value === value) || null
}

function normalizeActivityItemSelectorType(value: unknown): ActivityItemSelectorType {
  if (value === 'voucher' || value === 'mixed_items')
    return value
  return 'package'
}

function mapActivitySelectorTypeToMerchantItemType(selectorType: ActivityItemSelectorType): MerchantItemType | undefined {
  if (selectorType === 'package')
    return 'bundle'
  if (selectorType === 'voucher')
    return 'voucher'
  return undefined
}

function getActivityStyleOptionImage(option: ActivityStyleCardOption) {
  const apiImage = String(
    option.img
    || option.image
    || option.image_url
    || option.cover_img
    || option.thumbnail
    || '',
  ).trim()
  if (apiImage)
    return apiImage

  const value = String(option.value || option.id || '').trim()
  const label = String(option.label || option.title || '').trim()
  return fallbackActivityStyleOptions.find(option =>
    option.value === value || option.label === label,
  )?.image || fallbackActivityStyleOptions[0].image
}

function buildUserMessageId() {
  return `u_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`
}

function isComponentSelectionMessage(message: AiMessage) {
  const result = message.component_result
  return message.role === 'user'
    && !!result
    && typeof result === 'object'
    && !Array.isArray(result)
    && (
      !!(result as Record<string, any>).card_id
      || !!(result as Record<string, any>).component_type
    )
}

function getMessageThinkingMode(message?: ChatMessage | null): ThinkingMode {
  if (message?.componentResult && typeof message.componentResult === 'object' && !Array.isArray(message.componentResult)) {
    return message.componentResult.think_mode === 'quick' ? 'quick' : 'deep'
  }
  return 'deep'
}

function getThinkingExcerpt(content: string) {
  const normalized = content.replace(/\s+/g, ' ').trim()
  if (!normalized)
    return '当前输入内容'
  return normalized.length > 24 ? `${normalized.slice(0, 24)}...` : normalized
}

function buildThinkingSummaryItems(message: ChatMessage | null, activeMode: ModeKey) {
  const excerpt = getThinkingExcerpt(message?.content || '')
  if (activeMode === 'poster') {
    return [
      `用户输入“${excerpt}”，我会先提炼海报主题、目标人群、风格偏好和传播场景，明确本轮生成的核心目标。`,
      '我会继续补齐版式重点、视觉语气、标题层级和转化引导，再组织成可直接生成海报的结构化要求。',
    ]
  }

  return [
    `用户输入“${excerpt}”，我会先从这段表达里提取活动目标、商品线索、时间范围和场景限制，整理当前已知信息。`,
    '我会按活动创建流程继续补齐缺失条件，优先确认关键步骤，再把这些信息组织成后续活动生成所需的结构化上下文。',
  ]
}

function parseActivitySuggestionContent(content: string) {
  const normalized = content.replace(/\s+/g, ' ').trim()
  const labelPattern = /(示例|小提示|小贴士)\s*[:：]/g
  const matches = [...normalized.matchAll(labelPattern)]
  const lines: ActivitySuggestionContentLine[] = []

  if (!normalized)
    return { lines, example: '' }

  if (!matches.length) {
    lines.push({ text: normalized })
    return { lines, example: '' }
  }

  const intro = normalized.slice(0, matches[0].index).trim()
  if (intro)
    lines.push({ text: intro })

  let example = ''
  matches.forEach((match, index) => {
    const label = match[1]
    const start = (match.index || 0) + match[0].length
    const end = matches[index + 1]?.index ?? normalized.length
    const text = normalized.slice(start, end).trim()

    if (!text)
      return

    if ((label === '示例' || label === '小提示' || label === '小贴士') && !example)
      example = text

    lines.push({ label, text })
  })

  return { lines, example }
}

function getActivitySuggestionContentLines(suggestion: ActivitySuggestion) {
  return parseActivitySuggestionContent(suggestion.content).lines
}

function getActivitySuggestionAddContent(suggestion: ActivitySuggestion) {
  const parsed = parseActivitySuggestionContent(suggestion.content)
  return (parsed.example || suggestion.content).trim()
}

function normalizePromptTipItem(item: AiPromptTipItem, index: number, mode: ModeKey): ActivitySuggestion | null {
  const title = String(item.title || '').trim()
  const content = String(item.content || '').trim()
  const image = String(item.img || '').trim() || ACTIVITY_SUGGESTION_IMAGES[index % ACTIVITY_SUGGESTION_IMAGES.length]

  if (!title && !content)
    return null

  return {
    id: `${mode}-${item.id || index + 1}`,
    title: title || '使用技巧',
    content,
    image,
  }
}

const routeMode = computed<ModeKey>(() => route.query.mode === 'poster' ? 'poster' : 'activity')
const routeConversationId = computed(() => normalizeQueryValue(route.query.conversationId || route.query.history))
const routeEntrySource = computed(() => normalizeQueryValue(route.query.from))
const backRoutePath = computed(() => routeEntrySource.value === 'history' ? '/history' : '/')
const initialPrompt = computed(() => normalizeQueryValue(route.query.prompt))
const routeThinkingMode = computed<ThinkingMode>(() => normalizeThinkingModeQuery(route.query.thinkingMode))
const isMockPreviewMode = computed(() => normalizeQueryValue(route.query.mock) === '1')
const activeMode = ref<ModeKey>(routeMode.value)
const draftMessage = ref('')
const chatMessages = ref<ChatMessage[]>([])
const currentConversation = ref<AiConversation | null>(null)
const historyConversationTotal = ref(0)
const aiPointsBalance = ref<number | null>(null)
const csModalOpen = ref(false)
const loginGuideOpen = ref(false)
const isPageEntered = ref(false)
const messagePanelOpen = ref(false)
const unreadCleared = ref(false)
const messageTab = ref<'all' | 'unread'>('all')
const messageList = ref<MessageItem[]>([])
const activitySuccessModalOpen = ref(false)
const lastSuccessModalActivityId = ref(0)
const isActivityReleaseSubmitting = ref(false)
const isMessageWorking = ref(false)
const generationStartedAt = ref<number | null>(null)
const generationNow = ref(Date.now())
const generationElapsedSeconds = ref(0)
const generationEstimatedSeconds = ref(120)
const isMessageSubmitting = ref(false)
const isComposerManuallyStopped = ref(false)
const isConversationLoading = ref(false)
const isRouteSyncing = ref(false)
const selectedModel = ref(aiModelOptions[0].value)
const aiPageConfig = ref(normalizeAiPageConfig(null))
let aiPageConfigReadyPromise: Promise<void> | null = null
const failedOptionImageValues = ref<Set<string>>(new Set())
const selectedThinkingMode = ref<ThinkingMode>('deep')
const previewVisible = ref(false)
const previewImageUrl = ref('')
const pastedImages = ref<PastedImage[]>([])
const maxPastedImageCount = 5
const activityPreviewStatus = ref<'generating' | 'result'>('generating')
const resolvedActivityPreviewUrl = ref('')
const displayedActivityPreviewUrl = ref('')
const displayedActivityPreviewKey = ref(0)
const pendingActivityPreviewUrl = ref('')
const pendingActivityPreviewKey = ref(0)
const activityPreviewPreparingId = ref(0)
const posterProgress = ref<PosterProgressState>({
  step: '',
  message: '',
  progress: 0,
})
const isChatFullscreenMode = ref(true)
const lastAutoCollapsedResultKey = ref('')
const isActivitySuggestionAnimating = ref(false)
const activitySuggestionIndex = ref(0)
const selectedActivityGoal = ref('')
const selectedActivityDuration = ref('')
const activityDateRange = ref({
  start: '',
  end: '',
})
const isActivityBriefReadonly = ref(false)
const showActivityGoalSelector = ref(true)
const showActivityDateSelector = ref(true)
const showActivityProductSelector = ref(true)
const showActivityStyleSelector = ref(true)
const selectedActivityProductIds = ref<string[]>([])
const activityProductRequirement = ref('')
const selectedActivityStyle = ref('')
const activityStyleRequirement = ref('')
const activeAssistantMessageId = ref('')
const activityProductOptions = ref<ActivityProductOption[]>([])
const isActivityProductsLoading = ref(false)
const hasActivityProductsLoaded = ref(false)
let activitySuggestionAnimationTimer: ReturnType<typeof setTimeout> | null = null
let activitySuggestionAutoTimer: ReturnType<typeof setTimeout> | null = null
let generationTimer: ReturnType<typeof setInterval> | null = null
let assistantTypewriterTimer: ReturnType<typeof setInterval> | null = null
let aiStream: EventSource | null = null
let aiStreamVersion = 0
let autoItemCoverProgressTimer: ReturnType<typeof setInterval> | null = null
let isAutoItemCoverProgressRefreshing = false
const ACTIVITY_SUGGESTION_ANIMATION_MS = 760
const ACTIVITY_SUGGESTION_AUTO_DELAY_MS = 10000
const ASSISTANT_TYPEWRITER_INTERVAL_MS = 28
const ASSISTANT_TYPEWRITER_SHORT_MESSAGE_MAX_LENGTH = 96
const ASSISTANT_COMPONENT_REVEAL_DELAY_MS = 280
type AssistantTypewriterQueue = {
  text: string
  receivedLength: number
  seq?: number
}
type AssistantTypewriterSpeed = 'slow' | 'normal' | 'fast'
const assistantTypewriterQueues = new Map<string, AssistantTypewriterQueue>()
const assistantThinkingTypewriterQueues = new Map<string, AssistantTypewriterQueue>()
const assistantFastTypewriterMessageIds = new Set<string>()
const assistantPresentationQueues = new Map<string, Array<() => void>>()
const assistantPresentationTimers = new Map<string, ReturnType<typeof setTimeout>>()
let assistantTypewriterTickCount = 0
const AI_ACTIVITY_THEME_STAGE = 'activity_edit' as const
const AI_ACTIVITY_THEME_SYNC_RETRY_LIMIT = 6
const AI_ACTIVITY_THEME_SYNC_RETRY_DELAY = 800
const AI_ACTIVITY_THEME_CONFIRM_RETRY_LIMIT = 4
const AI_ACTIVITY_THEME_CONFIRM_RETRY_DELAY = 500
type ActivityThemeSyncStatus = 'synced' | 'fallback' | 'pending-cover' | 'failed' | 'skipped'
type ActivityThemeSyncResult = {
  status: ActivityThemeSyncStatus
  activityId?: number
  coverImg?: string
  backgroundColor?: string
  reason?: string
}
const syncedGeneratedActivityThemeKeys = new Set<string>()
const syncingGeneratedActivityThemeJobs = new Map<string, Promise<ActivityThemeSyncResult>>()
const ACTIVITY_SUGGESTION_IMAGES = [
  '/ai/suggestions/operations-colleague.png',
  '/ai/suggestions/precise-audience.png',
  '/ai/suggestions/main-focus.png',
  '/ai/suggestions/style-brief.png',
  '/ai/suggestions/share-poster.png',
  '/ai/suggestions/group-buying.png',
  '/ai/suggestions/real-scene.png',
  '/ai/suggestions/verification-rules.png',
  '/ai/suggestions/time-rules.png',
]

const fallbackActivitySuggestions: ActivitySuggestion[] = [
  {
    id: 'operations-colleague',
    title: '像跟运营同事沟通一样描述',
    content: '不用写复杂指令，直接说清楚“我要做什么活动、给谁看、想达到什么效果”就可以。越像真实经营需求，AI越容易生成贴合门店的活动方案。 示例：帮我做一个面向老客的复购活动，主推5次肩颈护理套餐，活动做10天，重点提升老客回店消费。',
    image: ACTIVITY_SUGGESTION_IMAGES[0],
  },
  {
    id: 'precise-audience',
    title: '描述人群越具体，文案越准',
    content: '“新客”“老客”“宝妈”“上班族”“附近3公里用户”“沉睡客户”这些人群信息，会直接影响活动文案、权益设计和转化路径。 示例：这次主要面向附近3公里的女性新客，重点吸引年轻上班族首次到店体验。',
    image: ACTIVITY_SUGGESTION_IMAGES[1],
  },
  {
    id: 'main-focus',
    title: '让活动更准，可以补一句“主推重点”',
    content: '如果你有特别想主推的项目、套餐、体验卡或储值权益，可以在对话里补充一句。快灵会优先围绕这个重点优化活动结构，让页面卖点更集中。 示例：这次主推99元新客补水体验，其他项目只作为升级套餐展示，不要平均分配页面篇幅。',
    image: ACTIVITY_SUGGESTION_IMAGES[2],
  },
  {
    id: 'style-brief',
    title: '想要高级感，可以直接说风格',
    content: '活动页不只是规则配置，也包括视觉和文案风格。可以告诉AI你想要轻奢、专业、热闹、年轻、节日感、医美感或高端感。 示例：页面采用高级轻奢风格，减少大红大金和夸张促销元素，文案简洁克制，价格保持清晰。',
    image: ACTIVITY_SUGGESTION_IMAGES[3],
  },
  {
    id: 'share-poster',
    title: '分享海报要像朋友发来的福利',
    content: '好的分享海报不需要说太多规则，重点是让人一眼看懂福利、门店、项目和领取动作。文案越像“朋友推荐”，用户转发时心理负担越低。 示例：请把分享海报做得像顾客推荐给朋友，重点突出“双人补水护理299元”，减少规则文字，保留清晰的参与引导。',
    image: ACTIVITY_SUGGESTION_IMAGES[4],
  },
  {
    id: 'group-buying',
    title: '拼团活动要突出“差一点就更便宜”',
    content: '拼团的关键不是复杂规则，而是让用户感受到“再拉一个人就更划算”。阶梯价格、成团进度、邀请按钮，都应该围绕这个心理设计。 示例：这是2人成团活动，单人价399元、成团价299元，请突出“再邀请1位好友即可享团价”和当前成团进度。',
    image: ACTIVITY_SUGGESTION_IMAGES[5],
  },
  {
    id: 'real-scene',
    title: '真实感能提升用户行动意愿',
    content: '用户看到有人下单、有人领券、有人助力，会更容易相信活动正在发生。适当加入实时动态，可以营造活动热度，但内容要自然，避免过度夸张。 小提示：请在活动页加入实时动态组件，用于展示真实的下单和参团记录。',
    image: ACTIVITY_SUGGESTION_IMAGES[6],
  },
  {
    id: 'verification-rules',
    title: '到店服务要提前讲清楚核销规则',
    content: '顾客下单前通常会关心有效期、可用门店、是否需要预约、能否退款、到店怎么用。规则越清楚，咨询成本越低，成交阻力也越小。 小提示：请在商品详情中写明：购买后90天内有效、需提前1天预约、限指定门店使用、共可核销5次；退款规则暂不填写。',
    image: ACTIVITY_SUGGESTION_IMAGES[7],
  },
  {
    id: 'time-rules',
    title: '活动时间和使用期限要分开说明',
    content: '活动开始与结束时间决定“什么时候可以买”，商品有效期决定“购买后什么时候能用”。两者分开表达，可以减少误解和售后咨询。 小提示：活动时间为14天，商品购买后90天内有效，请分别展示，不要把活动结束时间当作商品使用期限。',
    image: ACTIVITY_SUGGESTION_IMAGES[8],
  },
]
const promptTipsByMode = ref<Record<ModeKey, ActivitySuggestion[]>>({
  activity: [],
  poster: [],
})
const loadedPromptTipModes = ref<Partial<Record<ModeKey, boolean>>>({})
const loadingPromptTipModes = ref<Partial<Record<ModeKey, boolean>>>({})
const CHAT_COMPOSER_SELECTION_STORAGE_KEY = 'kl-ai-chat-composer-selection'

const thinkingModeOptions: ThinkingModeOption[] = [
  { value: 'quick', label: '快速思考' },
  { value: 'deep', label: '深度思考' },
]
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

const isActivityGenerating = computed(() => activeMode.value === 'activity' && activityPreviewStatus.value === 'generating')
const latestAssistantMessage = computed(() =>
  [...chatMessages.value].reverse().find(item => item.role === 'assistant' && !item.isSystem) || null,
)
const latestUserMessage = computed(() =>
  [...chatMessages.value].reverse().find(item => item.role === 'user' && !item.isSystem) || null,
)
const inquiryMessageCardTypes = new Set([
  'activity_goal_duration_selector',
  'activity_item_selector',
  'activity_style_selector',
  'activity_deep_confirm',
])
const finalActivityGenerationConfirmCardTypes = new Set([
  'activity_deep_confirm',
])
const finalPosterGenerationConfirmCardTypes = new Set([
  'poster_deep_confirm',
])

function hasInquiryMessageCard(message?: ChatMessage | null) {
  const cards = normalizeActivityCards(message?.cards)
  return cards.some(card => inquiryMessageCardTypes.has(card.type))
}

function hasFinalActivityGenerationConfirmResult(componentResult: ChatMessage['componentResult']): boolean {
  if (!componentResult)
    return false

  if (Array.isArray(componentResult))
    return componentResult.some(item => hasFinalActivityGenerationConfirmResult(item as ChatMessage['componentResult']))

  if (typeof componentResult !== 'object')
    return false

  const result = componentResult as Record<string, any>
  const componentType = String(result.component_type || '').trim()
  const stepKey = String(result.step_key || '').trim()
  const buttonText = collectComponentResultStrings(componentResult).join(' ')

  return finalActivityGenerationConfirmCardTypes.has(componentType)
    || finalActivityGenerationConfirmCardTypes.has(stepKey)
    || buttonText.includes('确认并开始生成')
    || buttonText.includes('开始生成')
}

function hasFinalPosterGenerationConfirmResult(componentResult: ChatMessage['componentResult']): boolean {
  if (!componentResult)
    return false

  if (Array.isArray(componentResult))
    return componentResult.some(item => hasFinalPosterGenerationConfirmResult(item as ChatMessage['componentResult']))

  if (typeof componentResult !== 'object')
    return false

  const result = componentResult as Record<string, any>
  const componentType = String(result.component_type || '').trim()
  const stepKey = String(result.step_key || '').trim()
  const buttonText = collectComponentResultStrings(componentResult).join(' ')

  return finalPosterGenerationConfirmCardTypes.has(componentType)
    || finalPosterGenerationConfirmCardTypes.has(stepKey)
    || buttonText.includes(deepConfirmSubmitText)
}

const isAwaitingActivityInquiryResponse = computed(() =>
  activeMode.value === 'activity'
  && !latestGeneratedActivity.value
  && !hasSubmittedFinalActivityGenerationConfirm.value
  && hasInquiryMessageCard(latestAssistantMessage.value),
)
const isAiResponseBusy = computed(() =>
  isMessageWorking.value && !isAwaitingActivityInquiryResponse.value,
)
const currentActivityGoalDurationCard = computed<ActivityGoalDurationCard | null>(() => {
  const cards = normalizeActivityCards(latestAssistantMessage.value?.cards)
  return cards.find(card => card.type === 'activity_goal_duration_selector') as ActivityGoalDurationCard || null
})
const currentActivityItemSelectorCard = computed<ActivityItemSelectorCard | null>(() => {
  const cards = normalizeActivityCards(latestAssistantMessage.value?.cards)
  return cards.find(card => card.type === 'activity_item_selector') as ActivityItemSelectorCard || null
})
const currentActivityStyleCard = computed<ActivityStyleSelectorCard | null>(() => {
  const cards = normalizeActivityCards(latestAssistantMessage.value?.cards)
  return cards.find(card => card.type === 'activity_style_selector') as ActivityStyleSelectorCard || null
})
const currentActivityDeepConfirmCard = computed<ActivityDeepConfirmCard | null>(() => {
  const cards = normalizeActivityCards(latestAssistantMessage.value?.cards)
  return cards.find(card => card.type === 'activity_deep_confirm') as ActivityDeepConfirmCard || null
})
const currentPosterDeepConfirmCard = computed<PosterDeepConfirmCard | null>(() => {
  const cards = normalizeActivityCards(latestAssistantMessage.value?.cards)
  return cards.find(card => card.type === 'poster_deep_confirm') as PosterDeepConfirmCard || null
})
const latestGeneratedActivity = computed(() => {
  for (let index = chatMessages.value.length - 1; index >= 0; index -= 1) {
    const message = chatMessages.value[index]
    if (message.role !== 'assistant' || message.isSystem)
      continue

    const activity = getChatMessageGeneratedActivity(message)
    if (activity)
      return activity
  }

  return null
})
const hasSubmittedFinalActivityGenerationConfirm = computed(() =>
  activeMode.value === 'activity'
  && chatMessages.value.some(message =>
    message.role === 'user'
    && hasFinalActivityGenerationConfirmResult(message.componentResult),
  ),
)
const hasSubmittedFinalPosterGenerationConfirm = computed(() =>
  activeMode.value === 'poster'
  && chatMessages.value.some(message =>
    message.role === 'user'
    && hasFinalPosterGenerationConfirmResult(message.componentResult),
  ),
)
const latestGeneratedPoster = computed(() => {
  for (let index = chatMessages.value.length - 1; index >= 0; index -= 1) {
    const message = chatMessages.value[index]
    if (message.role !== 'assistant' || message.isSystem)
      continue

    const poster = getChatMessageGeneratedPoster(message)
    if (poster)
      return poster
  }

  return null
})
const latestGeneratedResultKey = computed(() => {
  for (let index = chatMessages.value.length - 1; index >= 0; index -= 1) {
    const message = chatMessages.value[index]
    if (message.role !== 'assistant' || message.isSystem)
      continue

    const messageKey = message.messageId || message.id
    const activity = getChatMessageGeneratedActivity(message)
    if (activity?.activity_id)
      return `activity:${messageKey}:${activity.activity_id}`

    const poster = getChatMessageGeneratedPoster(message)
    if (poster?.url)
      return `poster:${messageKey}:${poster.url}`
  }

  return ''
})
const latestPosterImagePreviewCard = computed<PosterImagePreviewCard | null>(() => {
  for (let index = chatMessages.value.length - 1; index >= 0; index -= 1) {
    const message = chatMessages.value[index]
    if (message.role !== 'assistant' || message.isSystem)
      continue

    const card = normalizeActivityCards(message.cards).find(isPosterImagePreviewCard)
    if (card)
      return card
  }

  return null
})
const currentActivityItemSelectorType = computed<ActivityItemSelectorType>(() =>
  normalizeActivityItemSelectorType(currentActivityItemSelectorCard.value?.selector_type),
)
const aiPointsBalanceText = computed(() =>
  aiPointsBalance.value === null ? '--' : aiPointsBalance.value.toLocaleString('zh-CN'),
)
const activityGoalOptions = computed<SelectorItem[]>(() => {
  const options = getActivityGoalDurationCardSection(currentActivityGoalDurationCard.value, 'goal')?.options
  if (!options?.length)
    return fallbackActivityGoalOptions
  return options.map(item => ({ value: item.value, label: item.label, describe: item.describe }))
})
const activityDurationOptions = computed<SelectorItem[]>(() => {
  const options = getActivityGoalDurationCardSection(currentActivityGoalDurationCard.value, 'duration')?.options
  if (!options?.length)
    return fallbackActivityDurationOptions
  return options.map(item => ({ value: item.value, label: item.label }))
})
const activityStyleOptions = computed(() => {
  const filterGeneralStyle = (option: { value?: string, label?: string }) => option.value !== '通用风格' && option.label !== '通用风格'
  const fallbackOptions = fallbackActivityStyleOptions.filter(filterGeneralStyle)
  const options = currentActivityStyleCard.value?.options
  if (!options?.length)
    return fallbackOptions

  const normalizedOptions = options
    .map((option) => {
      const value = String(option.value || option.id || '').trim()
      const label = String(option.label || option.title || value || '未命名风格').trim()
      return {
        value: value || label,
        label,
        image: getActivityStyleOptionImage(option),
      }
    })
    .filter(option => option.value && filterGeneralStyle(option))

  return normalizedOptions.length ? normalizedOptions : fallbackOptions
})
const currentActivitySuggestionList = computed(() =>
  promptTipsByMode.value[activeMode.value]?.length
    ? promptTipsByMode.value[activeMode.value]
    : fallbackActivitySuggestions,
)
function getLoopedActivitySuggestionIndex(index: number, length: number) {
  return ((index % length) + length) % length
}

const currentActivitySuggestion = computed(() => {
  const suggestions = currentActivitySuggestionList.value
  if (!suggestions.length)
    return fallbackActivitySuggestions[0]

  return suggestions[getLoopedActivitySuggestionIndex(activitySuggestionIndex.value, suggestions.length)] || fallbackActivitySuggestions[0]
})
const activitySuggestionDisplayCards = computed<ActivitySuggestionStackCard[]>(() => {
  const suggestions = currentActivitySuggestionList.value
  const cards: ActivitySuggestionStackCard[] = []

  if (!suggestions.length)
    return cards

  const visibleCount = Math.min(3, suggestions.length)
  for (let position = 0; position < visibleCount; position += 1) {
    const sequence = activitySuggestionIndex.value + position
    const suggestion = suggestions[getLoopedActivitySuggestionIndex(sequence, suggestions.length)]
    if (!suggestion)
      continue

    cards.push({
      key: `${sequence}-${suggestion.id}`,
      suggestion,
      position: position as 0 | 1 | 2,
    })
  }

  return cards
})
const currentModeLabel = computed(() => activeMode.value === 'poster' ? '海报' : '活动')
const currentPrompt = computed(() => {
  const hasUserMessage = chatMessages.value.some(item => item.role === 'user' && !item.isSystem)
  return hasUserMessage ? '' : initialPrompt.value
})
const currentPromptOptions = computed(() => promptOptionMap[activeMode.value])
const imageModelOptions = computed(() => aiPageConfig.value.models)
const currentModel = computed(() =>
  imageModelOptions.value.find(item => item.value === selectedModel.value)?.label || imageModelOptions.value[0]?.label || '',
)
const composerModelText = computed(() => '使用 Quickling image-2 模型生成')
const currentThinkingModeOption = computed(() =>
  thinkingModeOptions.find(item => item.value === selectedThinkingMode.value) || thinkingModeOptions[0],
)
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
const hasDraftToSend = computed(() => Boolean(draftMessage.value.trim()))
const isWaitingAiResponse = computed(() =>
  isMessageSubmitting.value || (isAiResponseBusy.value && latestAssistantMessage.value?.status !== 'streaming'),
)
const isStreamingAiResponse = computed(() =>
  isAiResponseBusy.value && latestAssistantMessage.value?.status === 'streaming',
)
function getPosterPreviewCardImageUrl(card?: PosterImagePreviewCard | null) {
  const rawCard = card as Record<string, any> | null | undefined
  return String(rawCard?.image_url || rawCard?.poster?.url || '').trim()
}

function getFirstTimingValue(source: unknown, keys: string[]) {
  if (!source || typeof source !== 'object')
    return null

  const rawSource = source as Record<string, any>
  for (const key of keys) {
    const value = rawSource[key]
    if (value !== undefined && value !== null && String(value).trim() !== '')
      return value
  }

  return null
}

function formatElapsedSeconds(value: unknown) {
  if (typeof value === 'string' && !/^\d+(\.\d+)?$/.test(value.trim()))
    return value.trim()

  const seconds = Math.max(0, Math.round(Number(value) || 0))
  if (!seconds)
    return ''

  const minutes = Math.floor(seconds / 60)
  const remainderSeconds = seconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remainderSeconds).padStart(2, '0')}`
}

function formatElapsedClockSeconds(value: number) {
  const seconds = Math.max(0, Math.floor(value || 0))
  const minutes = Math.floor(seconds / 60)
  const remainderSeconds = seconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remainderSeconds).padStart(2, '0')}`
}

function formatEstimatedSeconds(value: unknown) {
  if (typeof value === 'string' && !/^\d+(\.\d+)?$/.test(value.trim()))
    return value.trim()

  const seconds = Math.max(0, Math.round(Number(value) || 0))
  if (!seconds)
    return ''
  if (seconds < 60)
    return `${seconds}秒`

  const minutes = Math.floor(seconds / 60)
  const remainderSeconds = seconds % 60
  return remainderSeconds ? `${minutes}分${remainderSeconds}秒` : `${minutes}分钟`
}

function formatGenerationEstimate(seconds: number) {
  if (seconds <= 120)
    return '2分钟'
  if (seconds <= 150)
    return '2分30秒'
  return '3分钟'
}

function getLatestUserGenerationMessage() {
  return [...chatMessages.value].reverse().find(item => item.role === 'user' && !item.isSystem) || null
}

function estimateGenerationSeconds() {
  const latestUserMessage = getLatestUserGenerationMessage()
  const contentLength = Array.from((latestUserMessage?.content || draftMessage.value || '').trim()).length
  const attachmentCount = latestUserMessage?.attachments?.length || pastedImages.value.length
  const complexity = contentLength + attachmentCount * 80

  if (activeMode.value === 'poster') {
    if (complexity >= 180)
      return 180
    if (complexity >= 80)
      return 150
    return 120
  }

  if (complexity >= 260)
    return 180
  if (complexity >= 120)
    return 150
  return 120
}

function getPosterGenerationTimingFromSource(source?: Record<string, any> | null) {
  const progress = source?.progress && typeof source.progress === 'object'
    ? source.progress as Record<string, any>
    : null
  const actualValue = getFirstTimingValue(source, ['actual', 'elapsed', 'used', 'cost', 'duration', 'actual_time', 'elapsed_time', 'used_time', 'cost_time', 'actual_seconds', 'elapsed_seconds', 'used_seconds', 'cost_seconds', 'duration_seconds'])
    ?? getFirstTimingValue(progress, ['actual', 'elapsed', 'used', 'cost', 'duration', 'actual_time', 'elapsed_time', 'used_time', 'cost_time', 'actual_seconds', 'elapsed_seconds', 'used_seconds', 'cost_seconds', 'duration_seconds'])
  const estimatedValue = getFirstTimingValue(source, ['estimated', 'estimate', 'predicted', 'predict', 'expected', 'total', 'estimated_time', 'estimate_time', 'predicted_time', 'predict_time', 'expected_time', 'total_time', 'estimated_seconds', 'estimate_seconds', 'predicted_seconds', 'predict_seconds', 'expected_seconds', 'total_seconds'])
    ?? getFirstTimingValue(progress, ['estimated', 'estimate', 'predicted', 'predict', 'expected', 'total', 'estimated_time', 'estimate_time', 'predicted_time', 'predict_time', 'expected_time', 'total_time', 'estimated_seconds', 'estimate_seconds', 'predicted_seconds', 'predict_seconds', 'expected_seconds', 'total_seconds'])

  const actual = formatElapsedSeconds(actualValue)
  const estimated = formatEstimatedSeconds(estimatedValue)
  if (!actual && !estimated)
    return null

  return {
    actual: actual || estimated,
    estimated: actual ? estimated : '',
  }
}

function isPosterImagePreviewCardGenerating(card?: PosterImagePreviewCard | null) {
  return Boolean(card)
    && !getPosterPreviewCardImageUrl(card!)
    && !['completed', 'failed', 'stopped'].includes(card!.status || '')
}

const isPosterGenerating = computed(() =>
  activeMode.value === 'poster'
  && isPosterImagePreviewCardGenerating(latestPosterImagePreviewCard.value),
)
const isGenerationTimingActive = computed(() => isAiResponseBusy.value || isPosterGenerating.value)
const currentGenerationElapsedText = computed(() => {
  if (generationStartedAt.value) {
    const seconds = Math.max(0, Math.floor((generationNow.value - generationStartedAt.value) / 1000))
    return formatElapsedClockSeconds(seconds)
  }

  return ''
})
const posterGenerationTimingText = computed(() => {
  if (activeMode.value !== 'poster')
    return null
  if (!isGenerationTimingActive.value)
    return null

  const sourceTiming = getPosterGenerationTimingFromSource(latestPosterImagePreviewCard.value as Record<string, any> | null)
  const localActual = currentGenerationElapsedText.value
  const estimated = sourceTiming?.estimated || posterProgress.value.estimated || formatGenerationEstimate(generationEstimatedSeconds.value)

  if (localActual && isAiResponseBusy.value)
    return { actual: localActual, estimated }

  if (localActual && isPosterGenerating.value)
    return { actual: localActual, estimated }

  if (sourceTiming)
    return sourceTiming

  return null
})
const composerGenerationTimingText = computed(() => {
  if (!isGenerationTimingActive.value)
    return null

  if (activeMode.value === 'poster')
    return posterGenerationTimingText.value

  const localActual = currentGenerationElapsedText.value
  return localActual
    ? {
        actual: localActual,
        estimated: formatGenerationEstimate(generationEstimatedSeconds.value),
      }
    : null
})
const shouldLockActivityComposer = computed(() =>
  activeMode.value === 'activity'
  && !latestGeneratedActivity.value
  && !isComposerManuallyStopped.value
  && !isAwaitingActivityInquiryResponse.value
  && chatMessages.value.some(item => item.role === 'assistant' && !item.isSystem),
)
const composerSendState = computed<'ready' | 'waiting' | 'working'>(() => {
  if (isPosterGenerating.value)
    return 'working'
  if (isMessageSubmitting.value)
    return 'waiting'
  if (isAwaitingActivityInquiryResponse.value)
    return 'ready'
  if (shouldLockActivityComposer.value)
    return 'working'
  if (isStreamingAiResponse.value)
    return 'working'
  if (isWaitingAiResponse.value)
    return 'waiting'
  return 'ready'
})
const shouldShowThinkingProcessCard = computed(() => {
  if (isMockPreviewMode.value)
    return true

  if (!latestAssistantMessage.value)
    return false

  if (!['pending', 'streaming'].includes(latestAssistantMessage.value.status || ''))
    return false

  return getMessageThinkingMode(latestUserMessage.value) === 'deep'
})
const thinkingProcessStatus = computed<ThinkingProcessStatus>(() =>
  isMockPreviewMode.value
    ? 'completed'
    : 'thinking',
)
const thinkingProcessSummaryItems = computed(() =>
  buildThinkingSummaryItems(latestUserMessage.value, activeMode.value),
)
const currentActivityInstruction = computed(() => {
  if (activeMode.value !== 'activity')
    return null

  for (let index = chatMessages.value.length - 1; index >= 0; index -= 1) {
    const message = chatMessages.value[index]
    const stage = getActivityUiStageFromMessage(message)
    if (stage) {
      return {
        stage,
        messageId: message.messageId || message.id,
      }
    }
  }

  return null
})
const shouldShowActivityBriefForm = computed(() =>
  activeMode.value === 'activity'
  && !!currentActivityGoalDurationCard.value
)
const shouldShowActivityProductSelector = computed(() =>
  activeMode.value === 'activity'
  && currentActivityItemSelectorCard.value?.type === 'activity_item_selector'
  && showActivityProductSelector.value,
)
const shouldShowActivityStyleSelector = computed(() =>
  activeMode.value === 'activity'
  && currentActivityInstruction.value?.stage === 'style'
  && !!currentActivityStyleCard.value
  && showActivityStyleSelector.value,
)
const shouldShowActivityResultConfirmBar = computed(() =>
  activeMode.value === 'activity' && !!latestGeneratedActivity.value && !isMessageWorking.value,
)
const currentGeneratedActivityId = computed(() => Number(latestGeneratedActivity.value?.activity_id || 0))
const currentGeneratedActivityModelId = computed(() => Number(latestGeneratedActivity.value?.activity_model_id || 0))
const latestGeneratedActivityCoverImage = computed(() => getLatestActivityCoverPreviewImage(currentGeneratedActivityId.value))
const activityPreviewUrl = computed(() => resolvedActivityPreviewUrl.value)
const isActivityPreviewPreparing = computed(() =>
  activeMode.value === 'activity'
  && activityPreviewPreparingId.value > 0
  && activityPreviewPreparingId.value === currentGeneratedActivityId.value,
)
const shouldShowActivitySuggestionPreview = computed(() =>
  activeMode.value === 'activity'
  && (
    isActivityGenerating.value
    || isMessageWorking.value
    || isActivityPreviewPreparing.value
    || (!latestGeneratedActivity.value && !activityPreviewUrl.value)
  ),
)
const shouldShowGuideSuggestionPreview = computed(() =>
  shouldShowActivitySuggestionPreview.value,
)
const currentGenerationLoadingTexts = computed(() =>
  activeMode.value === 'poster' ? posterGenerationLoadingTexts : activityGenerationLoadingTexts,
)
const currentPosterPreviewImage = computed(() => latestGeneratedPoster.value?.url || posterPreviewImage)
const shouldShowPosterGeneratingPreview = computed(() => {
  if (activeMode.value !== 'poster' || latestGeneratedPoster.value?.url)
    return false

  const latestPreviewCard = latestPosterImagePreviewCard.value
  if (latestPreviewCard && ['failed', 'stopped'].includes(latestPreviewCard.status || ''))
    return false

  return isPosterGenerating.value
    || isAiResponseBusy.value
    || hasSubmittedFinalPosterGenerationConfirm.value
})
const generatedActivityTitle = computed(() => {
  const title = String(latestGeneratedActivity.value?.title || '').trim()
  return title || '活动'
})
const showAdoptActivityButton = computed(() => activeMode.value === 'activity')
const showPublishActivityButton = computed(() => activeMode.value === 'activity')
const showExportPosterButton = computed(() => activeMode.value === 'poster')
const canAdoptCurrentResult = computed(() =>
  activeMode.value === 'activity'
  && shouldShowActivityResultConfirmBar.value
  && currentGeneratedActivityId.value > 0
  && !isMessageWorking.value
  && !isConversationLoading.value
  && !isActivityReleaseSubmitting.value,
)
const canPublishCurrentResult = computed(() =>
  activeMode.value === 'activity'
  && shouldShowActivityResultConfirmBar.value
  && currentGeneratedActivityId.value > 0
  && !isMessageWorking.value
  && !isConversationLoading.value
  && !isActivityReleaseSubmitting.value,
)
const canExportCurrentResult = computed(() =>
  activeMode.value === 'poster'
  && !!latestGeneratedPoster.value?.url
  && !isMessageWorking.value
  && !isConversationLoading.value,
)
watch([routeMode, routeConversationId, initialPrompt, routeThinkingMode, isMockPreviewMode], async () => {
  if (isRouteSyncing.value) {
    isRouteSyncing.value = false
    return
  }
  await initializeConversation()
}, { immediate: true })

watch(latestGeneratedResultKey, (resultKey) => {
  if (!resultKey)
    return
  if (resultKey === lastAutoCollapsedResultKey.value)
    return

  lastAutoCollapsedResultKey.value = resultKey
  collapseChatLayout()
})

watch(isAwaitingActivityInquiryResponse, (isAwaiting) => {
  if (isAwaiting)
    expandChatLayout()
})

watch(activityPreviewUrl, (nextUrl) => {
  queueActivityPreview(nextUrl)
}, { immediate: true })

watch(
  [activeMode, currentGeneratedActivityId, currentGeneratedActivityModelId, latestGeneratedActivityCoverImage, isMessageWorking],
  async ([mode, activityId, activityModelId, coverImage, isWorking], _oldValue, onCleanup) => {
    let cancelled = false
    onCleanup(() => {
      cancelled = true
    })

    if (mode !== 'activity' || !activityId) {
      resolvedActivityPreviewUrl.value = ''
      activityPreviewPreparingId.value = 0
      return
    }

    resolvedActivityPreviewUrl.value = ''
    activityPreviewPreparingId.value = activityId
    resetActivityPreviewFrame()

    const activity = latestGeneratedActivity.value
    const syncResult = await syncGeneratedActivityThemeFromCover(activity, {
      coverImg: coverImage,
      waitForCover: true,
    })
    if (cancelled || mode !== activeMode.value || activityId !== currentGeneratedActivityId.value || activityModelId !== currentGeneratedActivityModelId.value)
      return

    if (syncResult.status === 'pending-cover' && isWorking)
      return

    const syncUrl = buildActivityPreviewUrlSync(activityId, activityModelId)
    const previewUrl = await buildActivityPreviewUrl(activityId, activityModelId).catch(() => '')
    if (cancelled || mode !== activeMode.value || activityId !== currentGeneratedActivityId.value || activityModelId !== currentGeneratedActivityModelId.value)
      return

    resolvedActivityPreviewUrl.value = previewUrl.trim() || syncUrl
    activityPreviewPreparingId.value = 0
  },
  { immediate: true },
)

watch(shouldShowGuideSuggestionPreview, (shouldShow) => {
  if (shouldShow) {
    scheduleActivitySuggestionAutoSwitch()
    return
  }

  clearActivitySuggestionTimers()
  isActivitySuggestionAnimating.value = false
}, { immediate: true })

watch([activeMode, shouldShowGuideSuggestionPreview], ([mode, shouldShow]) => {
  if (!shouldShow)
    return

  void fetchAiPromptTips(mode)
}, { immediate: true })

watch(isGenerationTimingActive, (isActive) => {
  if (isActive) {
    startGenerationTimer()
    return
  }

  stopGenerationTimer()
})

aiPageConfigReadyPromise = fetchAiPageConfig()

watch(() => currentActivityInstruction.value?.messageId, (messageId, previousMessageId) => {
  if (!messageId || messageId === previousMessageId || !currentActivityInstruction.value)
    return

  resetActivitySelectorsByStage(currentActivityInstruction.value.stage)
})

watch(() => currentActivityGoalDurationCard.value?.card_id, (cardId, previousCardId) => {
  if (!cardId || cardId === previousCardId)
    return

  selectedActivityGoal.value = ''
  selectedActivityDuration.value = ''
  activityDateRange.value = {
    start: '',
    end: '',
  }
  isActivityBriefReadonly.value = false
  showActivityGoalSelector.value = true
  showActivityDateSelector.value = true
})

watch(() => currentActivityItemSelectorCard.value?.card_id, (cardId, previousCardId) => {
  if (!cardId || cardId === previousCardId)
    return

  selectedActivityProductIds.value = []
  activityProductRequirement.value = ''
  showActivityProductSelector.value = true
})

watch(() => currentActivityStyleCard.value?.card_id, (cardId, previousCardId) => {
  if (!cardId || cardId === previousCardId)
    return

  selectedActivityStyle.value = ''
  activityStyleRequirement.value = ''
  showActivityStyleSelector.value = true
})

onMounted(() => {
  void refreshAiPointsBalance()
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      isPageEntered.value = true
    })
  })
})

onUnmounted(() => {
  clearActivitySuggestionTimers()
  closeAiStream()
  clearPastedImages()
  resetGenerationTimer()
  clearAssistantTypewriter()
  stopMessageWorking()
  stopAutoItemCoverProgressPolling()
})

watch(
  () => ({
    mode: activeMode.value,
    shouldShow: shouldShowActivityProductSelector.value,
    cardId: currentActivityItemSelectorCard.value?.card_id || '',
    selectorType: currentActivityItemSelectorType.value,
  }),
  async ({ mode, shouldShow, cardId, selectorType }) => {
    if (mode !== 'activity' || !shouldShow || !cardId)
      return
    await fetchActivityProducts(selectorType)
  },
  { immediate: true },
)

function buildInitialMessages(mode: ModeKey): ChatMessage[] {
  void mode
  return []
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
    selectedModel.value = normalized.defaults.imageModel
  }
  catch {
    aiPageConfig.value = normalizeAiPageConfig(null)
    selectedSettingsByMode.value.activity.tone = defaultAiPageConfig.styles[0]?.value || ''
    selectedSettingsByMode.value.activity.activityModel = defaultAiPageConfig.activityModels[0]?.value || 'auto'
    selectedSettingsByMode.value.poster.tone = defaultAiPageConfig.styles[0]?.value || ''
    selectedSettingsByMode.value.poster.posterSize = defaultAiPageConfig.sizes[0]?.value || ''
    selectedModel.value = defaultAiPageConfig.models[0]?.value || ''
  }
}

function resetActivitySuggestionCarousel() {
  clearActivitySuggestionTimers()
  activitySuggestionIndex.value = 0
  isActivitySuggestionAnimating.value = false

  if (shouldShowGuideSuggestionPreview.value)
    scheduleActivitySuggestionAutoSwitch()
}

async function fetchAiPromptTips(mode: ModeKey) {
  if (loadedPromptTipModes.value[mode] || loadingPromptTipModes.value[mode])
    return

  loadingPromptTipModes.value = {
    ...loadingPromptTipModes.value,
    [mode]: true,
  }

  try {
    const result = await api.ai.getAiPromptTips({ type: mode })
    const items = Array.isArray(result?.items) ? result.items : []
    const normalizedTips = items
      .map((item: unknown, index: number) => normalizePromptTipItem(item as AiPromptTipItem, index, mode))
      .filter((item: ActivitySuggestion | null): item is ActivitySuggestion => Boolean(item))

    if (normalizedTips.length || mode === 'activity') {
      promptTipsByMode.value = {
        ...promptTipsByMode.value,
        [mode]: mode === 'activity' ? fallbackActivitySuggestions : normalizedTips,
      }

      if (mode === activeMode.value)
        resetActivitySuggestionCarousel()
    }

    loadedPromptTipModes.value = {
      ...loadedPromptTipModes.value,
      [mode]: true,
    }
  }
  catch {
    loadedPromptTipModes.value = {
      ...loadedPromptTipModes.value,
      [mode]: true,
    }
  }
  finally {
    loadingPromptTipModes.value = {
      ...loadingPromptTipModes.value,
      [mode]: false,
    }
  }
}

function buildMockActivityProductOptions(): ActivityProductOption[] {
  const items = [
    {
      id: 1001,
      type: 'bundle' as MerchantItemType,
      title: '新客小气泡清洁套餐',
      name: '新客小气泡清洁套餐',
      cover: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222827_QEqJdnJEPA.png',
      base_price: 99,
      stock: 200,
    },
    {
      id: 1002,
      type: 'voucher' as MerchantItemType,
      title: '到店护理抵扣券',
      name: '到店护理抵扣券',
      cover: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222911_yogqgOPrry.png',
      base_price: 29,
      stock: 500,
    },
    {
      id: 1003,
      type: 'stored_value' as MerchantItemType,
      title: '老客复购储值卡',
      name: '老客复购储值卡',
      cover: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778222930_Cq3TbNTP6B.png',
      base_price: 399,
      stock: 0,
    },
  ]

  return items.map(item => mapUnifiedItemToActivityProduct(item as UnifiedItem))
}

function buildMockActivityPreviewMessages(): ChatMessage[] {
  const briefCard: ActivityGoalDurationCard = {
    card_id: 'mock_goal_duration',
    type: 'activity_goal_duration_selector',
    title: '先确认活动目标和时间',
    step_key: 'activity_goal_duration',
    sections: [
      {
        section_key: 'goal',
        title: '本次店庆的核心目标是什么？',
        options: [
          { value: '拉新获客', label: '拉新获客' },
          { value: '老客复购', label: '老客复购' },
          { value: '会员储值', label: '会员储值' },
        ],
      },
      {
        section_key: 'duration',
        title: '活动计划的起止时间是？或者大概持续几天？',
        options: [
          { value: '最近10天', label: '最近 10 天' },
          { value: 'custom_range', label: '自定义时间', action: 'open_date_picker' },
        ],
      },
    ],
  }

  const productCard: ActivityItemSelectorCard = {
    card_id: 'mock_product_selector',
    type: 'activity_item_selector',
    title: '想主推的什么商品？快灵已帮你准备好系统内已有的商品',
    selector_type: 'mixed_items',
    step_key: 'activity_select_items',
    min_select_count: 1,
  }

  const styleCard: ActivityStyleSelectorCard = {
    card_id: 'mock_style_selector',
    type: 'activity_style_selector',
    title: '活动氛围有什么风格偏好？',
    step_key: 'activity_style_preference',
    options: fallbackActivityStyleOptions.map(option => ({
      value: option.value,
      label: option.label,
    })),
  }
  const confirmCard: ActivityDeepConfirmCard = {
    card_id: 'mock_activity_deep_confirm',
    type: 'activity_deep_confirm',
    title: '信息已确认，开始生成活动方案',
    step_key: 'activity_deep_confirm',
    submit_button_text: deepConfirmSubmitText,
    thinking: '正在根据活动目标、时间、商品和风格生成活动方案。',
    summary: ['活动目标：拉新获客', '活动周期：2026-07-10 至 2026-07-20', '主推商品：2 个'],
  }
  return [
    {
      id: 'mock_user_with_images',
      messageId: 'mock_user_with_images',
      role: 'user',
      status: 'success',
      content: '帮我做一个新客拉新活动，想要有节日感、价格门槛低一点，适合朋友圈转发。',
      attachments: [
        {
          url: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778685865_9Ez3vzr1I9.png',
          name: '参考海报.png',
          type: 'image/png',
        },
        {
          url: 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778663651_nlAVosokfd.png',
          name: '参考活动.png',
          type: 'image/png',
        },
      ],
      componentResult: {
        think_mode: 'deep',
        think_mode_label: '深度思考',
      },
    },
    {
      id: 'mock_assistant_text',
      messageId: 'mock_assistant_text',
      role: 'assistant',
      status: 'completed',
      content: '收到，我会用深度思考模式先拆解目标人群、商品承接、活动时间和页面风格。下面这组 mock 会展示活动生成对话里可能出现的文案、组件和状态，便于调整样式。',
    },
    {
      id: 'mock_assistant_brief_card',
      messageId: 'mock_assistant_brief_card',
      role: 'assistant',
      status: 'completed',
      content: '第一步，先确认活动目标和时间。',
      cards: [briefCard],
    },
    {
      id: 'mock_user_brief_result',
      messageId: 'mock_user_brief_result',
      role: 'user',
      status: 'success',
      content: '我已经选择了活动目标和时间',
      componentResult: {
        card_id: briefCard.card_id,
        component_type: briefCard.type,
        step_key: briefCard.step_key,
        status: 'submitted',
        goal: {
          value: '拉新获客',
          label: '拉新获客',
        },
        duration: {
          value: 'custom_range',
          label: '自定义时间',
          start_time: '2026-07-10 00:00:00',
          end_time: '2026-07-20 23:59:59',
        },
      },
    },
    {
      id: 'mock_assistant_product_card',
      messageId: 'mock_assistant_product_card',
      role: 'assistant',
      status: 'completed',
      content: '第二步，选择本次活动要承接转化的主推商品。',
      cards: [productCard],
    },
    {
      id: 'mock_user_product_result',
      messageId: 'mock_user_product_result',
      role: 'user',
      status: 'success',
      content: '我已经选择了活动项目',
      componentResult: {
        card_id: productCard.card_id,
        component_type: productCard.type,
        step_key: productCard.step_key,
        status: 'submitted',
        selector_type: 'mixed_items',
        items: [
          { item_id: 1001, item_type: 'mixed_items', title: '新客小气泡清洁套餐' },
          { item_id: 1002, item_type: 'mixed_items', title: '到店护理抵扣券' },
        ],
        item_requirement: '希望商品卡片能突出低门槛和到店转化。',
      },
    },
    {
      id: 'mock_assistant_style_card',
      messageId: 'mock_assistant_style_card',
      role: 'assistant',
      status: 'completed',
      content: '第三步，选择活动页面的视觉风格。',
      cards: [styleCard],
    },
    {
      id: 'mock_user_style_result',
      messageId: 'mock_user_style_result',
      role: 'user',
      status: 'success',
      content: '我已经选择了活动风格',
      componentResult: {
        card_id: styleCard.card_id,
        component_type: styleCard.type,
        step_key: styleCard.step_key,
        status: 'submitted',
        style: {
          value: 'trend_3d',
          label: '3D潮玩',
        },
        style_requirement: '颜色希望更明亮，按钮要明显一点。',
      },
    },
    {
      id: 'mock_assistant_pending',
      messageId: 'mock_assistant_pending',
      role: 'assistant',
      status: 'pending',
      content: '',
    },
    {
      id: 'mock_assistant_streaming_text',
      messageId: 'mock_assistant_streaming_text',
      role: 'assistant',
      status: 'streaming',
      content: '正在整理活动结构：我会先生成活动标题、利益点、商品承接区、裂变奖励和报名/开团引导，再同步右侧预览效果...',
    },
    {
      id: 'mock_assistant_streaming',
      messageId: 'mock_assistant_streaming',
      role: 'assistant',
      status: 'streaming',
      content: '',
    },
    {
      id: 'mock_assistant_stopped',
      messageId: 'mock_assistant_stopped',
      role: 'assistant',
      status: 'stopped',
      content: '',
    },
    {
      id: 'mock_assistant_error',
      messageId: 'mock_assistant_error',
      role: 'assistant',
      status: 'error',
      content: '',
      errorMessage: '这是 mock 错误状态，用来调试失败提示样式。',
    },
    {
      id: 'mock_assistant_result',
      messageId: 'mock_assistant_result',
      role: 'assistant',
      status: 'completed',
      content: '活动方案已生成完成。你可以查看右侧手机预览，也可以在这里继续调整活动标题、利益点、商品展示和页面氛围。',
      activity: {
        activity_id: 900001,
        title: '夏日新客爆款体验活动',
        status: 'draft',
        preview_url: null,
      },
      meta: {
        activity: {
          activity_id: 900001,
          title: '夏日新客爆款体验活动',
          status: 'draft',
          preview_url: null,
        },
      },
    },
    {
      id: 'mock_assistant_component_preview',
      messageId: 'mock_assistant_component_preview',
      role: 'assistant',
      status: 'completed',
      content: '组件集中预览：下面同时展示核心目标、活动时间、商品选择、风格选择和确认组件，方便你直接调整视觉效果。',
      cards: [
        {
          ...briefCard,
          card_id: 'mock_goal_duration_preview',
        },
        {
          ...productCard,
          card_id: 'mock_product_selector_preview',
        },
        {
          ...styleCard,
          card_id: 'mock_style_selector_preview',
        },
        confirmCard,
      ],
    },
  ]
}

function buildMockPosterPreviewMessages(): ChatMessage[] {
  return [
    {
      id: 'mock_poster_user_brief',
      messageId: 'mock_poster_user_brief',
      role: 'user',
      status: 'success',
      content: '帮我生成一张小红书风格的医美新客海报，主题是夏季补水修护，想要高级一点、标题明显、适合朋友圈转发。',
      attachments: [
        {
          url: posterPreviewImage,
          name: '海报参考图.png',
          type: 'image/png',
        },
      ],
      componentResult: {
        think_mode: 'deep',
        think_mode_label: '深度思考',
      },
    },
    {
      id: 'mock_poster_assistant_parse',
      messageId: 'mock_poster_assistant_parse',
      role: 'assistant',
      status: 'completed',
      content: '我会先提炼海报主题：夏季补水修护、新客低门槛体验、朋友圈转发场景。视觉上建议使用清透肤感、浅色背景、强标题层级和明确的行动按钮。',
    },
    {
      id: 'mock_poster_user_setting',
      messageId: 'mock_poster_user_setting',
      role: 'user',
      status: 'success',
      content: '尺寸选 3:4，风格想要通透、轻奢，不要太花。',
      componentResult: {
        think_mode: 'deep',
        poster_size: '3:4',
        tone: '轻奢通透',
      },
    },
    {
      id: 'mock_poster_assistant_plan',
      messageId: 'mock_poster_assistant_plan',
      role: 'assistant',
      status: 'completed',
      content: '好的，我会按 3:4 手机海报处理：顶部突出“夏季补水修护”，中部放项目利益点和价格锚点，底部补充预约引导与门店信任信息。',
    },
    {
      id: 'mock_poster_assistant_pending',
      messageId: 'mock_poster_assistant_pending',
      role: 'assistant',
      status: 'pending',
      content: '',
    },
    {
      id: 'mock_poster_assistant_streaming',
      messageId: 'mock_poster_assistant_streaming',
      role: 'assistant',
      status: 'streaming',
      content: '正在生成海报：已完成标题层级、主视觉方向、项目卖点和预约按钮布局，正在优化色彩、留白和移动端可读性...',
    },
    {
      id: 'mock_poster_assistant_stopped',
      messageId: 'mock_poster_assistant_stopped',
      role: 'assistant',
      status: 'stopped',
      content: '',
    },
    {
      id: 'mock_poster_assistant_error',
      messageId: 'mock_poster_assistant_error',
      role: 'assistant',
      status: 'error',
      content: '',
      errorMessage: '这是海报生成失败 mock，用于检查错误提示、重试入口和气泡样式。',
    },
    {
      id: 'mock_poster_assistant_result',
      messageId: 'mock_poster_assistant_result',
      role: 'assistant',
      status: 'completed',
      content: '海报已生成完成。右侧可以预览整张主图，你可以继续要求我修改标题、颜色、价格利益点、背景质感或导出当前版本。',
      attachments: [
        {
          url: posterPreviewImage,
          name: 'AI生成海报.png',
          type: 'image/png',
        },
      ],
    },
    {
      id: 'mock_poster_user_revision',
      messageId: 'mock_poster_user_revision',
      role: 'user',
      status: 'success',
      content: '标题再大一点，按钮更醒目，价格区域不要太靠下。',
    },
    {
      id: 'mock_poster_assistant_revision',
      messageId: 'mock_poster_assistant_revision',
      role: 'assistant',
      status: 'completed',
      content: '可以，我会把标题字号提升一级，按钮改成更高对比的暖色，同时把价格模块上移到用户第一眼能看到的位置。',
    },
  ]
}

function buildMockPreviewMessages(mode: ModeKey): ChatMessage[] {
  return mode === 'poster'
    ? buildMockPosterPreviewMessages()
    : buildMockActivityPreviewMessages()
}

function applyMockPreviewConversation() {
  currentConversation.value = null
  historyConversationTotal.value = 12
  draftMessage.value = ''
  activityPreviewStatus.value = 'result'
  isConversationLoading.value = false
  stopMessageWorking()
  resetActivityQuickSelectors()
  activityProductOptions.value = buildMockActivityProductOptions()
  hasActivityProductsLoaded.value = true
  selectedActivityGoal.value = '拉新获客'
  selectedActivityDuration.value = 'custom_range'
  activityDateRange.value = {
    start: '2026-07-10',
    end: '2026-07-20',
  }
  selectedActivityProductIds.value = ['1001', '1002']
  activityProductRequirement.value = '希望商品卡片能突出低门槛和到店转化。'
  selectedActivityStyle.value = 'trend_3d'
  activityStyleRequirement.value = '颜色希望更明亮，按钮要明显一点。'
  chatMessages.value = buildMockPreviewMessages(activeMode.value)
}

function collectComponentResultStrings(value: unknown, bucket: string[] = []) {
  if (typeof value === 'string') {
    const text = value.trim()
    if (text)
      bucket.push(text)
    return bucket
  }

  if (Array.isArray(value)) {
    value.forEach(item => collectComponentResultStrings(item, bucket))
    return bucket
  }

  if (value && typeof value === 'object') {
    Object.values(value as Record<string, unknown>).forEach(item => collectComponentResultStrings(item, bucket))
  }

  return bucket
}

function getActivityUiStageFromComponentResult(componentResult: ChatMessage['componentResult']): ActivityUiStage | null {
  const normalizedText = collectComponentResultStrings(componentResult)
    .join(' ')
    .toLowerCase()

  if (!normalizedText)
    return null

  if (/(brief|goal|date|time_range|activity_brief|核心目标|起止时间|活动时间)/i.test(normalizedText))
    return 'brief'
  if (/(product|goods|item|sku|merchant_item|主推商品|卖品|商品选择)/i.test(normalizedText))
    return 'product'
  if (/(style|tone|visual|氛围|风格|视觉风格)/i.test(normalizedText))
    return 'style'
  if (/(result|summary|preview|final|完成|采用活动|去发布|活动方案)/i.test(normalizedText))
    return 'result'

  return null
}

function getActivityUiStageFromMeta(meta: ChatMessage['meta']): ActivityUiStage | null {
  if (!meta)
    return null

  const normalizedText = collectComponentResultStrings(meta)
    .join(' ')
    .toLowerCase()

  if (!normalizedText)
    return null

  if (/(brief|goal|date|time_range|activity_brief|核心目标|起止时间|活动时间)/i.test(normalizedText))
    return 'brief'
  if (/(product|goods|item|sku|merchant_item|主推商品|卖品|商品选择)/i.test(normalizedText))
    return 'product'
  if (/(style|tone|visual|氛围|风格|视觉风格)/i.test(normalizedText))
    return 'style'
  if (/(result|summary|preview|final|完成|采用活动|去发布|活动方案)/i.test(normalizedText))
    return 'result'

  return null
}

function getActivityUiStageFromCards(cards: ChatMessage['cards']): ActivityUiStage | null {
  const normalizedCards = normalizeActivityCards(cards)
  if (normalizedCards.some(card => card.type === 'activity_goal_duration_selector'))
    return 'brief'
  if (normalizedCards.some(card => card.type === 'activity_item_selector'))
    return 'product'
  if (normalizedCards.some(card => card.type === 'activity_style_selector'))
    return 'style'
  if (normalizedCards.some(card => card.type === 'activity_deep_confirm'))
    return 'result'
  return null
}

function getActivityUiStageFromMessage(message: ChatMessage): ActivityUiStage | null {
  if (message.role !== 'assistant' || message.isSystem || message.status === 'error')
    return null

  if (getChatMessageGeneratedActivity(message))
    return 'result'

  const cardStage = getActivityUiStageFromCards(message.cards)
  if (cardStage)
    return cardStage

  const componentStage = getActivityUiStageFromComponentResult(message.componentResult)
  if (componentStage)
    return componentStage

  const metaStage = getActivityUiStageFromMeta(message.meta)
  if (metaStage)
    return metaStage

  const content = message.content.trim()
  if (!content)
    return null

  if (/(核心目标|起止时间|持续几天|活动计划的起止时间|活动周期|店庆的核心目标)/.test(content))
    return 'brief'
  if (/(主推的什么商品|主推商品|系统内已有的商品|选择商品|围绕这些商品)/.test(content))
    return 'product'
  if (/(活动氛围|风格偏好|视觉风格|按这个方向优化活动氛围)/.test(content))
    return 'style'
  if (/(活动基础搭建|直接采用|继续让我优化|查看并使用该活动|活动方案|去发布|重新生成一版活动方案)/.test(content))
    return 'result'

  return null
}

async function initializeConversation() {
  if (aiPageConfigReadyPromise)
    await aiPageConfigReadyPromise

  activeMode.value = routeMode.value
  selectedThinkingMode.value = routeThinkingMode.value

  if (isMockPreviewMode.value) {
    closeAiStream()
    applyMockPreviewConversation()
    syncChatLayoutModeForConversationState()
    return
  }

  draftMessage.value = initialPrompt.value.trim()
  activitySuggestionIndex.value = 0
  resetActivityQuickSelectors()
  closeAiStream()
  stopMessageWorking()
  resetGenerationTimer()

  if (!hasAiAccessToken()) {
    currentConversation.value = null
    applySavedComposerSelection()
    chatMessages.value = buildInitialMessages(activeMode.value)
    syncActivityPreviewStatus()
    resetChatLayoutMode()
    return
  }

  isConversationLoading.value = true
  try {
    if (routeConversationId.value) {
      await loadConversation(routeConversationId.value)
    }
    else {
      currentConversation.value = null
      applySavedComposerSelection()
      chatMessages.value = buildInitialMessages(activeMode.value)
      syncActivityPreviewStatus()
      resetChatLayoutMode()
    }
  }
  finally {
    isConversationLoading.value = false
    void refreshHistoryConversationTotal()
  }
}

async function refreshHistoryConversationTotal() {
  if (!hasAiAccessToken())
    return

  try {
    const result = await api.ai.getAiConversationList({
      shop_id: getCurrentShopId(),
      page: 1,
      per_page: 1,
    })
    historyConversationTotal.value = result.total || 0
  }
  catch {
    historyConversationTotal.value = historyConversationTotal.value || 0
  }
}

async function refreshAiPointsBalance() {
  if (!hasAiAccessToken())
    return

  try {
    const result = await api.ai.getAiPoints({ shop_id: getCurrentShopId() })
    const balance = Number(result.balance)
    aiPointsBalance.value = Number.isFinite(balance) ? balance : 0
  }
  catch {
    aiPointsBalance.value = aiPointsBalance.value ?? null
  }
}

function applyAiPointsBalance(value: unknown) {
  const balance = Number(value)
  if (Number.isFinite(balance))
    aiPointsBalance.value = balance
}

function mapApiMessageToChatMessage(message: AiMessage): ChatMessage {
  const activity = extractGeneratedActivity(message)
  const poster = extractGeneratedPoster(message)
  return {
    id: message.message_id,
    messageId: message.message_id,
    role: message.role,
    content: message.content || '',
    status: message.status,
    errorMessage: message.error_message || null,
    createdAt: message.created_at,
    isSystem: isComponentSelectionMessage(message),
    attachments: normalizeMessageAttachments(message.attachments),
    cards: normalizeActivityCards(message.components),
    activity,
    poster,
    componentResult: message.component_result ?? null,
    meta: mergeMessageMetaWithPoster(mergeMessageMetaWithActivity(message.meta ?? null, activity), poster),
  }
}

function upsertChatMessage(nextMessage: ChatMessage) {
  const index = chatMessages.value.findIndex(item => item.messageId === nextMessage.messageId)
  if (index === -1) {
    chatMessages.value.push(nextMessage)
    return
  }
  chatMessages.value[index] = {
    ...chatMessages.value[index],
    ...nextMessage,
  }
}

function patchAssistantMessage(messageId: string, patch: Partial<ChatMessage>) {
  const index = chatMessages.value.findIndex(item => item.messageId === messageId)
  if (index === -1) {
    chatMessages.value.push({
      id: messageId,
      messageId,
      role: 'assistant',
      content: '',
      ...patch,
    })
    return
  }

  chatMessages.value[index] = {
    ...chatMessages.value[index],
    ...patch,
  }
}

function appendAssistantText(messageId: string, text: string, seq?: number) {
  const index = chatMessages.value.findIndex(item => item.messageId === messageId)
  if (index === -1) {
    chatMessages.value.push({
      id: messageId,
      messageId,
      role: 'assistant',
      status: 'streaming',
      content: text,
      seq,
    })
    return
  }

  const currentMessage = chatMessages.value[index]
  chatMessages.value[index] = {
    ...currentMessage,
    status: 'streaming',
    content: `${currentMessage.content}${text}`,
    seq: seq ?? currentMessage.seq,
  }
}

function appendAssistantThinkingText(messageId: string, text: string, seq?: number) {
  const index = chatMessages.value.findIndex(item => item.messageId === messageId)
  if (index === -1) {
    chatMessages.value.push({
      id: messageId,
      messageId,
      role: 'assistant',
      status: 'streaming',
      content: '',
      meta: {
        deep_thinking_text: text,
      },
      seq,
    })
    return
  }

  const currentMessage = chatMessages.value[index]
  const currentMeta = currentMessage.meta && typeof currentMessage.meta === 'object' ? currentMessage.meta : {}
  chatMessages.value[index] = {
    ...currentMessage,
    status: 'streaming',
    meta: {
      ...currentMeta,
      deep_thinking_text: `${String(currentMeta.deep_thinking_text || '')}${text}`,
    },
    seq: seq ?? currentMessage.seq,
  }
}

function clearAssistantTypewriter() {
  if (assistantTypewriterTimer) {
    clearInterval(assistantTypewriterTimer)
    assistantTypewriterTimer = null
  }
  assistantTypewriterQueues.clear()
  assistantThinkingTypewriterQueues.clear()
  assistantFastTypewriterMessageIds.clear()
  assistantPresentationTimers.forEach(timer => clearTimeout(timer))
  assistantPresentationTimers.clear()
  assistantPresentationQueues.clear()
}

function hasPendingAssistantTypewriterText(messageId?: string) {
  if (messageId) {
    return Boolean(assistantTypewriterQueues.get(messageId)?.text)
      || Boolean(assistantThinkingTypewriterQueues.get(messageId)?.text)
  }

  return Array.from(assistantTypewriterQueues.values()).some(queue => queue.text.length > 0)
    || Array.from(assistantThinkingTypewriterQueues.values()).some(queue => queue.text.length > 0)
}

function getAssistantTypewriterSpeed(messageId: string, queue: AssistantTypewriterQueue): AssistantTypewriterSpeed {
  if (assistantFastTypewriterMessageIds.has(messageId))
    return 'fast'

  return queue.receivedLength > ASSISTANT_TYPEWRITER_SHORT_MESSAGE_MAX_LENGTH
    || queue.text.length > ASSISTANT_TYPEWRITER_SHORT_MESSAGE_MAX_LENGTH
    ? 'normal'
    : 'slow'
}

function getAssistantTextChunkLength(messageId: string, queue: AssistantTypewriterQueue) {
  const speed = getAssistantTypewriterSpeed(messageId, queue)
  if (speed === 'fast')
    return 10
  return speed === 'normal' ? 2 : 1
}

function getAssistantThinkingChunkLength(messageId: string, queue: AssistantTypewriterQueue) {
  const speed = getAssistantTypewriterSpeed(messageId, queue)
  if (speed === 'fast')
    return 8
  return speed === 'normal' ? 3 : 1
}

function shouldRenderThinkingOnCurrentTick(messageId: string, queue: AssistantTypewriterQueue) {
  const speed = getAssistantTypewriterSpeed(messageId, queue)
  if (speed !== 'normal')
    return true

  return assistantTypewriterTickCount % 2 === 0
}

function accelerateAssistantTypewriter(messageId: string) {
  assistantFastTypewriterMessageIds.add(messageId)
  ensureAssistantTypewriterTimer()
}

function processAssistantPresentationQueue(messageId: string) {
  if (assistantPresentationTimers.has(messageId))
    return

  const queue = assistantPresentationQueues.get(messageId)
  if (!queue?.length) {
    assistantPresentationQueues.delete(messageId)
    return
  }

  if (hasPendingAssistantTypewriterText(messageId)) {
    const nextTimer = setTimeout(() => {
      assistantPresentationTimers.delete(messageId)
      processAssistantPresentationQueue(messageId)
    }, ASSISTANT_TYPEWRITER_INTERVAL_MS)
    assistantPresentationTimers.set(messageId, nextTimer)
    return
  }

  const task = queue.shift()
  task?.()
  if (!queue.length) {
    assistantPresentationQueues.delete(messageId)
    return
  }

  const nextTimer = setTimeout(() => {
    assistantPresentationTimers.delete(messageId)
    processAssistantPresentationQueue(messageId)
  }, ASSISTANT_COMPONENT_REVEAL_DELAY_MS)
  assistantPresentationTimers.set(messageId, nextTimer)
}

function enqueueAssistantPresentation(messageId: string, task: () => void) {
  const queue = assistantPresentationQueues.get(messageId) || []
  queue.push(task)
  assistantPresentationQueues.set(messageId, queue)
  processAssistantPresentationQueue(messageId)
}

function flushAssistantTypewriter(messageId?: string) {
  if (messageId) {
    const queue = assistantTypewriterQueues.get(messageId)
    if (queue?.text) {
      appendAssistantText(messageId, queue.text, queue.seq)
      assistantTypewriterQueues.delete(messageId)
      syncActivityPreviewStatus()
    }
    const thinkingQueue = assistantThinkingTypewriterQueues.get(messageId)
    if (thinkingQueue?.text) {
      appendAssistantThinkingText(messageId, thinkingQueue.text, thinkingQueue.seq)
      assistantThinkingTypewriterQueues.delete(messageId)
      syncActivityPreviewStatus()
    }
  }
  else {
    assistantTypewriterQueues.forEach((queue, queuedMessageId) => {
      if (queue.text)
        appendAssistantText(queuedMessageId, queue.text, queue.seq)
    })
    assistantTypewriterQueues.clear()
    assistantThinkingTypewriterQueues.forEach((queue, queuedMessageId) => {
      if (queue.text)
        appendAssistantThinkingText(queuedMessageId, queue.text, queue.seq)
    })
    assistantThinkingTypewriterQueues.clear()
    syncActivityPreviewStatus()
  }

  if (!hasPendingAssistantTypewriterText() && assistantTypewriterTimer) {
    clearInterval(assistantTypewriterTimer)
    assistantTypewriterTimer = null
  }

  Array.from(assistantPresentationQueues.keys()).forEach(processAssistantPresentationQueue)
}

function runAssistantTypewriterTick() {
  assistantTypewriterTickCount += 1
  assistantTypewriterQueues.forEach((queue, messageId) => {
    if (!queue.text) {
      assistantTypewriterQueues.delete(messageId)
      return
    }

    const chunkLength = getAssistantTextChunkLength(messageId, queue)
    const chunk = queue.text.slice(0, chunkLength)
    queue.text = queue.text.slice(chunkLength)
    appendAssistantText(messageId, chunk, queue.seq)
    if (!queue.text)
      assistantTypewriterQueues.delete(messageId)
  })

  assistantThinkingTypewriterQueues.forEach((queue, messageId) => {
    if (!queue.text) {
      assistantThinkingTypewriterQueues.delete(messageId)
      return
    }
    if (!shouldRenderThinkingOnCurrentTick(messageId, queue))
      return

    const chunkLength = getAssistantThinkingChunkLength(messageId, queue)
    const chunk = queue.text.slice(0, chunkLength)
    queue.text = queue.text.slice(chunkLength)
    appendAssistantThinkingText(messageId, chunk, queue.seq)
    if (!queue.text)
      assistantThinkingTypewriterQueues.delete(messageId)
  })

  syncActivityPreviewStatus()
  Array.from(assistantPresentationQueues.keys()).forEach(processAssistantPresentationQueue)

  if (!hasPendingAssistantTypewriterText() && assistantTypewriterTimer) {
    clearInterval(assistantTypewriterTimer)
    assistantTypewriterTimer = null
  }
}

function ensureAssistantTypewriterTimer() {
  if (assistantTypewriterTimer)
    return

  assistantTypewriterTimer = setInterval(runAssistantTypewriterTick, ASSISTANT_TYPEWRITER_INTERVAL_MS)
}

function appendAssistantDelta(messageId: string, delta: string, seq?: number) {
  if (!delta)
    return

  const queue = assistantTypewriterQueues.get(messageId) || { text: '', receivedLength: 0, seq }
  queue.text += delta
  queue.receivedLength += delta.length
  queue.seq = seq ?? queue.seq
  assistantTypewriterQueues.set(messageId, queue)
  ensureAssistantTypewriterTimer()
}

function appendAssistantThinkingDelta(messageId: string, delta: string, seq?: number) {
  if (!delta)
    return

  const queue = assistantThinkingTypewriterQueues.get(messageId) || { text: '', receivedLength: 0, seq }
  queue.text += delta
  queue.receivedLength += delta.length
  queue.seq = seq ?? queue.seq
  assistantThinkingTypewriterQueues.set(messageId, queue)
  ensureAssistantTypewriterTimer()
}

function normalizeColorValue(value: unknown) {
  return String(value || '').trim().toUpperCase()
}

function getActivityThemeSyncKey(activityId: number, coverImg: string) {
  return `${activityId}:${coverImg || 'auto'}`
}

function isActivityBackgroundColorSynced(detail: Record<string, any> | null | undefined, backgroundColor: string) {
  return normalizeColorValue(getActivityDetailBackgroundColor(detail)) === normalizeColorValue(backgroundColor)
}

function getGeneratedActivityCoverImage(activity: AiGeneratedActivity | null | undefined) {
  const rawActivity = activity && typeof activity === 'object' ? activity as Record<string, any> : null
  return String(
    rawActivity?.cover_img
    || rawActivity?.coverImg
    || rawActivity?.cover
    || rawActivity?.image_url
    || rawActivity?.main_image
    || rawActivity?.mainImage
    || '',
  ).trim()
}

function getActivityPreviewCardImageUrl(card: ActivityAssistantCard | null | undefined) {
  if (!card || typeof card !== 'object')
    return ''

  const rawCard = card as Record<string, any>
  const activity = rawCard.activity && typeof rawCard.activity === 'object'
    ? rawCard.activity as Record<string, any>
    : null

  return String(
    rawCard.image_url
    || rawCard.imageUrl
    || rawCard.img_url
    || rawCard.imgUrl
    || rawCard.cover_img
    || rawCard.coverImg
    || rawCard.cover
    || rawCard.url
    || activity?.cover_img
    || activity?.coverImg
    || activity?.image_url
    || activity?.main_image
    || activity?.mainImage
    || '',
  ).trim()
}

function getActivityPreviewCardActivityId(card: ActivityAssistantCard | null | undefined) {
  if (!card || typeof card !== 'object')
    return 0

  const rawCard = card as Record<string, any>
  const activity = rawCard.activity && typeof rawCard.activity === 'object'
    ? rawCard.activity as Record<string, any>
    : null
  const activityId = Number(rawCard.activity_id || rawCard.activityId || activity?.activity_id || activity?.id || 0)
  return Number.isFinite(activityId) && activityId > 0 ? activityId : 0
}

function getLatestActivityCoverPreviewImage(activityId = 0) {
  for (let messageIndex = chatMessages.value.length - 1; messageIndex >= 0; messageIndex -= 1) {
    const message = chatMessages.value[messageIndex]
    if (message.role !== 'assistant' || message.isSystem)
      continue

    const cards = normalizeActivityCards(message.cards)
    for (let cardIndex = cards.length - 1; cardIndex >= 0; cardIndex -= 1) {
      const card = cards[cardIndex]
      if (card.type !== 'activity_cover_preview')
        continue

      const cardActivityId = getActivityPreviewCardActivityId(card)
      if (activityId && cardActivityId && cardActivityId !== activityId)
        continue

      const imageUrl = getActivityPreviewCardImageUrl(card)
      if (imageUrl)
        return imageUrl
    }
  }

  return ''
}

function normalizeActivityDetailComponentList(value: unknown): Record<string, any>[] {
  if (Array.isArray(value))
    return value.filter((item): item is Record<string, any> => Boolean(item && typeof item === 'object'))

  if (typeof value === 'string' && value.trim()) {
    try {
      return normalizeActivityDetailComponentList(JSON.parse(value))
    } catch {
      return []
    }
  }

  return []
}

function getMainGraphImageFromComponent(component: Record<string, any> | null | undefined): string {
  if (!component || typeof component !== 'object')
    return ''

  const type = String(
    component.type
    || component.components_type
    || component.component_type
    || component.componentType
    || component.component_key
    || component.componentKey
    || component.source_type
    || component.sourceType
    || component.paletteKey
    || component.key
    || '',
  ).trim()

  if (type === 'component_main_graphs' || type === 'basic-hero') {
    const image = String(
      component.img_url
      || component.image_url
      || component.cover_img
      || component.coverImg
      || component.cover
      || component.main_image
      || component.mainImage
      || component.url
      || '',
    ).trim()
    if (image)
      return image
  }

  const nestedSources = [
    component.components,
    component.children,
    component.items,
    component.component,
    component.component_info,
    component.componentInfo,
    component.components_info,
    component.componentsInfo,
    component.config,
    component.props,
    component.data,
    component.info,
  ]

  for (const source of nestedSources) {
    const nestedImage = getMainGraphImageFromActivityComponents(source)
    if (nestedImage)
      return nestedImage
  }

  return ''
}

function getMainGraphImageFromActivityComponents(value: unknown): string {
  const components = normalizeActivityDetailComponentList(value)
  for (const component of components) {
    const image = getMainGraphImageFromComponent(component)
    if (image)
      return image
  }

  if (value && typeof value === 'object' && !Array.isArray(value))
    return getMainGraphImageFromComponent(value as Record<string, any>)

  return ''
}

function getActivityDetailCoverImage(detail: Record<string, any> | null | undefined) {
  const meta = detail?.meta && typeof detail.meta === 'object' ? detail.meta as Record<string, any> : null
  const componentCover = getMainGraphImageFromActivityComponents(detail?.components)
    || getMainGraphImageFromActivityComponents(detail?.activity_components)
    || getMainGraphImageFromActivityComponents(detail?.activity_component)
    || getMainGraphImageFromActivityComponents(detail?.model_components)

  return String(
    componentCover
    || detail?.cover_img
    || detail?.coverImg
    || detail?.cover
    || detail?.image_url
    || detail?.main_image
    || detail?.mainImage
    || meta?.cover_img
    || meta?.coverImg
    || '',
  ).trim()
}

function getActivityDetailBackgroundColor(detail: Record<string, any> | null | undefined) {
  const meta = detail?.meta && typeof detail.meta === 'object' ? detail.meta as Record<string, any> : null
  return String(
    detail?.background_color
    || detail?.backgroundColor
    || meta?.background_color
    || meta?.backgroundColor
    || '',
  ).trim()
}

async function reloadCurrentActivityPreviewFrame(activityId: number) {
  if (activeMode.value !== 'activity' || activityId !== currentGeneratedActivityId.value)
    return

  const activityModelId = currentGeneratedActivityModelId.value
  const fallbackUrl = buildActivityPreviewUrlSync(activityId, activityModelId)
  const nextUrl = (await buildActivityPreviewUrl(activityId, activityModelId).catch(() => '')) || fallbackUrl
  if (nextUrl) {
    resolvedActivityPreviewUrl.value = nextUrl
    queueActivityPreview(nextUrl)
    return
  }

  displayedActivityPreviewKey.value += 1
}

function waitActivityThemeSyncRetry() {
  return new Promise(resolve => setTimeout(resolve, AI_ACTIVITY_THEME_SYNC_RETRY_DELAY))
}

function waitActivityThemeConfirmRetry() {
  return new Promise(resolve => setTimeout(resolve, AI_ACTIVITY_THEME_CONFIRM_RETRY_DELAY))
}

async function waitForActivityBackgroundColor(activityId: number, backgroundColor: string) {
  let latestDetail: Record<string, any> | null = null
  for (let attempt = 0; attempt < AI_ACTIVITY_THEME_CONFIRM_RETRY_LIMIT; attempt += 1) {
    try {
      latestDetail = await api.activity.getActivityDetail(activityId, { stage: AI_ACTIVITY_THEME_STAGE }) as Record<string, any>
      if (isActivityBackgroundColorSynced(latestDetail, backgroundColor))
        return latestDetail
    } catch (error) {
      console.warn('[ai-chat] confirm activity theme color failed:', {
        activityId,
        attempt: attempt + 1,
        error,
      })
    }

    if (attempt < AI_ACTIVITY_THEME_CONFIRM_RETRY_LIMIT - 1)
      await waitActivityThemeConfirmRetry()
  }

  return latestDetail
}

function syncGeneratedActivityThemeFromCover(
  activity: AiGeneratedActivity | null | undefined,
  options: { coverImg?: string, waitForCover?: boolean } = {},
) {
  if (activeMode.value !== 'activity' || isMockPreviewMode.value)
    return Promise.resolve({ status: 'skipped' } satisfies ActivityThemeSyncResult)

  const activityId = Number(activity?.activity_id || 0)
  if (!activityId)
    return Promise.resolve({ status: 'skipped' } satisfies ActivityThemeSyncResult)

  const initialCoverImg = String(
    options.coverImg
    || getLatestActivityCoverPreviewImage(activityId)
    || getGeneratedActivityCoverImage(activity)
    || '',
  ).trim()
  const initialSyncKey = getActivityThemeSyncKey(activityId, initialCoverImg)
  if (initialCoverImg && syncedGeneratedActivityThemeKeys.has(initialSyncKey)) {
    return Promise.resolve({
      status: 'synced',
      activityId,
      coverImg: initialCoverImg,
    } satisfies ActivityThemeSyncResult)
  }

  const currentJob = syncingGeneratedActivityThemeJobs.get(initialSyncKey)
  if (currentJob)
    return currentJob

  const syncJob = (async () => {
    try {
      let coverImg = initialCoverImg
      let detail: Record<string, any> | null = null
      let currentBackgroundColor = ''
      let colorResult: Awaited<ReturnType<typeof resolveMainImageBackgroundColors>> | null = null
      const retryLimit = options.waitForCover ? AI_ACTIVITY_THEME_SYNC_RETRY_LIMIT : 1

      for (let attempt = 0; attempt < retryLimit; attempt += 1) {
        coverImg = String(
          options.coverImg
          || getLatestActivityCoverPreviewImage(activityId)
          || coverImg
          || '',
        ).trim()

        try {
          detail = await api.activity.getActivityDetail(activityId, { stage: AI_ACTIVITY_THEME_STAGE }) as Record<string, any>
        } catch (error) {
          console.warn('[ai-chat] load activity detail for theme failed:', error)
        }

        coverImg = getActivityDetailCoverImage(detail) || coverImg
        currentBackgroundColor = getActivityDetailBackgroundColor(detail)

        if (coverImg) {
          colorResult = await resolveMainImageBackgroundColors(coverImg)
          if (colorResult?.source === 'sampled')
            break

          console.warn('[ai-chat] activity theme color fallback, will retry if possible:', {
            activityId,
            attempt: attempt + 1,
            retryLimit,
            reason: colorResult?.reason || 'unknown',
            coverImg,
          })
        }

        if (attempt < retryLimit - 1)
          await waitActivityThemeSyncRetry()
      }

      if (!coverImg || !colorResult) {
        return {
          status: 'pending-cover',
          activityId,
          reason: !coverImg ? 'cover-not-ready' : 'color-result-empty',
        } satisfies ActivityThemeSyncResult
      }

      const backgroundColor = String(colorResult.colors.pageBackground || '').trim()
      if (!backgroundColor) {
        return {
          status: 'failed',
          activityId,
          coverImg,
          reason: 'empty-background-color',
        } satisfies ActivityThemeSyncResult
      }

      const finalSyncKey = getActivityThemeSyncKey(activityId, coverImg)

      if (colorResult.source !== 'sampled') {
        if (!currentBackgroundColor) {
          await api.activity.updateActivity(
            activityId,
            { background_color: backgroundColor },
            { stage: AI_ACTIVITY_THEME_STAGE },
          )
          await waitForActivityBackgroundColor(activityId, backgroundColor)
        }
        return {
          status: 'fallback',
          activityId,
          coverImg,
          backgroundColor,
          reason: colorResult.reason || 'fallback',
        } satisfies ActivityThemeSyncResult
      }

      if (normalizeColorValue(currentBackgroundColor) === normalizeColorValue(backgroundColor)) {
        syncedGeneratedActivityThemeKeys.add(finalSyncKey)
        await reloadCurrentActivityPreviewFrame(activityId)
        return {
          status: 'synced',
          activityId,
          coverImg,
          backgroundColor,
        } satisfies ActivityThemeSyncResult
      }

      await api.activity.updateActivity(
        activityId,
        { background_color: backgroundColor },
        { stage: AI_ACTIVITY_THEME_STAGE },
      )
      const confirmedDetail = await waitForActivityBackgroundColor(activityId, backgroundColor)
      if (!isActivityBackgroundColorSynced(confirmedDetail, backgroundColor)) {
        console.warn('[ai-chat] activity theme color saved but not confirmed:', {
          activityId,
          coverImg,
          backgroundColor,
          currentBackgroundColor: getActivityDetailBackgroundColor(confirmedDetail),
        })
        return {
          status: 'failed',
          activityId,
          coverImg,
          backgroundColor,
          reason: 'save-not-confirmed',
        } satisfies ActivityThemeSyncResult
      }

      syncedGeneratedActivityThemeKeys.add(finalSyncKey)
      await reloadCurrentActivityPreviewFrame(activityId)
      return {
        status: 'synced',
        activityId,
        coverImg,
        backgroundColor,
      } satisfies ActivityThemeSyncResult
    } catch (error) {
      console.warn('[ai-chat] sync generated activity theme failed:', error)
      return {
        status: 'failed',
        activityId,
        reason: String((error as any)?.message || error || 'unknown'),
      } satisfies ActivityThemeSyncResult
    } finally {
      syncingGeneratedActivityThemeJobs.delete(initialSyncKey)
    }
  })()

  syncingGeneratedActivityThemeJobs.set(initialSyncKey, syncJob)
  return syncJob
}

function recordGeneratedActivity(activity: AiGeneratedActivity | null | undefined) {
  if (activeMode.value !== 'activity')
    return

  const activityId = Number(activity?.activity_id || 0)
  if (!activityId)
    return

  lastSuccessModalActivityId.value = activityId
  void syncGeneratedActivityThemeFromCover(activity, {
    coverImg: latestGeneratedActivityCoverImage.value,
    waitForCover: true,
  })
}

function openActivitySuccessModal() {
  if (activeMode.value !== 'activity' || !canPublishCurrentResult.value)
    return

  activitySuccessModalOpen.value = true
}

function shouldProcessStreamSeq(messageId: string, seq?: number) {
  if (!seq)
    return true

  const currentMessage = chatMessages.value.find(item => item.messageId === messageId)
  return seq > (currentMessage?.seq || 0)
}

function mergeActivityCards(
  currentCards: ChatMessage['cards'],
  nextCards: unknown,
) {
  const mergedMap = new Map<string, ActivityAssistantCard>()
  normalizeActivityCards(currentCards).forEach((card) => {
    const fallbackKey = isActivityImagePreviewCard(card) ? card.type : `${card.type}-${mergedMap.size}`
    mergedMap.set(card.card_id || fallbackKey, card)
  })
  normalizeActivityCards(nextCards).forEach((card) => {
    const fallbackKey = isActivityImagePreviewCard(card) ? card.type : `${card.type}-${mergedMap.size}`
    const key = card.card_id || fallbackKey
    if (isActivityImagePreviewCard(card)) {
      const existingKey = Array.from(mergedMap.entries())
        .find(([, existingCard]) => existingCard.type === card.type)?.[0]
      if (existingKey && existingKey !== key)
        mergedMap.delete(existingKey)
    }
    mergedMap.set(key, card)
  })
  const cardOrder = (card: ActivityAssistantCard) => {
    if (card.type === 'activity_cover_preview')
      return 900
    if (card.type === 'activity_detail_preview')
      return 901
    return 0
  }
  return Array.from(mergedMap.values()).sort((left, right) => cardOrder(left) - cardOrder(right))
}

function hasPendingAutoItemCoverCards(messages: ChatMessage[] = chatMessages.value) {
  return messages.some(message => normalizeActivityCards(message.cards).some((card) => {
    if (card.type !== 'activity_item_cover_preview')
      return false
    const status = String(card.status || card.progress?.step || '').trim().toLowerCase()
    return !['completed', 'failed', 'stopped'].includes(status)
  }))
}

function stopAutoItemCoverProgressPolling() {
  if (!autoItemCoverProgressTimer)
    return
  clearInterval(autoItemCoverProgressTimer)
  autoItemCoverProgressTimer = null
}

async function refreshAutoItemCoverProgress() {
  const conversationId = String(currentConversation.value?.conversation_id || '').trim()
  if (!conversationId || isAutoItemCoverProgressRefreshing) {
    if (!hasPendingAutoItemCoverCards())
      stopAutoItemCoverProgressPolling()
    return
  }

  isAutoItemCoverProgressRefreshing = true
  try {
    const result = await api.ai.getAiConversationMessages(conversationId, { page: 1, per_page: 100 })
    ;(result.items as AiMessage[])
      .filter((message: AiMessage) => message.role === 'assistant' && normalizeActivityCards(message.components).some(card => card.type === 'activity_item_cover_preview'))
      .forEach((message: AiMessage) => {
        const nextMessage = mapApiMessageToChatMessage(message)
        patchAssistantMessage(nextMessage.messageId || nextMessage.id, {
          cards: nextMessage.cards,
          meta: nextMessage.meta,
        })
      })
    syncActivityPreviewStatus()
  }
  catch {
    // 队列生成不受一次轮询失败影响，下一个周期会继续对账。
  }
  finally {
    isAutoItemCoverProgressRefreshing = false
    if (!hasPendingAutoItemCoverCards())
      stopAutoItemCoverProgressPolling()
  }
}

function startAutoItemCoverProgressPolling() {
  if (!hasPendingAutoItemCoverCards()) {
    stopAutoItemCoverProgressPolling()
    return
  }
  if (autoItemCoverProgressTimer)
    return

  void refreshAutoItemCoverProgress()
  autoItemCoverProgressTimer = setInterval(() => {
    void refreshAutoItemCoverProgress()
  }, 3000)
}

function syncPosterProgressFromCard(card: unknown) {
  if (!isPosterImagePreviewCard(card))
    return

  const timing = getPosterGenerationTimingFromSource(card as Record<string, any>)

  if (card.status === 'completed') {
    posterProgress.value = {
      step: 'completed',
      message: card.title || '海报已生成完成',
      progress: 100,
      elapsed: timing?.actual,
      estimated: timing?.estimated,
    }
    return
  }

  if (card.status === 'stopped') {
    posterProgress.value = {
      step: 'stopped',
      message: card.title || '海报生成已停止',
      progress: posterProgress.value.progress || 0,
      elapsed: timing?.actual || posterProgress.value.elapsed,
      estimated: timing?.estimated || posterProgress.value.estimated,
    }
    return
  }

  if (card.status === 'failed') {
    posterProgress.value = {
      step: 'error',
      message: card.title || '海报生成失败，请稍后重试',
      progress: posterProgress.value.progress || 0,
      elapsed: timing?.actual || posterProgress.value.elapsed,
      estimated: timing?.estimated || posterProgress.value.estimated,
    }
    return
  }

  if (!card.progress && !timing)
    return

  posterProgress.value = {
    step: String(card.progress?.step || ''),
    message: String(card.progress?.message || card.title || ''),
    progress: Math.max(0, Math.min(100, Number(card.progress?.progress) || posterProgress.value.progress || 0)),
    elapsed: timing?.actual || posterProgress.value.elapsed,
    estimated: timing?.estimated || posterProgress.value.estimated,
  }
}

function getLatestPendingAssistantMessageId() {
  const target = [...chatMessages.value].reverse().find(item =>
    item.role === 'assistant' && !item.isSystem && ['pending', 'streaming'].includes(item.status || ''),
  )

  return target?.messageId || ''
}

function syncActivityPreviewStatus() {
  if (activeMode.value !== 'activity') {
    activityPreviewStatus.value = 'result'
    return
  }

  const latestAssistantMessage = [...chatMessages.value].reverse().find(item => item.role === 'assistant' && !item.isSystem)
  if (!latestAssistantMessage) {
    activityPreviewStatus.value = 'generating'
    return
  }

  activityPreviewStatus.value = ['pending', 'streaming'].includes(latestAssistantMessage.status || '')
    ? 'generating'
    : 'result'
}

function resetActivityPreviewFrame() {
  displayedActivityPreviewUrl.value = ''
  pendingActivityPreviewUrl.value = ''
  displayedActivityPreviewKey.value += 1
  pendingActivityPreviewKey.value += 1
}

function queueActivityPreview(nextUrl: string) {
  const normalizedUrl = String(nextUrl || '').trim()
  if (!normalizedUrl) {
    resetActivityPreviewFrame()
    return
  }

  if (normalizedUrl === displayedActivityPreviewUrl.value || normalizedUrl === pendingActivityPreviewUrl.value)
    return

  pendingActivityPreviewUrl.value = normalizedUrl
  pendingActivityPreviewKey.value += 1
}

function handlePendingActivityPreviewLoad() {
  if (!pendingActivityPreviewUrl.value)
    return

  displayedActivityPreviewUrl.value = pendingActivityPreviewUrl.value
  displayedActivityPreviewKey.value += 1
  pendingActivityPreviewUrl.value = ''
}

function resetChatLayoutMode() {
  isChatFullscreenMode.value = true
  lastAutoCollapsedResultKey.value = ''
}

function toggleChatLayoutMode() {
  isChatFullscreenMode.value = !isChatFullscreenMode.value
}

function expandChatLayout() {
  if (isChatFullscreenMode.value)
    return false

  isChatFullscreenMode.value = true
  return true
}

function collapseChatLayout() {
  if (!isChatFullscreenMode.value)
    return false

  isChatFullscreenMode.value = false
  return true
}

function hasConversationContent() {
  return chatMessages.value.some(item => !item.isSystem)
}

function shouldExpandChatLayoutForConversationState() {
  if (!hasConversationContent())
    return true

  if (activeMode.value === 'poster') {
    if (latestGeneratedPoster.value?.url)
      return false

    if (hasSubmittedFinalPosterGenerationConfirm.value || isPosterGenerating.value)
      return false

    return true
  }

  if (activeMode.value !== 'activity')
    return true

  if (latestGeneratedActivity.value)
    return false

  if (hasSubmittedFinalActivityGenerationConfirm.value)
    return false

  return true
}

function syncChatLayoutModeForConversationState() {
  if (shouldExpandChatLayoutForConversationState()) {
    expandChatLayout()
    return
  }

  collapseChatLayout()
}

function applyConversationSelection(conversation: AiConversation | null | undefined) {
  const selection = conversation?.current_selection
  if (!selection)
    return

  applyComposerSelection({
    mode: activeMode.value,
    image_model: selection.image_model || undefined,
    thinking_mode: selection.thinking_mode || undefined,
    style: selection.style || undefined,
    aspect_ratio: selection.aspect_ratio || undefined,
    activity_model: selection.activity_model || undefined,
  }, false)
}

function getComposerSelectionSnapshot() {
  return {
    mode: activeMode.value,
    image_model: selectedModel.value || null,
    thinking_mode: selectedThinkingMode.value,
    style: getSelectedSettingValue('tone') || null,
    aspect_ratio: activeMode.value === 'poster' ? getSelectedSettingValue('posterSize') || null : null,
    activity_model: activeMode.value === 'activity' ? getSelectedSettingValue('activityModel') || null : null,
  }
}

function applyComposerSelection(selection: Record<string, any>, shouldPersist = true) {
  const mode: ModeKey = selection.mode === 'poster' || selection.mode === 'activity' ? selection.mode : activeMode.value

  if (selection.image_model && imageModelOptions.value.some(item => item.value === selection.image_model))
    selectedModel.value = selection.image_model

  if (selection.thinking_mode === 'quick' || selection.thinking_mode === 'deep')
    selectedThinkingMode.value = selection.thinking_mode

  if (selection.style && getPromptOptionItems('tone').some(item => item.value === selection.style))
    selectedSettingsByMode.value[mode].tone = selection.style

  if (mode === 'poster' && selection.aspect_ratio && aiPageConfig.value.sizes.some(item => item.value === selection.aspect_ratio))
    selectedSettingsByMode.value.poster.posterSize = selection.aspect_ratio

  if (mode === 'activity' && selection.activity_model && aiPageConfig.value.activityModels.some(item => item.value === selection.activity_model))
    selectedSettingsByMode.value.activity.activityModel = selection.activity_model

  if (shouldPersist)
    persistComposerSelection()
}

function persistComposerSelection() {
  try {
    localStorage.setItem(CHAT_COMPOSER_SELECTION_STORAGE_KEY, JSON.stringify(getComposerSelectionSnapshot()))
  }
  catch {}
}

function applySavedComposerSelection() {
  try {
    const raw = localStorage.getItem(CHAT_COMPOSER_SELECTION_STORAGE_KEY)
    if (!raw)
      return

    const selection = JSON.parse(raw)
    if (!selection || typeof selection !== 'object')
      return

    applyComposerSelection(selection, false)
  }
  catch {}
}

function inferConversationMode(conversation: AiConversation | null | undefined): ModeKey {
  const scene = String(conversation?.scene || '').toLowerCase()
  const configuredPosterScene = String(aiPageConfig.value.posterScene || '').toLowerCase()
  const metaMode = String(conversation?.meta?.mode || '').toLowerCase()

  if (metaMode === 'poster' || scene === configuredPosterScene || /poster|海报|kv/.test(scene))
    return 'poster'

  return 'activity'
}

async function loadConversation(conversationId: string) {
  const result = await api.ai.getAiConversationMessages(conversationId, {
    page: 1,
    per_page: 100,
  })

  currentConversation.value = result.conversation
  activeMode.value = inferConversationMode(result.conversation)
  applyConversationSelection(result.conversation)
  chatMessages.value = result.items.length
    ? result.items.map(mapApiMessageToChatMessage)
    : buildInitialMessages(activeMode.value)
  syncActivityPreviewStatus()

  const pendingAssistantMessage = [...result.items].reverse().find(item =>
    item.role === 'assistant' && ['pending', 'streaming'].includes(item.status),
  )

  if (pendingAssistantMessage) {
    startMessageWorking(pendingAssistantMessage.message_id)
    connectAiStream(api.ai.buildAiMessageStreamUrl(pendingAssistantMessage.message_id), pendingAssistantMessage.message_id)
  }
  else {
    removeAiGenerationTask({ conversationId })
  }

  startAutoItemCoverProgressPolling()

  syncChatLayoutModeForConversationState()
}

function syncRouteConversationId(conversationId: string) {
  if (!conversationId || (routeConversationId.value === conversationId && !initialPrompt.value))
    return

  isRouteSyncing.value = true
  void router.replace({
    query: {
      conversationId,
      from: routeEntrySource.value || undefined,
      prompt: undefined,
      history: undefined,
      mode: undefined,
      thinkingMode: undefined,
      source: undefined,
    },
  })
}

function startMessageWorking(messageId?: string) {
  isComposerManuallyStopped.value = false
  isMessageWorking.value = true
  startGenerationTimer()
  activeAssistantMessageId.value = messageId || activeAssistantMessageId.value

  const conversationId = currentConversation.value?.conversation_id
  if (messageId && conversationId) {
    upsertAiGenerationTask({
      conversationId,
      assistantMessageId: messageId,
      mode: activeMode.value,
      title: currentConversation.value?.title || undefined,
    })
  }
}

function stopMessageWorking() {
  isMessageWorking.value = false
  activeAssistantMessageId.value = ''
  if (!isPosterGenerating.value)
    stopGenerationTimer()
}

function clearGenerationTimer() {
  if (!generationTimer)
    return

  clearInterval(generationTimer)
  generationTimer = null
}

function startGenerationTimer() {
  if (generationStartedAt.value)
    return

  clearGenerationTimer()
  generationEstimatedSeconds.value = estimateGenerationSeconds()
  generationStartedAt.value = Date.now()
  generationNow.value = generationStartedAt.value
  generationElapsedSeconds.value = 0
  generationTimer = setInterval(() => {
    generationNow.value = Date.now()
  }, 1000)
}

function stopGenerationTimer() {
  if (generationStartedAt.value) {
    generationStartedAt.value = null
    generationNow.value = Date.now()
  }

  generationElapsedSeconds.value = 0
  clearGenerationTimer()
}

function resetGenerationTimer() {
  generationStartedAt.value = null
  generationNow.value = Date.now()
  generationElapsedSeconds.value = 0
  generationEstimatedSeconds.value = 120
  clearGenerationTimer()
}

function closeAiStream() {
  aiStreamVersion += 1
  if (aiStream) {
    aiStream.close()
    aiStream = null
  }
}

function parseStreamPayload<T>(event: Event) {
  if (!('data' in event))
    return null

  const rawData = String((event as MessageEvent).data || '').trim()
  if (!rawData)
    return null

  try {
    return JSON.parse(rawData) as T
  }
  catch {
    return null
  }
}

function handleStreamErrorMessage(messageId: string, errorMessage: string) {
  flushAssistantTypewriter(messageId)
  removeAiGenerationTask({ assistantMessageId: messageId })
  const currentMessage = chatMessages.value.find(item => item.messageId === messageId)
  patchAssistantMessage(messageId, {
    status: 'error',
    errorMessage,
    content: currentMessage?.content || '',
  })
  if (activeMode.value === 'poster') {
    posterProgress.value = {
      step: 'error',
      message: errorMessage || '海报生成失败，请稍后重试',
      progress: posterProgress.value.progress || 0,
    }
  }
  closeAiStream()
  stopMessageWorking()
  syncActivityPreviewStatus()
}

function connectAiStream(streamUrl: string, assistantMessageId: string) {
  closeAiStream()
  startMessageWorking(assistantMessageId)
  const currentVersion = aiStreamVersion
  const eventSource = new EventSource(streamUrl)
  aiStream = eventSource

  const isExpired = () => aiStreamVersion !== currentVersion

  eventSource.addEventListener('message_start', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamEventBase>(event)
    if (!shouldProcessStreamSeq(assistantMessageId, payload?.seq))
      return

    patchAssistantMessage(assistantMessageId, {
      status: 'streaming',
      seq: payload?.seq,
    })
    syncActivityPreviewStatus()
  })

  eventSource.addEventListener('message_delta', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamEventBase & { delta?: string }>(event)
    if (!shouldProcessStreamSeq(assistantMessageId, payload?.seq))
      return
    if (!payload?.delta)
      return

    appendAssistantDelta(assistantMessageId, payload.delta, payload.seq)
    syncActivityPreviewStatus()
  })

  eventSource.addEventListener('thinking_delta', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamEventBase & { delta?: string }>(event)
    if (!shouldProcessStreamSeq(assistantMessageId, payload?.seq))
      return
    if (!payload?.delta)
      return

    appendAssistantThinkingDelta(assistantMessageId, payload.delta, payload.seq)
    syncActivityPreviewStatus()
  })

  eventSource.addEventListener('message_card', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamMessageCardEvent>(event)
    if (!payload?.card || !shouldProcessStreamSeq(assistantMessageId, payload.seq))
      return

    if (payload.card.type === 'activity_item_cover_preview')
      startAutoItemCoverProgressPolling()
    enqueueAssistantPresentation(assistantMessageId, () => {
      const currentMessage = chatMessages.value.find(item => item.messageId === assistantMessageId)
      syncPosterProgressFromCard(payload.card)
      const poster = extractGeneratedPosterFromCards([payload.card]) || getChatMessageGeneratedPoster(currentMessage)
      patchAssistantMessage(assistantMessageId, {
        seq: payload.seq,
        cards: mergeActivityCards(currentMessage?.cards, [payload.card]),
        poster,
        meta: mergeMessageMetaWithPoster(currentMessage?.meta, poster),
      })
      syncActivityPreviewStatus()
    })
  })

  eventSource.addEventListener('activity_cover_progress', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamActivityImageProgressEvent>(event)
    if (!payload || !shouldProcessStreamSeq(assistantMessageId, payload.seq))
      return

    const target = payload.target === 'detail' ? 'detail' : 'cover'
    const completed = payload.step === 'completed'
    const failed = payload.step === 'failed'
    const stopped = payload.step === 'stopped'
    const type = target === 'detail' ? 'activity_detail_preview' : 'activity_cover_preview'
    const fallbackTitle = target === 'detail'
      ? (completed ? '活动详情图已生成' : '快灵正在制作活动详情图...')
      : (completed ? '活动主图已生成' : '快灵正在制作活动主图...')
    const card: ActivityAssistantCard = {
      card_id: `stream_${assistantMessageId}_${target}`,
      type,
      // 完成事件与带图片的 message_card 会先后到达。先准确呈现当前任务阶段，
      // 再由带 image_url 的卡片原位替换预览画布。
      status: completed ? 'completed' : (failed ? 'failed' : (stopped ? 'stopped' : 'generating')),
      title: String(payload.message || fallbackTitle),
      aspect_ratio: target === 'detail' ? '1:3' : '3:4',
      progress: {
        step: payload.step,
        message: String(payload.message || fallbackTitle),
        progress: Math.max(0, Math.min(100, Number(payload.progress) || 0)),
        elapsed_seconds: payload.elapsed_seconds,
        actual_seconds: payload.actual_seconds,
        estimated_seconds: payload.estimated_seconds,
      },
    }

    enqueueAssistantPresentation(assistantMessageId, () => {
      const currentMessage = chatMessages.value.find(item => item.messageId === assistantMessageId)
      patchAssistantMessage(assistantMessageId, {
        status: 'streaming',
        seq: payload.seq,
        cards: mergeActivityCards(currentMessage?.cards, [card]),
      })
      syncActivityPreviewStatus()
    })
  })

  eventSource.addEventListener('activity_generated', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamActivityGeneratedEvent>(event)
    if (!payload?.activity || !shouldProcessStreamSeq(assistantMessageId, payload.seq))
      return

    accelerateAssistantTypewriter(assistantMessageId)
    const currentMessage = chatMessages.value.find(item => item.messageId === assistantMessageId)
    const activity = normalizeGeneratedActivity(payload.activity)
    if (!activity)
      return

    patchAssistantMessage(assistantMessageId, {
      activity,
      meta: mergeMessageMetaWithActivity(currentMessage?.meta, activity),
      seq: payload.seq,
    })
    syncActivityPreviewStatus()
    recordGeneratedActivity(activity)
    void refreshAiPointsBalance()
  })

  eventSource.addEventListener('poster_progress', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamPosterProgressEvent>(event)
    if (!payload || !shouldProcessStreamSeq(assistantMessageId, payload.seq))
      return

    const timing = getPosterGenerationTimingFromSource(payload as Record<string, any>)
    posterProgress.value = {
      step: String(payload.step || ''),
      message: String(payload.message || ''),
      progress: Math.max(0, Math.min(100, Number(payload.progress) || 0)),
      elapsed: timing?.actual || posterProgress.value.elapsed,
      estimated: timing?.estimated || posterProgress.value.estimated,
    }
    patchAssistantMessage(assistantMessageId, {
      status: 'streaming',
      seq: payload.seq,
    })
  })

  eventSource.addEventListener('poster_generated', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamPosterGeneratedEvent>(event)
    if (!payload?.poster || !shouldProcessStreamSeq(assistantMessageId, payload.seq))
      return

    accelerateAssistantTypewriter(assistantMessageId)
    const currentMessage = chatMessages.value.find(item => item.messageId === assistantMessageId)
    const poster = normalizeGeneratedPoster(payload.poster)
    if (!poster)
      return

    posterProgress.value = {
      step: 'completed',
      message: '海报已生成完成',
      progress: 100,
    }
    patchAssistantMessage(assistantMessageId, {
      poster,
      meta: mergeMessageMetaWithPoster(currentMessage?.meta, poster),
      seq: payload.seq,
    })
    void refreshAiPointsBalance()
  })

  eventSource.addEventListener('message_completed', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamCompletedEvent>(event)
    if (!payload || !shouldProcessStreamSeq(assistantMessageId, payload.seq))
      return

    accelerateAssistantTypewriter(assistantMessageId)
    const currentContent = chatMessages.value.find(item => item.messageId === assistantMessageId)?.content || ''
    const queuedContent = assistantTypewriterQueues.get(assistantMessageId)?.text || ''
    const expectedContent = `${currentContent}${queuedContent}`
    if (payload.content && payload.content.startsWith(expectedContent)) {
      const remainingContent = payload.content.slice(expectedContent.length)
      if (remainingContent)
        appendAssistantDelta(assistantMessageId, remainingContent, payload.seq)
    }

    enqueueAssistantPresentation(assistantMessageId, () => {
      const currentMessage = chatMessages.value.find(item => item.messageId === assistantMessageId)
      const activity = extractGeneratedActivity(payload) || getChatMessageGeneratedActivity(currentMessage)
      const poster = extractGeneratedPoster(payload) || getChatMessageGeneratedPoster(currentMessage)
      patchAssistantMessage(assistantMessageId, {
        status: payload.status,
        content: payload.content || currentMessage?.content || '',
        errorMessage: null,
        cards: normalizeActivityCards(payload.components).length
          ? mergeActivityCards(currentMessage?.cards, payload.components)
          : currentMessage?.cards,
        activity,
        poster,
        meta: mergeMessageMetaWithPoster(mergeMessageMetaWithActivity(currentMessage?.meta, activity), poster),
        seq: payload.seq,
      })
      syncActivityPreviewStatus()
      recordGeneratedActivity(activity)
      if (activity || poster)
        void refreshAiPointsBalance()
      startAutoItemCoverProgressPolling()
    })
  })

  eventSource.addEventListener('done', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamDoneEvent>(event)
    if (!shouldProcessStreamSeq(assistantMessageId, payload?.seq))
      return
    accelerateAssistantTypewriter(assistantMessageId)
    if (payload?.finish_reason === 'stopped') {
      patchAssistantMessage(assistantMessageId, {
        status: 'stopped',
        seq: payload.seq,
      })
      if (activeMode.value === 'poster') {
        posterProgress.value = {
          step: 'stopped',
          message: '海报生成已停止',
          progress: posterProgress.value.progress || 0,
        }
      }
    }
    else if (payload?.finish_reason === 'error') {
      patchAssistantMessage(assistantMessageId, {
        status: 'error',
        errorMessage: '生成失败，请稍后重试',
        seq: payload.seq,
      })
    }

    removeAiGenerationTask({ assistantMessageId })
    closeAiStream()
    stopMessageWorking()
    syncActivityPreviewStatus()
    startAutoItemCoverProgressPolling()
    void refreshHistoryConversationTotal()
  })

  // 这里统一兼容服务端 `event: error` 和浏览器原生连接错误事件。
  eventSource.addEventListener('error', (event) => {
    if (isExpired())
      return

    const payload = parseStreamPayload<AiStreamErrorEvent>(event)
    if (payload?.message && shouldProcessStreamSeq(assistantMessageId, payload.seq)) {
      if (payload.code === 'ai_points_insufficient')
        applyAiPointsBalance(payload.balance)
      handleStreamErrorMessage(assistantMessageId, payload.message)
      return
    }

    const currentMessage = chatMessages.value.find(item => item.messageId === assistantMessageId)
    if (currentMessage && !['completed', 'stopped', 'error'].includes(currentMessage.status || ''))
      handleStreamErrorMessage(assistantMessageId, '连接已中断，请稍后重试')
  })
}

function getAttachmentUrl(attachment: any) {
  if (!attachment)
    return ''

  if (typeof attachment === 'string')
    return attachment.trim()

  if (typeof attachment !== 'object')
    return ''

  return String(
    attachment.url
    || attachment.image_url
    || attachment.file_url
    || attachment.src
    || attachment.href
    || '',
  ).trim()
}

function normalizeMessageAttachments(attachments: unknown): ChatAttachment[] {
  if (!Array.isArray(attachments))
    return []

  return attachments
    .map((attachment) => {
      const url = getAttachmentUrl(attachment)
      if (!url)
        return null

      const normalizedAttachment: ChatAttachment = {
        url,
      }
      if (typeof attachment?.name === 'string')
        normalizedAttachment.name = attachment.name
      if (typeof attachment?.type === 'string')
        normalizedAttachment.type = attachment.type
      else if (typeof attachment?.mime_type === 'string')
        normalizedAttachment.type = attachment.mime_type
      if (Number.isFinite(Number(attachment?.size)))
        normalizedAttachment.size = Number(attachment.size)
      return normalizedAttachment
    })
    .filter((attachment): attachment is ChatAttachment => attachment !== null)
}

function buildLocalDraftAttachments(images: PastedImage[]) {
  if (!images.length)
    return []

  return images.map(image => ({
    url: image.url,
    name: image.name,
    type: image.file.type,
    size: image.file.size,
  }))
}

async function uploadDraftAttachments(images: PastedImage[]) {
  if (!images.length)
    return []

  const uploadedUrls = await Promise.all(images.map(async (image) => {
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

  return uploadedUrls
}

function getThinkingModeComponentResult() {
  return {
    think_mode: selectedThinkingMode.value,
    think_mode_label: currentThinkingModeOption.value.label,
  }
}

function buildAiMessageOptions() {
  persistComposerSelection()

  return {
    style: getSelectedSettingValue('tone') || null,
    aspect_ratio: activeMode.value === 'poster' ? getSelectedSettingValue('posterSize') || null : null,
    activity_model: activeMode.value === 'activity' ? getSelectedSettingValue('activityModel') || null : null,
    image_model: selectedModel.value || null,
    thinking_mode: selectedThinkingMode.value,
  }
}

async function submitAiConversationMessage(options: {
  content: string
  attachments?: ChatAttachment[]
  componentResult?: Record<string, any> | any[] | null
  optimisticMessage?: ChatMessage
  onSuccess?: () => void
  onError?: () => void
  useExistingSubmitLock?: boolean
}) {
  const {
    content,
    attachments = [],
    componentResult = null,
    optimisticMessage,
    onSuccess,
    onError,
    useExistingSubmitLock = false,
  } = options

  if (!hasAiAccessToken()) {
    loginGuideOpen.value = true
    return false
  }

  const shouldAcquireSubmitLock = !useExistingSubmitLock
  if (shouldAcquireSubmitLock) {
    if (isMessageSubmitting.value || isAiResponseBusy.value || isConversationLoading.value)
      return false
    isMessageSubmitting.value = true
  }

  if (isMockPreviewMode.value) {
    klbMessage.info('当前为 mock 预览模式，不会发送真实接口')
    onSuccess?.()
    if (shouldAcquireSubmitLock)
      isMessageSubmitting.value = false
    return true
  }

  if (!currentConversation.value?.conversation_id) {
    klbMessage.info('请先在 AI 首页输入诉求创建会话')
    if (shouldAcquireSubmitLock)
      isMessageSubmitting.value = false
    return false
  }

  const userMessageId = optimisticMessage?.messageId || buildUserMessageId()
  const shouldHideUserMessage = Boolean(optimisticMessage?.isSystem)
  const nextOptimisticMessage = optimisticMessage || {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    attachments,
    createdAt: new Date().toISOString(),
    componentResult,
  } satisfies ChatMessage

  upsertChatMessage(nextOptimisticMessage)
  startMessageWorking()

  try {
    const result = await api.ai.sendAiMessage({
      conversation_id: currentConversation.value.conversation_id,
      user_message_id: userMessageId,
      content,
      scene: activeMode.value === 'poster' ? aiPageConfig.value.posterScene : AI_SCENE,
      shop_id: getCurrentShopId(),
      attachments,
      component_result: componentResult,
      options: buildAiMessageOptions(),
    })

    currentConversation.value = result.conversation
    const nextUserMessage = mapApiMessageToChatMessage(result.user_message)
    upsertChatMessage({
      ...nextUserMessage,
      isSystem: shouldHideUserMessage || nextUserMessage.isSystem,
      attachments: nextUserMessage.attachments?.length ? nextUserMessage.attachments : attachments,
    })
    upsertChatMessage(mapApiMessageToChatMessage(result.assistant_message))
    startMessageWorking(result.assistant_message.message_id)
    syncActivityPreviewStatus()
    syncChatLayoutModeForConversationState()
    syncRouteConversationId(result.conversation.conversation_id)
    await refreshHistoryConversationTotal()
    connectAiStream(api.ai.buildAiStreamUrl(result.stream_url), result.assistant_message.message_id)
    onSuccess?.()
    return true
  }
  catch {
    chatMessages.value = chatMessages.value.filter(item => item.messageId !== userMessageId)
    stopMessageWorking()
    syncActivityPreviewStatus()
    onError?.()
    return false
  }
  finally {
    if (shouldAcquireSubmitLock)
      isMessageSubmitting.value = false
  }
}

function getFallbackRegeneratePrompt() {
  return activeMode.value === 'poster'
    ? '请基于当前要求重新生成一版海报预览。'
    : '请基于当前要求重新生成一版活动方案。'
}

function getPreviousUserMessageContent(messageId: string) {
  const messageIndex = chatMessages.value.findIndex(item => (item.messageId || item.id) === messageId)
  if (messageIndex === -1)
    return ''

  for (let index = messageIndex - 1; index >= 0; index -= 1) {
    const message = chatMessages.value[index]
    if (message.role !== 'user' || message.isSystem)
      continue
    const content = getMessageDisplayContent(message).trim()
    if (content)
      return content
  }

  return ''
}

async function submitRegenerateMessage(content: string, componentResult: ChatMessage['componentResult'] = getThinkingModeComponentResult()) {
  const nextContent = content.trim()
  if (!nextContent || isMessageSubmitting.value || isAiResponseBusy.value || isPosterGenerating.value || isConversationLoading.value)
    return

  if (activeMode.value === 'poster') {
    posterProgress.value = {
      step: '',
      message: '',
      progress: 0,
    }
  }

  isMessageSubmitting.value = true
  const userMessageId = buildUserMessageId()
  const optimisticUserMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content: nextContent,
    attachments: [],
    createdAt: new Date().toISOString(),
    componentResult,
  } satisfies ChatMessage

  try {
    await submitAiConversationMessage({
      content: nextContent,
      attachments: [],
      componentResult,
      optimisticMessage: optimisticUserMessage,
      useExistingSubmitLock: true,
    })
  }
  catch {
    klbMessage.error('发送失败，请稍后重试')
  }
  finally {
    isMessageSubmitting.value = false
  }
}

function handleChatMessageRegenerate(payload: ChatMessageRegeneratePayload) {
  const content = payload.role === 'user'
    ? payload.content
    : getPreviousUserMessageContent(payload.id) || getFallbackRegeneratePrompt()

  void submitRegenerateMessage(content)
}

function handleReselectActivityItems() {
  void submitRegenerateMessage('我想重新选择主推项目')
}

function handleImageRegenerate(payload: { target: 'activity_cover' | 'activity_detail' | 'activity_item_cover' | 'poster', itemId?: number }) {
  if (payload.target === 'activity_item_cover' && (!payload.itemId || payload.itemId <= 0)) {
    klbMessage.error('未识别到商品，无法重新生成商品图')
    return
  }

  const content = payload.target === 'activity_cover'
    ? '请重新生成活动主图，其他活动配置保持不变。'
    : payload.target === 'activity_detail'
      ? '请重新生成活动详情图，其他活动配置保持不变。'
      : payload.target === 'activity_item_cover'
        ? '请重新生成该商品图，其他商品和活动配置保持不变。'
      : '请基于当前要求重新生成一版海报预览。'

  const componentResult = payload.target === 'poster'
    ? getThinkingModeComponentResult()
    : payload.target === 'activity_item_cover'
      ? {
          component_type: 'activity_item_cover_regenerate',
          step_key: 'activity_item_cover_regenerate',
          status: 'submitted',
          item_id: payload.itemId,
        }
    : {
        component_type: 'activity_image_regenerate',
        step_key: 'activity_image_regenerate',
        status: 'submitted',
        target: payload.target === 'activity_detail' ? 'detail' : 'cover',
      }

  void submitRegenerateMessage(content, componentResult)
}

async function sendMessage(contentOverride?: string) {
  const content = (typeof contentOverride === 'string' ? contentOverride : draftMessage.value).trim()
  const draftImages = [...pastedImages.value]
  if (isMessageSubmitting.value || isAiResponseBusy.value || isConversationLoading.value || shouldLockActivityComposer.value)
    return

  if (!content) {
    klbMessage.info(draftImages.length ? '请先输入内容后再发送图片' : '请先输入内容再发送')
    return
  }

  if (isMockPreviewMode.value) {
    klbMessage.info('当前为 mock 预览模式，消息不会发送到服务端')
    return
  }

  isMessageSubmitting.value = true

  if (activeMode.value === 'poster') {
    posterProgress.value = {
      step: '',
      message: '',
      progress: 0,
    }
  }

  const localAttachments = buildLocalDraftAttachments(draftImages)
  const userMessageId = buildUserMessageId()
  const optimisticUserMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    attachments: localAttachments,
    createdAt: new Date().toISOString(),
  } satisfies ChatMessage

  try {
    const attachments = await uploadDraftAttachments(draftImages)
    const success = await submitAiConversationMessage({
      content,
      attachments,
      componentResult: getThinkingModeComponentResult(),
      optimisticMessage: optimisticUserMessage,
      useExistingSubmitLock: true,
      onSuccess: () => {
        draftMessage.value = ''
        clearPastedImages()
      },
    })
    if (!success)
      return
  }
  catch {
    klbMessage.error('发送失败，请稍后重试')
  }
  finally {
    isMessageSubmitting.value = false
  }
}

async function handleStopMessage() {
  if (isMockPreviewMode.value) {
    klbMessage.info('当前为 mock 预览模式，无需停止生成')
    return
  }

  const assistantMessageId = activeAssistantMessageId.value || getLatestPendingAssistantMessageId()
  isComposerManuallyStopped.value = true
  closeAiStream()
  stopMessageWorking()
  syncActivityPreviewStatus()

  if (!assistantMessageId)
    return

  markAssistantImageCardsStopped(assistantMessageId)
  removeAiGenerationTask({ assistantMessageId })
  try {
    const result = await api.ai.stopAiMessage(assistantMessageId)
    upsertChatMessage(mapApiMessageToChatMessage(result.message))
  }
  catch {
    klbMessage.error('停止请求未成功，请稍后刷新确认任务状态')
  }
  finally {
    syncActivityPreviewStatus()
    void refreshHistoryConversationTotal()
  }
}

function markAssistantImageCardsStopped(assistantMessageId: string) {
  const message = chatMessages.value.find(item => item.messageId === assistantMessageId)
  if (!message)
    return

  const cards = normalizeActivityCards(message.cards).map((card) => {
    if (!isActivityImagePreviewCard(card) || ['completed', 'failed', 'stopped'].includes(card.status || ''))
      return card

    const label = card.type === 'activity_detail_preview' ? '活动详情图' : '活动主图'
    return {
      ...card,
      status: 'stopped',
      title: `${label}生成已停止`,
      progress: {
        ...(card.progress && typeof card.progress === 'object' ? card.progress : {}),
        step: 'stopped',
        message: `${label}生成已停止`,
      },
    }
  })

  patchAssistantMessage(assistantMessageId, {
    status: 'stopped',
    cards,
  })
}

function getMessageDisplayContent(message?: ChatMessage) {
  if (!message)
    return ''

  if (message.role === 'assistant' && !message.content.trim()) {
    const messageId = message.messageId || message.id
    const latestAssistantId = latestAssistantMessage.value?.messageId || latestAssistantMessage.value?.id
    if (shouldShowThinkingProcessCard.value && messageId === latestAssistantId)
      return ''

    if (['pending', 'streaming'].includes(message.status || ''))
      return ''
    if (message.status === 'stopped')
      return '本轮生成已停止'
    if (message.status === 'error')
      return message.errorMessage || '生成失败，请稍后重试'
  }

  return normalizeAiDisplayText(message.content)
}

function normalizeAiDisplayText(value: string) {
  return String(value || '')
    .replace(/\\r\\n/g, '\n')
    .replace(/\\n/g, '\n')
    .replace(/\r\n/g, '\n')
}

function isImageAttachment(attachment: ChatAttachment) {
  if (!attachment.url)
    return false

  if (attachment.type)
    return attachment.type.startsWith('image/')

  return attachment.url.startsWith('blob:')
    || attachment.url.startsWith('data:image/')
    || /\.(png|jpe?g|gif|webp|bmp|svg)(\?.*)?$/i.test(attachment.url)
}

function getMessageImageAttachments(message: ChatMessage) {
  return (message.attachments || []).filter(isImageAttachment)
}

function goBack() {
  router.push(backRoutePath.value)
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

function clearActivitySuggestionTimers() {
  if (activitySuggestionAnimationTimer)
    clearTimeout(activitySuggestionAnimationTimer)
  if (activitySuggestionAutoTimer)
    clearTimeout(activitySuggestionAutoTimer)

  activitySuggestionAnimationTimer = null
  activitySuggestionAutoTimer = null
}

function scheduleActivitySuggestionAutoSwitch(delay = ACTIVITY_SUGGESTION_AUTO_DELAY_MS) {
  if (activitySuggestionAutoTimer)
    clearTimeout(activitySuggestionAutoTimer)

  if (!shouldShowGuideSuggestionPreview.value)
    return

  activitySuggestionAutoTimer = setTimeout(() => {
    activitySuggestionAutoTimer = null
    advanceActivitySuggestion()
  }, delay)
}

function advanceActivitySuggestion() {
  const suggestions = currentActivitySuggestionList.value
  if (suggestions.length <= 1)
    return

  switchActivitySuggestion(activitySuggestionIndex.value + 1)
}

function switchActivitySuggestion(index: number) {
  if (isActivitySuggestionAnimating.value || !shouldShowGuideSuggestionPreview.value)
    return

  const suggestions = currentActivitySuggestionList.value
  if (suggestions.length <= 1) {
    scheduleActivitySuggestionAutoSwitch()
    return
  }

  const nextIndex = Math.max(0, Math.floor(index))
  if (nextIndex === activitySuggestionIndex.value) {
    scheduleActivitySuggestionAutoSwitch()
    return
  }

  if (activitySuggestionAnimationTimer)
    clearTimeout(activitySuggestionAnimationTimer)
  if (activitySuggestionAutoTimer)
    clearTimeout(activitySuggestionAutoTimer)

  isActivitySuggestionAnimating.value = true
  activitySuggestionIndex.value = nextIndex

  activitySuggestionAnimationTimer = setTimeout(() => {
    isActivitySuggestionAnimating.value = false
    activitySuggestionAnimationTimer = null
    scheduleActivitySuggestionAutoSwitch()
  }, ACTIVITY_SUGGESTION_ANIMATION_MS)
}

function handleActivitySuggestionAdd() {
  const content = getActivitySuggestionAddContent(currentActivitySuggestion.value)
  if (!content)
    return

  draftMessage.value = content
  klbMessage.success('已添加到对话输入区')
}

// 对话里的快捷提问卡只维护当前选择态，避免影响用户正在输入的内容。
function resetActivityQuickSelectors() {
  selectedActivityGoal.value = ''
  selectedActivityDuration.value = ''
  selectedActivityProductIds.value = []
  activityProductRequirement.value = ''
  selectedActivityStyle.value = ''
  activityStyleRequirement.value = ''
  activityDateRange.value = {
    start: '',
    end: '',
  }
  isActivityBriefReadonly.value = false
  showActivityGoalSelector.value = true
  showActivityDateSelector.value = true
  showActivityProductSelector.value = true
  showActivityStyleSelector.value = true
}

function resetActivitySelectorsByStage(stage: ActivityUiStage) {
  if (stage === 'brief') {
    isActivityBriefReadonly.value = false
    showActivityGoalSelector.value = true
    showActivityDateSelector.value = true
    return
  }

  if (stage === 'product') {
    showActivityProductSelector.value = true
    return
  }

  if (stage === 'style') {
    showActivityStyleSelector.value = true
    return
  }

  showActivityGoalSelector.value = false
  showActivityDateSelector.value = false
  showActivityProductSelector.value = false
  showActivityStyleSelector.value = false
}

function formatActivityProductPrice(value: unknown) {
  if (value === null || value === undefined || value === '')
    return '--'

  const text = String(value).trim()
  if (!text)
    return '--'
  if (text.startsWith('¥'))
    return text

  const amount = Number(value)
  return Number.isFinite(amount) ? `¥${amount.toFixed(2)}` : text
}

function getActivityProductTypeMeta(type?: MerchantItemType | string) {
  if (type === 'bundle')
    return { label: '套餐', tone: 'green' as const }
  if (type === 'stored_value')
    return { label: '储值卡', tone: 'orange' as const }
  return { label: '单品', tone: 'red' as const }
}

function buildActivityProductListParams(
  shopId: number,
  selectorType: ActivityItemSelectorType = 'package',
  useTypeFilter = true,
) {
  const merchantItemType = useTypeFilter
    ? mapActivitySelectorTypeToMerchantItemType(selectorType)
    : undefined
  return {
    shop_id: shopId,
    title: undefined,
    type: merchantItemType,
    status: 1 as const,
    page: 1,
    per_page: 20,
  }
}

function mapUnifiedItemToActivityProduct(item: UnifiedItem): ActivityProductOption {
  const typeMeta = getActivityProductTypeMeta(item.type)
  return {
    id: String(item.id),
    name: item.title || item.name || `卖品 ${item.id}`,
    image: item.cover || posterPreviewImage,
    price: formatActivityProductPrice(item.base_price),
    stock: Number(item.stock) === 0 ? '不限' : String(item.stock ?? '--'),
    typeLabel: typeMeta.label,
    typeTone: typeMeta.tone,
    rawItem: item,
  }
}

async function fetchActivityProducts(selectorType: ActivityItemSelectorType = 'package') {
  const shopId = getCurrentShopId()
  if (!shopId) {
    activityProductOptions.value = []
    selectedActivityProductIds.value = []
    hasActivityProductsLoaded.value = true
    return
  }

  isActivityProductsLoading.value = true
  try {
    const result = await api.goods.getUnifiedItemList(buildActivityProductListParams(shopId, selectorType))
    let items = Array.isArray(result?.items) ? result.items : []

    // 第二步卡片默认常给 `package`，如果店铺暂无套餐但有其他在售商品，则回退展示店铺商品列表，避免误判“无商品”。
    if (!items.length && selectorType !== 'mixed_items') {
      const fallbackResult = await api.goods.getUnifiedItemList(buildActivityProductListParams(shopId, selectorType, false))
      items = Array.isArray(fallbackResult?.items) ? fallbackResult.items : []
    }

    activityProductOptions.value = items.map(mapUnifiedItemToActivityProduct)
    const validIds = new Set(activityProductOptions.value.map(item => item.id))
    selectedActivityProductIds.value = selectedActivityProductIds.value.filter(itemId => validIds.has(itemId))
    hasActivityProductsLoaded.value = true
  }
  catch {
    activityProductOptions.value = []
    selectedActivityProductIds.value = []
    hasActivityProductsLoaded.value = true
    klbMessage.error('商品列表加载失败，请稍后重试')
  }
  finally {
    isActivityProductsLoading.value = false
  }
}

function handleActivityGoalSelect(value: string) {
  selectedActivityGoal.value = value
}

function handleActivityDurationSelect(value: string) {
  selectedActivityDuration.value = value
  if (value === 'custom_range')
    return
  activityDateRange.value = {
    start: '',
    end: '',
  }
}

function handleActivityStartDateChange(value: string) {
  selectedActivityDuration.value = 'custom_range'
  activityDateRange.value = {
    ...activityDateRange.value,
    start: value,
  }
}

function handleActivityEndDateChange(value: string) {
  selectedActivityDuration.value = 'custom_range'
  activityDateRange.value = {
    ...activityDateRange.value,
    end: value,
  }
}

function buildActivityGoalDurationComponentResult(status: 'submitted' | 'skipped') {
  const card = currentActivityGoalDurationCard.value
  if (!card)
    return null

  if (status === 'skipped') {
    return {
      card_id: card.card_id,
      component_type: card.type,
      step_key: card.step_key || 'activity_goal_duration',
      status: 'skipped',
    }
  }

  const goalSection = getActivityGoalDurationCardSection(card, 'goal')
  const durationSection = getActivityGoalDurationCardSection(card, 'duration')
  const goalOption = goalSection
    ? findCardOption(goalSection.options, selectedActivityGoal.value)
    : null
  if (goalSection && !goalOption) {
    klbMessage.info('请选择本次活动的核心目标')
    return null
  }

  let durationPayload: Record<string, any> | null = null
  if (durationSection && selectedActivityDuration.value) {
    const durationOption = findCardOption(durationSection?.options, selectedActivityDuration.value)
    if (!durationOption) {
      klbMessage.info('请选择活动时间')
      return null
    }

    let startTime: string | null = null
    let endTime: string | null = null
    if (durationOption.value === 'custom_range') {
      if (!activityDateRange.value.start) {
        klbMessage.info('请选择活动开始时间')
        return null
      }
      if (!activityDateRange.value.end) {
        klbMessage.info('请选择活动结束时间')
        return null
      }
      if (activityDateRange.value.start > activityDateRange.value.end) {
        klbMessage.info('结束时间不能早于开始时间')
        return null
      }
      startTime = `${activityDateRange.value.start} 00:00:00`
      endTime = `${activityDateRange.value.end} 23:59:59`
    }

    durationPayload = {
      value: durationOption.value,
      label: durationOption.label,
      start_time: startTime,
      end_time: endTime,
    }
  }

  return {
    card_id: card.card_id,
    component_type: card.type,
    step_key: card.step_key || 'activity_goal_duration',
    status: 'submitted',
    goal: goalOption
      ? {
          value: goalOption.value,
          label: goalOption.label,
        }
      : null,
    duration: durationPayload,
  }
}

async function submitActivityBriefCard(status: 'submitted' | 'skipped') {
  const componentResult = buildActivityGoalDurationComponentResult(status)
  if (!componentResult)
    return

  const content = status === 'submitted'
    ? '我已经选择了活动目标和时间'
    : '我先跳过这一步'
  const userMessageId = buildUserMessageId()
  const optimisticMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    isSystem: true,
    attachments: [],
    createdAt: new Date().toISOString(),
    componentResult,
  } satisfies ChatMessage

  const success = await submitAiConversationMessage({
    content,
    attachments: [],
    componentResult,
    optimisticMessage,
    onSuccess: () => {
      isActivityBriefReadonly.value = true
      showActivityGoalSelector.value = true
      showActivityDateSelector.value = true
      klbMessage.success(status === 'submitted' ? '已确认并继续' : '已跳过这一步')
    },
    onError: () => {
      klbMessage.error('提交失败，请稍后重试')
    },
  })

  if (!success)
    return
}

async function handleActivityBriefConfirm() {
  await submitActivityBriefCard('submitted')
}

async function submitActivityBriefSkipIfNeeded() {
  if (showActivityGoalSelector.value || showActivityDateSelector.value)
    return

  await submitActivityBriefCard('skipped')
}

async function handleActivityGoalSkip() {
  showActivityGoalSelector.value = false
  selectedActivityGoal.value = ''
  await submitActivityBriefSkipIfNeeded()
}

async function handleActivityDateSkip() {
  showActivityDateSelector.value = false
  selectedActivityDuration.value = ''
  activityDateRange.value = {
    start: '',
    end: '',
  }
  await submitActivityBriefSkipIfNeeded()
}

function buildActivityItemSelectorComponentResult(status: 'submitted' | 'skipped') {
  const card = currentActivityItemSelectorCard.value
  if (!card)
    return null

  const selectorType = normalizeActivityItemSelectorType(card.selector_type)
  if (status === 'skipped') {
    return {
      card_id: card.card_id,
      component_type: card.type,
      step_key: card.step_key || 'activity_select_items',
      status: 'skipped',
      selector_type: selectorType,
    }
  }

  const itemRequirement = activityProductRequirement.value.trim()
  const minSelectCount = Math.max(1, Number(card.min_select_count) || 1)
  if (!itemRequirement && selectedActivityProductIds.value.length < minSelectCount) {
    klbMessage.info(`请至少选择 ${minSelectCount} 个活动商品`)
    return null
  }

  const selectedProducts = activityProductOptions.value
    .filter(item => selectedActivityProductIds.value.includes(item.id))
    .map(item => ({
      item_id: Number(item.rawItem.id),
      item_type: selectorType,
      title: item.name,
      image: item.image,
      cover: item.rawItem.cover || item.image,
      type: item.rawItem.type,
      type_label: item.typeLabel,
      price: item.price,
      base_price: item.rawItem.base_price,
      stock: item.stock,
      stock_value: item.rawItem.stock,
    }))

  return {
    card_id: card.card_id,
    component_type: card.type,
    step_key: card.step_key || 'activity_select_items',
    status: 'submitted',
    selector_type: selectorType,
    items: selectedProducts,
    item_requirement: itemRequirement,
  }
}

async function submitActivityProductCard(status: 'submitted' | 'skipped') {
  if (status === 'submitted' && isActivityProductsLoading.value)
    return

  if (status === 'submitted' && !activityProductOptions.value.length && !activityProductRequirement.value.trim()) {
    klbMessage.info('当前暂无可选项目，请先去卖品库上架商品')
    return
  }

  const componentResult = buildActivityItemSelectorComponentResult(status)
  if (!componentResult)
    return

  const content = status === 'submitted'
    ? '我已经选择了活动商品'
    : '我先跳过商品选择'
  const userMessageId = buildUserMessageId()
  const optimisticMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    isSystem: true,
    attachments: [],
    createdAt: new Date().toISOString(),
    componentResult,
  } satisfies ChatMessage

  const success = await submitAiConversationMessage({
    content,
    attachments: [],
    componentResult,
    optimisticMessage,
    onSuccess: () => {
      showActivityProductSelector.value = false
      activityProductRequirement.value = ''
      klbMessage.success(status === 'submitted' ? '已确认商品并继续' : '已跳过商品选择')
    },
    onError: () => {
      klbMessage.error('提交失败，请稍后重试')
    },
  })

  if (!success)
    return
}

async function handleActivityProductConfirm() {
  if (isActivityProductsLoading.value)
    return

  await submitActivityProductCard('submitted')
}

async function handleActivityProductSkip() {
  await submitActivityProductCard('skipped')
}

function buildActivityStyleComponentResult(status: 'submitted' | 'skipped') {
  const card = currentActivityStyleCard.value
  if (!card)
    return null

  if (status === 'skipped') {
    return {
      card_id: card.card_id,
      component_type: card.type,
      step_key: card.step_key || 'activity_style_preference',
      status: 'skipped',
    }
  }

  const selectedOption = activityStyleOptions.value.find(item => item.value === selectedActivityStyle.value)
  const styleRequirement = activityStyleRequirement.value.trim()
  if (selectedOption) {
    selectedSettingsByMode.value.activity.tone = selectedOption.value
    persistComposerSelection()
  }

  if (!selectedOption && !styleRequirement) {
    klbMessage.info('请选择活动风格')
    return null
  }

  return {
    card_id: card.card_id,
    component_type: card.type,
    step_key: card.step_key || 'activity_style_preference',
    status: 'submitted',
    style: selectedOption
      ? {
          value: selectedOption.value,
          label: selectedOption.label,
        }
      : null,
    style_requirement: styleRequirement,
  }
}

async function submitActivityStyleCard(status: 'submitted' | 'skipped') {
  const componentResult = buildActivityStyleComponentResult(status)
  if (!componentResult)
    return

  const content = status === 'submitted'
    ? '我已经选择了活动风格'
    : '我先跳过风格选择'
  const userMessageId = buildUserMessageId()
  const optimisticMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    isSystem: true,
    attachments: [],
    createdAt: new Date().toISOString(),
    componentResult,
  } satisfies ChatMessage

  const success = await submitAiConversationMessage({
    content,
    attachments: [],
    componentResult,
    optimisticMessage,
    onSuccess: () => {
      showActivityStyleSelector.value = false
      activityStyleRequirement.value = ''
      klbMessage.success(status === 'submitted' ? '已确认风格并继续' : '已跳过风格选择')
    },
    onError: () => {
      klbMessage.error('提交失败，请稍后重试')
    },
  })

  if (!success)
    return
}

async function handleActivityStyleConfirm() {
  await submitActivityStyleCard('submitted')
}

async function handleActivityStyleSkip() {
  await submitActivityStyleCard('skipped')
}

function buildPosterDeepConfirmComponentResult() {
  const card = currentPosterDeepConfirmCard.value
  if (!card)
    return null

  return {
    card_id: card.card_id,
    component_type: card.type,
    step_key: card.step_key || 'poster_deep_confirm',
    status: 'submitted',
  }
}

function buildActivityDeepConfirmComponentResult() {
  const card = currentActivityDeepConfirmCard.value
  if (!card)
    return null

  return {
    card_id: card.card_id,
    component_type: card.type,
    step_key: card.step_key || 'activity_deep_confirm',
    status: 'submitted',
  }
}

async function handleActivityDeepConfirm(card?: ActivityAssistantCard | null) {
  const currentCard = currentActivityDeepConfirmCard.value
  if (!currentCard || (card?.card_id && card.card_id !== currentCard.card_id))
    return

  const componentResult = buildActivityDeepConfirmComponentResult()
  if (!componentResult)
    return

  selectedThinkingMode.value = 'deep'
  persistComposerSelection()

  const content = deepConfirmSubmitText
  const userMessageId = buildUserMessageId()
  const optimisticMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    isSystem: true,
    attachments: [],
    componentResult,
  } satisfies ChatMessage

  const didAutoCollapse = collapseChatLayout()

  await submitAiConversationMessage({
    content,
    attachments: [],
    componentResult,
    optimisticMessage,
    onError: () => {
      if (didAutoCollapse)
        isChatFullscreenMode.value = true
      klbMessage.error('提交失败，请稍后重试')
    },
  })
}

async function handlePosterDeepConfirm(card?: ActivityAssistantCard | null) {
  const currentCard = currentPosterDeepConfirmCard.value
  if (!currentCard || (card?.card_id && card.card_id !== currentCard.card_id))
    return

  const componentResult = buildPosterDeepConfirmComponentResult()
  if (!componentResult)
    return

  selectedThinkingMode.value = 'deep'
  persistComposerSelection()

  const content = deepConfirmSubmitText
  const userMessageId = buildUserMessageId()
  const optimisticMessage = {
    id: userMessageId,
    messageId: userMessageId,
    role: 'user',
    status: 'success',
    content,
    isSystem: true,
    attachments: [],
    componentResult,
  } satisfies ChatMessage

  const didAutoCollapse = collapseChatLayout()

  await submitAiConversationMessage({
    content,
    attachments: [],
    componentResult,
    optimisticMessage,
    onError: () => {
      if (didAutoCollapse)
        isChatFullscreenMode.value = true
      klbMessage.error('提交失败，请稍后重试')
    },
  })
}

function appendDraftMessage(content: string) {
  const currentContent = draftMessage.value.trim()
  draftMessage.value = currentContent ? `${currentContent}\n${content}` : content
}

function prependDraft(text: string) {
  draftMessage.value = `${text}${draftMessage.value}`.trim()
}

function openImagePreview(url: string) {
  if (!url)
    return

  previewImageUrl.value = url
  previewVisible.value = true
}

function handlePreviewVisibleChange(visible: boolean) {
  previewVisible.value = visible
  if (!visible)
    previewImageUrl.value = ''
}

function appendPastedImages(files: File[]) {
  const remainingCount = maxPastedImageCount - pastedImages.value.length
  if (remainingCount <= 0) {
    klbMessage.info(`最多上传 ${maxPastedImageCount} 张图片`)
    return
  }

  const nextImages = files
    .filter(file => file.type.startsWith('image/'))
    .slice(0, remainingCount)
    .map((file, index) => ({
      id: `${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`,
      file,
      url: URL.createObjectURL(file),
      name: file.name || 'clipboard-image',
    }))

  if (!nextImages.length) {
    klbMessage.info('请选择图片文件')
    return
  }

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
    .map(item => item.getAsFile())
    .filter((file): file is File => file !== null)

  if (!nextImages.length)
    return

  appendPastedImages(nextImages)
}

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

function getSelectedSettingValue(key: PromptOptionKey) {
  return selectedSettingsByMode.value[activeMode.value][key]
}

function getSelectedSettingLabel(key: PromptOptionKey) {
  const selectedValue = getSelectedSettingValue(key)
  return getPromptOptionSelectedItem(key)?.label || '请选择'
}

function getPromptOptionDisplayLabel(key: PromptOptionKey) {
  return getSelectedSettingLabel(key)
}

function selectSetting(key: PromptOptionKey, value: string) {
  selectedSettingsByMode.value[activeMode.value][key] = value
  persistComposerSelection()
}

function selectModel(value: string) {
  selectedModel.value = value
  persistComposerSelection()
}

function selectThinkingMode(value: ThinkingMode) {
  selectedThinkingMode.value = value
  persistComposerSelection()
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
  return !failedOptionImageValues.value.has(getOptionImageKey(key, item.value))
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

function getHeaderActionButtonClass(enabled: boolean, variant: 'default' | 'dark' = 'default') {
  if (!enabled)
    return 'cursor-not-allowed bg-white text-[#CBD5E1]'

  if (variant === 'dark')
    return 'cursor-pointer bg-[#0F182A] text-white shadow-[0_8px_18px_rgba(15,24,42,0.14)] hover:bg-[#111C30] hover:shadow-[0_10px_24px_rgba(15,24,42,0.18)]'

  return 'cursor-pointer bg-white text-[#0F182A] hover:shadow-[0_8px_20px_rgba(15,24,42,0.08)]'
}

async function releaseCurrentActivity(activityId: number) {
  if (isActivityReleaseSubmitting.value)
    return false

  isActivityReleaseSubmitting.value = true
  try {
    await api.activity.releaseActivity(activityId, { is_create: 1 })
    return true
  }
  catch (error) {
    console.warn('发布 AI 生成活动失败:', error)
    return false
  }
  finally {
    isActivityReleaseSubmitting.value = false
  }
}

async function adoptCurrentResult() {
  if (isMockPreviewMode.value) {
    klbMessage.info('当前为 mock 预览模式，不会跳转活动管理')
    return
  }

  if (!canAdoptCurrentResult.value)
    return

  const activityId = currentGeneratedActivityId.value
  if (!activityId) {
    klbMessage.warning('当前活动还未生成完成')
    return
  }

  if (activeMode.value === 'poster') {
    klbMessage.success('已采用当前海报方案')
    return
  }

  const released = await releaseCurrentActivity(activityId)
  if (!released)
    return

  router.push({
    path: '/activity',
    query: {
      from: 'ai',
      activity_id: String(activityId),
    },
  })
}

async function publishCurrentResult(options: { confirmed?: boolean } = {}) {
  if (isMockPreviewMode.value) {
    klbMessage.info('当前为 mock 预览模式，不会跳转活动编辑器')
    return false
  }

  if (!canPublishCurrentResult.value)
    return false

  const activityId = currentGeneratedActivityId.value
  if (!activityId) {
    klbMessage.warning('当前活动还未生成完成')
    return false
  }

  if (activeMode.value === 'poster') {
    klbMessage.info('当前为海报结果，请使用导出')
    return false
  }

  if (!options.confirmed) {
    recordGeneratedActivity(latestGeneratedActivity.value)
    openActivitySuccessModal()
    return true
  }

  const released = await releaseCurrentActivity(activityId)
  if (!released)
    return false

  const selectedActivityModel = activityModelOptions.value.find(item => item.value === getSelectedSettingValue('activityModel'))
  const selectedActivityModelId = /^\d+$/.test(String(selectedActivityModel?.value || ''))
    ? Number(selectedActivityModel?.value)
    : 0
  const activityModelId = Number(latestGeneratedActivity.value?.activity_model_id || 0) || selectedActivityModelId
  const route = router.resolve({
    path: '/activityEditor',
    query: {
      type: getSelectedSettingValue('activityModel') || 'redbag',
      id: String(activityId),
      activity_id: String(activityId),
      ...(activityModelId > 0 ? { activity_model_id: String(activityModelId) } : {}),
      ...(selectedActivityModel?.label ? { activity_model_name: selectedActivityModel.label } : {}),
      ...(getCurrentShopId() ? { shop_id: String(getCurrentShopId()) } : {}),
      from: 'ai',
      action: 'publish',
    },
  })
  window.open(route.href, '_blank')
  return true
}

async function handleSuccessModalPublish() {
  const activityId = lastSuccessModalActivityId.value || currentGeneratedActivityId.value
  if (!activityId) {
    klbMessage.warning('当前活动还未生成完成')
    return
  }

  const published = await publishCurrentResult({ confirmed: true })
  if (published)
    activitySuccessModalOpen.value = false
}

function exportCurrentResult() {
  if (isMockPreviewMode.value) {
    klbMessage.info('当前为 mock 预览模式，不会导出文件')
    return
  }

  if (!canExportCurrentResult.value)
    return

  downloadFile(currentPosterPreviewImage.value, activeMode.value === 'poster' ? 'ai-poster-preview.png' : 'ai-activity-preview.png')
  klbMessage.success(activeMode.value === 'poster' ? '海报已开始下载' : '活动预览已开始下载')
}

function handlePosterMessageDownload(url: string) {
  const posterUrl = String(url || '').trim()
  if (!posterUrl)
    return

  downloadFile(posterUrl, 'ai-poster-preview.png')
  klbMessage.success('海报已开始下载')
}

function handlePreviewAction(action: '重新生成' | '导出' | '添加到对话') {
  if (action === '重新生成') {
    if (isPosterGenerating.value)
      return

    void sendMessage(activeMode.value === 'poster'
      ? '请基于当前要求重新生成一版海报预览。'
      : '请基于当前要求重新生成一版活动方案。')
    return
  }

  if (action === '导出') {
    exportCurrentResult()
    return
  }

  draftMessage.value = activeMode.value === 'poster'
    ? '请基于右侧海报继续优化主标题、配色和质感。'
    : '请基于右侧结果继续优化活动标题、利益点和页面内容结构。'
  klbMessage.success('已将当前结果加入对话输入区')
}

function downloadFile(url: string, fileName: string) {
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = fileName
  anchor.target = '_blank'
  anchor.rel = 'noopener noreferrer'
  document.body.appendChild(anchor)
  anchor.click()
  document.body.removeChild(anchor)
}
</script>

<style scoped>
.ai-chat-page {
  position: relative;
  isolation: isolate;
  height: 100vh;
  background: #ffffff;
  overscroll-behavior-x: none;
  overflow-y: hidden;
}

.ai-chat-page::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background-color: #f1f3f5;
  background-image: radial-gradient(rgba(151, 159, 173, 0.14) 0.8px, transparent 0.8px);
  background-size: 12px 12px;
  opacity: 0;
  transition: opacity 420ms ease-out;
}

.ai-chat-page--entered::before {
  opacity: 1;
}

.ai-chat-canvas {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100vh;
  min-width: 0;
  box-sizing: border-box;
}

.ai-chat-header {
  width: 100%;
  margin: 0 auto;
  box-sizing: border-box;
  background-image: radial-gradient(rgba(151, 159, 173, 0.14) 0.8px, transparent 0.8px);
  background-size: 12px 12px;
  background-color: #f1f3f5;
}

.ai-chat-header::after {
  display: none;
}

.ai-chat-main {
  gap: 16px;
  height: max(667px, calc(100vh - 128px));
  min-height: 667px;
  opacity: 0;
  transform: translate3d(0, 72px, 0);
  transition:
    opacity 320ms ease-out 40ms,
    transform 460ms cubic-bezier(0.18, 0.72, 0.18, 1) 40ms,
    gap 680ms cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform, opacity, gap;
}

.ai-chat-dialog-column {
  width: clamp(520px, calc(100% - 436px), 744px);
  flex: 0 0 auto;
  height: 100%;
  min-height: 667px;
  opacity: 0;
  transform: translate3d(0, 48px, 0);
  transition:
    opacity 320ms ease-out 70ms,
    transform 480ms cubic-bezier(0.18, 0.72, 0.18, 1) 70ms,
    width 680ms cubic-bezier(0.22, 1, 0.36, 1),
    border-radius 520ms cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform, opacity, width;
}

.ai-chat-preview-column {
  min-width: 0;
  flex: 1 1 0;
  height: 100%;
  min-height: 667px;
  padding-bottom: 0;
  overflow-y: visible;
  -ms-overflow-style: none;
  scrollbar-width: none;
  box-sizing: border-box;
  opacity: 0;
  visibility: visible;
  transform: translate3d(28px, 0, 0) scale(0.985);
  transition:
    opacity 300ms ease-out 150ms,
    transform 520ms cubic-bezier(0.22, 1, 0.36, 1) 120ms,
    visibility 0s linear 0s;
  will-change: transform, opacity, width;
}

.ai-chat-page--entered .ai-chat-main,
.ai-chat-page--entered .ai-chat-dialog-column,
.ai-chat-page--entered .ai-chat-preview-column {
  opacity: 1;
  transform: translate3d(0, 0, 0);
}

.ai-chat-page--entered .ai-chat-main--fullscreen .ai-chat-preview-column {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transform: translate3d(28px, 0, 0) scale(0.985);
  transition:
    opacity 180ms ease-out,
    transform 360ms cubic-bezier(0.4, 0, 0.2, 1),
    visibility 0s linear 560ms;
}

.ai-chat-preview-column::-webkit-scrollbar {
  display: none;
}

@media (prefers-reduced-motion: reduce) {
  .ai-chat-page::before,
  .ai-chat-main,
  .ai-chat-dialog-column,
  .ai-chat-preview-column {
    transition: none;
  }
}

@media (max-width: 1040px) {
  .ai-chat-dialog-column {
    width: clamp(420px, calc(100% - 376px), 744px);
  }
}

@media (max-width: 820px) {
  .ai-chat-page {
    overflow-x: auto;
  }

  .ai-chat-canvas {
    min-width: 820px;
  }
}

.ai-chat-main--fullscreen {
  gap: 0;
}

.ai-chat-main--fullscreen .ai-chat-dialog-column {
  width: 100%;
}

.ai-chat-dialog-column :deep(.chat-message-window__stack) {
  width: min(720px, 100%);
  margin: 0 auto;
}

.ai-chat-dialog-column :deep(.chat-composer-shell) {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.ai-chat-dialog-column :deep(.chat-composer-meta),
.ai-chat-dialog-column :deep(.chat-composer-shell > .relative) {
  width: min(720px, 100%);
  flex: 0 0 auto;
}

.ai-chat-layout-toggle {
  width: 36px;
  height: 36px;
  margin-right: 14px;
  padding: 0;
  border: 0;
  border-radius: 10px;
  background: #f2f5fa;
  color: #0f182a;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition:
    background-color 0.18s ease,
    box-shadow 0.18s ease,
    transform 0.18s ease;
}

.ai-chat-layout-toggle:hover {
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(15, 24, 42, 0.08);
}

.ai-chat-layout-toggle .iconfont {
  font-size: 20px;
  line-height: 1;
  transition: transform 0.2s ease;
}

.ai-chat-layout-toggle--fullscreen .iconfont {
  transform: rotate(180deg);
}

.ai-chat-brand-bar {
  display: flex;
  align-items: center;
  gap: 24px;
  min-width: 0;
}

.ai-chat-brand-back {
  width: 62px;
  height: 36px;
  padding: 0;
  border: 0;
  border-radius: 12px;
  background: #ffffff;
  color: #0f182a;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  transition:
    box-shadow 0.18s ease;
}

.ai-chat-brand-back:hover {
  box-shadow: 0 8px 20px rgba(15, 24, 42, 0.08);
}

.ai-chat-brand-back .iconfont {
  font-size: 18px;
  line-height: 1;
}

.ai-chat-brand-back span {
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
}

.ai-chat-brand-divider {
  width: 1px;
  height: 16px;
  background: #CBD5E1;
  display: inline-block;
}

.ai-chat-header-divider {
  width: 1px;
  height: 16px;
  margin: 0 12px;
  background: #CBD5E1;
  display: inline-block;
  flex: 0 0 auto;
}

.ai-chat-brand-logo {
  width: 105px;
  height: 28px;
  object-fit: contain;
  display: block;
}

.ai-activity-suggestion-carousel {
  position: relative;
  width: 100%;
  max-width: 720px;
  height: 288px;
  margin-top: 36px;
}

.ai-activity-suggestion-card-layer {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.ai-activity-suggestion-card {
  position: absolute;
  overflow: hidden;
  border: 1px solid #e3e9f1;
  border-radius: 16px;
  transform-origin: center;
  transition:
    left 760ms cubic-bezier(0.2, 0, 0.12, 1),
    top 760ms cubic-bezier(0.2, 0, 0.12, 1),
    width 760ms cubic-bezier(0.2, 0, 0.12, 1),
    height 760ms cubic-bezier(0.2, 0, 0.12, 1),
    opacity 620ms cubic-bezier(0.2, 0, 0.12, 1),
    transform 760ms cubic-bezier(0.2, 0, 0.12, 1),
    box-shadow 760ms cubic-bezier(0.2, 0, 0.12, 1);
  pointer-events: none;
  will-change: left, top, width, height, opacity, transform;
}

.ai-activity-suggestion-card--position-0 {
  z-index: 30;
  left: 0;
  top: 38px;
  width: 100%;
  height: 240px;
  opacity: 1;
  border-color: transparent;
  background: #ffffff;
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
  pointer-events: auto;
}

.ai-activity-suggestion-card--position-1 {
  z-index: 20;
  left: 12px;
  top: 18px;
  width: calc(100% - 24px);
  height: 231px;
  opacity: 1;
  background: #fbfbfb;
  box-shadow: none;
}

.ai-activity-suggestion-card--position-2 {
  z-index: 10;
  left: 26px;
  top: -2px;
  width: calc(100% - 52px);
  height: 221px;
  opacity: 0.5;
  background: rgba(251, 251, 251, 0.5);
  box-shadow: none;
}

.ai-activity-suggestion-card-content {
  position: relative;
  height: 100%;
  display: flex;
  justify-content: space-between;
  padding: 38px 32px 0 40px;
  overflow: hidden;
  opacity: 1;
  transition: opacity 260ms ease;
}

.ai-activity-suggestion-card--position-1 .ai-activity-suggestion-card-content,
.ai-activity-suggestion-card--position-2 .ai-activity-suggestion-card-content {
  opacity: 0;
  transition-delay: 0ms;
}

.ai-activity-suggestion-carousel.is-switching .ai-activity-suggestion-card--position-0 .ai-activity-suggestion-card-content {
  transition-delay: 500ms;
}

.ai-activity-suggestion-carousel.is-switching .ai-activity-suggestion-card-leave-active .ai-activity-suggestion-card-content {
  transition-delay: 0ms;
}

.ai-activity-suggestion-copy {
  width: 346px;
  min-width: 0;
}

.ai-activity-suggestion-title {
  width: 100%;
  color: #0f182a;
  font-size: 16px;
  font-weight: 600;
  line-height: 22px;
  white-space: nowrap;
}

.ai-activity-suggestion-content {
  margin-top: 12px;
  color: #64748b;
  font-size: 12px;
  font-weight: 400;
  line-height: 17px;
  text-align: justify;
}

.ai-activity-suggestion-content-line + .ai-activity-suggestion-content-line {
  margin-top: 6px;
}

.ai-activity-suggestion-content-label {
  color: inherit;
  font-weight: 400;
}

.ai-activity-suggestion-add-action {
  position: absolute;
  left: 40px;
  top: 188px;
  display: inline-flex;
  align-items: center;
  padding: 0;
  border: 0;
  color: #0f182a;
  background: transparent;
  cursor: pointer;
  transition:
    color 0.18s ease,
    opacity 0.18s ease;
}

.ai-activity-suggestion-add-action:hover {
  color: #2563eb;
}

.ai-activity-suggestion-add-action:hover span {
  color: #2563eb;
}

.ai-activity-suggestion-add-action:active {
  color: #1d4ed8;
}

.ai-activity-suggestion-illustration-wrap {
  position: absolute;
  right: 32px;
  top: 25px;
  width: 254px;
  height: 190px;
  overflow: hidden;
}

.ai-activity-suggestion-illustration {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  backface-visibility: hidden;
}

.ai-activity-suggestion-card-enter-active,
.ai-activity-suggestion-card-leave-active {
  transition: opacity 620ms cubic-bezier(0.2, 0, 0.12, 1);
}

.ai-activity-suggestion-card-enter-from {
  opacity: 0;
}

.ai-activity-suggestion-card-leave-to {
  opacity: 0;
}

.ai-activity-suggestion-card-move {
  transition:
    transform 760ms cubic-bezier(0.2, 0, 0.12, 1),
    left 760ms cubic-bezier(0.2, 0, 0.12, 1),
    top 760ms cubic-bezier(0.2, 0, 0.12, 1),
    width 760ms cubic-bezier(0.2, 0, 0.12, 1),
    height 760ms cubic-bezier(0.2, 0, 0.12, 1),
    opacity 620ms cubic-bezier(0.2, 0, 0.12, 1);
}

.ai-activity-generating-status {
  width: fit-content;
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
  animation: ai-activity-generating-shine var(--ai-working-text-shine-duration) linear infinite;
}

.ai-activity-generating-status--guide {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  width: fit-content;
  line-height: 20px;
  background: none;
  animation: none;
  color: #0f182a;
  -webkit-text-fill-color: currentColor;
}

.ai-activity-generating-status__star {
  flex: 0 0 28px;
  align-self: center;
  margin-left: -8px;
  margin-right: 2px;
  opacity: 0.9;
  transform: translateY(1px) scale(1.04);
  transform-origin: center;
}

.ai-activity-generating-status__text {
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
  color: transparent;
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: ai-activity-generating-shine var(--ai-working-text-shine-duration) linear infinite;
  white-space: nowrap;
}

@keyframes ai-activity-generating-shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: 0% 0;
  }
}

.ai-poster-generating-preview {
  position: relative;
  display: flex;
  width: 100%;
  aspect-ratio: 3 / 4;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 32px;
  border-radius: inherit;
  background:
    linear-gradient(115deg, rgba(255, 255, 255, 0) 32%, rgba(255, 255, 255, 0.52) 48%, rgba(255, 255, 255, 0) 64%),
    linear-gradient(152deg, #E2E4E5 0%, #D3D5D7 43.54%, #EAEEF1 98.25%);
  background-size: 280% 100%, 100% 100%;
  animation: ai-poster-generating-preview-shimmer 5.2s linear infinite;
  will-change: background-position;
}

.ai-poster-generating-preview__star {
  opacity: 0.52;
}

@keyframes ai-poster-generating-preview-shimmer {
  0% {
    background-position: 180% 0, 0 0;
  }

  100% {
    background-position: -180% 0, 0 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ai-activity-generating-status,
  .ai-activity-generating-status__text,
  .ai-poster-generating-preview {
    animation: none;
  }
}

.ai-activity-preview-shell {
  box-sizing: border-box;
  width: 375px;
  height: max(667px, calc(100vh - 208px));
  min-height: 667px;
  flex-shrink: 0;
  padding: 8px;
  border-radius: 38px;
  background: #0f172a;
}

.ai-activity-preview-screen {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  border-radius: 30px;
  background: #fff6e5;
}

.ai-activity-preview-frame {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
  overflow: auto;
  pointer-events: auto;
}

.ai-activity-preview-frame--pending {
  position: fixed;
  left: -9999px;
  top: -9999px;
  width: 375px;
  height: 812px;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}

@media (max-height: 820px) {
  .ai-chat-page {
    height: auto;
    min-height: 100vh;
    overflow-y: auto;
  }

  .ai-chat-canvas {
    height: auto;
    min-height: 100vh;
  }

  .ai-chat-main {
    height: 667px;
    min-height: 667px;
    flex: 0 0 667px;
    overflow: visible !important;
  }

  .ai-chat-dialog-column {
    height: 667px;
    min-height: 667px;
  }

  .ai-chat-preview-column {
    overflow-y: visible !important;
  }

  .ai-activity-preview-frame {
    pointer-events: none;
  }
}

.ai-chat-message-panel {
  width: 410px;
  max-height: min(640px, calc(100vh - 32px));
  display: flex;
  flex-direction: column;
  background: #ffffff;
  overflow: hidden;
}

.ai-chat-message-panel__header {
  height: 60px;
  flex: 0 0 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  border-bottom: 1px solid #e3e9f1;
  box-sizing: border-box;
}

.ai-chat-message-panel__tabs {
  display: flex;
  align-items: center;
  gap: 24px;
}

.ai-chat-message-panel__tab {
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

.ai-chat-message-panel__tab--active {
  color: #0f182a;
  font-weight: 500;
}

.ai-chat-message-panel__tab-dot,
.ai-chat-message-panel__dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #e62222;
  display: inline-block;
}

.ai-chat-message-panel__tab-dot {
  position: absolute;
  top: 9px;
  right: -8px;
}

.ai-chat-message-panel__clear {
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

.ai-chat-message-panel__list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: none;
}

.ai-chat-message-panel__list::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.ai-chat-message-panel__empty {
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

.ai-chat-message-panel__empty-icon {
  display: block;
  width: 96px;
  height: 96px;
  object-fit: contain;
}

.ai-chat-message-panel__item {
  display: grid;
  grid-template-columns: 32px minmax(0, 1fr);
  column-gap: 8px;
  padding: 24px;
  border-bottom: 1px solid #e3e9f1;
  box-sizing: border-box;
}

.ai-chat-message-panel__item:last-child {
  border-bottom: 0;
}

.ai-chat-message-panel__avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f1f3f5;
  color: #0f182a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-chat-message-panel__avatar .iconfont {
  font-size: 18px;
  line-height: 1;
}

.ai-chat-message-panel__body {
  min-width: 0;
}

.ai-chat-message-panel__meta {
  min-height: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-chat-message-panel__sender {
  color: #0f182a;
  font-size: 14px;
  line-height: 20px;
  font-weight: 400;
}

.ai-chat-message-panel__time {
  color: #64748b;
  font-size: 11px;
  line-height: 15px;
  font-weight: 400;
}

.ai-chat-message-panel__content {
  margin-top: 10px;
  color: #0f182a;
  font-size: 16px;
  line-height: 28px;
  font-weight: 400;
  white-space: pre-line;
  word-break: break-word;
}

.ai-chat-message-panel__action {
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

.ai-chat-message-panel__action-icon {
  font-size: 16px;
  line-height: 1;
}

.ai-chat-message-panel__preview {
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

:global(.ai-chat-message-popover) {
  padding-top: 8px;
}

:global(.ai-chat-message-popover .ant-popover-inner) {
  padding: 0;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 4px 12px 4px rgba(47, 48, 49, 0.1);
  overflow: hidden;
}

:global(.ai-chat-message-popover .ant-popover-inner-content) {
  padding: 0;
}

:global(.ai-chat-message-popover .ant-popover-arrow) {
  display: none;
}

.ai-activity-success-modal {
  position: relative;
  width: 400px;
  height: 336px;
  overflow: hidden;
  border-radius: 24px;
  background: #ffffff;
  color: #0f182a;
}

.ai-activity-success-modal__close {
  position: absolute;
  top: 14px;
  right: 16px;
  z-index: 2;
  width: 32px;
  height: 32px;
  padding: 0;
  border: 0;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.3);
  color: #99a7bb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.ai-activity-success-modal__close .iconfont {
  font-size: 18px;
  line-height: 1;
}

.ai-activity-success-modal__body {
  height: 252px;
  padding: 24px 24px 16px;
  box-sizing: border-box;
}

.ai-activity-success-modal__icon {
  position: relative;
  width: 48px;
  height: 48px;
}

.ai-activity-success-modal__icon-bg {
  position: absolute;
  left: 6px;
  top: 6px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #c3f5ce;
}

.ai-activity-success-modal__icon-mark {
  position: absolute;
  left: 12px;
  top: 12px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #2eb450;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.ai-activity-success-modal__icon-mark .iconfont {
  font-size: 14px;
  line-height: 1;
}

.ai-activity-success-modal__title {
  margin: 16px 0 0;
  color: #0f182a;
  font-size: 18px;
  font-weight: 600;
  line-height: 28px;
}

.ai-activity-success-modal__desc,
.ai-activity-success-modal__tip {
  margin: 4px 0 0;
  width: 352px;
  color: #64748b;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
}

.ai-activity-success-modal__desc {
  margin-top: 4px;
}

.ai-activity-success-modal__tip {
  margin-top: 16px;
}

.ai-activity-success-modal__footer {
  height: 84px;
  padding: 24px;
  box-sizing: border-box;
  display: flex;
  align-items: center;
  gap: 16px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(15px);
}

.ai-activity-success-modal__secondary,
.ai-activity-success-modal__primary {
  width: 168px;
  height: 36px;
  padding: 0 16px;
  border: 0;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  line-height: 20px;
}

.ai-activity-success-modal__secondary {
  background: #f1f3f5;
  color: #0f182a;
}

.ai-activity-success-modal__primary {
  background: #e62222;
  color: #ffffff;
}

.ai-activity-success-modal__primary:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

:global(.ai-activity-success-modal-wrap .ant-modal) {
  padding-bottom: 0;
}

:global(.ai-activity-success-modal-wrap .ant-modal-content) {
  padding: 0;
  border-radius: 24px;
  background: transparent;
  box-shadow: none;
  overflow: hidden;
}

:global(.ai-activity-success-modal-wrap .ant-modal-body) {
  padding: 0;
}

:global(.ai-activity-success-modal-wrap .ant-modal-mask) {
  background: rgba(0, 0, 0, 0.5);
}
</style>
