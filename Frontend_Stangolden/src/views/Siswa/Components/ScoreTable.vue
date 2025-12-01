<template>
  <div class="bg-white rounded-xl shadow p-4">
    <div class="flex items-center justify-between mb-3">
      <h4 class="text-sm font-semibold">{{ title }}</h4>
      <div class="text-xs text-slate-500">Nama & Skor</div>
    </div>

    <div class="space-y-2">
      <div
        v-for="(r, idx) in rows"
        :key="idx"
        class="flex items-center justify-between border rounded-md px-3 py-2"
      >
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
            {{ idx + 1 }}
          </div>
          <div class="min-w-0">
            <div class="text-sm truncate">{{ r.name }}</div>
          </div>
        </div>

        <div class="text-sm font-medium text-slate-700">{{ r.score }}</div>
      </div>

      <div v-if="rows.length < 10">
        <div
          v-for="i in (10 - rows.length)"
          :key="'empty-' + i"
          class="flex items-center justify-between border rounded-md px-3 py-2 opacity-60"
        >
          <div class="text-sm text-slate-400">-</div>
          <div class="text-sm text-slate-400">0</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: { type: String, default: '' },
  rows: { type: Array, default: () => [] } // [{ name, score }]
})
</script>

<style scoped>
/* nothing special */
</style>