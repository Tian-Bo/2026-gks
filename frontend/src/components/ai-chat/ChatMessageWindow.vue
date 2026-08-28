<template>
  <div class="chat-message-window-shell mt-2 min-h-0 flex-1">
    <div
      ref="messageWindowRef"
      class="chat-message-window h-full min-h-0 overflow-y-auto px-[32px] pb-[32px]"
      @scroll.passive="handleMessageWindowScroll"
      @wheel.passive="handleMessageWindowWheel"
      @touchstart.passive="handleMessageWindowTouchStart"
    >
      <div ref="messageStackRef" class="chat-message-window__stack">
      <div v-if="currentPrompt" class="chat-message-row flex justify-end">
        <div class="ai-message-cell ai-message-cell--user has-message-actions">
          <div
            class="max-w-[calc(100%*2/3)] rounded-[16px_16px_6px_16px] bg-[#F1F3F5] px-[12px] py-3 text-[14px] leading-6 text-[#0F182A]">
            {{ currentPrompt }}
          </div>
          <div class="ai-message-action-bar ai-message-action-bar--user">
            <span class="ai-message-action-time">{{ getCurrentPromptActionTime() }}</span>
            <button
              type="button"
              class="ai-message-action-button"
              :class="{ 'is-copied': copiedActionKey === 'current-prompt' }"
              title="复制"
              aria-label="复制"
              @click.stop="copyMessageText(currentPrompt, 'current-prompt')"
            >
              <i class="iconfont icon-fuzhi" aria-hidden="true"></i>
              <span class="ai-message-action-tooltip">复制</span>
            </button>
            <button
              type="button"
              class="ai-message-action-button"
              title="重新生成"
              aria-label="重新生成"
              @click.stop="handleRegenerateCurrentPrompt"
            >
              <svg viewBox="0 0 20 20" aria-hidden="true">
                <path d="M16.2 8.2a6.4 6.4 0 0 0-11-2.9L3.5 7" />
                <path d="M3.5 3.4V7h3.6" />
                <path d="M3.8 11.8a6.4 6.4 0 0 0 11 2.9l1.7-1.7" />
                <path d="M16.5 16.6V13h-3.6" />
              </svg>
              <span class="ai-message-action-tooltip">重新生成</span>
            </button>
          </div>
        </div>
      </div>

      <div v-if="shouldShowGlobalThinkingProcessCard" class="ai-component-reveal-block" :style="getRevealStyle(0)">
        <ThinkingProcessCard :status="thinkingProcessStatus" :summary-items="thinkingProcessSummaryItems" />
      </div>

      <div
        v-for="item in visibleChatMessages"
        :key="item.id"
        class="chat-message-row flex"
        :data-message-key="getMessageActionKey(item)"
        :class="item.role === 'user' ? 'justify-end' : 'justify-start'">
        <div
          class="ai-message-cell"
          :class="[
            item.role === 'user' ? 'ai-message-cell--user' : 'ai-message-cell--assistant',
            shouldShowMessageActions(item) ? 'has-message-actions' : '',
          ]"
        >
        <div class="text-[14px] leading-6 text-[#0F182A]" :class="item.role === 'user'
          ? getMessageImageAttachments(item).length
            ? 'max-w-[450px] break-words rounded-[16px] rounded-br-[6px] bg-[#F1F3F5] px-[12px] py-3'
            : 'w-max max-w-[450px] break-words rounded-[16px] rounded-br-[6px] bg-[#F1F3F5] px-[12px] py-3'
          : 'w-full rounded-none bg-transparent px-0 py-0 shadow-none'">
          <div v-if="getMessageImageAttachments(item).length" class="flex max-w-[450px] flex-wrap gap-[8px]"
            :class="getMessageDisplayContent(item) ? 'mb-[10px]' : ''">
            <div v-for="(image, imageIndex) in getMessageImageAttachments(item)"
              :key="`${item.id}-${image.url}-${imageIndex}`"
              class="h-[59px] w-[59px] overflow-hidden rounded-[12px] bg-[#F8FAFC]">
              <img :src="image.url" :alt="image.name || '消息图片'"
                class="h-full w-full cursor-pointer object-cover"
                @click="emit('previewImage', image.url)">
            </div>
          </div>
          <PosterDeepThinkingBlock
            v-if="shouldShowPosterDeepThinkingBlock(item)"
            class="ai-component-reveal-block"
            :style="getRevealStyle(0)"
            :status="getDeepThinkingStatus(item)"
            :thinking="getDeepThinkingText(item)"
            :animate="isLiveAssistantMessage(item)"
          />
          <div
            v-if="shouldShowMessageText(item)"
            class="ai-message-text"
            :class="isAssistantWorkingMessage(item) ? 'ai-message-working-shine' : ''"
          >
            {{ getMessageDisplayContent(item) }}
          </div>
          <div
            v-if="item.role === 'assistant' && getActivityGoalDurationCard(item)"
            v-show="isEditableBriefCard(item)"
            class="ai-component-reveal-block mt-[16px]"
            :style="getRevealStyle(1)"
          >
            <ActivityBriefForm
              :goal-title="getCardSectionTitle(getActivityGoalDurationCard(item), 'goal', '本次店庆的核心目标是什么？')"
              :date-title="getCardSectionTitle(getActivityGoalDurationCard(item), 'duration', '活动计划的起止时间是？或者大概持续几天？')"
              :goal-value="isEditableBriefCard(item) ? selectedActivityGoal : getBriefCardGoalValue(item)"
              :date-value="isEditableBriefCard(item) ? selectedActivityDuration : getBriefCardDateValue(item)"
              :goal-options="getCardSectionOptions(getActivityGoalDurationCard(item), 'goal', activityGoalOptions)"
              :date-options="getCardSectionOptions(getActivityGoalDurationCard(item), 'duration', activityDurationOptions)"
              :start-value="isEditableBriefCard(item) ? activityDateRange.start : getBriefCardDatePart(getCardComponentResult(item, getActivityGoalDurationCard(item)?.card_id)?.duration?.start_time)"
              :end-value="isEditableBriefCard(item) ? activityDateRange.end : getBriefCardDatePart(getCardComponentResult(item, getActivityGoalDurationCard(item)?.card_id)?.duration?.end_time)"
              :show-goal-section="isEditableBriefCard(item) ? showActivityGoalSection && !!getCardSection(getActivityGoalDurationCard(item), 'goal') : !!getCardSection(getActivityGoalDurationCard(item), 'goal')"
              :show-date-section="isEditableBriefCard(item) ? showActivityDateSection && !!getCardSection(getActivityGoalDurationCard(item), 'duration') : !!getCardSection(getActivityGoalDurationCard(item), 'duration')"
              :readonly="!isEditableBriefCard(item)" @update:goal-value="emit('update:goalValue', $event)"
              @update:date-value="emit('update:dateValue', $event)"
              @update:start-value="emit('update:startValue', $event)" @update:end-value="emit('update:endValue', $event)"
              @skip-goal="emit('skipGoal')" @skip-date="emit('skipDate')" @confirm="emit('confirmBrief')" />
          </div>
          <div v-if="item.role === 'assistant' && getActivityGoalDurationCard(item) && !isEditableBriefCard(item)"
            class="ai-component-reveal-block mt-[16px] flex flex-col items-end gap-[16px]" :style="getRevealStyle(1)">
            <ActivitySelectedResultCard
              v-if="getSelectedBriefTextItems(item).goal"
              type="text"
              :title="getSelectedBriefTextItems(item).goal"
            />
            <ActivitySelectedResultCard
              v-if="getSelectedBriefTextItems(item).duration"
              type="text"
              :title="getSelectedBriefTextItems(item).duration"
            />
          </div>
          <ActivityProductSelector v-if="item.role === 'assistant' && getActivityItemSelectorCard(item)"
            v-show="isEditableProductCard(item)"
            class="ai-component-reveal-block mt-[16px]" :style="getRevealStyle(1)" :title="getActivityItemSelectorCard(item)?.title || '想主推的什么商品？快灵已帮你准备好系统内已有的商品'"
            :products="isEditableProductCard(item) ? activityProductOptions : []"
            :selected-ids="isEditableProductCard(item) ? selectedActivityProductIds : []" :page-size="4"
            :loading="isEditableProductCard(item) ? isActivityProductsLoading : false"
            :custom-requirement="activityProductRequirement" :readonly="!isEditableProductCard(item)"
            :readonly-summary-text="getProductCardSummaryText(item)"
            :readonly-summary-items="getProductCardSummaryItems(item)" empty-text="当前店铺暂无上架商品，先去卖品库完善后再来生成活动"
            :animate="isLiveAssistantMessage(item)"
            @update:selected-ids="emit('update:selectedProductIds', $event)"
            @update:custom-requirement="emit('update:productRequirement', $event)" @skip="emit('skipProduct')"
            @confirm="emit('confirmProduct')" />
          <div
            v-for="card in getActivityItemImagePreviewCards(item)"
            :key="String(card.card_id || card.item_id || card.item_title || 'activity-image')"
            v-if="activeMode === 'activity' && item.role === 'assistant'"
            class="ai-component-reveal-block ai-activity-image-preview-card ai-auto-item-cover-module mt-[20px]"
            :style="getRevealStyle(2)"
          >
            <div class="ai-activity-image-preview-card__header">
              <div
                class="ai-activity-image-preview-card__title"
                :class="isActivityImagePreviewGenerating(card) ? 'ai-message-working-shine' : 'is-completed'"
              >
                <LottieStar
                  class="ai-activity-image-preview-card__title-star"
                  :size="isActivityImagePreviewGenerating(card) ? 28 : 18"
                  :loop="isActivityImagePreviewGenerating(card)"
                  :autoplay="isActivityImagePreviewGenerating(card)"
                />
                <span>{{ getActivityImagePreviewTitle(card) }}</span>
              </div>
            </div>
            <div
              class="ai-activity-image-preview-card__canvas"
              :class="{ 'is-generating': isActivityImagePreviewGenerating(card) }"
              :style="getActivityImagePreviewCanvasStyle(card)"
            >
              <img
                v-if="getActivityImagePreviewUrl(card)"
                :src="getActivityImagePreviewUrl(card)"
                :alt="getActivityImagePreviewTitle(card)"
                @click="emit('previewImage', getActivityImagePreviewUrl(card))"
              >
              <div v-else class="ai-activity-image-preview-card__placeholder" aria-hidden="true">
                <LottieStar
                  :size="40"
                  :loop="isActivityImagePreviewGenerating(card)"
                  :autoplay="isActivityImagePreviewGenerating(card)"
                />
              </div>
            </div>
            <div class="ai-image-preview-card__actions">
              <button
                type="button"
                class="ai-image-preview-card__action"
                :disabled="isImageActionDisabled(card)"
                title="重新生成商品图"
                aria-label="重新生成商品图"
                @click.stop="emit('regenerateImage', { messageId: getMessageActionKey(item), target: 'activity_item_cover', itemId: Number(card.item_id || 0) })"
              >
                <svg viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M16.2 8.2a6.4 6.4 0 0 0-11-2.9L3.5 7" />
                  <path d="M3.5 3.4V7h3.6" />
                  <path d="M3.8 11.8a6.4 6.4 0 0 0 11 2.9l1.7-1.7" />
                  <path d="M16.5 16.6V13h-3.6" />
                </svg>
                <span class="ai-image-preview-card__tooltip">重新生成商品图</span>
              </button>
              <button
                type="button"
                class="ai-image-preview-card__action"
                :disabled="!getActivityImagePreviewUrl(card)"
                title="下载商品图"
                aria-label="下载商品图"
                @click.stop="emit('downloadPoster', getActivityImagePreviewUrl(card))"
              >
                <i class="iconfont icon-xiazai" aria-hidden="true"></i>
                <span class="ai-image-preview-card__tooltip">下载商品图</span>
              </button>
            </div>
          </div>
          <div
            v-if="item.role === 'assistant' && getActivityItemSelectorCard(item) && !isEditableProductCard(item) && !isPendingAutoItemCard(item) && !isRegenerationResponseMessage(item)"
            class="ai-component-reveal-block ai-auto-item-selection-module mt-[20px]"
            :style="getRevealStyle(2)"
          >
            <ActivitySelectedResultCard
              :type="getSelectedProductDisplayItems(item).length ? 'product' : 'text'"
              :title="getProductCardSummaryText(item)"
              :products="getSelectedProductDisplayItems(item)"
              :requirement="getProductCardRequirement(item)"
            />
            <div v-if="getActivityItemSelectorCard(item)?.auto_selected !== true" class="ai-module-actions">
              <button
                type="button"
                class="ai-image-preview-card__action"
                title="重新选择商品"
                aria-label="重新选择商品"
                @click.stop="emit('reselectItems')"
              >
                <svg viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M4 16h3.2L16 7.2A2.1 2.1 0 0 0 13 4.2L4.2 13z" />
                  <path d="m11.8 5.4 2.8 2.8" />
                </svg>
                <span class="ai-image-preview-card__tooltip">重新选择商品</span>
              </button>
            </div>
          </div>

          <ActivityStyleSelector v-if="item.role === 'assistant' && getActivityStyleSelectorCard(item)"
            v-show="isEditableStyleCard(item)"
            class="ai-component-reveal-block mt-[16px]" :style="getRevealStyle(1)" :title="getActivityStyleSelectorCard(item)?.title || '活动氛围有什么风格偏好？'"
            :options="activityStyleOptions" :selected-value="isEditableStyleCard(item) ? selectedActivityStyle : ''"
            :custom-requirement="activityStyleRequirement" :readonly="!isEditableStyleCard(item)"
            :readonly-summary-text="getStyleCardSummaryText(item)"
            :readonly-summary-items="getStyleCardSummaryItems(item)"
            :animate="isLiveAssistantMessage(item)"
            @update:selected-value="emit('update:selectedStyle', $event)"
            @update:custom-requirement="emit('update:styleRequirement', $event)" @skip="emit('skipStyle')"
            @confirm="emit('confirmStyle')" />
          <ActivitySelectedResultCard
            v-if="item.role === 'assistant' && getActivityStyleSelectorCard(item) && !isEditableStyleCard(item)"
            class="ai-component-reveal-block mt-[16px] ml-auto"
            :style="getRevealStyle(1)"
            type="text"
            :title="getStyleCardSummaryText(item)"
            :requirement="getStyleCardRequirement(item)"
          />
          <PosterDeepThinkingBlock
            v-if="shouldShowActivityDeepThinkingBlock(item)"
            class="ai-component-reveal-block mt-[22px]"
            :style="getRevealStyle(2)"
            :status="getDeepThinkingStatus(item)"
            :thinking="getDeepThinkingText(item)"
            :animate="isLiveAssistantMessage(item)"
          />

          <ActivityRuleCheckBlock
            v-if="activeMode === 'activity' && item.role === 'assistant' && getActivityRuleCheckCard(item)"
            class="ai-component-reveal-block mt-[16px]"
            :style="getRevealStyle(1)"
            :title="getActivityRuleCheckCard(item)?.title"
            :status="getActivityRuleCheckCard(item)?.status"
            :checks="getActivityRuleCheckCard(item)?.checks"
            :animate="isLiveAssistantMessage(item)"
          />

          <ActivityDeepSummaryBlock
            v-if="activeMode === 'activity' && item.role === 'assistant' && hasActivityDeepSummary(getActivityDeepConfirmCard(item))"
            class="ai-component-reveal-block mt-[22px]"
            :style="getRevealStyle(1)"
            :summary="getActivityDeepConfirmSummary(getActivityDeepConfirmCard(item))"
            :plan="getActivityDeepConfirmCard(item)?.plan"
            :animate="isLiveAssistantMessage(item)"
            @reveal-complete="markActivityDeepSummaryRevealed(item)"
          />

          <ActivityConfirmButton
            v-if="activeMode === 'activity' && item.role === 'assistant' && getActivityDeepConfirmCard(item) && isEditableActivityDeepConfirmCard(item) && (!hasActivityDeepSummary(getActivityDeepConfirmCard(item)) || isActivityDeepSummaryRevealed(item))"
            class="ai-component-reveal-block mt-[18px]"
            :style="getRevealStyle(2)"
            :button-text="hasBlockingActivityRules(getActivityDeepConfirmCard(item)) ? '请先修正规则' : deepConfirmSubmitText"
            :disabled="hasBlockingActivityRules(getActivityDeepConfirmCard(item))"
            @confirm="emit('confirmActivityDeep', getActivityDeepConfirmCard(item))"
          />

          <div
            v-for="card in getActivityImagePreviewCards(item)"
            :key="card.type"
            v-if="activeMode === 'activity' && item.role === 'assistant'"
            class="ai-component-reveal-block ai-activity-image-preview-card mt-[16px]"
            :style="getRevealStyle(2)"
          >
            <div class="ai-activity-image-preview-card__header">
              <div
                class="ai-activity-image-preview-card__title"
                :class="isActivityImagePreviewGenerating(card) ? 'ai-message-working-shine' : 'is-completed'"
              >
                <LottieStar
                  class="ai-activity-image-preview-card__title-star"
                  :size="isActivityImagePreviewGenerating(card) ? 28 : 18"
                  :loop="isActivityImagePreviewGenerating(card)"
                  :autoplay="isActivityImagePreviewGenerating(card)"
                />
                <span>{{ getActivityImagePreviewTitle(card) }}</span>
              </div>
            </div>
            <div
              class="ai-activity-image-preview-card__canvas"
              :class="{ 'is-generating': isActivityImagePreviewGenerating(card) }"
              :style="getActivityImagePreviewCanvasStyle(card)"
            >
              <img
                v-if="getActivityImagePreviewUrl(card)"
                :src="getActivityImagePreviewUrl(card)"
                :alt="getActivityImagePreviewTitle(card)"
                @click="emit('previewImage', getActivityImagePreviewUrl(card))"
              >
              <div v-else class="ai-activity-image-preview-card__placeholder" aria-hidden="true">
                <LottieStar
                  :size="40"
                  :loop="isActivityImagePreviewGenerating(card)"
                  :autoplay="isActivityImagePreviewGenerating(card)"
                />
              </div>
            </div>
            <div class="ai-image-preview-card__actions">
              <button
                type="button"
                class="ai-image-preview-card__action"
                :disabled="isImageActionDisabled(card)"
                :title="getActivityImageRegenerateLabel(card)"
                :aria-label="getActivityImageRegenerateLabel(card)"
                @click.stop="emit('regenerateImage', { messageId: getMessageActionKey(item), target: getActivityImageRegenerateTarget(card) })"
              >
                <svg viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M16.2 8.2a6.4 6.4 0 0 0-11-2.9L3.5 7" />
                  <path d="M3.5 3.4V7h3.6" />
                  <path d="M3.8 11.8a6.4 6.4 0 0 0 11 2.9l1.7-1.7" />
                  <path d="M16.5 16.6V13h-3.6" />
                </svg>
                <span class="ai-image-preview-card__tooltip">{{ getActivityImageRegenerateLabel(card) }}</span>
              </button>
              <button
                type="button"
                class="ai-image-preview-card__action"
                :disabled="!getActivityImagePreviewUrl(card)"
                :title="getActivityImageDownloadLabel(card)"
                :aria-label="getActivityImageDownloadLabel(card)"
                @click.stop="emit('downloadPoster', getActivityImagePreviewUrl(card))"
              >
                <i class="iconfont icon-xiazai" aria-hidden="true"></i>
                <span class="ai-image-preview-card__tooltip">{{ getActivityImageDownloadLabel(card) }}</span>
              </button>
              <span v-if="getMessageActionTime(item)" class="ai-activity-image-preview-card__timing">
                {{ getMessageActionTime(item) }}
              </span>
            </div>
          </div>

          <div
            v-if="activeMode === 'poster' && item.role === 'assistant' && getPosterImagePreviewCard(item)"
            class="ai-component-reveal-block ai-poster-preview-card mt-[16px]"
            :style="getRevealStyle(1)"
          >
            <div class="ai-poster-preview-card__header">
              <div
                class="ai-poster-preview-card__title"
                :class="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item)) ? 'ai-message-working-shine is-generating' : 'is-completed'"
              >
                <LottieStar
                  class="ai-poster-preview-card__title-star"
                  :size="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item)) ? 28 : 18"
                  :loop="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item))"
                  :autoplay="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item))"
                />
                <TextType
                  v-if="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item))"
                  :text="posterGenerationLoadingTexts"
                  as="span"
                  class-name="ai-poster-preview-card__title-type ai-message-working-shine"
                  :typing-speed="80"
                  :deleting-speed="38"
                  :pause-duration="6000"
                  cursor-character="_"
                  cursor-class-name="text-type-underline-cursor"
                  :cursor-blink-duration="0.55"
                  random
                />
                <span v-else class="ai-poster-preview-card__title-static-shine">{{ getPosterPreviewTitle(getPosterImagePreviewCard(item)) }}</span>
              </div>
              <div v-if="getPosterPreviewTimingText(getPosterImagePreviewCard(item))" class="ai-poster-preview-card__timing">
                <span>{{ getPosterPreviewTimingText(getPosterImagePreviewCard(item))?.actual }}</span>
                <span v-if="getPosterPreviewTimingText(getPosterImagePreviewCard(item))?.estimated" class="ai-poster-preview-card__timing-muted">
                  / {{ getPosterPreviewTimingText(getPosterImagePreviewCard(item))?.estimated }}
                </span>
              </div>
            </div>
            <div
              class="ai-poster-preview-card__canvas"
              :class="{ 'is-generating': isPosterPreviewCardGenerating(getPosterImagePreviewCard(item)) }"
              :style="getPosterPreviewCanvasStyle(getPosterImagePreviewCard(item))"
            >
              <img
                v-if="getPosterPreviewImageUrl(getPosterImagePreviewCard(item))"
                :src="getPosterPreviewImageUrl(getPosterImagePreviewCard(item))"
                alt="AI 生成海报"
                @click="emit('previewImage', getPosterPreviewImageUrl(getPosterImagePreviewCard(item)))"
              >
              <div v-else class="ai-poster-preview-card__placeholder" aria-hidden="true">
                <LottieStar
                  class="ai-poster-preview-card__placeholder-star"
                  :size="40"
                  :loop="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item))"
                  :autoplay="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item))"
                />
              </div>
            </div>
            <div class="ai-image-preview-card__actions">
              <button
                type="button"
                class="ai-image-preview-card__action"
                :disabled="isPosterPreviewCardGenerating(getPosterImagePreviewCard(item)) || isGenerating"
                title="重新生成海报"
                aria-label="重新生成海报"
                @click.stop="emit('regenerateImage', { messageId: getMessageActionKey(item), target: 'poster' })"
              >
                <svg viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M16.2 8.2a6.4 6.4 0 0 0-11-2.9L3.5 7" />
                  <path d="M3.5 3.4V7h3.6" />
                  <path d="M3.8 11.8a6.4 6.4 0 0 0 11 2.9l1.7-1.7" />
                  <path d="M16.5 16.6V13h-3.6" />
                </svg>
                <span class="ai-image-preview-card__tooltip">重新生成海报</span>
              </button>
              <button
                type="button"
                class="ai-image-preview-card__action"
                :disabled="!getPosterPreviewImageUrl(getPosterImagePreviewCard(item))"
                title="下载海报"
                aria-label="下载海报"
                @click.stop="emit('downloadPoster', getPosterPreviewImageUrl(getPosterImagePreviewCard(item)))"
              >
                <i class="iconfont icon-xiazai" aria-hidden="true"></i>
                <span class="ai-image-preview-card__tooltip">下载海报</span>
              </button>
            </div>
          </div>

          <PosterDeepSummaryBlock
            v-if="activeMode === 'poster' && item.role === 'assistant' && hasPosterDeepSummary(getPosterDeepConfirmCard(item))"
            class="ai-component-reveal-block mt-[22px]"
            :style="getRevealStyle(1)"
            :summary="getPosterDeepConfirmSummary(getPosterDeepConfirmCard(item))"
            :plan="getPosterDeepConfirmCard(item)?.plan"
            :animate="isLiveAssistantMessage(item)"
            @reveal-complete="markPosterDeepSummaryRevealed(item)"
          />

          <ActivityConfirmButton
            v-if="activeMode === 'poster' && item.role === 'assistant' && getPosterDeepConfirmCard(item) && isEditablePosterDeepConfirmCard(item) && (!hasPosterDeepSummary(getPosterDeepConfirmCard(item)) || isPosterDeepSummaryRevealed(item))"
            class="ai-component-reveal-block mt-[18px]"
            :style="getRevealStyle(2)"
            :button-text="deepConfirmSubmitText"
            @confirm="emit('confirmPosterDeep', getPosterDeepConfirmCard(item))"
          />

          <ActivityResultConfirmBar
            v-if="item.role === 'assistant' && shouldShowActivityResultConfirmBar && isLatestAssistantMessage(item)"
            class="ai-component-reveal-block mt-[16px]" :style="getRevealStyle(2)" :animate="isLiveAssistantMessage(item)" @adopt="emit('adopt')" @publish="emit('publish')" />
        </div>
        <div
          v-if="shouldShowMessageActions(item)"
          class="ai-message-action-bar"
          :class="item.role === 'user' ? 'ai-message-action-bar--user' : 'ai-message-action-bar--assistant'"
        >
          <span v-if="item.role === 'user' && getMessageActionTime(item)" class="ai-message-action-time">
            {{ getMessageActionTime(item) }}
          </span>
          <div class="ai-message-action-controls">
            <button
            v-if="getActionableMessageText(item)"
            type="button"
            class="ai-message-action-button"
            :class="{ 'is-copied': copiedActionKey === getMessageActionKey(item) }"
            title="复制"
            aria-label="复制"
            @click.stop="copyMessageText(getActionableMessageText(item), getMessageActionKey(item))"
          >
            <i class="iconfont icon-fuzhi" aria-hidden="true"></i>
            <span class="ai-message-action-tooltip">复制</span>
          </button>
          <button
            v-if="getActionableMessageText(item)"
            type="button"
            class="ai-message-action-button"
            title="重新生成"
            aria-label="重新生成"
            @click.stop="handleRegenerateMessage(item)"
          >
            <svg viewBox="0 0 20 20" aria-hidden="true">
              <path d="M16.2 8.2a6.4 6.4 0 0 0-11-2.9L3.5 7" />
              <path d="M3.5 3.4V7h3.6" />
              <path d="M3.8 11.8a6.4 6.4 0 0 0 11 2.9l1.7-1.7" />
              <path d="M16.5 16.6V13h-3.6" />
            </svg>
            <span class="ai-message-action-tooltip">重新生成</span>
          </button>
          </div>
          <span v-if="item.role === 'assistant' && getMessageActionTime(item)" class="ai-message-action-time">
            {{ getMessageActionTime(item) }}
          </span>
        </div>
        </div>
      </div>
    </div>
    </div>
    <button
      v-if="shouldShowScrollLatestButton"
      type="button"
      class="ai-scroll-latest-button"
      :class="{ 'is-generating': isGenerating }"
      title="回到最新"
      aria-label="回到最新"
      @click="handleScrollLatestClick"
    >
      <span class="ai-scroll-latest-button__glow" aria-hidden="true"></span>
      <span class="ai-scroll-latest-button__icon" aria-hidden="true">
        <svg viewBox="0 0 20 20">
          <path d="M10 3.5v12" />
          <path d="M5.2 10.8 10 15.5l4.8-4.7" />
        </svg>
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch, type PropType } from 'vue'
import ActivityBriefForm from './ActivityBriefForm.vue'
import ActivityConfirmButton from './ActivityConfirmButton.vue'
import ActivityDeepSummaryBlock from './ActivityDeepSummaryBlock.vue'
import ActivityProductSelector from './ActivityProductSelector.vue'
import ActivityResultConfirmBar from './ActivityResultConfirmBar.vue'
import ActivityRuleCheckBlock from './ActivityRuleCheckBlock.vue'
import ActivitySelectedResultCard from './ActivitySelectedResultCard.vue'
import ActivityStyleSelector from './ActivityStyleSelector.vue'
import LottieStar from './LottieStar.vue'
import PosterDeepSummaryBlock from './PosterDeepSummaryBlock.vue'
import PosterDeepThinkingBlock from './PosterDeepThinkingBlock.vue'
import TextType from './TextType.vue'
import ThinkingProcessCard from './ThinkingProcessCard.vue'
import {
  posterGenerationLoadingTexts,
} from '../../shared/generationLoadingCopy'

