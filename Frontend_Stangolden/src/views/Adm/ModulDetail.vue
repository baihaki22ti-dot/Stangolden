<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Daftar Modul" description="Kelola modul: tambah, edit, hapus" />

      <main class="p-6 max-w-4xl w-full mx-auto space-y-6">
        <header class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-800">Daftar Modul</h1>
            <p class="text-sm text-slate-500">Kelola materi modul yang akan ditampilkan kepada peserta.</p>
          </div>

          <div class="flex items-center gap-3">
            <button @click="openAdd" class="px-4 py-2 bg-emerald-600 text-white rounded">Tambah Modul</button>
          </div>
        </header>

        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="modules.length === 0" class="text-sm text-slate-500 p-6 text-center">
            Belum ada modul. Klik "Tambah Modul" untuk menambahkan.
          </div>

          <ul v-else class="space-y-3">
            <ModuleListItem
              v-for="(m, idx) in modules"
              :key="m.id"
              :module="m"
              :index="idx"
              @edit="openEdit"
              @delete="confirmDelete"
              :showControls="true"
            />
          </ul>
        </section>
      </main>
    </div>

    <!-- Form modal (add / edit) -->
    <ModuleFormModal
      v-model="formOpen"
      :mode="formMode"
      :module-data="editingModule"
      @submit="handleFormSubmit"
    />

    <!-- confirmation modal for save / delete -->
    <ConfirmModal
      v-model="confirmOpen"
      :title="confirmTitle"
      :message="confirmMessage"
      confirm-label="Ya, lanjutkan"
      @confirm="onConfirm"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import ModuleListItem from '@/views/Adm/Components/ModulListItem.vue'
import ModuleFormModal from '@/views/Adm/Components/ModulFormModal.vue'
import ConfirmModal from '@/views/Adm/Components/ConfirmModal.vue'
import { isSidebarOpen } from '@/stores/sidebar'

// STORAGE_KEY untuk menyimpan modul admin
const STORAGE_KEY = 'app_modul_list_v1'

const isOpen = isSidebarOpen
const modules = ref([])

// form modal state
const formOpen = ref(false)
const formMode = ref('add') // 'add' | 'edit'
const editingModule = ref(null)

// confirm modal state
const confirmOpen = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
let confirmAction = null

function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    modules.value = raw ? JSON.parse(raw) : []
  } catch (e) {
    modules.value = []
  }

  // Jika kosong, tambahkan contoh modul (sample) agar admin melihat contoh dan bisa klik
  if (!modules.value || modules.value.length === 0) {
    const samplePdf = 'https://mozilla.github.io/pdf.js/web/compressed.tracemonkey-pldi-09.pdf' // contoh PDF publik
    const now = new Date().toISOString()
    modules.value = [
      { id: 1, name: 'Modul 1 - Pengenalan', description: 'Ringkasan pengenalan modul 1.', pdfName: 'sample-modul1.pdf', pdfData: samplePdf, createdAt: now },
      { id: 2, name: 'Modul 2 - Konsep Dasar', description: 'Ringkasan konsep dasar untuk modul 2.', pdfName: 'sample-modul2.pdf', pdfData: samplePdf, createdAt: now },
      { id: 3, name: 'Modul 3 - Praktik', description: 'Ringkasan praktik dan latihan.', pdfName: 'sample-modul3.pdf', pdfData: samplePdf, createdAt: now },
      { id: 4, name: 'Modul 4 - Studi Kasus', description: 'Ringkasan studi kasus penting.', pdfName: 'sample-modul4.pdf', pdfData: samplePdf, createdAt: now },
      { id: 5, name: 'Modul 5 - Penutup', description: 'Ringkasan penutup dan tindak lanjut.', pdfName: 'sample-modul5.pdf', pdfData: samplePdf, createdAt: now }
    ]
    save()
  }
}

function save() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(modules.value)) } catch {}
}

// add / edit flows
function openAdd() {
  formMode.value = 'add'
  editingModule.value = null
  formOpen.value = true
}

function openEdit(module) {
  formMode.value = 'edit'
  // clone to avoid direct mutation before save
  editingModule.value = JSON.parse(JSON.stringify(module))
  formOpen.value = true
}

// When ModuleFormModal emits submit(payload), show confirmation before actually saving
function handleFormSubmit(payload) {
  confirmTitle.value = formMode.value === 'add' ? 'Konfirmasi Tambah Modul' : 'Konfirmasi Simpan Perubahan'
  confirmMessage.value = formMode.value === 'add'
    ? `Simpan modul "${payload.name}"?`
    : `Simpan perubahan untuk modul "${payload.name}"?`
  confirmOpen.value = true
  confirmAction = () => {
    if (formMode.value === 'add') {
      // assign id and createdAt
      const id = Date.now()
      modules.value.unshift({ id, ...payload, createdAt: new Date().toISOString() })
    } else if (formMode.value === 'edit') {
      const idx = modules.value.findIndex(m => m.id === payload.id)
      if (idx !== -1) modules.value.splice(idx, 1, payload)
    }
    save()
    formOpen.value = false
  }
}

// delete flow
function confirmDelete(module) {
  confirmTitle.value = 'Konfirmasi Hapus'
  confirmMessage.value = `Hapus modul "${module.name}"? Tindakan ini tidak dapat dibatalkan.`
  confirmOpen.value = true
  confirmAction = () => {
    modules.value = modules.value.filter(m => m.id !== module.id)
    save()
  }
}

// called when confirm modal confirmed
function onConfirm() {
  if (typeof confirmAction === 'function') confirmAction()
  confirmAction = null
  confirmOpen.value = false
}

onMounted(() => {
  load()
})
</script>

<style scoped>
/* small spacing */
ul { list-style: none; padding: 0; margin: 0; }
</style>