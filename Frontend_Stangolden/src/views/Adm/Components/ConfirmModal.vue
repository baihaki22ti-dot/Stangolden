<template>
  <transition name="fade">
    <div v-if="modelValue" class="fixed inset-0 z-70 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="cancel" aria-hidden="true"></div>

      <div class="bg-white rounded-xl shadow-lg z-80 w-full max-w-md p-6">
        <h3 class="text-lg font-semibold mb-2">{{ title }}</h3>
        <p class="text-sm text-slate-600 mb-4">{{ message }}</p>

        <div class="flex justify-end gap-3">
          <button @click="cancel" class="px-3 py-2 border rounded">Batal</button>
          <button @click="confirm" class="px-3 py-2 bg-red-600 text-white rounded">{{ confirmLabel }}</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { defineEmits, defineProps } from 'vue'
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Ya' }
})
const emit = defineEmits(['update:modelValue', 'confirm'])

function cancel() {
  emit('update:modelValue', false)
}
function confirm() {
  emit('confirm')
  emit('update:modelValue', false)
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>