type ChatImage = {
  url: string
  name?: string
}

type ChatMessageItem = {
  id: string
  messageId?: string
  role: string
  status?: string
  content?: string
  createdAt?: string | null
  isSystem?: boolean
  cards?: any[]
  componentResult?: Record<string, any> | any[] | null
  poster?: {
    url?: string | null
  } | null
  meta?: Record<string, any> | null
}

type SelectorItem = {
  value: string
  label: string
  describe?: string
}

type ActivityGoalDurationCardOption = {
  label: string
  value: string
  describe?: string
  action?: string
}

type ActivityGoalDurationCardSection = {
  section_key: string
  title?: string
  options?: ActivityGoalDurationCardOption[]
}

type ActivityAssistantCard = {
  type: string
  card_id?: string
  title?: string
  auto_selected?: boolean
  auto_pending_creation?: boolean
  goal_label?: string
  duration_label?: string
  duration_start_time?: string | null
  duration_end_time?: string | null
  style_label?: string
  selected_items?: Array<Record<string, any>>
  item_id?: number | string | null
  item_title?: string | null
  item_price?: number | string | null
  button_text?: string
  submit_button_text?: string
  status?: string
  thinking?: string | null
  summary?: string | string[] | null
  plan?: Record<string, any> | null
  aspect_ratio?: string | null
  width?: number | null
  height?: number | null
  image_url?: string | null
  progress?: {
    step?: string
    message?: string
    progress?: number
    elapsed_seconds?: number | string | null
    actual_seconds?: number | string | null
    estimated_seconds?: number | string | null
    estimate_seconds?: number | string | null
    predicted_seconds?: number | string | null
    total_seconds?: number | string | null
    elapsed_time?: string | null
    actual_time?: string | null
    estimated_time?: string | null
    estimate_time?: string | null
    predicted_time?: string | null
  } | null
  checks?: Array<{
    code?: string
    level?: string
    message?: string
  }> | null
  rule_check?: {
    status?: string
    checks?: Array<{
      code?: string
      level?: string
      message?: string
    }>
  } | null
  elapsed_seconds?: number | string | null
  actual_seconds?: number | string | null
  estimated_seconds?: number | string | null
  estimate_seconds?: number | string | null
  predicted_seconds?: number | string | null
  total_seconds?: number | string | null
  elapsed_time?: string | null
  actual_time?: string | null
  estimated_time?: string | null
  estimate_time?: string | null
  predicted_time?: string | null
  poster?: {
    url?: string
  } | null
  options?: SelectorItem[]
  sections?: ActivityGoalDurationCardSection[]
}

