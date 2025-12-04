<template>
  <li class="flex items-start gap-4 p-3 rounded-md border hover:shadow-sm">
    <div class="flex-1">
      <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
          <!-- CHANGED: Judul jadi link ke halaman viewer modul -->
          <router-link
            :to="`/modul/content/${module.id}`"
            class="block text-sm font-semibold text-slate-800 truncate"
          >
            {{ index + 1 }}. {{ module.name }}
          </router-link>

          <div class="text-xs text-slate-500 mt-1 line-clamp-3">{{ module.description }}</div>
        </div>

        <div class="ml-4 flex items-center gap-2">
          <a v-if="module.pdfName" :href="module.pdfData" target="_blank" class="text-xs text-sky-600 underline">
            {{ module.pdfName }}
          </a>

          <template v-if="showControls">
            <button @click="$emit('edit', module)" class="px-2 py-1 text-xs rounded border">Edit</button>
            <button @click="$emit('delete', module)" class="px-2 py-1 text-xs rounded bg-red-50 text-red-600">Hapus</button>
          </template>
        </div>
      </div>

      <div class="text-xs text-slate-400 mt-2">Dibuat: {{ module.createdAt ? new Date(module.createdAt).toLocaleString() : '-' }}</div>
    </div>
  </li>
</template>

<script setup>
import { defineProps } from 'vue'
const props = defineProps({
  module: { type: Object, required: true },
  index: { type: Number, default: 0 },
  showControls: { type: Boolean, default: true }
})
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>