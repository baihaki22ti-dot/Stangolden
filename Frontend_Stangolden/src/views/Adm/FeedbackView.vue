<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Feedback" description="Masukan & laporan dari pengguna" />

      <main class="p-6 max-w-6xl w-full mx-auto space-y-6">
        <!-- Submit feedback card -->
        <section class="bg-white rounded-xl shadow p-5">
          <h2 class="text-lg font-semibold mb-2">Kirim Feedback</h2>

          <form @submit.prevent="submitFeedback" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
              <select v-model="form.category" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">Pilih kategori</option>
                <option value="bug">Bug / Error</option>
                <option value="fitur">Permintaan Fitur</option>
                <option value="umum">Umum</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Judul</label>
              <input v-model="form.title" type="text" placeholder="Ringkas masalah atau saran"
                     class="w-full border rounded px-3 py-2 text-sm" />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
              <textarea v-model="form.message" rows="4" placeholder="Tuliskan detailnya..."
                        class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex items-center gap-3">
              <label class="text-sm font-medium text-slate-700">Prioritas</label>
              <div class="flex items-center gap-2">
                <label class="inline-flex items-center text-sm">
                  <input type="radio" v-model="form.priority" value="low" class="mr-2" /> Rendah
                </label>
                <label class="inline-flex items-center text-sm">
                  <input type="radio" v-model="form.priority" value="medium" class="mr-2" /> Sedang
                </label>
                <label class="inline-flex items-center text-sm">
                  <input type="radio" v-model="form.priority" value="high" class="mr-2" /> Tinggi
                </label>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <input type="file" @change="onFileChange" class="text-sm" />
              <span v-if="form.attachmentName" class="text-sm text-slate-500">{{ form.attachmentName }}</span>
            </div>

            <div class="flex items-center justify-end gap-3">
              <button type="button" @click="resetForm" class="px-4 py-2 bg-slate-100 rounded text-sm">Reset</button>
              <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded text-sm">Kirim</button>
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
          </form>
        </section>

        <!-- Feedback list -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="col-span-1">
            <h3 class="text-lg font-semibold mb-3">Daftar Feedback</h3>

            <div v-if="feedbacks.length === 0" class="bg-white rounded-xl shadow p-6 text-sm text-slate-600">
              Belum ada feedback.
            </div>

            <ul class="space-y-4">
              <li v-for="(fb, idx) in pagedFeedbacks" :key="fb.id" class="bg-white rounded-xl shadow p-4">
                <div class="flex justify-between items-start gap-3">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold">{{ fb.title }}</span>
                      <span class="text-xs px-2 py-0.5 rounded-full text-white"
                            :class="priorityColor(fb.priority)">{{ fb.priority }}</span>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">{{ fb.category }} • {{ formatDate(fb.createdAt) }}</div>
                  </div>

                  <div class="flex items-center gap-2">
                    <button @click="toggleResolved(fb.id)"
                            class="text-xs px-2 py-1 rounded border text-slate-700">
                      {{ fb.resolved ? 'Buka' : 'Selesai' }}
                    </button>
                    <button @click="removeFeedback(fb.id)" class="text-xs px-2 py-1 rounded bg-red-50 text-red-600">Hapus</button>
                  </div>
                </div>

                <p class="mt-3 text-sm text-slate-700">{{ fb.message }}</p>

                <div v-if="fb.attachmentName" class="mt-3">
                  <a :href="fb.attachmentData" target="_blank" class="text-xs text-sky-600 underline">
                    Lampiran: {{ fb.attachmentName }}
                  </a>
                </div>

                <div v-if="fb.resolved" class="mt-3 text-xs text-green-700 font-medium">Status: Selesai</div>
              </li>
            </ul>

            <!-- pagination -->
            <div v-if="pageCount > 1" class="mt-4 flex items-center gap-2">
              <button @click="prevPage" :disabled="page === 1" class="px-3 py-1 border rounded text-sm">Prev</button>
              <span class="text-sm">Hal: {{ page }} / {{ pageCount }}</span>
              <button @click="nextPage" :disabled="page === pageCount" class="px-3 py-1 border rounded text-sm">Next</button>
            </div>
          </div>

          <!-- Summary / Filters -->
          <aside class="col-span-1">
            <div class="bg-white rounded-xl shadow p-4 mb-4">
              <h4 class="text-sm font-semibold mb-2">Filter</h4>
              <div class="space-y-2">
                <select v-model="filter.category" class="w-full border rounded px-3 py-2 text-sm">
                  <option value="">Semua kategori</option>
                  <option value="bug">Bug / Error</option>
                  <option value="fitur">Permintaan Fitur</option>
                  <option value="umum">Umum</option>
                </select>

                <select v-model="filter.priority" class="w-full border rounded px-3 py-2 text-sm">
                  <option value="">Semua prioritas</option>
                  <option value="low">Rendah</option>
                  <option value="medium">Sedang</option>
                  <option value="high">Tinggi</option>
                </select>

                <label class="flex items-center gap-2 text-sm">
                  <input type="checkbox" v-model="filter.unresolved" />
                  Hanya unresolved
                </label>

                <button @click="applyFilter" class="w-full mt-2 px-3 py-2 bg-sky-600 text-white rounded text-sm">Terapkan</button>
                <button @click="resetFilter" class="w-full mt-2 px-3 py-2 border rounded text-sm">Reset</button>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow p-4">
              <h4 class="text-sm font-semibold mb-2">Ringkasan</h4>
              <p class="text-sm">Total Feedback: <span class="font-medium">{{ feedbacks.length }}</span></p>
              <p class="text-sm">Belum Selesai: <span class="font-medium">{{ unresolvedCount }}</span></p>
              <p class="text-sm">Tinggi: <span class="font-medium">{{ countPriority('high') }}</span></p>
            </div>
          </aside>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'

