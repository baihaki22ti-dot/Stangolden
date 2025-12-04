<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="pageTitle" description="Kelola modul berdasarkan halaman (group/sub group)" />

      <main class="p-6 max-w-4xl w-full mx-auto space-y-6">
        <header class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ pageTitle }}</h1>
            <p class="text-sm text-slate-500">Daftar modul untuk halaman ini.</p>
          </div>

          <div class="flex items-center gap-3">
            <button @click="openAdd" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">
              Tambah Modul
            </button>
          </div>
        </header>

        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center gap-3 mb-4">
            <span class="inline-flex items-center gap-2 text-xs px-2 py-1 border rounded">
              <strong>Group:</strong> {{ groupLabel }}
            </span>
            <span class="inline-flex items-center gap-2 text-xs px-2 py-1 border rounded">
              <strong>Halaman:</strong> {{ subLabel }}
            </span>
          </div>

          <div v-if="loading" class="p-6 text-center text-sm text-slate-500">Memuat...</div>

          <div v-else-if="modules.length === 0" class="text-sm text-slate-500 p-6 text-center">
            Belum ada modul pada halaman ini. Klik "Tambah Modul" untuk menambahkan.
          </div>

          <ul v-else class="space-y-3">
            <li v-for="m in modules" :key="m.id" class="flex items-start justify-between gap-3 border-b pb-3">
              <div class="space-y-1">
                <div class="font-medium text-slate-800">{{ m.name }}</div>
                <div class="text-xs text-slate-500">{{ m.description }}</div>
                <div class="text-xs">
                  <a
                    v-if="m.pdf_url"
                    :href="m.pdf_url"
                    target="_blank"
                    rel="noopener"
                    class="text-sky-600 hover:underline"
                  >
                    Buka PDF ({{ m.pdf_name }})
                  </a>
                  <span v-else class="text-slate-400">PDF belum tersedia</span>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <button @click="openEdit(m)" class="px-3 py-1 border rounded text-sm hover:bg-slate-50">Edit</button>
                <button @click="confirmDelete(m)" class="px-3 py-1 rounded bg-red-50 text-red-600 text-sm hover:bg-red-100">Hapus</button>
              </div>
            </li>
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

    <!-- confirmation modal for delete -->
    <ConfirmModal
      v-model="confirmOpen"
      :title="confirmTitle"
      :message="confirmMessage"
      confirm-label="Ya, hapus"
      @confirm="onConfirm"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import ConfirmModal from '@/views/Adm/Components/ConfirmModal.vue'
import ModuleFormModal from '@/views/Adm/Components/ModulFormModal.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const route = useRoute()

const modules = ref([])
const loading = ref(false)
const pageTitle = ref('')

const groupLabel = ref('')
const subLabel = ref('')

// form modal state
const formOpen = ref(false)
const formMode = ref('add') // 'add' | 'edit'
const editingModule = ref(null)

// confirm modal state
const confirmOpen = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
let confirmAction = null

function toTitle(str) {
  return (str || '')
    .replace(/-/g, ' ')
    .replace(/\b\w/g, s => s.toUpperCase())
}

async function load() {
  loading.value = true
  try {
    const group = String(route.params.group || '')
    const sub = String(route.params.sub || '')
    groupLabel.value = group === 'upkp' ? 'UPKP' : (group === 'tugas-belajar' ? 'Tugas Belajar' : group)
    subLabel.value = toTitle(sub)
    pageTitle.value = `Halaman ${groupLabel.value} - ${subLabel.value}`

    // filter by group and sub_group
    modules.value = await backendService.modules.list({ group, sub_group: sub })
  } catch (e) {
    console.error('Gagal memuat modul', e)
    modules.value = []
  } finally {
    loading.value = false
  }
}

function openAdd() {
  formMode.value = 'add'
  editingModule.value = null
  formOpen.value = true
}

function openEdit(m) {
  formMode.value = 'edit'
  editingModule.value = { ...m }
  formOpen.value = true
}

async function handleFormSubmit(payload) {
  try {
    // inject group + sub_group dari route agar konsisten dengan halaman
    const group = String(route.params.group || '')
    const sub = String(route.params.sub || '')
    const finalPayload = { ...payload, group, sub_group: sub }

    if (formMode.value === 'add') {
      await backendService.modules.create(finalPayload)
    } else {
      await backendService.modules.update(editingModule.value.id, finalPayload)
    }
    formOpen.value = false
    await load()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e.message || 'Gagal menyimpan modul')
  }
}

function confirmDelete(m) {
  confirmTitle.value = 'Konfirmasi Hapus Modul'
  confirmMessage.value = `Hapus modul "${m.name}"? Tindakan ini tidak dapat dibatalkan.`
  confirmOpen.value = true
  confirmAction = async () => {
    await backendService.modules.remove(m.id)
    await load()
  }
}

async function onConfirm() {
  try {
    if (typeof confirmAction === 'function') await confirmAction()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e.message || 'Gagal menghapus modul')
  } finally {
    confirmAction = null
    confirmOpen.value = false
  }
}

onMounted(load)
watch(() => route.fullPath, load)
</script>