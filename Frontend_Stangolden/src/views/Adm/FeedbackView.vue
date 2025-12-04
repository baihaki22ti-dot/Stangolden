<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="pageTitle" :description="pageDesc" />

      <main class="p-6 max-w-6xl w-full mx-auto space-y-6">
        <!-- Kirim feedback (admin dan siswa sama) -->
        <section class="bg-white rounded-xl shadow p-5">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold mb-2">Kirim Feedback</h2>
            <button @click="reload" class="px-3 py-1 border rounded text-sm hover:bg-slate-50">Muat Ulang</button>
          </div>

          <form @submit.prevent="submitFeedback" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                <select v-model="form.category" class="w-full border rounded px-3 py-2 text-sm">
                  <option value="">Pilih kategori</option>
                  <option value="bug">Bug / Error</option>
                  <option value="fitur">Permintaan Fitur</option>
                  <option value="umum">Umum</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Judul</label>
                <input
                  v-model="form.title"
                  type="text"
                  placeholder="Ringkas masalah atau saran"
                  class="w-full border rounded px-3 py-2 text-sm"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
              <textarea
                v-model="form.message"
                rows="4"
                placeholder="Tuliskan detailnya..."
                class="w-full border rounded px-3 py-2 text-sm"
              ></textarea>
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
              <span v-if="attachmentName" class="text-sm text-slate-500">{{ attachmentName }}</span>
            </div>

            <div class="flex items-center justify-end gap-3">
              <button type="button" @click="resetForm" class="px-4 py-2 bg-slate-100 rounded text-sm">Reset</button>
              <button type="submit" :disabled="submitting" class="px-4 py-2 bg-sky-600 text-white rounded text-sm">
                {{ submitting ? 'Mengirim...' : 'Kirim' }}
              </button>
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <p v-if="success" class="text-sm text-green-600">{{ success }}</p>
          </form>
        </section>

        <!-- Konten utama: daftar + (opsional) sidebar -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Daftar feedback -->
          <div class="col-span-1">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-lg font-semibold">Daftar Feedback</h3>
              <span class="text-sm text-slate-600">Total: {{ total }}</span>
            </div>

            <div v-if="loading" class="bg-white rounded-xl shadow p-6 text-sm text-slate-600">
              Memuat feedback...
            </div>

            <div v-else-if="pagedFeedbacks.length === 0" class="bg-white rounded-xl shadow p-6 text-sm text-slate-600">
              Belum ada feedback.
            </div>

            <ul v-else class="space-y-4">
              <li v-for="fb in pagedFeedbacks" :key="fb.id" class="bg-white rounded-xl shadow p-4">
                <div class="flex justify-between items-start gap-3">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold">{{ fb.title }}</span>
                      <span class="text-xs px-2 py-0.5 rounded-full text-white" :class="priorityColor(fb.priority)">
                        {{ fb.priority }}
                      </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                      {{ fb.category }} • {{ formatDate(fb.created_at) }}
                      <template v-if="fb.user_name"> • oleh {{ fb.user_name }}</template>
                    </div>
                  </div>

                  <div class="flex items-center gap-2">
                    <button
                      v-if="isAdminUser"
                      @click="toggleResolved(fb.id)"
                      class="text-xs px-2 py-1 rounded border text-slate-700"
                    >
                      {{ fb.resolved ? 'Buka' : 'Selesai' }}
                    </button>
                    <button
                      v-if="isAdminUser"
                      @click="removeFeedback(fb.id)"
                      class="text-xs px-2 py-1 rounded bg-red-50 text-red-600"
                    >
                      Hapus
                    </button>
                  </div>
                </div>

                <p class="mt-3 text-sm text-slate-700">{{ fb.message }}</p>

                <div v-if="fb.attachment_name && fb.attachment_url" class="mt-3">
                  <a :href="fb.attachment_url" target="_blank" class="text-xs text-sky-600 underline">
                    Lampiran: {{ fb.attachment_name }}
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

          <!-- Sidebar ringkasan + filter (HANYA ADMIN) -->
          <aside v-if="isAdminUser" class="col-span-1">
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

                <button @click="applyFilter" class="w-full mt-2 px-3 py-2 bg-sky-600 text-white rounded text-sm">
                  Terapkan
                </button>
                <button @click="resetFilter" class="w-full mt-2 px-3 py-2 border rounded text-sm">Reset</button>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow p-4">
              <h4 class="text-sm font-semibold mb-2">Ringkasan</h4>
              <p class="text-sm">Total Feedback: <span class="font-medium">{{ total }}</span></p>
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
import { ref, computed, watch, onMounted } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen

// Role
const me = ref(null)
const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})
const pageTitle = computed(() => (isAdminUser.value ? 'Feedback (Admin)' : 'Feedback'))
const pageDesc = computed(() =>
  isAdminUser.value ? 'Masukan & laporan dari pengguna (admin dapat memfilter seluruh feedback)'
                    : 'Masukan & laporan Anda (hanya feedback Anda yang ditampilkan)'
)

