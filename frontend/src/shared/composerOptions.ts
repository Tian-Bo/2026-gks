type AiConfigOption = {
  id?: string | number
  value?: string | number
  title?: string
  label?: string
  describe?: string
  img?: string
  image?: string
  image_url?: string
  cover_img?: string
  thumbnail?: string
  is_default?: boolean
}

type AiPageConfig = {
  styles?: AiConfigOption[]
  sizes?: AiConfigOption[]
  activity_models?: AiConfigOption[]
  models?: AiConfigOption[]
  poster_scene?: string
}

export type ModeKey = 'activity' | 'poster'
export type PromptOptionKey = 'tone' | 'activityModel' | 'posterSize'
export type ThinkingMode = 'deep' | 'quick'

export type PromptOption = {
  key: PromptOptionKey
  label: string
}

export type SelectorItem = {
  value: string
  label: string
  desc?: string
  iconClass?: string
  image?: string
  isDefault?: boolean
}

export const AI_IMAGE_MODEL_DISPLAY_NAME = 'GPT Image 2'

export const promptOptionMap: Record<ModeKey, PromptOption[]> = {
  activity: [
    { key: 'tone', label: '风格' },
    { key: 'activityModel', label: '活动模型' },
  ],
  poster: [
    { key: 'tone', label: '风格' },
    { key: 'posterSize', label: '海报尺寸' },
  ],
}

export const toneOptions: SelectorItem[] = [
  { value: '通用风格', label: '通用风格', desc: '不知道什么风格合适？快灵帮你决定', iconClass: 'icon-fengge' },
  { value: '轻奢高级', label: '轻奢高级', desc: '适配高端储值与品牌形象宣传场景', image: 'https://picsum.photos/seed/ai-style-luxury/80/80' },
  { value: '爆款促销', label: '爆款促销', desc: '低价引流、限时秒杀等拓客活动场景', image: 'https://picsum.photos/seed/ai-style-sale/80/80' },
  { value: '专业合规', label: '专业合规', desc: '合规宣传与专业内容场景', image: 'https://picsum.photos/seed/ai-style-pro/80/80' },
  { value: '节日氛围', label: '节日氛围', desc: '适配节日店庆等节点营销活动场景', image: 'https://picsum.photos/seed/ai-style-festival/80/80' },
  { value: '国潮新中式', label: '国潮新中式', desc: '国风养生、传统项目宣传适用场景', image: 'https://picsum.photos/seed/ai-style-chinese/80/80' },
  { value: '极简清新', label: '极简清新', desc: '适配网红项目与年轻客群社交场景', image: 'https://picsum.photos/seed/ai-style-minimal/80/80' },
  { value: '裂变福利', label: '裂变福利', desc: '适配拼团转介绍等分享裂变活动场景', image: 'https://picsum.photos/seed/ai-style-fission/80/80' },
  { value: '锁客尊贵', label: '锁客尊贵', desc: '会员储值、年卡锁客权益宣传场景', image: 'https://picsum.photos/seed/ai-style-vip/80/80' },
  { value: '活力元气', label: '活力元气', desc: '适配年轻客群夏日活动引流拓客场景', image: 'https://picsum.photos/seed/ai-style-energy/80/80' },
  { value: '韩系柔焦', label: '韩系柔焦', desc: '适配女性客群温柔宣传场景', image: 'https://picsum.photos/seed/ai-style-korean/80/80' },
  { value: '四季氛围', label: '四季氛围', desc: '适配应季项目与季节限定活动场景', image: 'https://picsum.photos/seed/ai-style-season/80/80' },
  { value: '复古怀旧', label: '复古怀旧', desc: '适配老客召回与周年店庆营销场景', image: 'https://picsum.photos/seed/ai-style-retro/80/80' },
]

export const posterSizeOptions: SelectorItem[] = [
  { value: '3:4', label: '3:4', desc: '适用于活动主图、活动海报', iconClass: 'icon-a-3_4' },
  { value: '1:1', label: '1:1', desc: '适用于商品主图', iconClass: 'icon-a-1_1' },
]

export const aiModelOptions: SelectorItem[] = [
  { value: 'gpt-image-2', label: AI_IMAGE_MODEL_DISPLAY_NAME, desc: '高质量视觉模型，适合精细画面', iconClass: 'icon-mozu' },
]

export function getImageModelDisplayName(value?: string | null) {
  return String(value || '').trim() ? AI_IMAGE_MODEL_DISPLAY_NAME : ''
}

