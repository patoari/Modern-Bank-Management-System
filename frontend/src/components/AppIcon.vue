<template>
  <component :is="'svg'" viewBox="0 0 24 24" fill="none" :stroke="stroke" :stroke-width="strokeWidth"
    stroke-linecap="round" stroke-linejoin="round"
    :class="sizeClass" :style="style" aria-hidden="true">
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  size: { type: [Number, String], default: 18 },
  stroke: { type: String, default: 'currentColor' },
  strokeWidth: { type: [Number, String], default: 1.75 },
  class: String,
})
const sizeClass = computed(() => props.class || '')
const style = computed(() => ({ width: `${props.size}px`, height: `${props.size}px`, flexShrink: 0 }))
</script>