onMounted(async () => {
  try {
    me.value = await backendService.auth.user()
  } catch (e) {
    console.warn('Gagal memuat user, default siswa:', e)
    me.value = { id: null, role: 'siswa', is_admin: false }
  }
  await reload()
})

// Form state
const form = ref({
  category: '',
  title: '',
  message: '',
  priority: 'medium',
})
const attachmentFile = ref(null)
const attachmentName = ref('')

const error = ref('')
const success = ref('')
const submitting = ref(false)

// List & filter state
const feedbacks = ref([])
const loading = ref(false)
const filter = ref({ category: '', priority: '', unresolved: false })
const page = ref(1)
const perPage = 6

function validateForm() {
  error.value = ''
  if (!form.value.title?.trim()) return (error.value = 'Judul wajib diisi')
  if (!form.value.message?.trim()) return (error.value = 'Deskripsi wajib diisi')
  return true
}

async function submitFeedback() {
  success.value = ''
  if (!validateForm()) return
  submitting.value = true
  try {
    await backendService.feedbacks.create({
      category: form.value.category || 'umum',
      title: form.value.title.trim(),
      message: form.value.message.trim(),
      priority: form.value.priority || 'medium',
      attachment: attachmentFile.value || undefined,
    })
    success.value = 'Feedback terkirim'
    resetForm()
    await reload()
    setTimeout(() => (success.value = ''), 2500)
  } catch (e) {
    console.error(e)
    error.value = e?.response?.data?.message || e.message || 'Gagal mengirim feedback'
  } finally {
    submitting.value = false
  }
}

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) {
    attachmentFile.value = null
    attachmentName.value = ''
    return
  }
  attachmentFile.value = file
  attachmentName.value = file.name
}

function resetForm() {
  form.value = { category: '', title: '', message: '', priority: 'medium' }
  attachmentFile.value = null
  attachmentName.value = ''
  error.value = ''
}

// Reload with role-aware behavior
async function reload() {
  loading.value = true
  try {
    const params = {}
    // Admin dapat filter server-side
    if (isAdminUser.value) {
      if (filter.value.category) params.category = filter.value.category
      if (filter.value.priority) params.priority = filter.value.priority
      if (filter.value.unresolved) params.unresolved = 1
    } else {
      // Siswa: batasi ke feedback milik user. Jika backend mendukung query user_id, kirimkan:
      if (me.value?.id) params.user_id = me.value.id
    }

    const items = await backendService.feedbacks.list(params)
    const raw = Array.isArray(items) ? items : []

    // Siswa: jika backend belum support filter user_id, lakukan client-side filter
    const filteredByOwner = isAdminUser.value
      ? raw
      : raw.filter(f => String(f.user_id || f.userId || '') === String(me.value?.id || ''))

    feedbacks.value = filteredByOwner
    page.value = 1
  } catch (e) {
    console.error('Gagal memuat feedback', e)
    feedbacks.value = []
  } finally {
    loading.value = false
  }
}

async function toggleResolved(id) {
  try {
    await backendService.feedbacks.toggle(id)
    await reload()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e.message || 'Gagal mengubah status')
  }
}

async function removeFeedback(id) {
  try {
    await backendService.feedbacks.remove(id)
    await reload()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e.message || 'Gagal menghapus feedback')
  }
}

// Client-side filter (admin only; siswa sudah disaring owner)
const filtered = computed(() => {
  const base = feedbacks.value
  if (!isAdminUser.value) return base
  return base.filter(f => {
    if (filter.value.category && f.category !== filter.value.category) return false
    if (filter.value.priority && f.priority !== filter.value.priority) return false
    if (filter.value.unresolved && f.resolved) return false
    return true
  })
})

const total = computed(() => filtered.value.length)
const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))
const pagedFeedbacks = computed(() => {
  const start = (page.value - 1) * perPage
  return filtered.value.slice(start, start + perPage)
})

function nextPage() { if (page.value < pageCount.value) page.value++ }
function prevPage() { if (page.value > 1) page.value-- }

// Filter actions (admin only)
function applyFilter() {
  page.value = 1
  reload()
}
function resetFilter() {
  filter.value = { category: '', priority: '', unresolved: false }
  page.value = 1
  reload()
}

// Summary (admin only)
const unresolvedCount = computed(() => filtered.value.filter(f => !f.resolved).length)
function countPriority(p) { return filtered.value.filter(f => f.priority === p).length }

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

watch(filter, () => {
  if (isAdminUser.value) {
    // opsional auto reload saat filter berubah pada admin
    // reload()
  }
}, { deep: true })
</script>

<style scoped>
article > .bg-slate-50 img {
  max-height: 220px;
}
</style>