export const defaultAiPageConfig = {
  styles: toneOptions,
  sizes: posterSizeOptions,
  activityModels: [
    {
      value: 'auto',
      label: '活动模型',
      desc: '不知道什么模型？快灵帮你决定',
      iconClass: 'icon-mozu',
    },
  ],
  models: aiModelOptions,
  posterScene: 'merchant_poster_assistant',
}

export function getPromptOptionTitle(key: PromptOptionKey) {
  if (key === 'tone')
    return '选择风格'
  if (key === 'posterSize')
    return '选择图片比例'
  if (key === 'activityModel')
    return '选择活动模型'
  return ''
}

export function getPromptOptionOverlayWidth(key: PromptOptionKey) {
  if (key === 'tone')
    return 320
  if (key === 'activityModel')
    return 300
  return 240
}

export function getPromptOptionTooltip(key: PromptOptionKey) {
  if (key === 'tone')
    return '选择风格'
  if (key === 'posterSize')
    return '选择图片比例'
  if (key === 'activityModel')
    return '选择活动模型'
  return '选择比例'
}

export function getPosterSizeIconClass(option: Pick<SelectorItem, 'value' | 'label'> | string) {
  const text = typeof option === 'string'
    ? option
    : `${option.value} ${option.label}`
  const normalizedText = text.replace(/\s+/g, '')
  return normalizedText.includes('1:1') ? 'icon-a-1_1' : 'icon-a-3_4'
}

export function getPromptOptionIconClass(key: PromptOptionKey) {
  if (key === 'tone')
    return 'icon-fengge'
  if (key === 'posterSize')
    return 'icon-a-3_4'
  if (key === 'activityModel')
    return 'icon-mozu'
  return 'icon-mozu'
}

export function getPromptOptionItems(
  mode: ModeKey,
  key: PromptOptionKey,
  aiPageConfig = defaultAiPageConfig,
) {
  if (key === 'tone')
    return aiPageConfig.styles
  if (key === 'posterSize')
    return aiPageConfig.sizes
  if (key === 'activityModel')
    return mode === 'activity' ? aiPageConfig.activityModels : []
  return []
}

function normalizeConfigOption(option: AiConfigOption, fallbackIconClass: string): SelectorItem {
  const value = String(option.id || option.value || '').trim()
  const label = String(option.title || option.label || value || '未命名选项')
  const image = String(
    option.img
    || option.image
    || option.image_url
    || option.cover_img
    || option.thumbnail
    || '',
  ).trim()
  return {
    value,
    label,
    desc: String(option.describe || '').trim(),
    image: image || undefined,
    iconClass: image ? undefined : fallbackIconClass,
    isDefault: Boolean(option.is_default),
  }
}

function normalizeConfigOptionList(
  list: AiConfigOption[] | undefined,
  fallbackList: SelectorItem[],
  fallbackIconClass: string,
  resolveIconClass?: (option: SelectorItem) => string,
) {
  const normalized = Array.isArray(list)
    ? list
        .map(option => normalizeConfigOption(option, fallbackIconClass))
        .map(option => ({
          ...option,
          iconClass: option.image ? undefined : resolveIconClass?.(option) || option.iconClass,
        }))
        .filter(option => option.value)
    : []

  return normalized.length ? normalized : fallbackList
}

function getDefaultConfigValue(list: AiConfigOption[] | undefined, fallbackValue: string) {
  if (!Array.isArray(list))
    return fallbackValue

  const target = list.find(item => item.is_default) || list[0]
  return String(target?.id || target?.value || fallbackValue || '').trim()
}

export function normalizeAiPageConfig(config: AiPageConfig | null | undefined) {
  return {
    styles: normalizeConfigOptionList(config?.styles, toneOptions, 'icon-fengge'),
    sizes: normalizeConfigOptionList(config?.sizes, posterSizeOptions, 'icon-a-3_4', getPosterSizeIconClass),
    activityModels: normalizeConfigOptionList(config?.activity_models, defaultAiPageConfig.activityModels, 'icon-mozu'),
    models: normalizeConfigOptionList(config?.models, aiModelOptions, 'icon-mozu'),
    posterScene: String(config?.poster_scene || defaultAiPageConfig.posterScene),
    defaults: {
      style: getDefaultConfigValue(config?.styles, toneOptions[0]?.value || ''),
      aspectRatio: getDefaultConfigValue(config?.sizes, posterSizeOptions[0]?.value || ''),
      activityModel: getDefaultConfigValue(config?.activity_models, defaultAiPageConfig.activityModels[0]?.value || 'auto'),
      imageModel: getDefaultConfigValue(config?.models, aiModelOptions[0]?.value || ''),
    },
  }
}
