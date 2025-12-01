<template>
  <div class="bg-white rounded-xl shadow p-4">
    <h4 class="text-sm font-semibold mb-3">{{ title }}</h4>
    <div class="space-y-2">
      <div v-for="it in items" :key="it.rank" class="flex items-center justify-between text-sm">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-medium">{{ it.rank }}</div>
          <div class="min-w-0">
            <div class="truncate font-medium">{{ it.name }}</div>
            <div class="text-xs text-slate-400">Skor: {{ it.score }}</div>
          </div>
        </div>
      </div>
      <div v-if="items.length === 0" class="text-sm text-slate-500">Belum ada data.</div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: { type: String, default: '' },
  items: { type: Array, default: () => [] } // [{ rank, name, score }]
})
</script>

<style scoped>
/* small */
</style>