export const posterScene = 'merchant_poster'

export const pageConfig = {
  styles: [
    { value: 'general', label: '通用风格', describe: '由快灵自动匹配', is_default: true },
    { value: 'trend_3d', label: '3D潮玩', describe: '高对比、强视觉记忆点' },
    { value: 'light_luxury', label: '轻奢质感', describe: '克制、精致、适合高客单' },
  ],
  sizes: [
    { value: '3:4', label: '3:4', describe: '小红书常用比例', is_default: true },
    { value: '1:1', label: '1:1', describe: '适合朋友圈' },
  ],
  activity_models: [
    { value: 'auto', label: '智能推荐', describe: '根据诉求自动匹配活动模型', is_default: true },
    { value: 'redbag', label: '红包裂变', describe: '适合拉新与分享' },
    { value: 'group_buy', label: '拼团活动', describe: '适合多人到店转化' },
  ],
  models: [
    { value: 'kl-image', label: '快灵图像模型', describe: '默认生成模型', is_default: true },
  ],
  poster_scene: posterScene,
}

export const promptTips = {
  activity: [
    { id: 1, type: 'activity', title: '明确目标', content: '先说清拉新、复购或储值目标，再补充活动时间。' },
    { id: 2, type: 'activity', title: '选择主推商品', content: '告诉我希望重点承接的套餐、券或储值卡。' },
  ],
  poster: [
    { id: 3, type: 'poster', title: '描述主题', content: '提供主题、目标人群、主文案和希望的画面氛围。' },
    { id: 4, type: 'poster', title: '选择比例', content: '朋友圈、小红书和门店屏幕适合不同画幅。' },
  ],
}

const activityImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778663651_nlAVosokfd.png'
const posterImage = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778685865_9Ez3vzr1I9.png'

export const inspirations = [
  { id: 1, type: 'activity', title: '夏日新客爆款体验活动', prompt: '为医美门店生成夏日新客体验活动，强调低门槛、到店转化和朋友圈传播。', quick_prompt: '做一个新客拉新活动', cover_img: activityImage, preview_image: activityImage, image_url: activityImage, author_name: '星颜美学', like_count: 286, used_count: 92, activity_id: 900001, activity_model_id: 1, created_at: '2026-08-28 10:20:00' },
  { id: 2, type: 'poster', title: '夏日补水修护海报', prompt: '生成一张小红书风格的夏季补水修护海报，标题醒目、质感高级。', quick_prompt: '做一张夏日补水修护海报', cover_img: posterImage, preview_image: posterImage, image_url: posterImage, author_name: '轻医美研究所', like_count: 168, used_count: 51, created_at: '2026-08-27 16:10:00' },
  { id: 3, type: 'activity', title: '七夕双人焕颜计划', prompt: '围绕情侣到店与双人套餐，设计七夕限时活动。', quick_prompt: '设计老客复购活动', cover_img: activityImage, preview_image: activityImage, image_url: activityImage, author_name: '初见皮肤管理', like_count: 124, used_count: 38, activity_id: 900002, activity_model_id: 1, created_at: '2026-08-26 11:30:00' },
  { id: 4, type: 'poster', title: '会员焕新季主视觉', prompt: '为会员焕新季生成高级感主视觉海报。', quick_prompt: '生成会员焕新季主视觉', cover_img: posterImage, preview_image: posterImage, image_url: posterImage, author_name: '悦己美研社', like_count: 95, used_count: 24, created_at: '2026-08-25 09:45:00' },
]

export const sampleImages = { activityImage, posterImage }
