<template>
  <div class="preview-mini-header">
    <div class="preview-mini-header__status"><span>9:41</span><span><i class="iconfont icon-xinhao"></i><i class="iconfont icon-80dianliang"></i></span></div>
    <div class="preview-mini-header__nav"><span class="preview-mini-header__shop">活动预览</span><img :src="capsuleUrl" alt="" /></div>
  </div>
</template>

<script setup lang="ts">
const capsuleUrl = 'https://kuailiebian-1305584593.cos.ap-guangzhou.myqcloud.com/1782915160_22jn4fV38m.png'
</script>

<style scoped>
.preview-mini-header { width: 375px; height: 90px; background: #fff; color: #0f182a; font-family: 'PingFang SC', sans-serif; }
.preview-mini-header__status { display: flex; height: 46px; align-items: center; justify-content: space-between; padding: 0 24px; font-size: 17px; font-weight: 600; }
.preview-mini-header__status i { margin-left: 6px; font-size: 18px; }
.preview-mini-header__nav { display: flex; height: 44px; align-items: center; justify-content: space-between; padding: 0 12px; }
.preview-mini-header__shop { font-size: 14px; font-weight: 600; }
.preview-mini-header__nav img { width: 88px; height: auto; }
</style>
