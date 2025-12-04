<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 flex items-center justify-center z-[10000] p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

        <form @submit.prevent="submit" class="relative w-full max-w-lg bg-white rounded-xl shadow-lg p-6 space-y-5 border border-slate-200">
          <h2 class="text-lg font-semibold">{{ mode === 'add' ? 'Tambah Modul' : 'Edit Modul' }}</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Nama Modul</label>
              <input v-model.trim="form.name" type="text" class="w-full border rounded px-3 py-2 text-sm" required />
            </div>

            <!-- Group & Sub Group dikendalikan oleh halaman; tidak perlu input di modal agar sederhana -->
            <div class="text-xs text-slate-500">
              Grup dan Halaman mengikuti rute saat ini.
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Deskripsi</label>
              <textarea v-model.trim="form.description" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">
                File PDF {{ mode==='add' ? '(wajib)' : '(opsional untuk ganti)' }}
              </label>
              <input type="file" accept="application/pdf" @change="onFileChange" class="w-full text-sm" :required="mode==='add'" />
              <p v-if="fileName" class="text-xs text-slate-500 mt-1">Dipilih: {{ fileName }}</p>
              <p v-else-if="mode==='edit' && moduleData?.pdf_name" class="text-xs text-slate-500 mt-1">Saat ini: {{ moduleData.pdf_name }}</p>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <button type="button" @click="close" class="px-4 py-2 border rounded">Batal</button>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded">{{ mode==='add' ? 'Tambah' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </transition>
  </Teleport>
</template>

<!-- <script setup>
import { reactive, ref, watch, computed } from 'vue'

const props = defineProps({ modelValue: Boolean, mode: String, moduleData: Object })
const emit = defineEmits(['update:modelValue','submit'])

const form = reactive({ id: null, name: '', group: '', sub_group: '', description: '', pdfFile: null })
const fileName = ref('')

const SUB_GROUP_MAP = {
  upkp: [
    { value: 'wawasan-kebangsaan', label: 'Wawasan Kebangsaan' },
    { value: 'nilai-nilai-kemenkeu', label: 'Nilai-Nilai Kemenkeu' },
    { value: 'etika-pns', label: 'Etika PNS' },
    { value: 'tata-aturan-kepegawaian', label: 'Tata Aturan Kepegawaian' },
    { value: 'fungsi-kemenkeu', label: 'Fungsi Kemenkeu' },
  ],
  'tugas-belajar': [
    { value: 'tpa', label: 'TPA' },
    { value: 'tbi', label: 'TBI' },
  ]
}

const subGroupOptions = computed(() => SUB_GROUP_MAP[form.group] || [])

watch(() => form.group, () => { form.sub_group = '' })

watch(() => props.moduleData, (val) => {
  if (props.mode === 'edit' && val) {
    form.id = val.id
    form.name = val.name ?? ''
    form.group = val.group ?? ''
    form.sub_group = val.sub_group ?? ''
    form.description = val.description ?? ''
    form.pdfFile = null
    fileName.value = ''
  } else {
    form.id = null; form.name = ''; form.group = ''; form.sub_group = ''; form.description = ''; form.pdfFile = null; fileName.value = ''
  }
}, { immediate: true })

function onFileChange(e) {
  const f = e.target.files?.[0] || null
  form.pdfFile = f
  fileName.value = f ? f.name : ''
}

function close() { emit('update:modelValue', false) }
function submit() { emit('submit', { ...form }) }
</script> -->
<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({ modelValue: Boolean, mode: String, moduleData: Object })
const emit = defineEmits(['update:modelValue','submit'])

const form = reactive({ id: null, name: '', description: '', pdfFile: null })
const fileName = ref('')

watch(() => props.moduleData, (val) => {
  if (props.mode === 'edit' && val) {
    form.id = val.id
    form.name = val.name ?? ''
    form.description = val.description ?? ''
    form.pdfFile = null
    fileName.value = ''
  } else {
    form.id = null; form.name = ''; form.description = ''; form.pdfFile = null; fileName.value = ''
  }
}, { immediate: true })

function onFileChange(e) {
  const f = e.target.files?.[0] || null
  form.pdfFile = f
  fileName.value = f ? f.name : ''
}

function close() { emit('update:modelValue', false) }
function submit() {
  emit('submit', { name: form.name, description: form.description, pdfFile: form.pdfFile })
}


</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>