// simple persistence key
const STORAGE_KEY = 'app_feedbacks_v1'

// state
const isOpen = isSidebarOpen
const router = useRouter()
const feedbacks = ref([])

// form
const form = ref({
  category: '',
  title: '',
  message: '',
  priority: 'medium',
  attachmentName: '',
  attachmentData: ''
})

const error = ref('')
const success = ref('')

// filters & pagination
const filter = ref({ category: '', priority: '', unresolved: false })
const page = ref(1)
const perPage = 6

// load saved feedbacks from localStorage
function loadStored() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    feedbacks.value = raw ? JSON.parse(raw) : []
  } catch (e) {
    feedbacks.value = []
  }
}
loadStored()

watch(feedbacks, (v) => {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(v)) } catch {}
}, { deep: true })

function validateForm() {
  error.value = ''
  if (!form.value.title.trim()) return (error.value = 'Judul wajib diisi')
  if (!form.value.message.trim()) return (error.value = 'Deskripsi wajib diisi')
  return true
}

function submitFeedback() {
  success.value = ''
  if (!validateForm()) return

  const payload = {
    id: Date.now(),
    category: form.value.category || 'umum',
    title: form.value.title.trim(),
    message: form.value.message.trim(),
    priority: form.value.priority || 'medium',
    attachmentName: form.value.attachmentName || '',
    attachmentData: form.value.attachmentData || '',
    resolved: false,
    createdAt: new Date().toISOString()
  }

  feedbacks.value.unshift(payload)
  success.value = 'Feedback terkirim'
  // reset form
  form.value = { category: '', title: '', message: '', priority: 'medium', attachmentName: '', attachmentData: '' }
  // reset page to first
  page.value = 1

  // clear success after a short moment
  setTimeout(() => (success.value = ''), 2500)
}

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  form.value.attachmentName = file.name
  // read as data URL for demo purposes
  const reader = new FileReader()
  reader.onload = () => (form.value.attachmentData = reader.result)
  reader.readAsDataURL(file)
}

// list actions
function removeFeedback(id) {
  feedbacks.value = feedbacks.value.filter(f => f.id !== id)
}

function toggleResolved(id) {
  const idx = feedbacks.value.findIndex(f => f.id === id)
  if (idx === -1) return
  feedbacks.value[idx].resolved = !feedbacks.value[idx].resolved
}

// filters / pagination computed
const filtered = computed(() => {
  return feedbacks.value.filter(f => {
    if (filter.value.category && f.category !== filter.value.category) return false
    if (filter.value.priority && f.priority !== filter.value.priority) return false
    if (filter.value.unresolved && f.resolved) return false
    return true
  })
})

const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))

const pagedFeedbacks = computed(() => {
  const start = (page.value - 1) * perPage
  return filtered.value.slice(start, start + perPage)
})

function nextPage() { if (page.value < pageCount.value) page.value++ }
function prevPage() { if (page.value > 1) page.value-- }

function applyFilter() { page.value = 1 }
function resetFilter() {
  filter.value = { category: '', priority: '', unresolved: false }
  page.value = 1
}

const unresolvedCount = computed(() => feedbacks.value.filter(f => !f.resolved).length)
function countPriority(p) { return feedbacks.value.filter(f => f.priority === p).length }

function priorityColor(p) {
  if (p === 'high') return 'bg-red-500'
  if (p === 'medium') return 'bg-yellow-500'
  return 'bg-green-500'
}

function formatDate(iso) {
  try {
    const d = new Date(iso)
    return d.toLocaleString()
  } catch { return iso }
}
</script>

<style scoped>
/* small tuning so bottom of cards look good */
article > .bg-slate-50 img {
  max-height: 220px;
}
</style>