const props = defineProps({
  activeMode: {
    type: String as PropType<'activity' | 'poster'>,
    required: true,
  },
  isMockPreviewMode: {
    type: Boolean,
    default: false,
  },
  isGenerating: {
    type: Boolean,
    default: false,
  },
  shouldShowActivityBriefForm: {
    type: Boolean,
    required: true,
  },
  selectedActivityGoal: {
    type: String,
    required: true,
  },
  selectedActivityDuration: {
    type: String,
    required: true,
  },
  activityGoalOptions: {
    type: Array as PropType<SelectorItem[]>,
    required: true,
  },
  activityDurationOptions: {
    type: Array as PropType<SelectorItem[]>,
    required: true,
  },
  activityDateRange: {
    type: Object as PropType<{ start: string, end: string }>,
    required: true,
  },
  showActivityGoalSelector: {
    type: Boolean,
    required: true,
  },
  showActivityDateSelector: {
    type: Boolean,
    required: true,
  },
  isActivityBriefReadonly: {
    type: Boolean,
    required: true,
  },
  shouldShowActivityProductSelector: {
    type: Boolean,
    required: true,
  },
  activityProductOptions: {
    type: Array as PropType<any[]>,
    required: true,
  },
  activityProductRequirement: {
    type: String,
    required: true,
  },
  selectedActivityProductIds: {
    type: Array as PropType<string[]>,
    required: true,
  },
  isActivityProductsLoading: {
    type: Boolean,
    required: true,
  },
  shouldShowActivityStyleSelector: {
    type: Boolean,
    required: true,
  },
  activityStyleOptions: {
    type: Array as PropType<any[]>,
    required: true,
  },
  selectedActivityStyle: {
    type: String,
    required: true,
  },
  activityStyleRequirement: {
    type: String,
    required: true,
  },
  shouldShowActivityResultConfirmBar: {
    type: Boolean,
    required: true,
  },
  currentPrompt: {
    type: String,
    required: true,
  },
  shouldShowThinkingProcessCard: {
    type: Boolean,
    required: true,
  },
  thinkingProcessStatus: {
    type: String as PropType<'thinking' | 'completed'>,
    required: true,
  },
  thinkingProcessSummaryItems: {
    type: Array as PropType<string[]>,
    required: true,
  },
  posterAspectRatio: {
    type: String,
    default: '3:4',
  },
  chatMessages: {
    type: Array as PropType<ChatMessageItem[]>,
    required: true,
  },
  getMessageDisplayContent: {
    type: Function as PropType<(message: any) => string>,
    required: true,
  },
  getMessageImageAttachments: {
    type: Function as PropType<(message: any) => ChatImage[]>,
    required: true,
  },
})

