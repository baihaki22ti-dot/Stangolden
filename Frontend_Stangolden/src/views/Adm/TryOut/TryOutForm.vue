<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="TryOut Admin" description="Kelola folder tryout, seri TO, dan sesi ujian per domain." />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- Domain header -->
        <section class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold">Domain: {{ domainLabel }}</h3>
            <button @click="reloadAll" class="ml-auto px-3 py-2 bg-sky-600 text-white rounded text-sm">Reload</button>
            <button @click="openCreateGroupModal" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Buat Folder</button>
          </div>
        </section>

        <!-- Groups list -->
        <section class="bg-white rounded-xl shadow p-4">
          <h3 class="font-semibold mb-3">Folder TryOut Besar</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="g in groups" :key="g.id" class="border rounded p-3">
              <div class="flex items-center justify-between">
                <div class="min-w-0">
                  <div class="font-semibold truncate">{{ g.name }}</div>
                  <div class="text-xs text-slate-500">Domain: {{ g.domain }} • {{ g.description || '-' }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="openSeries(g)" class="px-2 py-1 rounded border text-sm">Buka</button>
                  <button @click="removeGroup(g.id)" class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm">Hapus</button>
                </div>
              </div>

              <!-- inline series panel inside each group -->
              <div v-if="activeGroup && activeGroup.id === g.id" class="mt-3 border-t pt-3 space-y-3">
                <div class="flex items-center gap-2">
                  <input v-model.number="newSeries.number" type="number" min="1" placeholder="TO # (angka)" class="border rounded px-3 py-2 text-sm w-28" />
                  <input v-model="newSeries.title" placeholder="Judul opsional (misal TO 1)" class="border rounded px-3 py-2 text-sm flex-1" />
                  <button @click="createSeries" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Tambah Series TO</button>
                </div>

                <!-- list series -->
                <div class="grid grid-cols-1 gap-3">
                  <div v-for="s in series" :key="s.id" class="border rounded p-3">
                    <div class="flex items-start justify-between">
                      <div class="min-w-0">
                        <div class="font-semibold truncate">TO {{ s.number }} {{ s.title ? '- '+s.title : '' }}</div>
                        <div class="text-xs text-slate-500">Active: {{ s.is_active ? 'Ya' : 'Tidak' }}</div>
                      </div>
                      <div class="flex items-center gap-2">
                        <button @click="openSessions(s)" class="px-2 py-1 rounded border text-sm">Lihat TO</button>
                        <button @click="removeSeries(s.id)" class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm">Hapus</button>
                      </div>
                    </div>

                    <!-- inline sessions panel -->
                    <div v-if="activeSeries && activeSeries.id === s.id" class="mt-3 border-t pt-3 space-y-3">
                      <!-- Add TO (sessions) one by one -->
                      <div class="flex items-center gap-2">
                        <select v-model="newSession.key" class="border rounded px-3 py-2 text-sm">
                          <option :value="null">Pilih kategori TO</option>
                          <option value="tskkwk">TO TSKKWK</option>
                          <option value="tpa-verbal">TO TPA - Verbal</option>
                          <option value="tpa-numerik">TO TPA - Numerik</option>
                          <option value="tpa-figural">TO TPA - Figural</option>
                          <option value="tbi-structure">TO TBI - Structure</option>
                          <option value="tbi-reading">TO TBI - Reading</option>
                        </select>
                        <input v-model="newSession.title" placeholder="Judul TO (opsional)" class="border rounded px-3 py-2 text-sm flex-1" />
                        <input v-model.number="newSession.duration_minutes" type="number" min="1" placeholder="Durasi (menit)" class="border rounded px-3 py-2 text-sm w-32" />
                        <button @click="createSession" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Tambah TO</button>
                      </div>

                      <div class="grid grid-cols-1 gap-3">
                        <div v-for="se in sessions" :key="se.id" class="border rounded p-3">
                          <div class="flex items-start justify-between">
                            <div class="min-w-0">
                              <div class="font-semibold truncate">{{ se.title }}</div>
                              <div class="text-xs text-slate-500">Key: {{ se.key }} • Durasi: {{ se.duration_minutes }}m</div>
                            </div>
                            <div class="flex items-center gap-2">
                              <button @click="removeSession(se.id)" class="px-2 py-1 rounded bg-red-50 text-red-600 text-sm">Hapus</button>
                            </div>
                          </div>

                          <!-- generate from Bank -->
                          <div class="mt-3 flex items-center gap-2">
                            <select v-model="gen.bank_id" class="border rounded px-2 py-1 text-sm">
                              <option :value="null">Pilih bank</option>
                              <option v-for="b in banksFiltered(se.key)" :key="b.id" :value="b.id">{{ b.title }} ({{ b.domain }})</option>
                            </select>
                            <input v-model.number="gen.count" type="number" min="1" placeholder="Jumlah" class="border rounded px-2 py-1 text-sm w-24" />
                            <button @click="generate(se.id)" class="px-2 py-1 bg-sky-600 text-white rounded text-sm">Generate</button>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="groups.length===0" class="text-sm text-slate-500">Belum ada folder.</div>
        </section>

        <!-- Bank & Import -->
        <section class="bg-white rounded-xl shadow p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold">Bank Soal & Import</h3>
            <div class="flex items-center gap-2">
              <select v-model="bankForm.domain" class="border rounded px-3 py-2 text-sm">
                <option value="upkp">UPKP</option>
                <option value="tubel">Tugas Belajar</option>
                <option value="shared">Shared</option>
              </select>
              <select v-model="bankForm.session_key" class="border rounded px-3 py-2 text-sm">
                <option :value="null">Pilih kategori bank</option>
                <option value="tskkwk">TSKKWK</option>
                <option value="tpa-verbal">TPA Verbal</option>
                <option value="tpa-numerik">TPA Numerik</option>
                <option value="tpa-figural">TPA Figural</option>
                <option value="tbi-structure">TBI Structure</option>
                <option value="tbi-reading">TBI Reading</option>
              </select>
              <input v-model="bankForm.title" placeholder="Judul bank" class="border rounded px-3 py-2 text-sm" />
              <button @click="createBank" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Tambah Bank</button>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <select v-model="importForm.bank_id" class="border rounded px-3 py-2 text-sm">
              <option :value="null">Pilih bank untuk import</option>
              <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.title }} ({{ b.domain }} • {{ b.session_key }})</option>
            </select>
            <input type="file" @change="onFile" accept=".docx" class="text-sm" />
            <button @click="importDocx" class="px-3 py-2 bg-sky-600 text-white rounded text-sm">Import DOCX</button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="b in banks" :key="b.id" class="border rounded p-3">
              <div class="font-semibold">{{ b.title }}</div>
              <div class="text-xs text-slate-500">{{ b.domain }} • {{ b.session_key }}</div>
            </div>
          </div>
        </section>

        <div v-if="loading" class="text-sm text-slate-600">Loading...</div>
        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
      </main>
    </div>

    <!-- Create Group Modal -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="createGroupModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="closeCreateGroupModal"></div>
          <div class="relative w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h4 class="text-lg font-semibold">Buat Folder TryOut</h4>
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

