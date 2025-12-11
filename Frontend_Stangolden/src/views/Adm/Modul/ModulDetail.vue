<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="pageTitle" description="Kelola modul per halaman" />

      <main class="p-6 max-w-4xl w-full mx-auto space-y-6">
        <!-- Header -->
        <header class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ pageTitle }}</h1>
            <p class="text-sm text-slate-500">Daftar modul pada halaman ini.</p>
          </div>
          <div class="flex items-center gap-3">
            <button
              v-if="isAdminUser"
              @click="openAdd"
              class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
            >
              Tambah Modul
            </button>
          </div>
        </header>

        <!-- Tools: Search + Page size -->
        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <input
              v-model.trim="search"
              type="search"
              placeholder="Cari modul berdasarkan nama..."
              class="border rounded px-3 py-2 text-sm w-full md:w-80"
              aria-label="Cari modul"
            />
            <div class="flex items-center gap-3">
              <label class="text-sm flex items-center gap-2">
                <span>Per halaman</span>
                <select v-model.number="perPage" class="border rounded px-2 py-1 text-sm">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </label>
              <button @click="load" class="px-3 py-2 bg-sky-600 text-white rounded text-sm hover:bg-sky-700">
                Reload
              </button>
            </div>
          </div>
        </section>

        <!-- List -->
        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="loading" class="p-6 text-center text-sm text-slate-500">Memuat...</div>

          <div v-else-if="filteredModules.length === 0" class="text-sm text-slate-500 p-6 text-center">
            Tidak ada modul yang cocok.
          </div>

          <ul v-else class="space-y-3">
            <li v-for="m in pagedModules" :key="m.id" class="flex items-start justify-between gap-3 border-b pb-3">
              <div class="space-y-1 flex-1">
                <div class="font-medium text-slate-800 truncate">{{ m.name }}</div>
                <div class="text-xs text-slate-500 line-clamp-2">{{ m.description }}</div>
                <div class="text-xs text-slate-400">
                  Dibuat: {{ m.created_at ? new Date(m.created_at).toLocaleString() : '-' }}
                </div>
              </div>

              <!-- Controls (right side) -->
              <div class="flex items-center gap-2 shrink-0">
                <!-- Buka Modul (viewer) -->
                <router-link
                  :to="`/modul/content/${m.id}`"
                  class="px-3 py-1 rounded text-sm bg-indigo-600 text-white hover:bg-indigo-700"
                  title="Buka modul (preview PDF & YouTube)"
                >
                  Buka Modul
                </router-link>

                <!-- Edit -->
                <button
                  v-if="isAdminUser"
                  @click="openEdit(m)"
                  class="px-3 py-1 rounded text-sm bg-amber-500 text-white hover:bg-amber-600"
                  title="Edit modul"
                >
                  Edit
                </button>

                <!-- Hapus -->
                <button
                  v-if="isAdminUser"
                  @click="confirmDelete(m)"
                  class="px-3 py-1 rounded text-sm bg-rose-600 text-white hover:bg-rose-700"
                  title="Hapus modul"
                >
                  Hapus
                </button>
              </div>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="filteredModules.length > 0" class="mt-4 flex items-center justify-between text-sm">
            <div class="text-slate-600">
              Menampilkan {{ fromIndex + 1 }}–{{ toIndex }} dari {{ filteredModules.length }} modul
            </div>
            <div class="flex items-center gap-2">
              <button class="px-3 py-1 border rounded hover:bg-slate-50" :disabled="page === 1" @click="page--">
                Sebelumnya
              </button>
              <span>Halaman {{ page }} / {{ totalPages }}</span>
              <button class="px-3 py-1 border rounded hover:bg-slate-50" :disabled="page === totalPages" @click="page++">
                Berikutnya
              </button>
            </div>
          </div>
        </section>
      </main>
    </div>

    <ModuleFormModal v-model="formOpen" :mode="formMode" :module-data="editingModule" @submit="handleFormSubmit" />
    <ConfirmModal v-model="confirmOpen" :title="confirmTitle" :message="confirmMessage" confirm-label="Ya, hapus" @confirm="onConfirm" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
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
const pageTitle = ref('Halaman Modul')

const formOpen = ref(false)
const formMode = ref('add')
const editingModule = ref(null)

const confirmOpen = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
let confirmAction = null

// Search & Pagination
const search = ref('')
const page = ref(1)
const perPage = ref(10)
watch(search, () => { page.value = 1 })

function normalizeSlug(s) { return String(s || '').trim().toLowerCase().replace(/\s+/g, '-') }

async function load() {
  loading.value = true
  try {
    const group = normalizeSlug(route.params.group)
    const sub = normalizeSlug(route.params.slug)

    const params = {}
    if (group) params.group = group
    if (sub) params.sub_group = sub

    if (!params.group && !params.sub_group) {
      modules.value = []
      return
    }

    const res = await backendService.adminModules.list(params)
    modules.value = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : [])
    page.value = 1
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
  const group = normalizeSlug(route.params.group)
  const sub = normalizeSlug(route.params.slug)
  if (!group) { alert('Group belum diisi. Navigasi ke halaman dengan group yang valid.'); return }
  if (!sub) { alert('Halaman (sub_group) belum diisi. Navigasi ke halaman dengan sub_group valid.'); return }

  try {
    const common = {
      name: payload.name,
      group,
      sub_group: sub,
      description: payload.description,
      pdfFile: payload.pdfFile,
      youtube_url: payload.youtube_url || ''
    }
    if (formMode.value === 'add') {
      await backendService.adminModules.create(common)
    } else {
      await backendService.adminModules.update(editingModule.value.id, common)
    }
    formOpen.value = false
    await load()
  } catch (e) {
    console.error('Submit error:', e)
    alert(e?.response?.data?.message || e.message || 'Gagal menyimpan modul')
  }
}

function confirmDelete(m) {
  confirmTitle.value = 'Konfirmasi Hapus Modul'
  confirmMessage.value = `Hapus modul "${m.name}"? Tindakan ini tidak dapat dibatalkan.`
  confirmOpen.value = true
  confirmAction = async () => {
    await backendService.adminModules.remove(m.id)
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

const me = ref(null)
const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})
onMounted(async () => {
  try {
    me.value = await backendService.auth.user()
  } catch (e) {
    console.warn('Gagal memuat user, default siswa:', e)
    me.value = { role: 'siswa', is_admin: false }
  }
})

onMounted(load)
watch(() => route.fullPath, load)

// Computed: filter & page
const filteredModules = computed(() => {
  const q = (search.value || '').toLowerCase().trim()
  if (!q) return modules.value
  return (modules.value || []).filter(m => String(m.name || '').toLowerCase().includes(q))
})
const totalPages = computed(() => Math.max(1, Math.ceil(filteredModules.value.length / perPage.value)))
const fromIndex = computed(() => (page.value - 1) * perPage.value)
const toIndex = computed(() => Math.min(filteredModules.value.length, fromIndex.value + perPage.value))
const pagedModules = computed(() => filteredModules.value.slice(fromIndex.value, toIndex.value))
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  line-clamp: 2; /* standard property for compatibility */
  -webkit-line-clamp: 2; /* maks 3 baris */
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>