<template>
  <transition name="fade">
    <div v-if="modelValue" class="fixed inset-0 z-60 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="close" aria-hidden="true"></div>

      <div class="bg-white rounded-xl shadow-lg z-70 w-full max-w-2xl p-6">
        <header class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">{{ mode === 'add' ? 'Tambah Modul' : 'Edit Modul' }}</h3>
          <button @click="close" class="text-slate-600">Tutup</button>
        </header>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama modul</label>
            <input v-model="form.name" required class="w-full border rounded px-3 py-2 text-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
            <textarea v-model="form.description" rows="4" class="w-full border rounded px-3 py-2 text-sm"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Upload PDF (opsional)</label>
            <div class="flex items-center gap-3">
              <input type="file" accept="application/pdf" @change="onFileChange" />
              <div v-if="form.pdfName" class="text-sm text-slate-500">{{ form.pdfName }}</div>
              <button v-if="form.pdfData" type="button" @click="removeFile" class="text-xs text-red-600 px-2 py-1 border rounded">Hapus file</button>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <button type="button" @click="close" class="px-4 py-2 border rounded">Batal</button>
            <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded">{{ mode === 'add' ? 'Tambah' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, watch, reactive } from 'vue'
import { defineEmits, defineProps } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  mode: { type: String, default: 'add' }, // 'add' | 'edit'
  moduleData: { type: Object, default: null } // when editing
})

const emit = defineEmits(['update:modelValue', 'submit'])

const form = reactive({
  id: null,
  name: '',
  description: '',
  pdfName: '',
  pdfData: ''
})

watch(() => props.moduleData, (v) => {
  if (v) {
    form.id = v.id
    form.name = v.name || ''
    form.description = v.description || ''
    form.pdfName = v.pdfName || ''
    form.pdfData = v.pdfData || ''
  } else {
    // reset
    form.id = null
    form.name = ''
    form.description = ''
    form.pdfName = ''
    form.pdfData = ''
  }
}, { immediate: true })

function close() {
  emit('update:modelValue', false)
}

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    form.pdfData = reader.result
    form.pdfName = file.name
  }
  reader.readAsDataURL(file)
}

function removeFile() {
  form.pdfData = ''
  form.pdfName = ''
}

function submit() {
  // basic validation
  if (!form.name.trim()) return
  // build payload
  const payload = {
    id: form.id,
    name: form.name.trim(),
    description: form.description.trim(),
    pdfName: form.pdfName || '',
    pdfData: form.pdfData || ''
  }
  emit('submit', payload)
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>