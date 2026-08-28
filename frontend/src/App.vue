<template>
  <main class="ai-chat-page" :class="{ 'ai-chat-page--entered': entered }">
    <header class="ai-chat-header">
      <div class="ai-chat-brand">
        <button class="brand-back" type="button" @click="restartConversation">
          <i class="iconfont icon-youjiantou rotate-180" aria-hidden="true" />
          <span>返回</span>
        </button>
        <span class="brand-divider" />
        <div class="brand-mark">快灵 <b>AI</b></div>
      </div>
      <div class="header-actions">
        <div class="credit-pill"><span>灵点</span><b>2,680</b></div>
        <span class="header-divider" />
        <button type="button" class="history-button" @click="restartConversation">新建对话</button>
      </div>
    </header>

    <section class="ai-chat-canvas">
      <div class="ai-chat-main">
        <section class="dialog-column">
          <div class="dialog-title-row">
            <div>
              <p class="eyebrow">AI 活动助手</p>
              <h1>活动对话生成器</h1>
            </div>
            <span class="status-dot"><span /> 已连接</span>
          </div>

          <ChatMessageWindow
            active-mode="activity"
            :is-mock-preview-mode="false"
            :is-generating="isGenerating"
            :should-show-activity-brief-form="stage === 'brief'"
            :selected-activity-goal="goal"
            :selected-activity-duration="duration"
            :activity-goal-options="goalOptions"
            :activity-duration-options="durationOptions"
            :activity-date-range="dateRange"
            :show-activity-goal-selector="true"
            :show-activity-date-selector="true"
            :is-activity-brief-readonly="false"
            :should-show-activity-product-selector="stage === 'product'"
            :activity-product-options="products"
            :activity-product-requirement="productRequirement"
            :selected-activity-product-ids="selectedProductIds"
            :is-activity-products-loading="false"
            :should-show-activity-style-selector="stage === 'style'"
            :activity-style-options="styleOptions"
            :selected-activity-style="style"
            :activity-style-requirement="styleRequirement"
            :should-show-activity-result-confirm-bar="stage === 'result'"
            :should-show-thinking-process-card="isGenerating"
            :thinking-process-status="isGenerating ? 'thinking' : 'completed'"
            :thinking-process-summary-items="thinkingItems"
            :current-prompt="currentPrompt"
            :chat-messages="messages"
            :get-message-display-content="getMessageDisplayContent"
            :get-message-image-attachments="getMessageImageAttachments"
            @update:goal-value="goal = $event"
            @update:date-value="duration = $event"
            @update:start-value="dateRange.start = $event"
            @update:end-value="dateRange.end = $event"
            @skip-goal="goal = ''"
            @skip-date="duration = ''"
            @confirm-brief="confirmBrief"
            @update:selected-product-ids="selectedProductIds = $event"
            @update:product-requirement="productRequirement = $event"
            @skip-product="selectedProductIds = []"
            @confirm-product="confirmProducts"
            @update:selected-style="style = $event"
            @update:style-requirement="styleRequirement = $event"
            @skip-style="style = ''"
            @confirm-style="confirmStyle"
            @confirm-activity-deep="generateActivity"
            @adopt="adoptActivity"
            @publish="openPreview"
            @regenerate-message="regenerateMessage"
          />

          <ChatComposer
            active-mode="activity"
            current-mode-label="活动生成"
            :draft-message="draftMessage"
            :pasted-images="[]"
            selected-thinking-mode="deep"
            :current-prompt-options="promptOptions"
            current-model="活动智能体"
            current-model-value="auto"
            composer-summary-text="深度思考"
            :image-model-options="[]"
            :is-message-working="isGenerating"
            :send-state="isGenerating ? 'working' : 'ready'"
            :generation-timing-text="null"
            :get-prompt-option-items="getPromptOptionItems"
            :get-prompt-option-display-label="getPromptOptionDisplayLabel"
            :get-prompt-option-title="getPromptOptionTitle"
            :get-prompt-option-overlay-width="getPromptOptionOverlayWidth"
            :get-prompt-option-icon-class="getPromptOptionIconClass"
            :get-prompt-option-selected-item="getPromptOptionSelectedItem"
            :is-prompt-option-selected="isPromptOptionSelected"
            @update:draft-message="draftMessage = $event"
            @select-setting="selectPromptSetting"
            @send="sendMessage"
          />
        </section>

        <aside class="preview-column">
          <div class="preview-kicker">实时预览</div>
          <div class="preview-card">
            <div class="preview-browser-bar">
              <span /><span /><span />
              <div>活动预览</div>
            </div>
            <div class="phone-frame">
              <div class="phone-top"><span>9:41</span><span>● ● ●</span></div>
              <template v-if="result">
                <div class="campaign-hero">
                  <p>限时福利</p>
                  <h2>{{ result.plan.title }}</h2>
                  <span>{{ result.plan.subtitle }}</span>
                </div>
                <div class="campaign-body">
                  <div class="campaign-badge">AI 已生成</div>
                  <h3>{{ selectedProductText }}</h3>
                  <p>{{ result.plan.incentive }}</p>
                  <div class="campaign-products">
                    <img v-for="item in selectedProducts.slice(0, 2)" :key="item.id" :src="item.image" :alt="item.name">
                  </div>
                  <button type="button" @click="openPreview">立即参与</button>
                </div>
              </template>
              <template v-else>
                <div class="preview-empty">
                  <div class="preview-spark">✦</div>
                  <strong>活动将在这里生成</strong>
                  <span>填写对话信息后，实时查看活动页效果</span>
                </div>
              </template>
            </div>
          </div>
          <div class="preview-summary">
            <span>对接字段</span>
            <p><code>storeId</code> · <code>productIds</code> · <code>activityConfig</code></p>
          </div>
        </aside>
      </div>
    </section>

    <a-modal v-model:open="previewOpen" title="活动预览已就绪" :footer="null" width="420px">
      <p class="modal-copy">已生成活动配置草稿，可将它交给裂变快的发布与预览能力继续处理。</p>
      <pre>{{ result?.plan }}</pre>
    </a-modal>
  </main>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import ChatComposer from './components/ai-chat/ChatComposer.vue'
