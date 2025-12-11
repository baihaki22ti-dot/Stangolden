<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Series TryOut" description="Kelola seri TO dalam folder." />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- Header -->
        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold">
              Folder: {{ seriesTitle }}
            </h3>
            <!-- Tambah Jenis Soal (buka modal import CSV) — HANYA ADMIN -->
            <button
              v-if="isAdminUser"
              @click="openImportModal"
              class="ml-auto px-3 py-2 bg-emerald-600 text-white rounded text-sm"
            >
              Tambah Jenis Soal
            </button>
          </div>
          <div class="text-xs text-slate-500">Series ID: {{ seriesId ?? '-' }}</div>
        </section>

        <!-- Daftar Sessions / Question Banks -->
        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="loading" class="text-sm text-slate-600">Memuat...</div>
          <div v-else-if="sessions.length === 0 && banks.length === 0" class="text-sm text-slate-500">Tryout belum tersedia.</div>

          <div v-else class="grid grid-cols-1 md:grid-cols-1 gap-4">
            <!-- Sessions list (keperluan generate) -->
            <div v-for="se in sessions" :key="se.id" class="border rounded p-3 hover:shadow-sm">
              <div class="flex items-center justify-between">
                <div class="min-w-0">
                  <div class="font-semibold truncate">{{ se.title }}</div>
                  <div class="text-xs text-slate-500">Key: {{ se.key }} • Durasi: {{ se.duration_minutes }}m</div>
                </div>
                <div class="flex items-center gap-2">
                  <select
                    :id="`gen-bank-${se.id}`"
                    :name="`gen_bank_${se.id}`"
                    v-model="genMap[se.id].bank_id"
                    class="border rounded px-2 py-1 text-sm"
                    :aria-label="`Pilih bank untuk session ${se.key}`"
                  >
                    <option :value="null">Pilih bank ({{ se.key }})</option>
                    <option
                      v-for="b in banksByCategory(se.key)"
                      :key="b.id"
                      :value="b.id"
                    >
                      {{ b.title }}
                    </option>
                  </select>

                  <input
                    :id="`gen-count-${se.id}`"
                    :name="`gen_count_${se.id}`"
                    v-model.number="genMap[se.id].count"
                    type="number"
                    min="1"
                    class="border rounded px-2 py-1 text-sm w-20"
                    placeholder="Jumlah"
                    :aria-label="`Jumlah soal untuk session ${se.key}`"
                  />
                  <button @click="generate(se)" class="px-2 py-1 rounded border text-sm">
                    Generate
                  </button>
                </div>
              </div>
            </div>

            <!-- Question banks component -->
            <QuestionBanksList :seriesId="seriesId" />
          </div>
        </section>

        <div v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</div>
      </main>
    </div>

    <!-- Modal: Tambah Jenis Soal (pilih kategori + upload CSV) — HANYA ADMIN -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="importModal && isAdminUser" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="closeImportModal"></div>
          <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold">Tambah Jenis Soal</h4>
            <p class="text-xs text-slate-600">
              Pilih kategori (jenis soal) untuk series ini lalu unggah file CSV. Sistem akan mengonversi dan menyimpan soal ke database.
            </p>

            <!-- Pilih kategori (akan dibuatkan bank baru di series ini) -->
            <label class="text-sm" for="category">Kategori
              <select id="category" name="category" v-model="importForm.category" class="w-full border rounded px-3 py-2 text-sm">
                <option value="tskkwk">TSKKWK</option>
                <option value="tpa-verbal">TPA Verbal</option>
                <option value="tpa-numerik">TPA Numerik</option>
                <option value="tpa-figural">TPA Figural</option>
                <option value="tbi-structure">TBI Structure</option>
                <option value="tbi-reading">TBI Reading</option>
              </select>
            </label>

            <!-- Judul bank baru (opsional, default dari kategori) -->
            <label class="text-sm" for="bank-title">Judul Bank
              <input id="bank-title" name="bank_title" v-model="importForm.title" class="w-full border rounded px-3 py-2 text-sm" placeholder="Contoh: Bank TSKKWK TO 1" />
            </label>

            <!-- Upload file CSV -->
            <label class="text-sm" for="csv-file">File CSV
              <input id="csv-file" name="file" type="file" accept=".csv" @change="onFile" class="w-full text-sm" />
            </label>

            <div class="flex items-center justify-end gap-2">
              <button @click="closeImportModal" class="px-3 py-2 border rounded text-sm">Batal</button>
              <button @click="saveImportCsv" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Simpan</button>
            </div>

            <div v-if="importError" class="text-sm text-red-600">{{ importError }}</div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import QuestionBanksList from '@/views/Adm/TryOut/QuestionBanksList.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute } from 'vue-router'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const route = useRoute()

