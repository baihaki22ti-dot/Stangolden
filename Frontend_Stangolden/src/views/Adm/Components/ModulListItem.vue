<template>
  <li class="flex items-start gap-4 p-3 rounded-md border hover:shadow-sm">
    <div class="flex-1">
      <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
          <!-- Klik judul untuk membuka halaman viewer modul -->
          <router-link
            :to="toContent"
            class="block text-sm font-semibold text-slate-800 truncate"
          >
            {{ index + 1 }}. {{ module.name }}
          </router-link>

          <div class="text-xs text-slate-500 mt-1 line-clamp-3">
            {{ module.description }}
          </div>
        </div>

        <div class="ml-4 flex items-center gap-2 shrink-0">
          <!-- Tombol detail menuju viewer -->
          <router-link :to="toContent" class="px-2 py-1 text-xs rounded border">
            Lihat Detail
          </router-link>

          <!-- Kontrol admin -->
          <template v-if="showControls">
            <button @click="$emit('edit', module)" class="px-2 py-1 text-xs rounded border">Edit</button>
            <button @click="$emit('delete', module)" class="px-2 py-1 text-xs rounded bg-red-50 text-red-600">Hapus</button>
          </template>
        </div>
      </div>

      <div class="text-xs text-slate-400 mt-2">
        Dibuat: {{ module.createdAt ? new Date(module.createdAt).toLocaleString() : '-' }}
      </div>
    </div>
  </li>
</template>

<script setup>
import { defineProps, computed } from 'vue'

const props = defineProps({
  module: { type: Object, required: true },
  index: { type: Number, default: 0 },
  showControls: { type: Boolean, default: true }
})

const toContent = computed(() => `/modul/content/${props.module?.id}`)
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  line-clamp: 3; /* standard property for compatibility */
  -webkit-line-clamp: 3; /* maks 3 baris */
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>