const emit = defineEmits<{
  (e: 'update:goalValue', value: string): void
  (e: 'update:dateValue', value: string): void
  (e: 'update:startValue', value: string): void
  (e: 'update:endValue', value: string): void
  (e: 'skipGoal'): void
  (e: 'skipDate'): void
  (e: 'confirmBrief'): void
  (e: 'update:selectedProductIds', value: string[]): void
  (e: 'update:productRequirement', value: string): void
  (e: 'skipProduct'): void
  (e: 'confirmProduct'): void
  (e: 'update:selectedStyle', value: string): void
  (e: 'update:styleRequirement', value: string): void
  (e: 'skipStyle'): void
  (e: 'confirmStyle'): void
  (e: 'confirmActivityDeep', card: ActivityAssistantCard | null): void
  (e: 'confirmPosterDeep', card: ActivityAssistantCard | null): void
  (e: 'adopt'): void
  (e: 'publish'): void
  (e: 'previewImage', url: string): void
  (e: 'regenerateMessage', payload: { id: string, role: string, content: string }): void
  (e: 'regenerateImage', payload: { messageId: string, target: 'activity_cover' | 'activity_detail' | 'activity_item_cover' | 'poster', itemId?: number }): void
  (e: 'reselectItems'): void
  (e: 'downloadPoster', url: string): void
}>()

const messageWindowRef = ref<HTMLDivElement | null>(null)
const messageStackRef = ref<HTMLDivElement | null>(null)
const deepConfirmSubmitText = '确认并开始生成'
const scrollTargetTop = ref(0)
const isUserReadingHistory = ref(false)
const shouldStickToBottom = ref(true)
const shouldShowScrollLatestButton = ref(false)
const isScrollNearBottom = ref(true)
const copiedActionKey = ref('')
const revealedActivityDeepSummaryCardIds = ref(new Set<string>())
const revealedPosterDeepSummaryCardIds = ref(new Set<string>())
let scrollAnimationFrame: number | null = null
let contentResizeObserver: ResizeObserver | null = null
let copiedActionTimer: ReturnType<typeof window.setTimeout> | null = null
let lastScrollTop = 0
const visibleChatMessages = computed(() => props.chatMessages.filter(item => !item.isSystem))
const latestAssistantMessageId = computed(() =>
  [...props.chatMessages].reverse().find(item => item.role === 'assistant')?.messageId
  || [...props.chatMessages].reverse().find(item => item.role === 'assistant')?.id
  || '',
)
const latestVisibleUserMessage = computed(() =>
  [...visibleChatMessages.value].reverse().find(item => item.role === 'user') || null,
)
const latestVisibleMessage = computed(() => visibleChatMessages.value[visibleChatMessages.value.length - 1] || null)

const messageScrollSignature = computed(() => {
  const lastMessage = props.chatMessages[props.chatMessages.length - 1]
  return JSON.stringify({
    messageCount: props.chatMessages.length,
    lastMessageId: lastMessage?.id || '',
    lastMessageRole: lastMessage?.role || '',
    lastMessageStatus: lastMessage?.status || '',
    lastMessageContent: lastMessage ? props.getMessageDisplayContent(lastMessage) || '' : '',
    lastMessageImageCount: lastMessage ? props.getMessageImageAttachments(lastMessage).length : 0,
    lastMessageCardCount: lastMessage?.cards?.length || 0,
    currentPrompt: props.currentPrompt,
    showThinkingProcessCard: props.shouldShowThinkingProcessCard,
    thinkingProcessStatus: props.thinkingProcessStatus,
    thinkingProcessSummarySize: props.thinkingProcessSummaryItems.length,
    showActivityBriefForm: props.shouldShowActivityBriefForm,
    isActivityBriefReadonly: props.isActivityBriefReadonly,
    showActivityProductSelector: props.shouldShowActivityProductSelector,
    showActivityStyleSelector: props.shouldShowActivityStyleSelector,
    showActivityResultConfirmBar: props.shouldShowActivityResultConfirmBar,
  })
})

const latestUserTurnScrollSignature = computed(() => {
  const latestUserMessage = latestVisibleUserMessage.value
  return JSON.stringify({
    latestUserMessageId: latestUserMessage?.id || '',
    latestUserMessageKey: latestUserMessage ? getMessageActionKey(latestUserMessage) : '',
    latestUserMessageContent: latestUserMessage ? props.getMessageDisplayContent(latestUserMessage) || '' : '',
    latestUserMessageImageCount: latestUserMessage ? props.getMessageImageAttachments(latestUserMessage).length : 0,
    currentPrompt: props.currentPrompt,
  })
})

const shouldShowGlobalThinkingProcessCard = computed(() =>
  props.activeMode !== 'poster'
  && props.isMockPreviewMode
  && !(props.activeMode === 'activity' && visibleChatMessages.value.some(shouldShowActivityDeepThinkingBlock))
  && props.shouldShowThinkingProcessCard,
)

const showActivityGoalSection = computed(() =>
  props.isMockPreviewMode
  ||
  props.shouldShowActivityBriefForm && (props.showActivityGoalSelector || props.isActivityBriefReadonly),
)

const showActivityDateSection = computed(() =>
  props.isMockPreviewMode
  ||
  props.shouldShowActivityBriefForm && (props.showActivityDateSelector || props.isActivityBriefReadonly),
)

function getActivityGoalDurationCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'activity_goal_duration_selector') || null
}

function getActivityItemSelectorCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'activity_item_selector') || null
}

function getActivityStyleSelectorCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'activity_style_selector') || null
}

function getActivityDeepConfirmCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'activity_deep_confirm') || null
}

function getActivityRuleCheckCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'activity_rule_check') || null
}

function hasBlockingActivityRules(card: ActivityAssistantCard | null) {
  const ruleCheck = card?.rule_check && typeof card.rule_check === 'object'
    ? card.rule_check as Record<string, any>
    : null
  return ruleCheck?.status === 'blocked'
}

function getPosterImagePreviewCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'poster_image_preview') || null
}

function getPosterDeepConfirmCard(message: ChatMessageItem) {
  return (message.cards || []).find(card => card.type === 'poster_deep_confirm') || null
}

function getActivityImagePreviewCards(message: ChatMessageItem) {
  return (message.cards || [])
    .filter(card => ['activity_cover_preview', 'activity_detail_preview'].includes(card.type))
    .sort((left, right) => {
      const order = (card: ActivityAssistantCard) => card.type === 'activity_cover_preview' ? 0 : 1
      return order(left) - order(right)
    })
}

function getActivityItemImagePreviewCards(message: ChatMessageItem) {
  return (message.cards || [])
    .filter(card => card.type === 'activity_item_cover_preview')
}

function getMessageIndex(message: ChatMessageItem) {
  return props.chatMessages.findIndex(item => item.id === message.id)
}

function getPreviousUserMessage(message: ChatMessageItem) {
  const messageIndex = getMessageIndex(message)
  if (messageIndex === -1)
    return null

  for (let index = messageIndex - 1; index >= 0; index -= 1) {
    const previousMessage = props.chatMessages[index]
    if (previousMessage.role === 'user')
      return previousMessage
  }

  return null
}

function isPosterDeepConfirmResult(result: unknown) {
  if (!result || Array.isArray(result) || typeof result !== 'object')
    return false

  const rawResult = result as Record<string, any>
  return rawResult.component_type === 'poster_deep_confirm'
    || rawResult.step_key === 'poster_deep_confirm'
}

function isActivityDeepConfirmResult(result: unknown) {
  if (!result || Array.isArray(result) || typeof result !== 'object')
    return false

  const rawResult = result as Record<string, any>
  return rawResult.component_type === 'activity_deep_confirm'
    || rawResult.step_key === 'activity_deep_confirm'
}

function isTriggeredByPosterDeepConfirm(message: ChatMessageItem) {
  return isPosterDeepConfirmResult(getPreviousUserMessage(message)?.componentResult)
}

function isTriggeredByActivityDeepConfirm(message: ChatMessageItem) {
  return isActivityDeepConfirmResult(getPreviousUserMessage(message)?.componentResult)
}

function getActionableMessageText(message: ChatMessageItem) {
  return props.getMessageDisplayContent(message).trim()
}

function shouldShowMessageActions(message: ChatMessageItem) {
  if (isAssistantWorkingMessage(message))
    return false
  if (message.role === 'assistant' && (message.cards || []).length > 0)
    return false
  return !!getActionableMessageText(message)
}

function normalizeAiDisplayText(value: string) {
  return value
    .replace(/\\r\\n/g, '\n')
    .replace(/\\n/g, '\n')
    .replace(/\r\n/g, '\n')
}

function getMessageActionKey(message: ChatMessageItem) {
  return message.messageId || message.id
}

function padTimeNumber(value: number) {
  return String(value).padStart(2, '0')
}

function formatClockTime(date: Date) {
  return `${padTimeNumber(date.getHours())}:${padTimeNumber(date.getMinutes())}`
}

function isSameDate(left: Date, right: Date) {
  return left.getFullYear() === right.getFullYear()
    && left.getMonth() === right.getMonth()
    && left.getDate() === right.getDate()
}

function parseActionTime(value?: string | null) {
  if (!value)
    return null
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : date
}