import ChatMessageWindow from './components/ai-chat/ChatMessageWindow.vue'
import {
  defaultAiPageConfig,
  getPromptOptionIconClass as getSharedPromptOptionIconClass,
  getPromptOptionItems as getSharedPromptOptionItems,
  getPromptOptionOverlayWidth as getSharedPromptOptionOverlayWidth,
  getPromptOptionTitle as getSharedPromptOptionTitle,
  promptOptionMap,
  type PromptOptionKey,
} from './shared/composerOptions'
import { generateActivityPlan, type ActivityGenerationResult } from './services/activityAssistant'

type Product = {
  id: string
  name: string
  image: string
  price: string
  stock: string
  typeLabel: string
  typeTone: 'red' | 'orange' | 'green'
}

type ChatMessage = {
  id: string
  messageId: string
  role: 'assistant' | 'user'
  content: string
  createdAt: string
  cards?: Record<string, unknown>[]
  componentResult?: Record<string, unknown>
}

const entered = ref(false)
const stage = ref<'brief' | 'product' | 'style' | 'plan' | 'result'>('brief')
const draftMessage = ref('')
const currentPrompt = ref('')
const isGenerating = ref(false)
const previewOpen = ref(false)
const result = ref<ActivityGenerationResult | null>(null)
const goal = ref('拉新获客')
const duration = ref('最近10天')
const style = ref('通用风格')
const productRequirement = ref('')
const styleRequirement = ref('')
const selectedProductIds = ref<string[]>(['p1'])
const dateRange = reactive({ start: '', end: '' })