// Domain dari route param (bukan path)
const domain = ref('upkp')
function syncDomain() {
  const raw = (route.params?.domain || '').toString().toLowerCase()
  domain.value = (raw === 'tugas-belajar' || raw === 'tubel' || raw === 'tb') ? 'tubel' : 'upkp'
}
const domainLabel = computed(() => domain.value === 'upkp' ? 'UPKP' : 'Tugas Belajar')

const groups = ref([])
const series = ref([])
const sessions = ref([])
const activeGroup = ref(null)
const activeSeries = ref(null)

// Default null supaya tidak auto 'tskkwk'
const newGroup = ref({ name: '', description: '' })
const newSeries = ref({ number: 1, title: '' })
const newSession = ref({ key: null, title: '', duration_minutes: 60 }) // key null, paksa user pilih

const banks = ref([])
const bankForm = ref({ domain: 'upkp', session_key: null, title: '' }) // session_key null, paksa user pilih
const importForm = ref({ bank_id: null, file: null })

const gen = ref({ bank_id: null, count: 20 })
const loading = ref(false)
const error = ref('')
const createGroupModal = ref(false)

function openCreateGroupModal() { createGroupModal.value = true }
function closeCreateGroupModal() { createGroupModal.value = false }

async function loadGroups() {
  loading.value = true
  error.value = ''
  try {
    const res = await backendService.admin.tryout.listGroups({ domain: domain.value })
    groups.value = res.data || res || []
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Gagal memuat groups'
    groups.value = []
  } finally {
    loading.value = false
  }
}

async function reloadAll() {
  await loadGroups()
  if (activeGroup.value) await openSeries(activeGroup.value)
  if (activeSeries.value) await openSessions(activeSeries.value)
}

async function createGroup() {
  const name = (newGroup.value?.name || '').trim()
  if (!name) return alert('Nama folder wajib')
  try {
    const payload = { domain: domain.value, name, description: newGroup.value?.description || '' }
    const resp = await backendService.admin.tryout.createGroup(payload)
    const created = resp?.data || resp
    newGroup.value = { name: '', description: '' }
    createGroupModal.value = false

    await loadGroups()

    const g = (created && created.id) ? created : groups.value.find(x => x.name === name && x.domain === domain.value)
    if (g) await openSeries(g)
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal membuat folder')
  }
}

