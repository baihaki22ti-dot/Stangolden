<template>
  <div v-if="modelValue" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)"></div>
    <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg p-6 space-y-4">
      <h4 class="text-lg font-semibold">{{ mode === 'edit' ? 'Edit Modul' : 'Tambah Modul' }}</h4>

      <label class="text-sm">Nama
        <input v-model="form.name" class="w-full border rounded px-3 py-2 text-sm" />
      </label>
      <label class="text-sm">Deskripsi
        <textarea v-model="form.description" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
      </label>

      <!-- Link YouTube (opsional) -->
      <label class="text-sm">Link YouTube (opsional)
        <input
          v-model="form.youtube_url"
          placeholder="https://youtu.be/xxxx atau https://www.youtube.com/watch?v=xxxx"
          class="w-full border rounded px-3 py-2 text-sm"
        />
      </label>

      <!-- Preview embed jika link valid -->
      <div v-if="youtubeEmbedUrl" class="mt-2">
        <div class="text-xs text-slate-500 mb-1">Preview Video</div>
        <div class="aspect-video w-full rounded overflow-hidden border bg-black/5">
          <iframe
            :src="youtubeEmbedUrl"
            title="YouTube video preview"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            class="w-full h-full"
          ></iframe>
        </div>
      </div>

      <label class="text-sm">PDF
        <input type="file" accept="application/pdf" @change="onPdf" class="w-full text-sm" />
        <div v-if="mode==='edit' && moduleData?.pdf_name" class="text-xs text-slate-500 mt-1">Saat ini: {{ moduleData.pdf_name }}</div>
      </label>

      <div class="flex items-center justify-end gap-2">
        <button @click="$emit('update:modelValue', false)" class="px-3 py-2 border rounded text-sm">Batal</button>
        <button @click="submit" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Simpan</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  mode: { type: String, default: 'add' }, // 'add' | 'edit'
  moduleData: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue', 'submit'])

const form = reactive({
  name: '',
  description: '',
  youtube_url: '',
  pdfFile: null,
})

watch(() => props.moduleData, (m) => {
  if (props.mode === 'edit' && m) {
    form.name = m.name || ''
    form.description = m.description || ''
    form.youtube_url = m.youtube_url || ''
    form.pdfFile = null
  } else {
    form.name = ''
    form.description = ''
    form.youtube_url = ''
    form.pdfFile = null
  }
}, { immediate: true })

function onPdf(e) {
  form.pdfFile = e.target.files?.[0] || null
}

// Build embed url if possible
const youtubeEmbedUrl = computed(() => {
  const u = String(form.youtube_url || '').trim()
  if (!u) return ''
  const short = u.match(/youtu\.be\/([A-Za-z0-9_-]{6,})/)
  const long = u.match(/[?&]v=([A-Za-z0-9_-]{6,})/)
  const id = (short && short[1]) || (long && long[1]) || ''
  return id ? `https://www.youtube.com/embed/${id}` : ''
})

function submit() {
  emit('submit', { ...form })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>