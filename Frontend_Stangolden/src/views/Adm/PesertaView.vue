<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Peserta" description="Kelola pendaftaran, aktivasi, masa berlaku, dan kategori peserta" />

      <!-- Blur hanya ketika editing -->
      <main
        class="p-6 max-w-7xl w-full mx-auto space-y-6 transition"
        :class="editing ? 'filter blur-sm pointer-events-none select-none' : ''"
      >
        <!-- header / actions -->
        <section class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">Manajemen Peserta</h2>
            <p class="text-sm text-slate-500">Terima peserta, atur masa aktif, dan tetapkan kategori (UPKP / Tugas Belajar).</p>
          </div>

            <div class="flex items-center gap-3">
              <input v-model="q" type="search" placeholder="Cari nama atau email..."
                     class="border rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-indigo-200" />
              <select v-model="filter.category" class="border rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-indigo-200">
                <option value="">Semua kategori</option>
                <option value="upkp">UPKP</option>
                <option value="tugas-belajar">Tugas Belajar</option>
                <option value="both">Keduanya</option>
              </select>

              <button @click="exportCsv" class="px-3 py-2 bg-sky-600 text-white rounded text-sm hover:bg-sky-700">Export CSV</button>
              <button @click="createFake" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm hover:bg-emerald-700">Tambah contoh</button>
            </div>
        </section>

        <!-- summary -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white rounded-xl shadow p-4">
            <div class="text-sm text-slate-500">Total Registrasi</div>
            <div class="text-2xl font-bold">{{ counts.total ?? 0 }}</div>
          </div>
          <div class="bg-white rounded-xl shadow p-4">
            <div class="text-sm text-slate-500">Menunggu ACC</div>
            <div class="text-2xl font-bold text-amber-600">{{ counts.pending ?? 0 }}</div>
          </div>
          <div class="bg-white rounded-xl shadow p-4">
            <div class="text-sm text-slate-500">Aktif Saat Ini</div>
            <div class="text-2xl font-bold text-emerald-600">{{ counts.active ?? 0 }}</div>
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
                      <input type="date" :value="p.expiresAt ? formatInputDate(p.expiresAt) : ''" @change="setExpiry(p.id, $event.target.value)" class="border rounded px-2 py-1 text-sm focus:outline-none focus:ring focus:ring-indigo-200" />
                      <button @click="clearExpiry(p.id)" class="text-xs text-slate-600 px-2 py-1 rounded border hover:bg-slate-50">Clear</button>
                    </div>
                  </td>

                  <td class="py-3 pr-4 align-top">
                    <div class="flex flex-wrap items-center gap-2">
                      <button v-if="!p.approved" @click="approve(p.id)" class="px-3 py-1 rounded bg-amber-500 text-white text-sm hover:bg-amber-600">ACC</button>
                      <button v-else @click="revoke(p.id)" class="px-3 py-1 rounded border text-sm hover:bg-slate-100">Revoke</button>

                      <button
                        @click="toggleActive(p.id)"
                        :class="p.active ? 'px-3 py-1 rounded bg-red-50 text-red-600 border hover:bg-red-100' : 'px-3 py-1 rounded bg-emerald-50 text-emerald-600 border hover:bg-emerald-100'"
                        class="text-sm"
                      >
                        {{ p.active ? 'Nonaktifkan' : 'Aktifkan' }}
                      </button>

                      <button @click="editCandidate(p.id)" class="px-2 py-1 rounded border text-sm hover:bg-slate-100">Edit</button>
                      <!-- <button @click="remove(p.id)" class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm hover:bg-red-100">Hapus</button> -->
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
              <button @click="prev" :disabled="page===1" class="px-3 py-1 rounded border text-sm disabled:opacity-40 hover:bg-slate-50">Prev</button>
              <button @click="next" :disabled="page===pageCount" class="px-3 py-1 rounded border text-sm disabled:opacity-40 hover:bg-slate-50">Next</button>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Edit Modal: Teleport ke body agar bebas dari blur parent -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="editing" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="stopEditing"></div>
          <div class="relative w-full max-w-xl bg-white rounded-xl shadow-lg p-6 space-y-5 border border-slate-200">
            <h4 class="text-lg font-semibold">Edit Peserta</h4>

            <div class="grid grid-cols-1 gap-3">
              <label class="text-sm">Nama
                <input v-model="editForm.name" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-indigo-200" />
              </label>

              <label class="text-sm">Email
                <input v-model="editForm.email" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-indigo-200" />
              </label>

              <div class="text-sm">
                Kategori
                <div class="flex gap-4 mt-1">
                  <label class="inline-flex items-center gap-2 text-xs">
                    <input type="checkbox" v-model="editForm.categories.upkp" /> UPKP
                  </label>
                  <label class="inline-flex items-center gap-2 text-xs">
                    <input type="checkbox" v-model="editForm.categories.tugasBelajar" /> Tugas Belajar
                  </label>
                </div>
              </div>

              <label class="text-sm">Masa aktif sampai
                <input type="date" v-model="editForm.expiresAt" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-indigo-200" />
              </label>

              <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                  <input type="checkbox" v-model="editForm.approved" /> Disetujui
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                  <input type="checkbox" v-model="editForm.active" /> Aktif
                </label>
              </div>

              <div class="flex items-center justify-end gap-3 mt-4">
                <button @click="stopEditing" type="button" class="px-3 py-2 border rounded text-sm hover:bg-slate-100">Batal</button>
                <button @click="saveEdit" type="button" class="px-3 py-2 bg-sky-600 text-white rounded text-sm hover:bg-sky-700">
                  Simpan
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, reactive } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen

