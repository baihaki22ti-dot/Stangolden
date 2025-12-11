<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Series TryOut" description="Kelola seri TO dalam folder." />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- Header -->
        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold">Folder: {{ groupTitle }}</h3>

            <!-- Tambah Series hanya untuk admin -->
            <button
              v-if="isAdminUser"
              @click="openCreateSeriesModal"
              class="ml-auto px-3 py-2 bg-emerald-600 text-white rounded text-sm"
            >
              Tambah Series
            </button>
          </div>
          <div class="text-xs text-slate-500">Group ID: {{ groupId ?? '-' }}</div>

          <!-- Tools: Search + Page size -->
          <div class="mt-3 flex items-center gap-3">
            <input
              v-model="search"
              type="search"
              placeholder="Cari series berdasarkan judul..."
              class="border rounded px-3 py-2 text-sm w-full md:w-80"
              aria-label="Cari series"
            />
            <label class="text-sm flex items-center gap-2">
              <span>Per halaman</span>
              <select v-model.number="perPage" class="border rounded px-2 py-1 text-sm">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </label>
          </div>
        </section>

        <!-- Daftar Series -->
        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="loading" class="text-sm text-slate-600">Memuat...</div>
          <div v-else-if="filteredAll.length === 0" class="text-sm text-slate-500">Tryout belum tersedia.</div>

          <div v-else class="grid grid-cols-1 md:grid-cols-1 gap-4">
            <div v-for="s in pagedSeries" :key="s.id" class="border rounded p-3 hover:shadow-sm">
              <div class="flex items-start justify-between">
                <div class="min-w-0">
                  <div class="font-semibold truncate">
                    TO {{ s.number ?? s.id }} {{ s.title ? '- ' + s.title : '' }}
                  </div>
                  <div class="text-xs text-slate-500">Active: {{ s.is_active ? 'Ya' : 'Tidak' }}</div>
                  <div class="text-xs text-slate-500" v-if="s.description">{{ s.description }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <!-- Arahkan ke halaman detail series (sessions) -->
                  <router-link
                    :to="`/tryoutadm/series/${s.id}`"
                    class="px-2 py-1 rounded border text-sm"
                  >
                    Buka TO
                  </router-link>

                  <!-- Edit & Hapus hanya untuk admin -->
                  <button
                    v-if="isAdminUser"
                    @click="openEditSeriesModal(s)"
                    class="px-2 py-1 rounded border text-sm"
                  >
                    Edit
                  </button>
                  <button
                    v-if="isAdminUser"
                    @click="removeSeries(s.id)"
                    class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="filteredAll.length > 0" class="mt-4 flex items-center justify-between text-sm">
            <div class="text-slate-600">
              Menampilkan {{ fromIndex + 1 }}–{{ toIndex }} dari {{ filteredAll.length }} series
            </div>
            <div class="flex items-center gap-2">
              <button class="px-3 py-1 border rounded" :disabled="page === 1" @click="page--">Sebelumnya</button>
              <span>Halaman {{ page }} / {{ totalPages }}</span>
              <button class="px-3 py-1 border rounded" :disabled="page === totalPages" @click="page++">Berikutnya</button>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Modal: Tambah Series (hanya admin) -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="createSeriesModal && isAdminUser" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="closeCreateSeriesModal"></div>
          <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold">Tambah Series TO</h4>
            <label class="text-sm">Nomor TO
              <input v-model.number="newSeries.number" type="number" min="1" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <label class="text-sm">Judul
              <input v-model="newSeries.title" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <label class="text-sm">Deskripsi
              <input v-model="newSeries.description" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <div class="flex items-center justify-end gap-2">
              <button @click="closeCreateSeriesModal" class="px-3 py-2 border rounded text-sm">Batal</button>
              <button @click="createSeries" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Simpan</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal: Edit Series (hanya admin) -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="editSeriesModal && isAdminUser" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="closeEditSeriesModal"></div>
          <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold">Edit Series TO</h4>
            <label class="text-sm">Nomor TO
              <input v-model.number="editSeries.number" type="number" min="1" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <label class="text-sm">Judul
              <input v-model="editSeries.title" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <label class="text-sm">Deskripsi
              <input v-model="editSeries.description" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <label class="text-sm flex items-center gap-2">
              <input type="checkbox" v-model="editSeries.is_active" />
              <span>Active</span>
            </label>
            <div class="flex items-center justify-end gap-2">
              <button @click="closeEditSeriesModal" class="px-3 py-2 border rounded text-sm">Batal</button>
              <button @click="updateSeries" class="px-3 py-2 bg-sky-600 text-white rounded text-sm">Update</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const route = useRoute()

// groupId dari route param; valid hanya jika angka
const groupId = computed(() => {
  const raw = route.params.groupId
  const n = raw ? Number(raw) : NaN
  return Number.isNaN(n) ? null : n
})

const groupTitle = ref('Folder')
const series = ref([]) // raw dari API
const loading = ref(false)

// Search & pagination
const search = ref('')
const page = ref(1)
const perPage = ref(10)
watch(search, () => { page.value = 1 })

// Filter: by group then by search
const filteredAll = computed(() => {
  const gid = groupId.value
  const list = (Array.isArray(series.value) ? series.value : []).filter(s => Number(s.group_id) === Number(gid))
  const q = (search.value || '').trim().toLowerCase()
  if (!q) return list
  return list.filter(s => String(s.title || '').toLowerCase().includes(q))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredAll.value.length / perPage.value)))
