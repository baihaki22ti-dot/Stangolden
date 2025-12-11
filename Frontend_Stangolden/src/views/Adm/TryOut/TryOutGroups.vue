<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="`TryOut Admin - ${domainLabel}`" description="Kelola folder (group) per domain." />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold">Folder TryOut Besar ({{ domainLabel }})</h3>
            <button @click="loadGroups" class="ml-auto px-3 py-2 bg-sky-600 text-white rounded text-sm">Reload</button>

            <!-- Hanya admin yang bisa lihat tombol Tambah Folder -->
            <button
              v-if="isAdminUser"
              @click="openCreateGroupModal"
              class="px-3 py-2 bg-emerald-600 text-white rounded text-sm"
            >
              Tambah Folder
            </button>
          </div>

          <!-- Tools: Search + Page size -->
          <div class="mt-3 flex items-center gap-3">
            <input
              v-model="search"
              type="search"
              placeholder="Cari folder berdasarkan nama..."
              class="border rounded px-3 py-2 text-sm w-full md:w-80"
              aria-label="Cari folder"
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

        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="filteredGroups.length===0" class="text-sm text-slate-500">Belum ada folder.</div>

          <div v-else class="grid grid-cols-1 md:grid-cols-1 gap-4">
            <div v-for="g in pagedGroups" :key="g.id" class="border rounded p-3 hover:shadow-sm">
              <div class="flex items-center justify-between">
                <div class="min-w-0">
                  <div class="font-semibold truncate">{{ g.name }}</div>
                  <div class="text-xs text-slate-500">Domain: {{ g.domain }} • {{ g.description || '-' }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <router-link
                    :to="{ name: 'TryoutAdminSeriesList', params: { groupId: g.id } }"
                    class="px-2 py-1 rounded border text-sm"
                  >
                    Buka Series
                  </router-link>

                  <!-- Hanya admin yang bisa lihat tombol Hapus -->
                  <button
                    v-if="isAdminUser"
                    @click="removeGroup(g.id)"
                    class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="filteredGroups.length > 0" class="mt-4 flex items-center justify-between text-sm">
            <div class="text-slate-600">
              Menampilkan {{ fromIndex + 1 }}–{{ toIndex }} dari {{ filteredGroups.length }} folder
            </div>
            <div class="flex items-center gap-2">
              <button class="px-3 py-1 border rounded" :disabled="page === 1" @click="page--">Sebelumnya</button>
              <span>Halaman {{ page }} / {{ totalPages }}</span>
              <button class="px-3 py-1 border rounded" :disabled="page === totalPages" @click="page++">Berikutnya</button>
            </div>
          </div>
        </section>

        <div v-if="loading" class="text-sm text-slate-600">Loading...</div>
        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
      </main>
    </div>

    <!-- Create Group Modal (hanya admin) -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="createGroupModal && isAdminUser" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="closeCreateGroupModal"></div>
          <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold">Buat Folder TryOut</h4>
            <div class="text-xs text-slate-600">Domain: <span class="px-2 py-1 rounded bg-slate-100">{{ domain }}</span></div>
            <label class="text-sm">Nama
              <input v-model="newGroup.name" class="w-full border rounded px-3 py-2 text-sm" />
            </label>
            <label class="text-sm">Deskripsi
              <textarea v-model="newGroup.description" class="w-full border rounded px-3 py-2 text-sm" rows="3"></textarea>
            </label>
            <div class="flex items-center justify-end gap-2">
              <button @click="closeCreateGroupModal" class="px-3 py-2 border rounded text-sm">Batal</button>
              <button @click="createGroup" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Simpan</button>
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

const domain = ref('')

// slug domain: upkp | tubel | tugas-belajar | tb
function syncDomain() {
  const raw = (route.params?.domain || '').toString().toLowerCase()
  if (raw === 'tubel' || raw === 'tugas-belajar' || raw === 'tb') {
    domain.value = 'tubel'
  } else {
    domain.value = 'upkp'
  }
}

onMounted(async () => {
  syncDomain()
  await initMe()
  await loadGroups()
})
watch(() => route.params?.domain, async () => {
  syncDomain()
  await loadGroups()
})

const domainLabel = computed(() => domain.value === 'upkp' ? 'UPKP' : 'Tugas Belajar')

const groups = ref([])
const loading = ref(false)
const error = ref('')
const createGroupModal = ref(false)
const newGroup = ref({ name: '', description: '' })

// Search & pagination
const search = ref('')
const page = ref(1)
const perPage = ref(10)

watch(search, () => { page.value = 1 })

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

function openCreateGroupModal() {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  createGroupModal.value = true
}
function closeCreateGroupModal() { createGroupModal.value = false }

async function loadGroups() {
  loading.value = true
  error.value = ''
  try {
    const res = await backendService.admin.tryout.listGroups({ domain: domain.value })
    groups.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
    page.value = 1
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Gagal memuat groups'
    groups.value = []
  } finally {
    loading.value = false
  }
}

const filteredGroups = computed(() => {
  const q = (search.value || '').trim().toLowerCase()
  if (!q) return groups.value
  return (groups.value || []).filter(g => String(g.name || '').toLowerCase().includes(q))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredGroups.value.length / perPage.value)))
const fromIndex = computed(() => (page.value - 1) * perPage.value)
const toIndex = computed(() => Math.min(filteredGroups.value.length, fromIndex.value + perPage.value))
const pagedGroups = computed(() => filteredGroups.value.slice(fromIndex.value, toIndex.value))

async function createGroup() {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  const name = (newGroup.value?.name || '').trim()
  if (!name) return alert('Nama folder wajib')
  try {
    const payload = { domain: domain.value, name, description: newGroup.value?.description || '' }
    await backendService.admin.tryout.createGroup(payload)
    newGroup.value = { name: '', description: '' }
    createGroupModal.value = false
    await loadGroups()
  } catch (e) {
    alert(e?.response?.data?.message || e?.message || 'Gagal membuat folder')
  }
}

async function removeGroup(id) {
  if (!isAdminUser.value) return alert('Aksi ini hanya untuk admin.')
  if (!confirm('Hapus folder ini?')) return
  try {
    await backendService.admin.tryout.destroyGroup(id)
    await loadGroups()
  } catch (e) {
    alert(e?.response?.data?.message || e?.message || 'Gagal menghapus folder')
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>