function formatReadableActionTime(date: Date) {
  const now = new Date()
  const yesterday = new Date(now)
  yesterday.setDate(now.getDate() - 1)

  if (isSameDate(date, now))
    return formatClockTime(date)

  if (isSameDate(date, yesterday))
    return `昨天 ${formatClockTime(date)}`

  if (date.getFullYear() === now.getFullYear())
    return `${date.getMonth() + 1}月${date.getDate()}日 ${formatClockTime(date)}`

  return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日 ${formatClockTime(date)}`
}

function getMessageActionTime(message: ChatMessageItem) {
  const date = parseActionTime(message.createdAt)
  return date ? formatReadableActionTime(date) : ''
}

function getCurrentPromptActionTime() {
  return formatReadableActionTime(new Date())
}

function fallbackCopyText(text: string) {
  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.setAttribute('readonly', 'true')
  textarea.style.position = 'fixed'
  textarea.style.left = '-9999px'
  textarea.style.top = '0'
  document.body.appendChild(textarea)
  textarea.select()
  document.execCommand('copy')
  document.body.removeChild(textarea)
}

async function copyMessageText(text: string, key: string) {
  const content = text.trim()
  if (!content)
    return

  try {
    if (navigator.clipboard?.writeText)
      await navigator.clipboard.writeText(content)
    else
      fallbackCopyText(content)
    copiedActionKey.value = key
    if (copiedActionTimer)
      window.clearTimeout(copiedActionTimer)
    copiedActionTimer = window.setTimeout(() => {
      if (copiedActionKey.value === key)
        copiedActionKey.value = ''
      copiedActionTimer = null
    }, 1200)
  }
  catch {
    fallbackCopyText(content)
    copiedActionKey.value = key
  }
}

function handleRegenerateCurrentPrompt() {
  const content = props.currentPrompt.trim()
  if (!content)
    return
  emit('regenerateMessage', {
    id: 'current-prompt',
    role: 'user',
    content,
  })
}

function handleRegenerateMessage(message: ChatMessageItem) {
  emit('regenerateMessage', {
    id: getMessageActionKey(message),
    role: message.role,
    content: getActionableMessageText(message),
  })
}

function getCardComponentResult(message: ChatMessageItem, cardId?: string) {
  if (!cardId)
    return null

  const messageIndex = getMessageIndex(message)
  if (messageIndex === -1)
    return null

  for (let index = messageIndex + 1; index < props.chatMessages.length; index += 1) {
    const nextMessage = props.chatMessages[index]
    if (nextMessage.role !== 'user')
      continue
    if (!nextMessage.componentResult || Array.isArray(nextMessage.componentResult))
      continue
    if (nextMessage.componentResult.card_id === cardId)
      return nextMessage.componentResult
  }

  return null
}

function isLatestAssistantMessage(message: ChatMessageItem) {
  return (message.messageId || message.id) === latestAssistantMessageId.value
}

function isEditableBriefCard(message: ChatMessageItem) {
  const card = getActivityGoalDurationCard(message)
  if (!card)
    return false
  if (props.isMockPreviewMode)
    return !getCardComponentResult(message, card.card_id)
  return props.shouldShowActivityBriefForm
    && isLatestAssistantMessage(message)
    && !getCardComponentResult(message, card.card_id)
}

function isEditableProductCard(message: ChatMessageItem) {
  const card = getActivityItemSelectorCard(message)
  if (!card)
    return false
  if (card.auto_selected === true)
    return false
  if (props.isMockPreviewMode)
    return !getCardComponentResult(message, card.card_id)
  return props.shouldShowActivityProductSelector
    && isLatestAssistantMessage(message)
    && !getCardComponentResult(message, card.card_id)
}

function isPendingAutoItemCard(message: ChatMessageItem) {
  const card = getActivityItemSelectorCard(message)
  return card?.auto_selected === true && card.auto_pending_creation === true
}

function isRegenerationRequestMessage(message: ChatMessageItem | null): boolean {
  if (!message)
    return false

  const results = Array.isArray(message.componentResult)
    ? message.componentResult
    : [message.componentResult]

  return results.some((result) => {
    const componentType = String(result?.component_type || '')
    const stepKey = String(result?.step_key || '')
    return componentType === 'activity_image_regenerate'
      || stepKey === 'activity_image_regenerate'
      || stepKey === 'activity_item_cover_regenerate'
  })
}

function isRegenerationResponseMessage(message: ChatMessageItem): boolean {
  return message.role === 'assistant' && isRegenerationRequestMessage(getPreviousUserMessage(message))
}

function isEditableStyleCard(message: ChatMessageItem) {
  const card = getActivityStyleSelectorCard(message)
  if (!card)
    return false
  if (props.isMockPreviewMode)
    return !getCardComponentResult(message, card.card_id)
  return props.shouldShowActivityStyleSelector
    && isLatestAssistantMessage(message)
    && !getCardComponentResult(message, card.card_id)
}

function isEditablePosterDeepConfirmCard(message: ChatMessageItem) {
  const card = getPosterDeepConfirmCard(message)
  if (!card)
    return false
  if (props.isMockPreviewMode)
    return !getCardComponentResult(message, card.card_id)
  return isLatestAssistantMessage(message)
    && !getCardComponentResult(message, card.card_id)
}

function isEditableActivityDeepConfirmCard(message: ChatMessageItem) {
  const card = getActivityDeepConfirmCard(message)
  if (!card)
    return false
  if (props.isMockPreviewMode)
    return !getCardComponentResult(message, card.card_id)
  return isLatestAssistantMessage(message)
    && !getCardComponentResult(message, card.card_id)
}

function getActivityDeepSummaryRevealKey(message: ChatMessageItem) {
  return getActivityDeepConfirmCard(message)?.card_id || getMessageActionKey(message)
}

function markActivityDeepSummaryRevealed(message: ChatMessageItem) {
  const key = getActivityDeepSummaryRevealKey(message)
  if (revealedActivityDeepSummaryCardIds.value.has(key))
    return

  const next = new Set(revealedActivityDeepSummaryCardIds.value)
  next.add(key)
  revealedActivityDeepSummaryCardIds.value = next
}

function isActivityDeepSummaryRevealed(message: ChatMessageItem) {
  return revealedActivityDeepSummaryCardIds.value.has(getActivityDeepSummaryRevealKey(message))
}

function getPosterDeepSummaryRevealKey(message: ChatMessageItem) {
  return getPosterDeepConfirmCard(message)?.card_id || getMessageActionKey(message)
}

function markPosterDeepSummaryRevealed(message: ChatMessageItem) {
  const key = getPosterDeepSummaryRevealKey(message)
  if (revealedPosterDeepSummaryCardIds.value.has(key))
    return

  const next = new Set(revealedPosterDeepSummaryCardIds.value)
  next.add(key)
  revealedPosterDeepSummaryCardIds.value = next
}

function isPosterDeepSummaryRevealed(message: ChatMessageItem) {
  return revealedPosterDeepSummaryCardIds.value.has(getPosterDeepSummaryRevealKey(message))
}

function getDeepConfirmSummary(card: ActivityAssistantCard | null) {
  if (Array.isArray(card?.summary))
    return card.summary.map(item => String(item)).filter(Boolean).join('\n')
  return typeof card?.summary === 'string' ? card.summary : ''
}

function getActivityDeepConfirmSummary(card: ActivityAssistantCard | null) {
  return getDeepConfirmSummary(card)
}

function getPosterDeepConfirmSummary(card: ActivityAssistantCard | null) {
  return getDeepConfirmSummary(card)
}

function hasActivityDeepPlan(card: ActivityAssistantCard | null) {
  if (!card?.plan || typeof card.plan !== 'object')
    return false

  return [
    'title',
    'activity_title',
    'key_copy',
    'slogan',
    'benefit',
    'notes',
    'goal',
    'goal_title',
    'goal_label',
    'duration',
    'date_range',
    'items',
    'products',
    'selected_items',
    'style',
    'style_title',
    'style_label',
  ].some((key) => {
    const value = card.plan?.[key]
    if (Array.isArray(value))
      return value.length > 0
    if (value && typeof value === 'object')
      return Object.keys(value).length > 0
    return typeof value === 'string' && value.trim() !== ''
  })
}

function hasPosterDeepPlan(card: ActivityAssistantCard | null) {
  if (!card?.plan || typeof card.plan !== 'object')
    return false

  return ['style', 'style_title', 'aspect_ratio', 'image_model', 'image_model_title', 'title', 'key_copy', 'notes']
    .some(key => typeof card.plan?.[key] === 'string' && card.plan[key].trim() !== '')
}

function hasPosterDeepSummary(card: ActivityAssistantCard | null) {
  return !!getPosterDeepConfirmSummary(card) || hasPosterDeepPlan(card)
}

function hasActivityDeepSummary(card: ActivityAssistantCard | null) {
  return !!getActivityDeepConfirmSummary(card) || hasActivityDeepPlan(card)
}

function shouldShowActivityDeepThinkingBlock(message: ChatMessageItem) {
  if (props.activeMode !== 'activity' || message.role !== 'assistant')
    return false

  const card = getActivityDeepConfirmCard(message)
  if (card)
    return hasActivityDeepThinkingContent(message)

  if (isTriggeredByActivityDeepConfirm(message))
    return false

  return props.shouldShowThinkingProcessCard
    && isLatestAssistantMessage(message)
    && ['pending', 'streaming'].includes(message.status || '')
    && hasActivityDeepThinkingContent(message)
}

function shouldShowPosterDeepThinkingBlock(message: ChatMessageItem) {
  if (props.activeMode !== 'poster' || message.role !== 'assistant')
    return false

  const card = getPosterDeepConfirmCard(message)
  if (card)
    return hasPosterDeepThinkingContent(message)

  if (isTriggeredByPosterDeepConfirm(message))
    return false

  return props.shouldShowThinkingProcessCard
    && isLatestAssistantMessage(message)
    && ['pending', 'streaming'].includes(message.status || '')
    && hasPosterDeepThinkingContent(message)
}

function getActivityDeepThinkingStatus(message: ChatMessageItem): 'thinking' | 'completed' {
  return getActivityDeepConfirmCard(message) ? 'completed' : 'thinking'
}

function getPosterDeepThinkingStatus(message: ChatMessageItem): 'thinking' | 'completed' {
  return getPosterDeepConfirmCard(message) ? 'completed' : 'thinking'
}

function getDeepThinkingStatus(message: ChatMessageItem): 'thinking' | 'completed' {
  return shouldShowActivityDeepThinkingBlock(message)
    ? getActivityDeepThinkingStatus(message)
    : getPosterDeepThinkingStatus(message)
}

function getActivityDeepThinkingText(message: ChatMessageItem) {
  const thinkingText = String(message.meta?.deep_thinking_text || '').trim()
  if (isLiveAssistantMessage(message) && thinkingText)
    return normalizeAiDisplayText(thinkingText)

  const card = getActivityDeepConfirmCard(message)
  if (typeof card?.thinking === 'string' && card.thinking.trim())
    return normalizeAiDisplayText(card.thinking)

  if (thinkingText)
    return normalizeAiDisplayText(thinkingText)

  const content = props.getMessageDisplayContent(message).trim()
  if (content)
    return content

  return ''
}

function getPosterDeepThinkingText(message: ChatMessageItem) {
  const thinkingText = String(message.meta?.deep_thinking_text || '').trim()
  if (isLiveAssistantMessage(message) && thinkingText)
    return normalizeAiDisplayText(thinkingText)

  const card = getPosterDeepConfirmCard(message)
  if (typeof card?.thinking === 'string' && card.thinking.trim())
    return normalizeAiDisplayText(card.thinking)

  if (thinkingText)
    return normalizeAiDisplayText(thinkingText)

  const content = props.getMessageDisplayContent(message).trim()
  if (content)
    return content

  return ''
}

function hasActivityDeepThinkingContent(message: ChatMessageItem) {
  return getActivityDeepThinkingText(message).trim() !== ''
}

function hasPosterDeepThinkingContent(message: ChatMessageItem) {
  return getPosterDeepThinkingText(message).trim() !== ''
}

function getDeepThinkingText(message: ChatMessageItem) {
  return shouldShowActivityDeepThinkingBlock(message)
    ? getActivityDeepThinkingText(message)
    : getPosterDeepThinkingText(message)
}

function shouldShowMessageText(message: ChatMessageItem) {
  if (shouldShowPosterDeepThinkingBlock(message) || shouldShowActivityDeepThinkingBlock(message))
    return false
  return !!props.getMessageDisplayContent(message)
}

function getBriefCardGoalValue(message: ChatMessageItem) {
  const card = getActivityGoalDurationCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  return result?.status === 'submitted' ? String(result.goal?.value || '') : ''
}

function getBriefCardDateValue(message: ChatMessageItem) {
  const card = getActivityGoalDurationCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  return result?.status === 'submitted' ? String(result.duration?.value || '') : ''
}

function getBriefCardDatePart(value?: string | null) {
  return typeof value === 'string' ? value.slice(0, 10) : ''
}

function getSelectedBriefTextItems(message: ChatMessageItem) {
  const card = getActivityGoalDurationCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  const goalLabel = result?.goal?.label ? `已选择 ${result.goal.label}` : ''
  let durationLabel = ''
  if (result?.duration?.label) {
    durationLabel = result.duration.label
    if (result.duration.start_time && result.duration.end_time)
      durationLabel = `${getBriefCardDatePart(result.duration.start_time)} - ${getBriefCardDatePart(result.duration.end_time)}`
    durationLabel = `已选择 ${durationLabel}`
  }
  return {
    goal: goalLabel,
    duration: durationLabel,
  }
}

function getProductCardSummaryText(message: ChatMessageItem) {
  const card = getActivityItemSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  if (card?.auto_selected === true) {
    const count = Array.isArray(card.selected_items) ? card.selected_items.length : 0
    if (card.auto_pending_creation === true)
      return count ? `已识别并待创建 ${count} 个商品` : '已识别待创建商品'
    return count ? `已自动创建并选中 ${count} 个商品` : '已自动创建并选中商品'
  }
  if (!result)
    return '待选择商品'
  if (result.status === 'skipped')
    return '已跳过商品选择'
  const count = Array.isArray(result.items) ? result.items.length : 0
  return count ? `已选择 ${count} 个商品` : '已完成商品选择'
}

function getProductCardSummaryItems(message: ChatMessageItem) {
  const card = getActivityItemSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  const summaryItems = Array.isArray(result?.items)
    ? result.items.map((item: Record<string, any>) => String(item.title || '')).filter(Boolean)
    : []
  const requirement = typeof result?.item_requirement === 'string' ? result.item_requirement.trim() : ''
  return requirement ? [...summaryItems, `补充诉求：${requirement}`] : summaryItems
}

function getProductCardRequirement(message: ChatMessageItem) {
  const card = getActivityItemSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  return typeof result?.item_requirement === 'string' ? result.item_requirement.trim() : ''
}

function pickProductDisplayValue(...values: unknown[]) {
  for (const value of values) {
    if (value !== null && value !== undefined && String(value).trim() !== '')
      return value
  }
  return ''
}

function formatSelectedProductPrice(value: unknown) {
  const pickedValue = pickProductDisplayValue(value)
  if (pickedValue === '')
    return '--'

  const text = String(pickedValue).trim()
  if (text.startsWith('¥'))
    return text

  const amount = Number(text)
  return Number.isFinite(amount) ? `¥${amount.toFixed(2)}` : text
}

function formatSelectedProductStock(value: unknown) {
  const pickedValue = pickProductDisplayValue(value)
  if (pickedValue === '')
    return '--'

  const stock = Number(pickedValue)
  return Number.isFinite(stock) && stock === 0 ? '不限' : String(pickedValue)
}

function resolveSelectedProductTypeTone(item: Record<string, any>, matchedProduct?: any) {
  if (matchedProduct?.typeTone)
    return matchedProduct.typeTone

  const typeLabel = String(pickProductDisplayValue(item.type_label, item.typeLabel, matchedProduct?.typeLabel))
  if (typeLabel === '储值卡')
    return 'orange'
  if (typeLabel === '单品')
    return 'red'
  return 'green'
}

function getSelectedProductDisplayItems(message: ChatMessageItem) {
  const card = getActivityItemSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  const selectedItems = Array.isArray(result?.items)
    ? result.items
    : (card?.auto_selected === true && Array.isArray(card.selected_items) ? card.selected_items : [])
  if (selectedItems.length === 0)
    return []

  return selectedItems.map((item: Record<string, any>, index: number) => {
    const matchedProduct = props.activityProductOptions.find((product: any) =>
      String(product.rawItem?.id || product.id) === String(item.item_id || item.id),
    )
    return {
      name: String(item.title || matchedProduct?.name || `已选项目 ${index + 1}`),
      image: String(pickProductDisplayValue(matchedProduct?.image, item.image, item.cover, item.cover_img)),
      typeLabel: String(pickProductDisplayValue(
        matchedProduct?.typeLabel,
        item.type_label,
        item.typeLabel,
        item.item_type === 'voucher' ? '单品' : '套餐',
      )),
      typeTone: resolveSelectedProductTypeTone(item, matchedProduct),
      stock: formatSelectedProductStock(pickProductDisplayValue(matchedProduct?.stock, item.stock, item.stock_value)),
      price: formatSelectedProductPrice(pickProductDisplayValue(matchedProduct?.price, item.price, item.base_price)),
    }
  })
}

function getStyleCardSummaryText(message: ChatMessageItem) {
  const card = getActivityStyleSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  if (!result)
    return '待选择风格'
  if (result.status === 'skipped')
    return '已跳过风格选择'
  return result?.style?.label ? `已选择风格：${result.style.label}` : '已完成风格选择'
}

function getStyleCardSummaryItems(message: ChatMessageItem) {
  const card = getActivityStyleSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  const requirement = typeof result?.style_requirement === 'string' ? result.style_requirement.trim() : ''
  return requirement ? [`补充诉求：${requirement}`] : []
}

function getStyleCardRequirement(message: ChatMessageItem) {
  const card = getActivityStyleSelectorCard(message)
  const result = getCardComponentResult(message, card?.card_id)
  return typeof result?.style_requirement === 'string' ? result.style_requirement.trim() : ''
}

function getCardSection(
  card: ActivityAssistantCard | null,
  sectionKey: 'goal' | 'duration',
) {
  return card?.sections?.find(section => section.section_key === sectionKey) || null
}

function getCardSectionTitle(
  card: ActivityAssistantCard | null,
  sectionKey: 'goal' | 'duration',
  fallbackTitle: string,
) {
  return getCardSection(card, sectionKey)?.title || fallbackTitle
}

function getCardSectionOptions(
  card: ActivityAssistantCard | null,
  sectionKey: 'goal' | 'duration',
  fallbackOptions: SelectorItem[],
) {
  const options = getCardSection(card, sectionKey)?.options
  if (!options?.length)
    return fallbackOptions
  return options.map(option => ({
    value: option.value,
    label: option.label,
    describe: option.describe,
  }))
}

function parseAspectRatio(value: unknown, fallbackWidth = 3, fallbackHeight = 4) {
  const ratio = String(value || '').trim()
  const matched = ratio.match(/^(\d+(?:\.\d+)?):(\d+(?:\.\d+)?)$/)
  if (!matched)
    return fallbackHeight / fallbackWidth

  const width = Number(matched[1])
  const height = Number(matched[2])
  if (!Number.isFinite(width) || !Number.isFinite(height) || width <= 0 || height <= 0)
    return fallbackHeight / fallbackWidth

  return height / width
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

function getPosterPreviewTimingText(card: ActivityAssistantCard | null) {
  const progress = card?.progress && typeof card.progress === 'object'
    ? card.progress as Record<string, any>
    : null
  const actualValue = getFirstTimingValue(card, ['actual', 'elapsed', 'used', 'cost', 'duration', 'actual_time', 'elapsed_time', 'used_time', 'cost_time', 'actual_seconds', 'elapsed_seconds', 'used_seconds', 'cost_seconds', 'duration_seconds'])
    ?? getFirstTimingValue(progress, ['actual', 'elapsed', 'used', 'cost', 'duration', 'actual_time', 'elapsed_time', 'used_time', 'cost_time', 'actual_seconds', 'elapsed_seconds', 'used_seconds', 'cost_seconds', 'duration_seconds'])
  const estimatedValue = getFirstTimingValue(card, ['estimated', 'estimate', 'predicted', 'predict', 'expected', 'total', 'estimated_time', 'estimate_time', 'predicted_time', 'predict_time', 'expected_time', 'total_time', 'estimated_seconds', 'estimate_seconds', 'predicted_seconds', 'predict_seconds', 'expected_seconds', 'total_seconds'])
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

function normalizePosterPreviewTitle(title: string) {
  return title
    .replace(/[（(]?\s*已用时\s*\d+(?:\.\d+)?\s*秒\s*[）)]?/g, '')
    .replace(/\s{2,}/g, ' ')
    .trim()
}

function getPosterPreviewTitle(card: ActivityAssistantCard | null) {
  if (!card)
    return '快灵正在生成海报'

  if (card.status === 'completed')
    return normalizePosterPreviewTitle(card.title || '') || '海报已生成'
  if (card.status === 'failed')
    return normalizePosterPreviewTitle(card.title || '') || '海报生成失败'
  if (card.status === 'stopped')
    return normalizePosterPreviewTitle(card.title || '') || '海报生成已停止'

  return normalizePosterPreviewTitle(card.title || '') || '快灵正在生成海报'
}

function getPosterPreviewImageUrl(card: ActivityAssistantCard | null) {
  return String(card?.image_url || card?.poster?.url || '').trim()
}

function isPosterPreviewCardGenerating(card: ActivityAssistantCard | null) {
  return !getPosterPreviewImageUrl(card) && !['completed', 'failed', 'stopped'].includes(card?.status || '')
}

function getPosterPreviewCanvasStyle(card: ActivityAssistantCard | null) {
  const rawWidth = Number(card?.width)
  const rawHeight = Number(card?.height)
  const ratio = Number.isFinite(rawWidth) && Number.isFinite(rawHeight) && rawWidth > 0 && rawHeight > 0
    ? rawHeight / rawWidth
    : parseAspectRatio(card?.aspect_ratio || props.posterAspectRatio)

  return {
    aspectRatio: `1 / ${ratio}`,
  }
}

function getActivityImagePreviewUrl(card: ActivityAssistantCard) {
  return String(card.image_url || '').trim()
}

type ActivityImagePreviewState = 'creating' | 'queued' | 'prompt_ready' | 'generating' | 'uploading' | 'finalizing' | 'completed' | 'failed' | 'stopped'

function getActivityImagePreviewState(card: ActivityAssistantCard): ActivityImagePreviewState {
  const status = String(card.status || '').trim().toLowerCase()
  const progress = card.progress && typeof card.progress === 'object'
    ? card.progress as Record<string, any>
    : null
  const step = String(progress?.step || '').trim().toLowerCase()

  if (status === 'stopped' || step === 'stopped')
    return 'stopped'
  if (['failed', 'error'].includes(status) || ['failed', 'error'].includes(step))
    return 'failed'
  if (getActivityImagePreviewUrl(card))
    return 'completed'
  if (status === 'completed' || step === 'completed')
    return 'finalizing'
  if (status === 'creating' || step === 'creating')
    return 'creating'
  if (step === 'queued')
    return 'queued'
  if (step === 'prompt_ready')
    return 'prompt_ready'
  if (step === 'uploading')
    return 'uploading'

  return 'generating'
}

function isActivityImagePreviewGenerating(card: ActivityAssistantCard) {
  return ['creating', 'queued', 'prompt_ready', 'generating', 'uploading', 'finalizing'].includes(getActivityImagePreviewState(card))
}

function getActivityImagePreviewTitle(card: ActivityAssistantCard) {
  const itemTitle = String(card.item_title || '').trim()
  const isItemCover = card.type === 'activity_item_cover_preview'
  const targetLabel = isItemCover
    ? `商品「${itemTitle || '项目'}」图`
    : (card.type === 'activity_detail_preview' ? '活动详情图' : '活动主图')
  const state = getActivityImagePreviewState(card)
  const progressMessage = String(card.progress?.message || '').trim()

  if (state === 'completed')
    return isItemCover ? `商品「${itemTitle || '项目'}」已创建完成，商品图已生成` : `${targetLabel}已生成`
  if (state === 'finalizing')
    return `${targetLabel}已生成，正在加载预览...`
  if (state === 'failed')
    return progressMessage || (isItemCover ? `商品「${itemTitle || '项目'}」已创建完成，但商品图暂未生成成功` : `${targetLabel}生成失败`)
  if (state === 'stopped')
    return `${targetLabel}生成已停止`

  // 图片卡的旧 title 可能来自前一阶段；进行中始终优先使用本轮 progress.message。
  if (progressMessage)
    return progressMessage
  if (state === 'queued')
    return `${targetLabel}已进入生成队列...`
  if (state === 'creating')
    return isItemCover ? `快灵正在创建商品「${itemTitle || '项目'}」...` : `快灵正在创建${targetLabel}...`
  if (state === 'prompt_ready')
    return `快灵正在整理${targetLabel}的画面要求...`
  if (state === 'uploading')
    return `${targetLabel}已完成，快灵正在整理结果...`
  return `快灵正在生成${targetLabel}，预计需要 30-90 秒，请稍候...`
}

function getActivityImageRegenerateTarget(card: ActivityAssistantCard): 'activity_cover' | 'activity_detail' {
  return card.type === 'activity_detail_preview' ? 'activity_detail' : 'activity_cover'
}

function getActivityImageRegenerateLabel(card: ActivityAssistantCard) {
  return card.type === 'activity_detail_preview' ? '重新生成详情图' : '重新生成活动主图'
}

function getActivityImageDownloadLabel(card: ActivityAssistantCard) {
  return card.type === 'activity_detail_preview' ? '下载详情图' : '下载活动主图'
}

function isImageActionDisabled(card: ActivityAssistantCard) {
  return isActivityImagePreviewGenerating(card) || props.isGenerating
}

function getActivityImagePreviewCanvasStyle(card: ActivityAssistantCard) {
  const ratio = parseAspectRatio(card.aspect_ratio || (card.type === 'activity_detail_preview' ? '1:3' : (card.type === 'activity_item_cover_preview' ? '1:1' : '3:4')))
  return {
    aspectRatio: `1 / ${ratio}`,
  }
}

function isAssistantWorkingMessage(message: ChatMessageItem) {
  return message.role === 'assistant' && ['pending', 'streaming'].includes(message.status || '') && !message.content?.trim()
}

function isLiveAssistantMessage(message: ChatMessageItem) {
  return message.role === 'assistant' && ['pending', 'streaming'].includes(message.status || '')
}

function getRevealStyle(step = 0) {
  return {
    '--ai-reveal-delay': `${Math.max(0, step) * 110}ms`,
  }
}

function cancelScrollAnimation() {
  if (scrollAnimationFrame !== null) {
    cancelAnimationFrame(scrollAnimationFrame)
    scrollAnimationFrame = null
  }
}

function isMessageWindowNearBottom(container: HTMLElement, threshold = 48) {
  return container.scrollHeight - container.scrollTop - container.clientHeight <= threshold
}

function getScrollBottomDistance(container: HTMLDivElement) {
  return Math.max(0, container.scrollHeight - container.clientHeight - container.scrollTop)
}

function updateScrollLatestButtonState(options: { forceNearBottom?: boolean } = {}) {
  const container = messageWindowRef.value
  if (!container) {
    shouldShowScrollLatestButton.value = false
    isScrollNearBottom.value = true
    lastScrollTop = 0
    return
  }

  const distance = getScrollBottomDistance(container)
  isScrollNearBottom.value = typeof options.forceNearBottom === 'boolean'
    ? options.forceNearBottom
    : distance <= 24
  shouldShowScrollLatestButton.value = distance > 96
  lastScrollTop = container.scrollTop
}

function handleMessageWindowScroll() {
  const container = messageWindowRef.value
  if (!container)
    return

  const isScrollingUp = container.scrollTop < lastScrollTop - 1
  if (isScrollingUp) {
    cancelScrollAnimation()
    isUserReadingHistory.value = true
    shouldStickToBottom.value = false
    updateScrollLatestButtonState({ forceNearBottom: false })
    return
  }

  if (isMessageWindowNearBottom(container)) {
    isUserReadingHistory.value = false
    shouldStickToBottom.value = true
  }

  updateScrollLatestButtonState()
}

function handleMessageWindowWheel(event: WheelEvent) {
  const container = messageWindowRef.value
  if (!container)
    return

  if (event.deltaY < 0 || !isMessageWindowNearBottom(container)) {
    cancelScrollAnimation()
    isUserReadingHistory.value = true
    shouldStickToBottom.value = false
    updateScrollLatestButtonState({ forceNearBottom: false })
  }
}

function handleMessageWindowTouchStart() {
  const container = messageWindowRef.value
  if (!container)
    return

  if (!isMessageWindowNearBottom(container)) {
    cancelScrollAnimation()
    isUserReadingHistory.value = true
    shouldStickToBottom.value = false
    updateScrollLatestButtonState({ forceNearBottom: false })
  }
}

function runScrollAnimation() {
  const container = messageWindowRef.value
  if (!container) {
    scrollAnimationFrame = null
    return
  }

  const currentTop = container.scrollTop
  scrollTargetTop.value = Math.max(0, container.scrollHeight - container.clientHeight)
  const distance = scrollTargetTop.value - currentTop
  if (Math.abs(distance) < 1) {
    container.scrollTop = scrollTargetTop.value
    scrollAnimationFrame = null
    isUserReadingHistory.value = false
    shouldStickToBottom.value = true
    updateScrollLatestButtonState()
    return
  }

  container.scrollTop = currentTop + distance * 0.22
  updateScrollLatestButtonState()
  scrollAnimationFrame = requestAnimationFrame(runScrollAnimation)
}

async function scrollToBottom(options: { immediate?: boolean, force?: boolean } = {}) {
  await nextTick()
  const container = messageWindowRef.value
  if (!container)
    return

  if (!options.force && !shouldStickToBottom.value && isUserReadingHistory.value && !isMessageWindowNearBottom(container))
    return

  scrollTargetTop.value = Math.max(0, container.scrollHeight - container.clientHeight)
  if (options.immediate) {
    cancelScrollAnimation()
    container.scrollTop = scrollTargetTop.value
    isUserReadingHistory.value = false
    shouldStickToBottom.value = true
    updateScrollLatestButtonState()
    return
  }

  if (scrollAnimationFrame === null)
    scrollAnimationFrame = requestAnimationFrame(runScrollAnimation)
}

async function scrollMessageToTop(messageKey: string) {
  await nextTick()
  const container = messageWindowRef.value
  if (!container || !messageKey)
    return

  const targetElement = Array.from(container.querySelectorAll<HTMLElement>('[data-message-key]'))
    .find(element => element.dataset.messageKey === messageKey)
  if (!targetElement)
    return

  cancelScrollAnimation()
  const containerRect = container.getBoundingClientRect()
  const targetRect = targetElement.getBoundingClientRect()
  const maxScrollTop = Math.max(0, container.scrollHeight - container.clientHeight)
  const nextScrollTop = container.scrollTop + targetRect.top - containerRect.top
  container.scrollTop = Math.min(maxScrollTop, Math.max(0, nextScrollTop))
  shouldStickToBottom.value = false
  isUserReadingHistory.value = true
  updateScrollLatestButtonState({ forceNearBottom: false })
}

function handleScrollLatestClick() {
  void scrollToBottom({ force: true })
}

onMounted(() => {
  void scrollToBottom({ immediate: true, force: true })

  if (messageStackRef.value) {
    contentResizeObserver = new ResizeObserver(() => {
      if (shouldStickToBottom.value)
        void scrollToBottom()
    })
    contentResizeObserver.observe(messageStackRef.value)
  }
})

onUnmounted(() => {
  cancelScrollAnimation()
  contentResizeObserver?.disconnect()
  contentResizeObserver = null
  if (copiedActionTimer)
    window.clearTimeout(copiedActionTimer)
})

watch(messageScrollSignature, async () => {
  await nextTick()
  if (isScrollNearBottom.value)
    void scrollToBottom({ immediate: true })
  else
    updateScrollLatestButtonState()
}, { flush: 'post' })

watch(latestUserTurnScrollSignature, () => {
  const latestUserMessage = latestVisibleUserMessage.value
  const latestMessage = latestVisibleMessage.value

  // 局部重生成属于当前会话的后续输出，不能把用户刚点击的操作消息固定到视口顶部。
  if (isRegenerationRequestMessage(latestUserMessage)) {
    shouldStickToBottom.value = true
    isUserReadingHistory.value = false
    void scrollToBottom({ force: true })
    return
  }

  const shouldAnchorLatestUserMessage = latestMessage?.role === 'user'
    || (
      latestMessage?.role === 'assistant'
      && ['pending', 'streaming'].includes(latestMessage.status || '')
    )

  if (latestUserMessage && shouldAnchorLatestUserMessage) {
    void scrollMessageToTop(getMessageActionKey(latestUserMessage))
    return
  }

  if (props.currentPrompt.trim())
    void scrollToBottom({ force: true })
}, { flush: 'post' })
</script>

<style scoped>
.chat-message-window-shell {
  position: relative;
  min-height: 0;
}

.chat-message-window {
  -ms-overflow-style: none;
  overflow-anchor: none;
  scrollbar-width: none;
}

.chat-message-window::-webkit-scrollbar {
  display: none;
}

.chat-message-window__stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.chat-message-row {
  animation: ai-message-row-reveal 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.ai-message-cell {
  position: relative;
  min-width: 0;
  overflow: visible;
}

.ai-message-cell.has-message-actions {
  padding-bottom: 0;
}

.ai-message-cell--user {
  display: flex;
  max-width: 100%;
  flex-direction: column;
  align-items: flex-end;
}

.ai-message-cell--assistant {
  width: 100%;
}

.ai-message-cell--assistant.has-message-actions {
  padding-bottom: 0;
}

.ai-message-action-bar {
  position: absolute;
  top: calc(100% - 28px);
  z-index: 8;
  display: flex;
  align-items: center;
  gap: 12px;
  opacity: 0;
  padding: 6px 14px 4px;
  pointer-events: none;
  transition:
    opacity 180ms ease;
}

.ai-message-action-bar--user {
  position: relative;
  top: auto;
  right: auto;
  justify-content: flex-end;
  margin-top: 8px;
  padding: 0;
}

.ai-message-action-bar--assistant {
  position: relative;
  top: auto;
  left: auto;
  width: 100%;
  justify-content: flex-start;
  padding: 4px 0;
}

.ai-message-action-controls {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.ai-message-action-time {
  display: inline-flex;
  height: 32px;
  align-items: center;
  color: #64748b;
  font-size: 13px;
  font-weight: 400;
  line-height: 20px;
  white-space: nowrap;
}

.ai-message-cell:hover > .ai-message-action-bar,
.ai-message-cell:focus-within > .ai-message-action-bar,
.ai-message-action-bar:focus-within {
  opacity: 1;
  pointer-events: auto;
}

.ai-message-action-button {
  position: relative;
  display: inline-flex;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  font-size: 0;
  line-height: 1;
  padding: 0;
  transition:
    background-color 160ms ease,
    color 160ms ease;
}

.ai-message-action-button:hover,
.ai-message-action-button:focus-visible {
  background: #f1f3f5;
}

.ai-message-action-button:hover,
.ai-message-action-button:focus-visible {
  color: #0f182a;
}

.ai-message-action-button .iconfont {
  display: inline-flex;
  width: 18px;
  height: 18px;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  line-height: 18px;
}

.ai-message-action-button svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.8;
}

.ai-message-action-tooltip {
  position: absolute;
  left: 50%;
  bottom: calc(100% + 8px);
  z-index: 12;
  height: 28px;
  padding: 0 10px;
  border-radius: 6px;
  background: #0f182a;
  color: #ffffff;
  font-size: 12px;
  font-weight: 400;
  line-height: 28px;
  opacity: 0;
  pointer-events: none;
  transform: translateX(-50%);
  transition:
    opacity 180ms ease;
  white-space: nowrap;
}

.ai-message-action-tooltip::after {
  position: absolute;
  left: 50%;
  bottom: -4px;
  width: 8px;
  height: 8px;
  background: #0f182a;
  content: "";
  transform: translateX(-50%) rotate(45deg);
}

.ai-message-action-button:hover > .ai-message-action-tooltip,
.ai-message-action-button:focus-visible > .ai-message-action-tooltip {
  opacity: 1;
}

.ai-scroll-latest-button {
  --ai-scroll-latest-border-width: 1px;
  position: absolute;
  bottom: 18px;
  left: 50%;
  z-index: 12;
  display: inline-flex;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  border: var(--ai-scroll-latest-border-width) solid #e3e9f1;
  border-radius: 9999px;
  background: #fff;
  box-shadow:
    0 12px 28px rgba(15, 24, 42, 0.12),
    0 2px 8px rgba(15, 24, 42, 0.08);
  color: #0f182a;
  cursor: pointer;
  isolation: isolate;
  overflow: visible;
  transform: translateX(-50%);
  transition:
    border-color 180ms ease,
    box-shadow 180ms ease;
}

.ai-scroll-latest-button:hover,
.ai-scroll-latest-button:focus-visible {
  border-color: #cbd5e1;
  box-shadow:
    0 16px 34px rgba(15, 24, 42, 0.16),
    0 4px 12px rgba(15, 24, 42, 0.1);
}

.ai-scroll-latest-button__glow {
  position: absolute;
  left: 6px;
  right: 6px;
  bottom: -5px;
  z-index: -1;
  height: 18px;
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
  opacity: 0;
  pointer-events: none;
  transform: scaleX(0.88);
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
  animation: ai-scroll-latest-glow-flow 3.2s ease-in-out infinite;
  will-change: background-position, filter, opacity, transform;
}

.ai-scroll-latest-button:hover .ai-scroll-latest-button__glow,
.ai-scroll-latest-button:focus-visible .ai-scroll-latest-button__glow,
.ai-scroll-latest-button.is-generating .ai-scroll-latest-button__glow {
  opacity: 0.54;
  transform: scaleX(1.08);
}

@property --ai-scroll-latest-border-angle {
  syntax: "<angle>";
  inherits: false;
  initial-value: 300deg;
}

.ai-scroll-latest-button.is-generating {
  --ai-scroll-latest-border-angle: 300deg;
  --ai-scroll-latest-border-width: 1.5px;
  border-color: transparent;
}

.ai-scroll-latest-button.is-generating::before {
  position: absolute;
  inset: calc(-1 * var(--ai-scroll-latest-border-width));
  z-index: 0;
  padding: var(--ai-scroll-latest-border-width);
  border-radius: inherit;
  background: conic-gradient(
    from var(--ai-scroll-latest-border-angle) at 50% 50%,
    #ffffff 0deg,
    #ffffff 46deg,
    #f8fff3 62deg,
    #bee2c7 82deg,
    #d3baeb 116deg,
    #953aef 154deg,
    #6e18c3 188deg,
    #e62222 268deg,
    #f7a589 314deg,
    #f3c394 330deg,
    #eeeea4 344deg,
    #ffffff 360deg
  );
  content: "";
  pointer-events: none;
  animation: ai-scroll-latest-border-flow 2.2s linear infinite;
  mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  mask-composite: exclude;
  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  -webkit-mask-composite: xor;
}

.ai-scroll-latest-button.is-generating::after {
  position: absolute;
  inset: 0;
  z-index: 0;
  border-radius: inherit;
  background: #fff;
  content: "";
  pointer-events: none;
}

.ai-scroll-latest-button__icon {
  position: relative;
  z-index: 1;
  display: inline-flex;
}

.ai-scroll-latest-button__icon svg {
  width: 20px;
  height: 20px;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.8;
}

.ai-message-text {
  white-space: pre-wrap;
}

.ai-component-reveal-block {
  transform-origin: top left;
  animation: ai-component-reveal 620ms cubic-bezier(0.2, 0.9, 0.2, 1) var(--ai-reveal-delay, 0ms) both;
  will-change: opacity, transform, clip-path;
}

.ai-poster-preview-card {
  width: min(100%, 672px);
  clip-path: none;
  animation-name: ai-poster-preview-card-reveal;
}

.ai-poster-preview-card__header {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.ai-poster-preview-card__title {
  display: inline-flex;
  min-width: 0;
  flex: 1 1 auto;
  align-items: center;
  gap: 4px;
  color: #0f182a;
  font-weight: 500;
}

.ai-poster-preview-card__title.is-generating {
  font-size: 14px;
  line-height: 20px;
}

.ai-poster-preview-card__title.is-completed {
  font-size: 14px;
  line-height: 20px;
}

.ai-poster-preview-card__title-static-shine {
  width: fit-content;
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  background-position: 0% 0;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
}

.ai-poster-preview-card__title-star {
  flex: 0 0 auto;
  margin-left: 0;
  margin-right: 2px;
  transform: translateY(1px) scale(1.04);
  transform-origin: center;
}

.ai-poster-preview-card__timing {
  flex-shrink: 0;
  color: #0F182A;
  font-size: 12px;
  font-weight: 500;
  line-height: 18px;
}

.ai-poster-preview-card__timing-muted {
  color: #64748B;
  font-weight: 400;
}

.ai-poster-preview-card__canvas {
  position: relative;
  width: min(200px, 100%);
  margin-top: 24px;
  overflow: hidden;
  border-radius: 12px;
  background: linear-gradient(152deg, #E2E4E5 0%, #D3D5D7 43.54%, #EAEEF1 98.25%);
}

.ai-poster-preview-card__canvas.is-generating {
  background:
    linear-gradient(115deg, rgba(255, 255, 255, 0) 32%, rgba(255, 255, 255, 0.52) 48%, rgba(255, 255, 255, 0) 64%),
    linear-gradient(152deg, #E2E4E5 0%, #D3D5D7 43.54%, #EAEEF1 98.25%);
  background-size: 280% 100%, 100% 100%;
  animation: ai-poster-preview-shimmer 5.2s linear infinite;
  will-change: background-position;
}

.ai-poster-preview-card__canvas img {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: inherit;
  cursor: pointer;
  object-fit: cover;
}

.ai-poster-preview-card__placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: inherit;
}

.ai-poster-preview-card__placeholder-star {
  opacity: 0.52;
}

.ai-image-preview-card__actions {
  display: flex;
  width: min(200px, 100%);
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 180ms ease;
}

.ai-activity-image-preview-card:hover .ai-image-preview-card__actions,
.ai-activity-image-preview-card:focus-within .ai-image-preview-card__actions,
.ai-poster-preview-card:hover .ai-image-preview-card__actions,
.ai-poster-preview-card:focus-within .ai-image-preview-card__actions {
  opacity: 1;
  pointer-events: auto;
}

.ai-image-preview-card__action {
  position: relative;
  display: inline-flex;
  width: 32px;
  height: 32px;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
}

.ai-image-preview-card__action svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.7;
}

.ai-image-preview-card__action:hover:not(:disabled) {
  background: #f1f3f5;
  color: #0f182a;
}

.ai-activity-image-preview-card + .ai-activity-image-preview-card {
  margin-top: 28px !important;
}

.ai-auto-item-cover-module,
.ai-auto-item-selection-module {
  width: min(100%, 672px);
  box-sizing: border-box;
  overflow: visible;
}

.ai-module-actions {
  display: flex;
  width: min(200px, 100%);
  margin-top: 8px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 180ms ease;
}

.ai-auto-item-selection-module:hover .ai-module-actions,
.ai-auto-item-selection-module:focus-within .ai-module-actions {
  opacity: 1;
  pointer-events: auto;
}

.ai-image-preview-card__action:disabled {
  cursor: not-allowed;
  opacity: 0.42;
}

.ai-image-preview-card__action:focus-visible {
  outline: 2px solid #2563eb;
  outline-offset: 2px;
}

.ai-image-preview-card__tooltip {
  position: absolute;
  top: calc(100% + 6px);
  bottom: auto;
  left: 50%;
  z-index: 30;
  display: none;
  width: max-content;
  max-width: 180px;
  padding: 4px 8px;
  border-radius: 4px;
  background: #0f182a;
  color: #ffffff;
  font-size: 12px;
  line-height: 18px;
  pointer-events: none;
  transform: translateX(-50%);
}

.ai-image-preview-card__action:hover:not(:disabled) .ai-image-preview-card__tooltip,
.ai-image-preview-card__action:focus-visible .ai-image-preview-card__tooltip {
  display: block;
}

.ai-activity-image-preview-card {
  width: min(100%, 672px);
}

.ai-activity-image-preview-card__header {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.ai-activity-image-preview-card__title {
  display: inline-flex;
  min-width: 0;
  flex: 1 1 auto;
  align-items: center;
  gap: 4px;
  color: #0f182a;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
}

.ai-activity-image-preview-card__title.is-completed {
  color: #0f182a;
}

.ai-activity-image-preview-card__title-star {
  flex: 0 0 auto;
  margin-right: 2px;
}

.ai-activity-image-preview-card__timing {
  flex: 0 0 auto;
  color: #64748b;
  font-size: 12px;
  line-height: 18px;
}

.ai-activity-image-preview-card__canvas {
  position: relative;
  width: min(200px, 100%);
  margin-top: 16px;
  overflow: hidden;
  border-radius: 12px;
  background: linear-gradient(152deg, #e2e4e5 0%, #d3d5d7 43.54%, #eaeef1 98.25%);
}

.ai-activity-image-preview-card__canvas.is-generating {
  background:
    linear-gradient(115deg, rgba(255, 255, 255, 0) 32%, rgba(255, 255, 255, 0.52) 48%, rgba(255, 255, 255, 0) 64%),
    linear-gradient(152deg, #e2e4e5 0%, #d3d5d7 43.54%, #eaeef1 98.25%);
  background-size: 280% 100%, 100% 100%;
  animation: ai-poster-preview-shimmer 5.2s linear infinite;
}

.ai-activity-image-preview-card__canvas img {
  display: block;
  width: 100%;
  height: 100%;
  cursor: pointer;
  object-fit: cover;
}

.ai-activity-image-preview-card__placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.52;
}

@keyframes ai-poster-preview-shimmer {
  0% {
    background-position: 180% 0, 0 0;
  }

  100% {
    background-position: -180% 0, 0 0;
  }
}

.ai-message-working-shine {
  width: fit-content;
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
  animation: ai-message-working-shine var(--ai-working-text-shine-duration) linear infinite;
}

@keyframes ai-message-working-shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: 0% 0;
  }
}

@keyframes ai-message-row-reveal {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }

  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes ai-scroll-latest-border-flow {
  to {
    --ai-scroll-latest-border-angle: 660deg;
  }
}

@keyframes ai-scroll-latest-glow-flow {
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

@keyframes ai-component-reveal {
  0% {
    opacity: 0;
    clip-path: inset(0 0 100% 0 round 16px);
    transform: translateY(14px) scale(0.985);
  }

  45% {
    opacity: 1;
  }

  100% {
    opacity: 1;
    clip-path: inset(0 0 0 0 round 16px);
    transform: translateY(0) scale(1);
  }
}

@keyframes ai-poster-preview-card-reveal {
  0% {
    opacity: 0;
    transform: translateY(14px) scale(0.985);
  }

  45% {
    opacity: 1;
  }

  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .ai-message-action-bar,
  .ai-scroll-latest-button,
  .ai-scroll-latest-button.is-generating::before,
  .ai-scroll-latest-button__glow {
    animation: none;
    transition: none;
  }

  .ai-poster-preview-card__canvas.is-generating {
    animation: none;
  }

  .ai-activity-image-preview-card__canvas.is-generating {
    animation: none;
  }
}
</style>
