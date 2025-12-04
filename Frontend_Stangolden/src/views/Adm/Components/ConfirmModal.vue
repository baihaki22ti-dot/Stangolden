<template>
  <Teleport to="body">
    <transition name="fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 flex items-center justify-center z-[10000]"
        aria-modal="true"
        role="dialog"
      >
        <!-- overlay -->
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="close"
        ></div>

        <!-- modal content -->
        <div
            class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-5 border border-slate-200"
        >
          <header class="space-y-1">
            <h2 class="text-lg font-semibold text-slate-800">
              {{ title }}
            </h2>
            <p v-if="message" class="text-sm text-slate-600">
              {{ message }}
            </p>
          </header>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 rounded bg-slate-200 text-slate-700 hover:bg-slate-300 text-sm"
            >
              Batal
            </button>
            <button
              type="button"
              @click="confirm"
              class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 text-sm"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Lanjutkan' }
})
const emit = defineEmits(['update:modelValue','confirm'])

function close() {
  emit('update:modelValue', false)
}
function confirm() {
  emit('confirm')
  emit('update:modelValue', false)
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity .18s ease }
.fade-enter-from,
.fade-leave-to { opacity: 0 }
</style>