// seriesId computed agar aman ketika param berubah
const seriesId = computed(() => {
  const raw = route.params.seriesId
  const n = raw ? Number(raw) : NaN
  return Number.isNaN(n) ? null : n
})

const seriesInfo = ref(null)
const seriesTitle = computed(() => {
  if (!seriesInfo.value) return `TO ${seriesId.value ?? '-'}`
  return seriesInfo.value.title ? seriesInfo.value.title : `TO ${seriesInfo.value.number || seriesId.value || '-'}`
})

const sessions = ref([])
const loading = ref(false)
const error = ref('')

// Banks untuk series ini (dimuat juga oleh QuestionBanksList)
const banks = ref([])

// State generate per-session
const genMap = reactive({}) // { [sessionId]: { bank_id, count } }

// Modal import
const importModal = ref(false)
const importForm = ref({
  category: 'tskkwk',
  title: '',
  file: null,
})
const importError = ref('')

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
    // default non-admin jika gagal ambil user
    me.value = { role: 'siswa', is_admin: false }
  }
}

function openImportModal() {
  if (!isAdminUser.value) {
    alert('Aksi ini hanya untuk admin.')
    return
  }
  importError.value = ''
  importForm.value = { category: 'tskkwk', title: '', file: null }
  importModal.value = true
}
function closeImportModal() {
  importModal.value = false
}

function onFile(e) {
  importForm.value.file = e.target.files?.[0] || null
}

function ensureGenEntry(sessionId) {
  if (!genMap[sessionId]) {
    genMap[sessionId] = { bank_id: null, count: 20 }
  }
}

// Filter bank sesuai kategori dan series aktif
function banksByCategory(cat) {
  return (banks.value || []).filter(b => Number(b.series_id) === Number(seriesId.value) && (b.category || b.session_key) === cat)
}

async function loadSessions() {
  if (!seriesId.value) { sessions.value = []; return }
  loading.value = true
  error.value = ''
  try {
    const res = await backendService.admin.tryout.listSessions(seriesId.value)
    sessions.value = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : [])
    sessions.value.forEach(se => ensureGenEntry(se.id))
    // Placeholder series info; kalau ada endpoint detail seris, isi dari sana
    seriesInfo.value = seriesInfo.value || { id: seriesId.value, number: seriesId.value }
  } catch (e) {
    error.value = e?.message || 'Gagal memuat sessions'
    sessions.value = []
  } finally {
    loading.value = false
  }
}

async function loadBanks() {
  if (!seriesId.value) { banks.value = []; return }
  try {
    const res = await backendService.admin.banks.index({ series_id: seriesId.value })
    banks.value = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : [])
  } catch {
    banks.value = []
  }
}

// Alur simpan import CSV:
// 1) Buat bank baru dengan series_id + category + title
// 2) Upload CSV ke endpoint import/csv dengan bank_id
async function saveImportCsv() {
  if (!isAdminUser.value) {
    alert('Aksi ini hanya untuk admin.')
    return
  }
  importError.value = ''
  try {
    if (!seriesId.value) throw new Error('Series ID tidak valid')
    if (!importForm.value.file) throw new Error('File CSV belum dipilih')
    const title = (importForm.value.title || '').trim() || `Bank ${importForm.value.category.toUpperCase()} TO ${seriesId.value}`

    // 1) Create bank
    const bank = await backendService.admin.banks.store({
      series_id: seriesId.value,
      category: importForm.value.category,
      title,
      description: '',
      is_active: true,
    })
    const bankId = bank?.data?.id ?? bank?.id
    if (!bankId) throw new Error('Gagal membuat bank')

    // 2) Import CSV ke bank yang baru
    const fd = new FormData()
    fd.append('file', importForm.value.file)
    fd.append('bank_id', bankId)

    // Prefer bank-scoped if available; otherwise generic
    if (backendService?.admin?.import?.csvToBank) {
      await backendService.admin.import.csvToBank(bankId, fd)
    } else {
      await backendService.admin.import.csv(fd)
    }

    // Reload banks & sessions
    await loadBanks()
    importModal.value = false
    alert('Soal berhasil diimpor dari CSV ke bank.')
  } catch (e) {
    console.error(e)
    importError.value = e?.response?.data?.message || e?.message || 'Gagal mengimpor soal'
  }
}

// Generate snapshot dari bank ke session
async function generate(se) {
  const form = genMap[se.id]
  if (!form?.bank_id || !form?.count) return alert('Pilih bank dan jumlah')
  try {
    await backendService.admin.tryout.generateSessionQuestions(se.id, {
      bank_id: form.bank_id,
      count: form.count,
    })
    alert('Session questions generated')
  } catch (e) {
    alert(e?.response?.data?.message || e?.message || 'Gagal generate')
  }
}

onMounted(async () => {
  await initMe()
  await loadSessions()
  await loadBanks()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>