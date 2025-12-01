<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="pageTitle" :description="pageSubtitle" />

      <main class="p-6 max-w-3xl w-full mx-auto space-y-6">
        <header>
          <h1 class="text-2xl font-bold text-slate-800">{{ formMode === 'add' ? 'Tambah TryOut' : 'Edit TryOut' }}</h1>
          <p class="text-sm text-slate-500">Isi informasi tryout lalu simpan.</p>
        </header>

        <section class="bg-white rounded-xl shadow p-6">
          <form @submit.prevent="onSave" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Judul</label>
              <input v-model="form.title" required class="w-full border rounded px-3 py-2 text-sm" />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal pelaksanaan</label>
              <input v-model="form.date" type="date" class="w-full border rounded px-3 py-2 text-sm" />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi singkat</label>
              <textarea v-model="form.description" rows="4" class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-between">
              <div>
                <button type="button" @click="onCancel" class="px-4 py-2 border rounded">Batal</button>
              </div>
              <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded">{{ formMode === 'add' ? 'Buat' : 'Simpan' }}</button>
              </div>
            </div>
          </form>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const isOpen = isSidebarOpen

const STORAGE_KEY = 'app_tryouts_v1'
const formMode = ref('add') // 'add' | 'edit'
const form = ref({
  id: null,
  title: '',
  date: '',
  description: '',
  createdAt: ''
})

const pageTitle = ref('TryOut')
const pageSubtitle = ref('')

function loadData() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    const list = raw ? JSON.parse(raw) : []
    if (route.params.id) {
      const found = list.find(t => String(t.id) === String(route.params.id))
      if (found) {
        form.value = { ...found }
        formMode.value = 'edit'
        pageTitle.value = 'Edit TryOut'
        pageSubtitle.value = found.title
      } else {
        // not found — fallback to add
        formMode.value = 'add'
      }
    } else {
      // new
      formMode.value = 'add'
      form.value = { id: null, title: '', date: '', description: '', createdAt: '' }
      pageTitle.value = 'Tambah TryOut'
    }
  } catch (e) {
    formMode.value = 'add'
  }
}

onMounted(() => loadData())

function persist(list) {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list)) } catch {}
}

function onSave() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    const list = raw ? JSON.parse(raw) : []

    if (formMode.value === 'add') {
      const id = Date.now()
      const payload = { id, title: form.value.title.trim(), date: form.value.date || '', description: form.value.description.trim(), createdAt: new Date().toISOString() }
      list.unshift(payload)
      persist(list)
      router.push({ path: '/admin/tryout' })
    } else {
      const idx = list.findIndex(t => String(t.id) === String(form.value.id))
      if (idx !== -1) {
        list.splice(idx, 1, { ...form.value })
        persist(list)
      }
      router.push({ path: '/admin/tryout' })
    }
  } catch (e) {
    console.error(e)
    alert('Gagal menyimpan data')
  }
}

function onCancel() {
  router.push({ path: '/admin/tryout' })
}
</script>

<style scoped>
/* minor spacing tweaks if needed */
</style>