const goalOptions = [
  { value: '拉新获客', label: '拉新获客' },
  { value: '老客复购', label: '老客复购' },
  { value: '提升客单价', label: '提升客单价' },
  { value: '会员储值', label: '会员储值' },
]
const durationOptions = [
  { value: '最近10天', label: '最近 10 天' },
  { value: '近期五一劳动节', label: '近期五一劳动节' },
  { value: 'custom_range', label: '自定义时间' },
]
const styleOptions = defaultAiPageConfig.styles
const products: Product[] = [
  { id: 'p1', name: '舒缓焕亮护理套餐', image: 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=160&q=80', price: '¥199', stock: '286', typeLabel: '套餐', typeTone: 'red' },
  { id: 'p2', name: '焕肤体验卡', image: 'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=160&q=80', price: '¥69', stock: '518', typeLabel: '次卡', typeTone: 'orange' },
  { id: 'p3', name: '春日轻享储值礼', image: 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=160&q=80', price: '¥399', stock: '96', typeLabel: '储值', typeTone: 'green' },
  { id: 'p4', name: '水光亮肤单次体验', image: 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=160&q=80', price: '¥129', stock: '168', typeLabel: '单品', typeTone: 'orange' },
]

const messages = ref<ChatMessage[]>([])
const promptOptions = promptOptionMap.activity
const selectedPromptSettings = reactive<Record<PromptOptionKey, string>>({ tone: '通用风格', activityModel: 'auto', posterSize: '3:4' })
const selectedProducts = computed(() => products.filter(product => selectedProductIds.value.includes(product.id)))
const selectedProductText = computed(() => selectedProducts.value.map(product => product.name).join('、') || '店铺主推商品')
const thinkingItems = computed(() => [
  `识别活动目标：${goal.value || '待补充'}`,
  `匹配商品：${selectedProductText.value}`,
  '生成活动规则与页面内容',
])

function now() {
  return new Date().toISOString()
}

function id(prefix: string) {
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 6)}`
}

function getMessageDisplayContent(message: ChatMessage) {
  return message.content || ''
}

function getMessageImageAttachments() {
  return []
}

function createBriefCard() {
  return {
    type: 'activity_goal_duration_selector',
    card_id: 'brief-card',
    sections: [
      { section_key: 'goal', title: '这次活动最想解决什么问题？', options: goalOptions },
      { section_key: 'duration', title: '活动准备持续多久？', options: durationOptions },
    ],
  }
}

function createProductCard() {
  return { type: 'activity_item_selector', card_id: 'product-card', title: '选择本次活动的主推商品' }
}

function createStyleCard() {
  return { type: 'activity_style_selector', card_id: 'style-card', title: '给活动选一个视觉与表达风格' }
}

function pushAssistant(content: string, cards?: Record<string, unknown>[]) {
  messages.value.push({ id: id('assistant'), messageId: id('message'), role: 'assistant', content, createdAt: now(), cards })
}

function pushComponentResult(cardId: string, payload: Record<string, unknown>) {
  messages.value.push({
    id: id('user'), messageId: id('message'), role: 'user', content: '', createdAt: now(),
    componentResult: { card_id: cardId, ...payload },
  })
}

function restartConversation() {
  stage.value = 'brief'
  result.value = null
  currentPrompt.value = ''
  draftMessage.value = ''
  messages.value = []
  pushAssistant('你好，我是快灵 AI。告诉我你想做什么活动，我会从目标、商品和风格出发，为你生成可直接预览的活动方案。', [createBriefCard()])
}

function confirmBrief() {
  if (!goal.value || !duration.value)
    return
  pushComponentResult('brief-card', {
    status: 'submitted',
    goal: { value: goal.value, label: goal.value },
    duration: { value: duration.value, label: duration.value, start_time: dateRange.start, end_time: dateRange.end },
  })
  stage.value = 'product'
  pushAssistant(`已记下：本次重点是“${goal.value}”，活动周期为“${duration.value}”。接下来选择主推商品，我会把商品卖点写入活动规则。`, [createProductCard()])
}

function confirmProducts() {
  if (!selectedProductIds.value.length && !productRequirement.value.trim())
    return
  pushComponentResult('product-card', {
    status: 'submitted',
    items: selectedProducts.value.map(item => ({ item_id: item.id, title: item.name, price: item.price, image: item.image })),
    item_requirement: productRequirement.value,
  })
  stage.value = 'style'
  pushAssistant('商品已选好。最后选一个风格，我会用它统一活动主标题、页面语气和视觉建议。', [createStyleCard()])
}

function confirmStyle() {
  pushComponentResult('style-card', { status: 'submitted', style: { label: style.value }, style_requirement: styleRequirement.value })
  stage.value = 'plan'
  pushAssistant('信息已经齐全。我将先梳理商品承接和转化规则，再生成完整活动方案。', [{
    type: 'activity_deep_confirm',
    card_id: 'plan-card',
    title: '活动方案准备完成',
    thinking: '正在结合目标、商品、周期和风格推演活动玩法。',
    summary: ['目标与人群已匹配', '商品权益已组合', '活动页面结构已规划'],
    plan: { goal: goal.value, products: selectedProductText.value, style: style.value },
  }])
}

async function generateActivity() {
  if (isGenerating.value)
    return
  isGenerating.value = true
  try {
    const generated = await generateActivityPlan({
      messages: messages.value.map(message => message.content).filter(Boolean),
      storeId: 'demo-store-001',
      productIds: selectedProductIds.value,
      objective: goal.value,
      schedule: { start: dateRange.start, end: dateRange.end, label: duration.value },
      style: style.value,
    })
    result.value = generated
    pushComponentResult('plan-card', { component_type: 'activity_deep_confirm' })
    stage.value = 'result'
    pushAssistant(generated.content, [{
      type: 'activity_rule_check',
      card_id: 'rule-check-card',
      title: '活动规则检查',
      status: 'passed',
      checks: [{ level: 'success', message: generated.plan.rule }],
    }])
  }
  finally {
    isGenerating.value = false
  }
}

function sendMessage() {
  const content = draftMessage.value.trim()
  if (!content || isGenerating.value)
    return
  currentPrompt.value = content
  draftMessage.value = ''
  window.setTimeout(() => {
    currentPrompt.value = ''
    messages.value.push({ id: id('user'), messageId: id('message'), role: 'user', content, createdAt: now() })
    if (stage.value === 'brief')
      pushAssistant('我已收到你的补充。请先确认下面两个关键信息，方便我生成更贴合门店的活动。')
    else
      pushAssistant('补充已加入当前活动草稿。你可以继续完善，或按当前步骤完成生成。')
  }, 120)
}

function regenerateMessage() {
  if (stage.value === 'result')
    void generateActivity()
}

function adoptActivity() {
  previewOpen.value = true
}

function openPreview() {
  previewOpen.value = true
}

function getPromptOptionItems(key: PromptOptionKey) {
  return getSharedPromptOptionItems('activity', key, defaultAiPageConfig)
}

function getPromptOptionDisplayLabel(key: PromptOptionKey) {
  const current = getPromptOptionItems(key).find(item => item.value === selectedPromptSettings[key])
  return current?.label || getPromptOptionItems(key)[0]?.label || ''
}

function getPromptOptionTitle(key: PromptOptionKey) {
  return getSharedPromptOptionTitle(key)
}

function getPromptOptionOverlayWidth(key: PromptOptionKey) {
  return getSharedPromptOptionOverlayWidth(key)
}

function getPromptOptionIconClass(key: PromptOptionKey) {
  return getSharedPromptOptionIconClass(key)
}

function getPromptOptionSelectedItem(key: PromptOptionKey) {
  return getPromptOptionItems(key).find(item => item.value === selectedPromptSettings[key])
}

function isPromptOptionSelected(key: PromptOptionKey, value: string) {
  return selectedPromptSettings[key] === value
}

function selectPromptSetting(key: PromptOptionKey, value: string) {
  selectedPromptSettings[key] = value
  if (key === 'tone')
    style.value = value
}

onMounted(async () => {
  restartConversation()
  await nextTick()
  entered.value = true
})
</script>

<style scoped>
.ai-chat-page { min-height: 100vh; background: #f1f3f5; }
.ai-chat-header { position: fixed; inset: 0 0 auto; z-index: 20; height: 72px; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; border-bottom: 1px solid rgba(15,24,42,.06); background: rgba(241,243,245,.88); backdrop-filter: blur(16px); }
.ai-chat-brand,.header-actions { display: flex; align-items: center; gap: 16px; }
.brand-back,.history-button { border: 0; background: transparent; color: #0f182a; cursor: pointer; font-weight: 600; }
.brand-back { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; }
.brand-back:hover,.history-button:hover { color: #e62222; }
.brand-divider,.header-divider { width: 1px; height: 20px; background: #d9e0ea; }
.brand-mark { font: 700 19px/1 DouyinSans, "PingFang SC", sans-serif; letter-spacing: .3px; }
.brand-mark b { color: #e62222; }
.credit-pill { display: flex; align-items: center; gap: 9px; padding: 5px 10px; border-radius: 8px; background: #0f182a; color: #fff; font-size: 12px; }
.credit-pill b { color: #ffcf69; }
.history-button { min-width: 92px; height: 36px; border-radius: 8px; background: #fff; font-size: 13px; box-shadow: 0 4px 12px rgba(15,24,42,.05); }
.ai-chat-canvas { width: min(1500px, 100%); min-height: 100vh; margin: 0 auto; padding: 96px 16px 28px; }
.ai-chat-main { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(390px, .95fr); gap: 16px; min-height: calc(100vh - 124px); opacity: 0; transform: translateY(8px); transition: opacity .45s ease, transform .45s ease; }
.ai-chat-page--entered .ai-chat-main { opacity: 1; transform: translateY(0); }
.dialog-column,.preview-column { min-height: 0; border-radius: 24px; background: #fff; box-shadow: 0 12px 36px rgba(15,24,42,.06); }
.dialog-column { display: flex; flex-direction: column; overflow: hidden; }
.dialog-title-row { display: flex; align-items: center; justify-content: space-between; min-height: 76px; padding: 16px 28px; border-bottom: 1px solid #f0f2f5; }
.eyebrow { margin: 0 0 3px; color: #98a2b3; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
h1 { margin: 0; font-size: 17px; line-height: 1.3; }
.status-dot { display: inline-flex; align-items: center; gap: 6px; color: #667085; font-size: 12px; }
.status-dot span { width: 7px; height: 7px; border-radius: 50%; background: #35c759; box-shadow: 0 0 0 4px #eafbf0; }
.preview-column { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px; background: linear-gradient(145deg,#fff 0%,#fafbfc 100%); overflow: hidden; }
.preview-kicker { align-self: flex-start; margin: 0 0 16px; color: #64748b; font-size: 13px; font-weight: 700; }
.preview-card { width: min(100%, 460px); padding: 10px; border: 1px solid #e8edf3; border-radius: 22px; background: #fff; box-shadow: 0 18px 40px rgba(15,24,42,.1); }
.preview-browser-bar { display: flex; align-items: center; gap: 5px; height: 30px; padding: 0 7px; color: #98a2b3; font-size: 10px; }
.preview-browser-bar span { width: 7px; height: 7px; border-radius: 50%; background: #d9e0ea; }
.preview-browser-bar div { flex: 1; margin-left: 5px; border-radius: 6px; background: #f4f6f8; padding: 4px 8px; text-align: center; }
.phone-frame { position: relative; min-height: 530px; overflow: hidden; border-radius: 17px; background: #f6f7f9; }
.phone-top { display: flex; justify-content: space-between; padding: 12px 18px 0; color: #fff; font-size: 10px; position: relative; z-index: 1; }
.campaign-hero { min-height: 215px; margin-top: -24px; padding: 72px 25px 24px; background: radial-gradient(circle at top right, #ffd671 0, transparent 30%), linear-gradient(135deg,#ee5e59,#e62222 55%,#9d1021); color: #fff; }
.campaign-hero p { margin: 0 0 12px; font-size: 12px; letter-spacing: .12em; opacity: .84; }
.campaign-hero h2 { max-width: 240px; margin: 0 0 9px; font: 700 29px/1.15 DouyinSans, "PingFang SC", sans-serif; }
.campaign-hero span { display: block; max-width: 250px; font-size: 12px; line-height: 1.65; opacity: .86; }
.campaign-body { margin: -18px 14px 14px; padding: 20px; border-radius: 18px; background: #fff; box-shadow: 0 8px 22px rgba(163,26,32,.1); position: relative; }
.campaign-badge { display: inline-block; padding: 4px 8px; border-radius: 99px; background: #fff1f1; color: #e62222; font-size: 10px; font-weight: 700; }
.campaign-body h3 { margin: 13px 0 7px; font-size: 16px; }
.campaign-body p { margin: 0; color: #667085; font-size: 12px; line-height: 1.65; }
.campaign-products { display: flex; gap: 8px; margin: 14px 0; }
.campaign-products img { width: 54px; height: 54px; border-radius: 10px; object-fit: cover; }
.campaign-body button { width: 100%; border: 0; border-radius: 10px; padding: 11px; background: #e62222; color: #fff; cursor: pointer; font-weight: 700; }
.preview-empty { display: flex; height: 530px; padding: 40px; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #667085; }
.preview-spark { display: grid; width: 58px; height: 58px; margin-bottom: 20px; place-items: center; border-radius: 18px; background: #fff1f1; color: #e62222; font-size: 30px; }
.preview-empty strong { color: #0f182a; font-size: 16px; }
.preview-empty span { max-width: 190px; margin-top: 9px; font-size: 12px; line-height: 1.7; }
.preview-summary { width: min(100%, 460px); margin-top: 20px; padding: 14px 16px; border-radius: 14px; background: #f6f7f9; color: #667085; font-size: 12px; }
.preview-summary span { color: #0f182a; font-weight: 700; }
.preview-summary p { margin: 6px 0 0; }
code { padding: 2px 4px; border-radius: 4px; background: #fff; color: #e62222; }
.modal-copy { color: #667085; line-height: 1.7; }
pre { overflow: auto; padding: 14px; border-radius: 10px; background: #f6f7f9; color: #0f182a; font-size: 12px; white-space: pre-wrap; }
@media (max-width: 920px) { .ai-chat-header { padding: 0 16px; }.ai-chat-main { grid-template-columns: 1fr; }.preview-column { display: none; }.dialog-column { min-height: calc(100vh - 112px); }.ai-chat-canvas { padding: 84px 8px 12px; } }
@media (max-width: 560px) { .brand-divider,.credit-pill,.header-divider { display: none; }.ai-chat-header { height: 62px; }.history-button { min-width: 72px; height: 32px; }.ai-chat-canvas { padding-top: 70px; }.dialog-title-row { min-height: 64px; padding: 12px 16px; }.eyebrow { display: none; } }
</style>