const fromIndex = computed(() => (page.value - 1) * perPage.value)
const toIndex = computed(() => Math.min(filteredAll.value.length, fromIndex.value + perPage.value))
const pagedSeries = computed(() => filteredAll.value.slice(fromIndex.value, toIndex.value))

const createSeriesModal = ref(false)
const newSeries = ref({ number: 1, title: '', description: '' })

const editSeriesModal = ref(false)
const editSeries = ref({ id: null, number: 1, title: '', description: '', is_active: false })

// User info untuk kontrol akses admin
const me = ref(null)
const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})

async function initMe() {
  try {
    me.value = await backendService.auth.user()
  } catch (e) {
    me.value = { role: 'siswa', is_admin: false }
  }
}

function openCreateSeriesModal() {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  createSeriesModal.value = true
}
function closeCreateSeriesModal() { createSeriesModal.value = false }

function openEditSeriesModal(s) {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  editSeries.value = {
    id: s.id,
    number: Number(s.number || 1),
    title: s.title || '',
    description: s.description || '',
    is_active: !!s.is_active,
  }
  editSeriesModal.value = true
}
function closeEditSeriesModal() { editSeriesModal.value = false }

async function loadSeries() {
  if (!groupId.value) { series.value = []; return }
  loading.value = true
  try {
    const res = await backendService.admin.tryout.listSeries(groupId.value)
    series.value = res?.data || res || []
    groupTitle.value = `Group #${groupId.value}`
    page.value = 1
  } catch (e) {
    series.value = []
  } finally {
    loading.value = false
  }
}

async function createSeries() {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  const num = Number(newSeries.value?.number || 0)
  if (!num) return alert('Nomor TO wajib')
  try {
    await backendService.admin.tryout.createSeries(groupId.value, {
      number: num,
      title: newSeries.value.title,
      description: newSeries.value.description
    })
    newSeries.value = { number: 1, title: '', description: '' }
    createSeriesModal.value = false
    await loadSeries()
    alert('Series TO berhasil dibuat.')
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal menambah series')
  }
}

async function updateSeries() {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  if (!editSeries.value.id) return
  try {
    const payload = {
      number: editSeries.value.number,
      title: editSeries.value.title,
      description: editSeries.value.description,
      is_active: editSeries.value.is_active ? 1 : 0,
    }
    await backendService.raw.put(`/api/admin/tryout/series/${editSeries.value.id}`, payload)
    editSeriesModal.value = false
    await loadSeries()
    alert('Series TO berhasil diupdate.')
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal update series')
  }
}

async function removeSeries(id) {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  if (!confirm('Hapus series ini?')) return
  try {
    await backendService.admin.tryout.destroySeries(id)
    await loadSeries()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal menghapus series')
  }
}

onMounted(async () => {
  await initMe()
  await loadSeries()
})
watch(() => route.params.groupId, async () => {
  await loadSeries()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>