// Search / Filter / Pagination
const q = ref('')
const filter = reactive({ category: '' })
const page = ref(1)
const perPage = 8

// Data
const peserta = ref([])
const counts = reactive({ total: 0, pending: 0, active: 0 })

// Editing modal
const editing = ref(false)
const editForm = reactive({
  id: null,
  name: '',
  email: '',
  categories: { upkp: false, tugasBelajar: false },
  expiresAt: '',
  approved: false,
  active: false
})

const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = { q: q.value ?? '', category: filter.category ?? '' }
    const res = await backendService.users.list(params)
    const data = Array.isArray(res?.data) ? res.data : []
    const cnts = res?.counts || {}
    peserta.value = data
    counts.total = cnts.total ?? 0
    counts.pending = cnts.pending ?? 0
    counts.active = cnts.active ?? 0
  } catch (err) {
    console.error('Failed loading peserta', err)
    error.value = err?.response?.data?.message || err?.message || 'Gagal memuat data peserta'
    peserta.value = []
    counts.total = 0
    counts.pending = 0
    counts.active = 0
  } finally {
    loading.value = false
  }
}

async function createFake() {
  try { await backendService.users.createFake(); await load() }
  catch (e) { console.error(e); alert('Gagal membuat user contoh') }
}

async function approve(id) {
  try { await backendService.users.approve(id); await load() }
  catch (e) { console.error(e); alert('Gagal approve') }
}

async function revoke(id) {
  try { await backendService.users.revoke(id); await load() }
  catch (e) { console.error(e); alert('Gagal revoke') }
}

async function toggleActive(id) {
  try { await backendService.users.toggleActive(id); await load() }
  catch (e) { console.error(e); alert('Gagal toggle active') }
}

async function toggleCategory(id, key, checked) {
  try {
    const payload = {}
    if (key === 'upkp') payload.upkp = checked
    if (key === 'tugasBelajar') payload.tugas_belajar = checked
    await backendService.users.update(id, payload)
    await load()
  } catch (e) { console.error(e); alert('Gagal update kategori') }
}

async function setExpiry(id, isoDate) {
  try { await backendService.users.setExpiry(id, isoDate || null); await load() }
  catch (e) { console.error(e); alert('Gagal set expiry') }
}

async function clearExpiry(id) {
  try { await backendService.users.setExpiry(id, null); await load() }
  catch (e) { console.error(e); alert('Gagal clear expiry') }
}

function isExpired(p) {
  if (!p?.expiresAt) return false
  const e = new Date(p.expiresAt)
  e.setHours(23,59,59,999)
  return e < new Date()
}

function editCandidate(id) {
  const p = peserta.value.find(x => x.id === id)
  if (!p) return
  editForm.id = p.id
  editForm.name = p.name ?? ''
  editForm.email = p.email ?? ''
  editForm.categories = {
    upkp: !!(p.categories?.upkp),
    tugasBelajar: !!(p.categories?.tugasBelajar)
  }
  editForm.expiresAt = p.expiresAt ? p.expiresAt : ''
  editForm.approved = !!p.approved
  editForm.active = !!p.active
  editing.value = true
}

function stopEditing() {
  editing.value = false
  editForm.id = null
}

async function saveEdit() {
  try {
    const payload = {
      name: editForm.name,
      email: editForm.email,
      upkp: !!editForm.categories.upkp,
      tugas_belajar: !!editForm.categories.tugasBelajar,
      expires_at: editForm.expiresAt || null,
      approved: !!editForm.approved,
      active: !!editForm.active
    }
    await backendService.users.update(editForm.id, payload)
    editing.value = false
    await load()
  } catch (e) {
    console.error(e)
    alert('Gagal menyimpan perubahan')
  }
}

async function remove(id) {
  if (!confirm('Hapus peserta ini?')) return
  try { await backendService.users.destroy(id); await load() }
  catch (e) { console.error(e); alert('Gagal menghapus peserta') }
}

const filtered = computed(() => {
  const list = Array.isArray(peserta.value) ? peserta.value : []
  const qv = q.value.trim().toLowerCase()
  return list.filter(p => {
    if (filter.category) {
      if (filter.category === 'upkp' && !p.categories?.upkp) return false
      if (filter.category === 'tugas-belajar' && !p.categories?.tugasBelajar) return false
      if (filter.category === 'both' && !(p.categories?.upkp && p.categories?.tugasBelajar)) return false
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

function formatInputDate(iso) {
  if (!iso) return ''
  try { return new Date(iso).toISOString().slice(0,10) } catch { return '' }
}

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
      p.categories?.upkp ? '1' : '0',
      p.categories?.tugasBelajar ? '1' : '0',
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

watch([q, () => filter.category], () => {
  page.value = 1
  load() // jika ingin filter di server
})

onMounted(() => {
  load()
})
</script>

<style scoped>
table th, table td { vertical-align: top; }
.fade-enter-active,
.fade-leave-active { transition: opacity .18s ease }
.fade-enter-from,
.fade-leave-to { opacity: 0 }
</style>