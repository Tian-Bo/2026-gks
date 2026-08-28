<template>
  <section class="activity-rule-check" :class="`is-${status}`">
    <div class="activity-rule-check__heading" :class="{ 'activity-rule-check__reveal': animate }" style="--activity-rule-check-delay: 0ms">
      <span class="activity-rule-check__marker" aria-hidden="true"></span>
      <span>{{ title }}</span>
    </div>
    <ul class="activity-rule-check__list">
      <li v-for="(check, index) in normalizedChecks" :key="check.code" :class="[`is-${check.level}`, { 'activity-rule-check__reveal': animate }]"
        :style="animate ? { '--activity-rule-check-delay': `${120 + index * 90}ms` } : undefined">
        {{ check.message }}
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type RuleCheck = {
  code?: string
  level?: 'error' | 'warning' | 'info' | string
  message?: string
}

const props = withDefaults(defineProps<{
  title?: string
  status?: string
  checks?: RuleCheck[] | null
  animate?: boolean
}>(), {
  title: '活动规则核对',
  status: 'warning',
  checks: () => [],
})

const normalizedChecks = computed(() => (props.checks || [])
  .filter(check => String(check.message || '').trim())
  .map((check, index) => ({
    code: String(check.code || `rule_check_${index}`),
    level: ['error', 'warning', 'info'].includes(String(check.level)) ? String(check.level) : 'info',
    message: String(check.message || '').trim(),
  })))
</script>

<style scoped>
.activity-rule-check {
  width: min(100%, 672px);
  padding: 14px 16px;
  border: 1px solid #fde68a;
  border-radius: 8px;
  background: #fffbeb;
  color: #713f12;
}

.activity-rule-check.is-blocked {
  border-color: #fecaca;
  background: #fff7f7;
  color: #991b1b;
}

.activity-rule-check__heading {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  line-height: 20px;
}

.activity-rule-check__marker {
  width: 8px;
  height: 8px;
  flex: 0 0 auto;
  border-radius: 50%;
  background: currentColor;
}

.activity-rule-check__list {
  display: grid;
  gap: 6px;
  margin: 10px 0 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
  line-height: 20px;
}

.activity-rule-check__list li {
  position: relative;
  padding-left: 12px;
}

.activity-rule-check__list li::before {
  position: absolute;
  top: 8px;
  left: 0;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: currentColor;
  content: "";
}

.activity-rule-check__reveal {
  opacity: 0;
  transform: translateY(6px);
  animation: activity-rule-check-reveal 320ms cubic-bezier(0.22, 1, 0.36, 1) var(--activity-rule-check-delay, 0ms) both;
}

@keyframes activity-rule-check-reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
