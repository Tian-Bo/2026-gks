<?php

namespace App\Services;

class AiCatalog
{
    public const POSTER_SCENE = 'merchant_poster';

    public const ACTIVITY_IMAGE = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778663651_nlAVosokfd.png';

    public const POSTER_IMAGE = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1778685865_9Ez3vzr1I9.png';

    public static function pageConfig(): array
    {
        return [
            'styles' => [
                ['value' => 'general', 'label' => '通用风格', 'describe' => '由快灵自动匹配', 'is_default' => true],
                ['value' => 'trend_3d', 'label' => '3D潮玩', 'describe' => '高对比、强视觉记忆点'],
                ['value' => 'light_luxury', 'label' => '轻奢质感', 'describe' => '克制、精致、适合高客单'],
            ],
            'sizes' => [
                ['value' => '3:4', 'label' => '3:4', 'describe' => '小红书常用比例', 'is_default' => true],
                ['value' => '1:1', 'label' => '1:1', 'describe' => '适合朋友圈'],
            ],
            'activity_models' => [
                ['value' => 'auto', 'label' => '智能推荐', 'describe' => '根据诉求自动匹配活动模型', 'is_default' => true],
                ['value' => 'redbag', 'label' => '红包裂变', 'describe' => '适合拉新与分享'],
                ['value' => 'group_buy', 'label' => '拼团活动', 'describe' => '适合多人到店转化'],
            ],
            'models' => [
                ['value' => 'kl-image', 'label' => '快灵图像模型', 'describe' => '默认生成模型', 'is_default' => true],
            ],
            'poster_scene' => self::POSTER_SCENE,
        ];
    }

    public static function promptTips(?string $type = null): array
    {
        $all = [
            'activity' => [
                ['id' => 1, 'type' => 'activity', 'title' => '明确目标', 'content' => '先说清拉新、复购或储值目标，再补充活动时间。'],
                ['id' => 2, 'type' => 'activity', 'title' => '选择主推商品', 'content' => '告诉我希望重点承接的套餐、券或储值卡。'],
            ],
            'poster' => [
                ['id' => 3, 'type' => 'poster', 'title' => '描述主题', 'content' => '提供主题、目标人群、主文案和希望的画面氛围。'],
                ['id' => 4, 'type' => 'poster', 'title' => '选择比例', 'content' => '朋友圈、小红书和门店屏幕适合不同画幅。'],
            ],
        ];

        return $type === null ? array_merge($all['activity'], $all['poster']) : ($all[$type] ?? []);
    }
}
