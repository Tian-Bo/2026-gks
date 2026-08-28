<script setup lang="ts">
import TextType from './TextType.vue'
import { posterGenerationLoadingTexts } from '../../shared/generationLoadingCopy'
</script>

<template>
  <div class="poster-generating-preview">
    <div class="poster-generating-preview__title ai-poster-generating-shine">
      <TextType
        :text="posterGenerationLoadingTexts"
        as="span"
        class-name="poster-generating-preview__type"
        :typing-speed="80"
        :deleting-speed="38"
        :pause-duration="6000"
        cursor-character="_"
        cursor-class-name="text-type-underline-cursor"
        :cursor-blink-duration="0.55"
        random
      />
    </div>
    <div class="poster-generating-preview__canvas" />
  </div>
</template>

<style scoped>
.poster-generating-preview {
  width: min(100%, 672px);
  padding: 0;
}

.poster-generating-preview__title {
  width: fit-content;
  color: #0f182a;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
}

.ai-poster-generating-shine {
  background: var(--ai-working-text-gradient);
  background-size: var(--ai-working-text-gradient-size);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
  animation: ai-poster-generating-shine var(--ai-working-text-shine-duration) linear infinite;
}

@keyframes ai-poster-generating-shine {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: 0% 0;
  }
}

.poster-generating-preview__canvas {
  width: 290px;
  height: 386px;
  margin-top: 24px;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0) 30%, rgba(255, 255, 255, 0.45) 48%, rgba(255, 255, 255, 0) 66%),
    #dfe3e8;
  background-size: 220% 220%, 100% 100%;
  animation: poster-generating-shimmer 1.6s ease-in-out infinite;
}

@keyframes poster-generating-shimmer {
  0% {
    background-position: 160% 0, 0 0;
  }

  100% {
    background-position: -60% 0, 0 0;
  }
}
</style>