async function removeGroup(id) {
  if (!confirm('Hapus folder ini?')) return
  await backendService.admin.tryout.destroyGroup(id)
  if (activeGroup.value?.id === id) {
    activeGroup.value = null
    series.value = []
    sessions.value = []
    activeSeries.value = null
  }
  await loadGroups()
}

async function openSeries(g) {
  activeGroup.value = g
  loading.value = true
  error.value = ''
  try {
    const res = await backendService.admin.tryout.listSeries(g.id)
    series.value = res.data || res || []
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Gagal memuat series'
    series.value = []
  } finally {
    loading.value = false
  }
}

async function createSeries() {
  if (!activeGroup.value) return alert('Pilih folder terlebih dahulu')
  const num = Number(newSeries.value?.number || 0)
  if (!num) return alert('Nomor TO wajib')
  await backendService.admin.tryout.createSeries(activeGroup.value.id, { number: num, title: newSeries.value.title })
  newSeries.value.number = 1
  newSeries.value.title = ''
  await openSeries(activeGroup.value)
}

async function removeSeries(id) {
  if (!confirm('Hapus series ini?')) return
  await backendService.admin.tryout.destroySeries(id)
  if (activeSeries.value?.id === id) {
    activeSeries.value = null
    sessions.value = []
  }
  await openSeries(activeGroup.value)
}

async function openSessions(s) {
  activeSeries.value = s
  loading.value = true
  error.value = ''
  try {
    const res = await backendService.admin.tryout.listSessions(s.id)
    sessions.value = res.data || res || []
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Gagal memuat sessions'
    sessions.value = []
  } finally {
    loading.value = false
  }
}

async function createSession() {
  if (!activeSeries.value) return alert('Pilih series terlebih dahulu')
  if (!newSession.value.key) return alert('Pilih kategori TO terlebih dahulu') // cegah default tskkwk
  const payload = { ...newSession.value }
  if (!payload.title) payload.title = `TO ${String(payload.key).toUpperCase().replace('-', ' ')}`
  await backendService.admin.tryout.createSession(activeSeries.value.id, payload)
  newSession.value.title = ''
  await openSessions(activeSeries.value)
}

async function removeSession(id) {
  if (!confirm('Hapus session ini?')) return
  await backendService.admin.tryout.destroySession(id)
  await openSessions(activeSeries.value)
}

function banksFiltered(key) {
  return (banks.value || []).filter(b => b.session_key === key)
}

async function generate(sessionId) {
  if (!gen.value.bank_id || !gen.value.count) return alert('Pilih bank dan jumlah')
  await backendService.admin.tryout.generateSessionQuestions(sessionId, { bank_id: gen.value.bank_id, count: gen.value.count })
  alert('Session questions generated')
}

async function loadBanks() {
  const res = await backendService.admin.banks.index({})
  // catat: backend index banks bisa mengembalikan array langsung atau {data:[]}
  banks.value = Array.isArray(res) ? res : (Array.isArray(res?.data) ? res.data : [])
}

function onFile(e) {
  importForm.value.file = e.target.files?.[0] || null
}

async function createBank() {
  const payload = { ...bankForm.value }
  if (!payload.session_key) return alert('Pilih kategori bank terlebih dahulu') // cegah default tskkwk
  if (!(payload.title || '').trim()) return alert('Judul bank wajib')
  await backendService.admin.banks.store(payload)
  bankForm.value.title = ''
  await loadBanks()
}

async function importDocx() {
  if (!importForm.value.bank_id || !importForm.value.file) return alert('Pilih bank dan file DOCX')
  const fd = new FormData()
  fd.append('file', importForm.value.file)
  const bank = banks.value.find(b => b.id === importForm.value.bank_id)
  fd.append('domain', bank?.domain || domain.value || 'shared')
  fd.append('session_key', bank?.session_key || 'tpa-verbal')
  fd.append('bank_id', importForm.value.bank_id)
  await backendService.admin.import.docx(fd)
  alert('Import selesai')
  await loadBanks()
}

onMounted(async () => {
  syncDomain()
  await loadGroups()
  await loadBanks()
})
watch(() => route.params?.domain, async () => {
  syncDomain()
  // reset pilihan domain bank agar sesuai domain aktif (opsional)
  if (domain.value === 'tubel') bankForm.value.domain = 'tubel'
  else bankForm.value.domain = 'upkp'
  await reloadAll()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity .18s ease }
.fade-enter-from,
.fade-leave-to { opacity: 0 }
</style>