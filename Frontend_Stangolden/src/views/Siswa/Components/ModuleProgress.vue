<template>
 <div class="space-y-3">
    <div v-for="(m, idx) in items" :key="idx" class="flex items-center gap-3">
      <div class="flex-1">
        <div class="flex items-center justify-between mb-1">
          <div class="text-sm font-medium truncate">{{ m.label }}</div>
          <div class="text-xs text-slate-500">{{ m.percent }}%</div>
        </div>

        <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
          <div
            class="h-3 rounded-full transition-all duration-500"
            :class="m.color || 'bg-slate-400'"
            :style="{ width: (Math.max(0, Math.min(100, m.percent)) + '%') }"
            ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  items: { type: Array, default: () => [] } // [{ label, percent, color }]
})
</script>

<style scoped>
/* nothing special */
</style>