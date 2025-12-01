<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Peserta" description="Kelola pendaftaran, aktivasi, masa berlaku, dan kategori peserta" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- header / actions -->
        <section class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">Manajemen Peserta</h2>
            <p class="text-sm text-slate-500">Terima peserta, atur masa aktif, dan tetapkan kategori (UPKP / Tugas Belajar).</p>
          </div>

          <div class="flex items-center gap-3">
            <input v-model="q" type="search" placeholder="Cari nama atau email..."
                   class="border rounded px-3 py-2 text-sm" />
            <select v-model="filter.category" class="border rounded px-3 py-2 text-sm">
              <option value="">Semua kategori</option>
              <option value="upkp">UPKP</option>
              <option value="tugas-belajar">Tugas Belajar</option>
              <option value="both">Keduanya</option>
            </select>

            <button @click="exportCsv" class="px-3 py-2 bg-sky-600 text-white rounded text-sm">Export CSV</button>
            <button @click="createFake" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Tambah contoh</button>
          </div>
        </section>

        <!-- summary -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white rounded-xl shadow p-4">
            <div class="text-sm text-slate-500">Total Registrasi</div>
            <div class="text-2xl font-bold">{{ peserta.length }}</div>
          </div>
          <div class="bg-white rounded-xl shadow p-4">
            <div class="text-sm text-slate-500">Menunggu ACC</div>
            <div class="text-2xl font-bold text-amber-600">{{ pendingCount }}</div>
          </div>
          <div class="bg-white rounded-xl shadow p-4">
            <div class="text-sm text-slate-500">Aktif Saat Ini</div>
            <div class="text-2xl font-bold text-emerald-600">{{ activeCount }}</div>
          </div>
        </section>

        <!-- peserta table -->
        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Daftar Pendaftar</h3>
            <div class="text-sm text-slate-500">Klik "ACC" untuk mengaktifkan. Atur tanggal kadaluarsa untuk menonaktifkan otomatis.</div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="text-left text-slate-600">
                <tr>
                  <th class="py-2 pr-4">#</th>
                  <th class="py-2 pr-4">Nama / Email</th>
                  <th class="py-2 pr-4">Kategori</th>
                  <th class="py-2 pr-4">Status</th>
                  <th class="py-2 pr-4">Masa Aktif Sampai</th>
                  <th class="py-2 pr-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(p, idx) in paged" :key="p.id" class="border-t">
                  <td class="py-3 pr-4 align-top w-10">{{ startIndex + idx + 1 }}</td>

                  <td class="py-3 pr-4 align-top">
                    <div class="font-medium">{{ p.name }}</div>
                    <div class="text-xs text-slate-500">{{ p.email }}</div>
                  </td>

                  <td class="py-3 pr-4 align-top">
                    <div class="flex flex-col text-xs">
                      <label class="inline-flex items-center gap-2">
                        <input type="checkbox" :checked="p.categories.upkp" @change="toggleCategory(p.id,'upkp',$event.target.checked)" />
                        <span>UPKP</span>
                      </label>
                      <label class="inline-flex items-center gap-2">
                        <input type="checkbox" :checked="p.categories.tugasBelajar" @change="toggleCategory(p.id,'tugasBelajar',$event.target.checked)" />
                        <span>Tugas Belajar</span>
                      </label>
                    </div>
                  </td>

                  <td class="py-3 pr-4 align-top">
                    <div class="flex items-center gap-2">
                      <span :class="p.active ? 'px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700' : 'px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600'">
                        {{ p.active ? 'Aktif' : (p.approved ? 'Dinonaktifkan' : 'Menunggu') }}
                      </span>
                      <span v-if="isExpired(p)" class="text-xs text-red-600">Kadaluarsa</span>
                    </div>
                  </td>

                  <td class="py-3 pr-4 align-top">
                    <div class="flex items-center gap-2">
                      <input type="date" :value="p.expiresAt ? formatInputDate(p.expiresAt) : ''" @change="setExpiry(p.id, $event.target.value)" class="border rounded px-2 py-1 text-sm" />
                      <button @click="clearExpiry(p.id)" class="text-xs text-slate-600 px-2 py-1 rounded border">Clear</button>
                    </div>
                  </td>

                  <td class="py-3 pr-4 align-top">
                    <div class="flex items-center gap-2">
                      <button v-if="!p.approved" @click="approve(p.id)" class="px-3 py-1 rounded bg-amber-500 text-white text-sm">ACC</button>
                      <button v-else @click="revoke(p.id)" class="px-3 py-1 rounded border text-sm">Revoke</button>

                      <button @click="toggleActive(p.id)" :class="p.active ? 'px-3 py-1 rounded bg-red-50 text-red-600 border' : 'px-3 py-1 rounded bg-emerald-50 text-emerald-600 border' " class="text-sm">
                        {{ p.active ? 'Nonaktifkan' : 'Aktifkan' }}
                      </button>

                      <button @click="editCandidate(p.id)" class="px-2 py-1 rounded border text-sm">Edit</button>
                      <button @click="remove(p.id)" class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm">Hapus</button>
                    </div>
                  </td>
                </tr>

                <tr v-if="filtered.length === 0">
                  <td colspan="6" class="py-6 text-center text-slate-500">Tidak ada pendaftar sesuai filter</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- pagination -->
          <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-slate-500">Halaman {{ page }} / {{ pageCount }}</div>
            <div class="flex items-center gap-2">
              <button @click="prev" :disabled="page===1" class="px-3 py-1 rounded border text-sm">Prev</button>
              <button @click="next" :disabled="page===pageCount" class="px-3 py-1 rounded border text-sm">Next</button>
            </div>
          </div>
        </section>

        <!-- Edit modal (inline simple drawer) -->
        <section v-if="editing" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/40" @click="stopEditing"></div>
          <div class="bg-white rounded-xl shadow-lg p-6 z-60 w-full max-w-xl">
            <h4 class="text-lg font-semibold mb-3">Edit Peserta</h4>

            <div class="grid grid-cols-1 gap-3">
              <label class="text-sm">Nama
                <input v-model="editForm.name" class="w-full border rounded px-3 py-2 text-sm" />
              </label>

              <label class="text-sm">Email
                <input v-model="editForm.email" class="w-full border rounded px-3 py-2 text-sm" />
              </label>

              <label class="text-sm">Kategori</label>
              <div class="flex gap-3">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" v-model="editForm.categories.upkp" /> UPKP
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" v-model="editForm.categories.tugasBelajar" /> Tugas Belajar
                </label>
              </div>

              <label class="text-sm">Masa aktif sampai
                <input type="date" v-model="editForm.expiresAt" class="w-full border rounded px-3 py-2 text-sm" />
              </label>

              <div class="flex items-center justify-end gap-3 mt-4">
                <button @click="stopEditing" class="px-3 py-2 border rounded">Batal</button>
                <button @click="saveEdit" class="px-3 py-2 bg-sky-600 text-white rounded">Simpan</button>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
