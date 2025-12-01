<template>
  <transition name="fade">
    <div v-if="modelValue" class="fixed inset-0 z-60 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="close" aria-hidden="true"></div>

      <div class="bg-white rounded-xl shadow-lg z-70 w-full max-w-2xl p-6">

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama modul</label>
            <input v-model="form.name" required class="w-full border rounded px-3 py-2 text-sm" />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
            <textarea v-model="form.description" rows="4" class="w-full border rounded px-3 py-2 text-sm"></textarea>
          </div>

        </form>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, watch, reactive } from 'vue'
import { defineEmits, defineProps } from 'vue'


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