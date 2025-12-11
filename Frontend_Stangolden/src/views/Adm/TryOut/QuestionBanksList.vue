<template>
  <div class="p-4 bg-white rounded shadow">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">Question Banks</h3>
      <div class="text-sm text-slate-600">
        <div>UPKP Total = TPA + TSKKWK: <span class="font-semibold">{{ upkpTotal.toFixed(2) }}</span></div>
        <div>TUBEL Total = TPA + TBI: <span class="font-semibold">{{ tubelTotal.toFixed(2) }}</span></div>
      </div>
    </div>

    <!-- Tools: Search + Page size -->
    <div class="flex items-center gap-3 mb-3">
      <input
        v-model="search"
        type="search"
        placeholder="Cari bank berdasarkan nama..."
        class="border rounded px-3 py-2 text-sm w-full md:w-80"
        aria-label="Cari bank berdasarkan nama"
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

    <div v-if="loading" class="text-sm text-slate-500">Memuat...</div>
    <div v-else-if="filteredBanks.length === 0" class="text-sm text-slate-500">Belum ada bank soal.</div>

    <ul v-else class="space-y-2">
      <li v-for="b in pagedBanks" :key="b.id" class="p-3 border rounded flex justify-between items-center">
        <div class="min-w-0">
          <div class="font-medium truncate">{{ b.title || ('Bank #' + b.id) }}</div>
          <div class="text-xs text-slate-500">
            Category: {{ b.category || b.session_key || '-' }} • Series: {{ b.series_id || '-' }}
            <span v-if="bankStatus[b.id]?.done" class="ml-2 inline-block px-2 py-1 text-xs bg-emerald-100 text-emerald-700 rounded">done</span>
          </div>
          <div v-if="bankStatus[b.id]?.score !== undefined" class="text-sm">
            Score: <span class="font-semibold">{{ bankStatus[b.id].score.toFixed(2) }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <!-- Kerjakan -->
          <a
            class="px-3 py-1 rounded border text-sm"
            :href="attemptHref(b)"
            target="_blank"
            rel="noopener"
            @click="onOpenAttempt"
          >
            Kerjakan
          </a>

          <!-- Hapus bank (hanya admin) -->
          <button
            v-if="isAdminUser"
            class="px-3 py-1 rounded border text-sm bg-red-600 text-white"
            @click="removeBank(b)"
            :disabled="busyId === b.id"
            title="Hapus bank (beserta metadata)"
          >
            Hapus Bank
          </button>
        </div>
      </li>
    </ul>

    <!-- Pagination -->
    <div v-if="filteredBanks.length > 0" class="mt-4 flex items-center justify-between text-sm">
      <div class="text-slate-600">
        Menampilkan {{ fromIndex + 1 }}–{{ toIndex }} dari {{ filteredBanks.length }} bank
      </div>
      <div class="flex items-center gap-2">
        <button class="px-3 py-1 border rounded" :disabled="page === 1" @click="page--">Sebelumnya</button>
        <span>Halaman {{ page }} / {{ totalPages }}</span>
        <button class="px-3 py-1 border rounded" :disabled="page === totalPages" @click="page++">Berikutnya</button>
      </div>
    </div>

    <div v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import backendService from '@/services/backendServices'
import { useRouter } from 'vue-router'

const router = useRouter()
const props = defineProps({
  seriesId: { type: [Number, String], required: false }
})

const banks = ref([])
const loading = ref(false)
const error = ref('')
const bankStatus = ref({})
const busyId = ref(null)

// Search & pagination (client-side fallback)
const search = ref('')
const page = ref(1)
const perPage = ref(10)

const me = ref(null)
const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})

async function initMe() {
  try {
    me.value = await backendService.auth.user()
  } catch (_) {
    me.value = { role: 'siswa', is_admin: false }
  }
}

function attemptHref(b) {
  const r = router.resolve({
    name: 'TryoutAdminBankAttempt',
    params: { bankId: b.id },
    query: { seriesId: b.series_id, category: b.category || b.session_key }
  })
  return r.href
}
function onOpenAttempt() {}

async function loadBanks() {
  loading.value = true
  error.value = ''
  try {
    const params = {}
    if (props.seriesId) params.series_id = props.seriesId
    const res = await backendService.admin.banks.index(params)
    banks.value = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : [])
    const statusMap = {}
    for (const b of banks.value) {
      const raw = localStorage.getItem(`bank:${b.id}:score`)
      if (raw) {
        const obj = JSON.parse(raw)
        statusMap[b.id] = { done: true, score: Number(obj?.score || 0) }
      }
    }
    bankStatus.value = statusMap
    page.value = 1
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Gagal memuat banks'
    banks.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await initMe()
  await loadBanks()
})
watch(() => props.seriesId, async () => { await loadBanks() })
watch(search, () => { page.value = 1 })

const filteredBanks = computed(() => {
  const q = (search.value || '').trim().toLowerCase()
  if (!q) return banks.value
  return (banks.value || []).filter(b => String(b.title || '').toLowerCase().includes(q))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredBanks.value.length / perPage.value)))
const fromIndex = computed(() => (page.value - 1) * perPage.value)
const toIndex = computed(() => Math.min(filteredBanks.value.length, fromIndex.value + perPage.value))
const pagedBanks = computed(() => filteredBanks.value.slice(fromIndex.value, toIndex.value))

const upkpTotal = computed(() => {
  let tpa = 0, tsk = 0
  for (const b of banks.value) {
    const s = bankStatus.value[b.id]?.score || 0
    const cat = String(b.category || b.session_key || '')
    if (cat.startsWith('tpa')) tpa += s
    if (cat === 'tskkwk') tsk += s
  }
  return tpa + tsk
})
const tubelTotal = computed(() => {
  let tpa = 0, tbi = 0
  for (const b of banks.value) {
    const s = bankStatus.value[b.id]?.score || 0
    const cat = String(b.category || b.session_key || '')
    if (cat.startsWith('tpa')) tpa += s
    if (cat.startsWith('tbi')) tbi += s
  }
  return tpa + tbi
})

// Hapus bank (admin only)
async function removeBank(b) {
  if (!isAdminUser.value) {
    alert('Aksi ini hanya untuk admin.')
    return
  }
  if (!b?.id) return
  if (!confirm(`Hapus bank "${b.title || ('#'+b.id)}"? Semua data terkait bisa ikut terhapus.`)) return
  busyId.value = b.id
  error.value = ''
  try {
    if (backendService?.admin?.banks?.destroy) {
      await backendService.admin.banks.destroy(b.id)
    } else {
      await backendService.raw.delete(`/api/admin/banks/${b.id}`)
    }
    alert('Bank berhasil dihapus.')
    await loadBanks()
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Gagal menghapus bank'
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .18s ease }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>