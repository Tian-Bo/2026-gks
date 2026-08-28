const previewImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778663651_nlAVosokfd.png'
const posterImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778685865_9Ez3vzr1I9.png'

const inspirations = [
  { id: 1, type: 'activity', title: '夏日新客爆款体验活动', prompt: '为医美门店生成夏日新客体验活动，强调低门槛、到店转化和朋友圈传播。', cover_img: previewImage, image_url: previewImage, author_name: '星颜美学', like_count: 286, used_count: 92, created_at: '2026-08-28 10:20:00', activity_id: 900001, activity_model_id: 1 },
  { id: 2, type: 'poster', title: '夏日补水修护海报', prompt: '生成一张小红书风格的夏季补水修护海报，标题醒目、质感高级。', cover_img: posterImage, image_url: posterImage, author_name: '轻医美研究所', like_count: 168, used_count: 51, created_at: '2026-08-27 16:10:00' },
  { id: 3, type: 'activity', title: '七夕双人焕颜计划', prompt: '围绕情侣到店与双人套餐，设计七夕限时活动。', cover_img: previewImage, image_url: previewImage, author_name: '初见皮肤管理', like_count: 124, used_count: 38, created_at: '2026-08-26 11:30:00', activity_id: 900002, activity_model_id: 1 },
  { id: 4, type: 'poster', title: '会员焕新季主视觉', prompt: '为会员焕新季生成高级感主视觉海报。', cover_img: posterImage, image_url: posterImage, author_name: '悦己美研社', like_count: 95, used_count: 24, created_at: '2026-08-25 09:45:00' },
]

const conversations = [
  { conversation_id: 'mock-activity-001', title: '夏日新客爆款体验活动', scene: 'merchant_assistant', preview_image: previewImage, status: 'active', meta: { mode: 'activity' }, updated_at: '2026-08-28 10:20:00', created_at: '2026-08-28 10:20:00' },
  { conversation_id: 'mock-poster-001', title: '夏日补水修护海报', scene: 'poster', preview_image: posterImage, status: 'active', meta: { mode: 'poster' }, updated_at: '2026-08-27 16:10:00', created_at: '2026-08-27 16:10:00' },
  { conversation_id: 'mock-activity-002', title: '七夕双人焕颜计划', scene: 'merchant_assistant', preview_image: previewImage, status: 'active', meta: { mode: 'activity' }, updated_at: '2026-08-26 11:30:00', created_at: '2026-08-26 11:30:00' },
  { conversation_id: 'mock-poster-002', title: '会员焕新季主视觉', scene: 'poster', preview_image: posterImage, status: 'active', meta: { mode: 'poster' }, updated_at: '2026-08-25 09:45:00', created_at: '2026-08-25 09:45:00' },
]

const api: any = {
  ai: {
    getAiPageConfig: async () => null,
    getAiPoints: async () => ({ balance: 1280 }),
    getAiPromptTips: async () => ({ items: [] }),
    getAiConversationList: async () => ({ items: conversations, total: conversations.length }),
    getAiConversationMessages: async () => ({ items: [], conversation: conversations[0] }),
    getAiInspirations: async (params: { type?: string } = {}) => ({
      items: params.type && params.type !== 'all' ? inspirations.filter(item => item.type === params.type) : inspirations,
      quick_prompts: params.type === 'poster'
        ? [{ id: 1, type: 'poster', content: '做一张夏日补水修护海报' }, { id: 2, type: 'poster', content: '生成会员焕新季主视觉' }]
        : [{ id: 1, type: 'activity', content: '做一个新客拉新活动' }, { id: 2, type: 'activity', content: '设计老客复购活动' }],
    }),
    getAiInspirationDetail: async (id: number) => inspirations.find(item => item.id === id) || inspirations[0],
    toggleContentReaction: async () => ({ is_active: 1, count: 1 }),
    sendAiMessage: async (data: { content?: string }) => ({
      conversation: { conversation_id: 'mock-activity-001', title: data.content || 'AI 活动方案' },
      assistant_message: { message_id: 'mock-assistant-001' },
      stream_url: '',
    }),
    stopAiMessage: async () => ({ message: {} }),
    buildAiMessageStreamUrl: () => '',
    buildAiStreamUrl: () => '',
  },
  goods: { getUnifiedItemList: async () => ({ items: [], total: 0 }) },
  activity: {
    getActivityDetail: async () => ({}),
    updateActivity: async () => ({}),
    releaseActivity: async () => ({}),
  },
}

export default api