/*
  PesertaView.vue
  - Admin dapat melihat daftar registrasi peserta
  - ACC (approve) peserta sehingga mereka bisa login (approved + active)
  - Admin bisa set tanggal kadaluarsa (expiresAt) — jika tanggal lewat, user dinonaktifkan otomatis
  - Admin bisa menandai kategori peserta (upkp, tugasBelajar) atau keduanya
  - Data disimpan di localStorage (key: app_peserta_v1) untuk demo / pengujian; adapt ke API jika perlu
*/

import { ref, computed, watch, onMounted, reactive } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'

const STORAGE_KEY = 'app_peserta_v1'

// reactive state
const isOpen = isSidebarOpen
const q = ref('')
const filter = reactive({ category: '' })
const page = ref(1)
const perPage = 8

const peserta = ref([]) // array of peserta objects

// editing state
const editing = ref(false)
const editForm = reactive({
  id: null,
  name: '',
  email: '',
  categories: { upkp: false, tugasBelajar: false },
  expiresAt: '', // yyyy-mm-dd
  approved: false,
  active: false
})

// quick helper to load/save
function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    peserta.value = raw ? JSON.parse(raw) : []
  } catch (e) {
    peserta.value = []
  }
  // on load ensure expired accounts are deactivated
  deactivateExpired()
}

function save() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(peserta.value)) } catch {}
}

// small demo helper to add example users
function createFake() {
  const id = Date.now() + Math.floor(Math.random()*1000)
  peserta.value.unshift({
    id,
    name: 'User ' + String(id).slice(-4),
    email: `user${String(id).slice(-4)}@example.com`,
    categories: { upkp: Math.random() > 0.5, tugasBelajar: Math.random() > 0.5 },
    expiresAt: '', // not set
    approved: false,
    active: false,
    createdAt: new Date().toISOString()
  })
  save()
}

// approve / revoke
function approve(id) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  p.approved = true
  p.active = true
  save()
}

function revoke(id) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  p.approved = false
  p.active = false
  save()
}

// toggle active manually (admin override)
function toggleActive(id) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  p.active = !p.active
  save()
}

// categories
function toggleCategory(id, key, checked) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  if (key === 'upkp') p.categories.upkp = checked
  if (key === 'tugasBelajar') p.categories.tugasBelajar = checked
  save()
}

// expiry helpers
function setExpiry(id, isoDate) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  p.expiresAt = isoDate || ''
  // if setting a past date, immediately mark inactive
  if (p.expiresAt && new Date(p.expiresAt) < startOfToday()) {
    p.active = false
  }
  save()
}

function clearExpiry(id) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  p.expiresAt = ''
  save()
}

function isExpired(p) {
  if (!p.expiresAt) return false
  const e = new Date(p.expiresAt)
  // treat end of day as inclusive
  e.setHours(23,59,59,999)
  return e < new Date()
}

function deactivateExpired() {
  const now = new Date()
  let changed = false
  peserta.value.forEach(p => {
    if (p.expiresAt) {
      const e = new Date(p.expiresAt)
      e.setHours(23,59,59,999)
      if (e < now && p.active) {
        p.active = false
        changed = true
      }
    }
  })
  if (changed) save()
}

// editing helpers
function editCandidate(id) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  editForm.id = p.id
  editForm.name = p.name
  editForm.email = p.email
  editForm.categories = { ...p.categories }
  editForm.expiresAt = p.expiresAt ? p.expiresAt : ''
  editForm.approved = !!p.approved
  editForm.active = !!p.active
  editing.value = true
}

function stopEditing() {
  editing.value = false
  // reset form
  editForm.id = null
}

function saveEdit() {
  const p = peserta.value.find(x => x.id === editForm.id)
  if (!p) return
  p.name = editForm.name
  p.email = editForm.email
  p.categories = { ...editForm.categories }
  p.expiresAt = editForm.expiresAt || ''
  p.approved = !!editForm.approved
  p.active = !!editForm.active
  save()
  editing.value = false
}

// remove peserta
function remove(id) {
  if (!confirm('Hapus peserta ini?')) return
  peserta.value = peserta.value.filter(x => x.id !== id)
  save()
}

// filters and pagination
const filtered = computed(() => {
  const qv = q.value.trim().toLowerCase()
  return peserta.value.filter(p => {
    if (filter.category) {
      if (filter.category === 'upkp' && !p.categories.upkp) return false
      if (filter.category === 'tugas-belajar' && !p.categories.tugasBelajar) return false
      if (filter.category === 'both' && !(p.categories.upkp && p.categories.tugasBelajar)) return false
    }
    if (!qv) return true
    return (p.name && p.name.toLowerCase().includes(qv)) || (p.email && p.email.toLowerCase().includes(qv))
  })
})

const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))

const paged = computed(() => {
  const start = (page.value - 1) * perPage
  return filtered.value.slice(start, start + perPage)
})

const startIndex = computed(() => (page.value - 1) * perPage)

function next() { if (page.value < pageCount.value) page.value++ }
function prev() { if (page.value > 1) page.value-- }

// summary counts
const pendingCount = computed(() => peserta.value.filter(p => !p.approved).length)
const activeCount = computed(() => peserta.value.filter(p => p.active).length)

// utilities
function formatInputDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  // yyyy-mm-dd
  return d.toISOString().slice(0,10)
}

function formatDate(iso) {
  if (!iso) return '-'
  try { return new Date(iso).toLocaleDateString() } catch { return iso }
}

function startOfToday() {
  const d = new Date()
  d.setHours(0,0,0,0)
  return d
}

// export CSV simple implementation
function exportCsv() {
  const rows = [
    ['id','name','email','approved','active','expiresAt','categories_upkp','categories_tugasBelajar','createdAt']
  ]
  peserta.value.forEach(p => {
    rows.push([
      p.id,
      p.name,
      p.email,
      p.approved ? '1' : '0',
      p.active ? '1' : '0',
      p.expiresAt || '',
      p.categories.upkp ? '1' : '0',
      p.categories.tugasBelajar ? '1' : '0',
      p.createdAt || ''
    ])
  })
  const csv = rows.map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `peserta_${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// watch q or filter to reset page
watch([q, () => filter.category], () => { page.value = 1 })

// save on changes
watch(peserta, () => save(), { deep: true })

// load on mounted and setup periodic expiry check
onMounted(() => {
  load()
  // check expiry every minute (for demo). In production this could be daily or handled server-side.
  const interval = setInterval(() => deactivateExpired(), 60 * 1000)
  // clear interval on unload
  window.addEventListener('beforeunload', () => clearInterval(interval))
})
</script>

<style scoped>
/* keep table readable on small screens */
table th, table td { vertical-align: top; }
</style>