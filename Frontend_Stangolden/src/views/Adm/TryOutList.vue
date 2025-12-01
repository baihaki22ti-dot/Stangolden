<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="TryOut" description="Kelola daftar tryout: tambah, edit, hapus" />

      <main class="p-6 max-w-5xl w-full mx-auto space-y-6">
        <header class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-800">Daftar TryOut</h1>
            <p class="text-sm text-slate-500">Daftar tryout yang tersedia. Klik judul untuk melihat detail peserta (jika ada).</p>
          </div>

          <div class="flex items-center gap-3">
            <button @click="goToNew" class="px-4 py-2 bg-emerald-600 text-white rounded">Tambah TryOut</button>
          </div>
        </header>

        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="tryouts.length === 0" class="text-sm text-slate-500 p-6 text-center">
            Belum ada tryout. Klik "Tambah TryOut" untuk membuat baru.
          </div>

          <ul v-else class="space-y-3">
            <TryOutListItem
              v-for="(t, idx) in tryouts"
              :key="t.id"
              :item="t"
              :index="idx"
              @edit="onEdit"
              @delete="onDelete"
            />
          </ul>

          <!-- pagination can be added here if needed -->
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import TryOutListItem from '@/views/Adm/Components/TryOutListItem.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { useRouter } from 'vue-router'

const router = useRouter()
const isOpen = isSidebarOpen

const STORAGE_KEY = 'app_tryouts_v1'
const tryouts = ref([])

// load sample data when empty to make UI visible
function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    tryouts.value = raw ? JSON.parse(raw) : []
  } catch (e) {
    tryouts.value = []
  }

  if (!tryouts.value || tryouts.value.length === 0) {
    const now = new Date().toISOString()
    tryouts.value = [
      { id: 1, title: 'Tryout Seleksi 2025 - Sesi 1', createdAt: now, date: '2025-12-10', description: 'Tryout umum untuk persiapan seleksi.' },
      { id: 2, title: 'Tryout Bahasa - TPA', createdAt: now, date: '2025-12-17', description: 'Tryout khusus bahasa & TPA.' },
      { id: 3, title: 'Tryout Kemenkeu - Simulasi', createdAt: now, date: '2026-01-05', description: 'Simulasi ujian internal Kemenkeu.' }
    ]
    save()
  }
}

function save() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(tryouts.value)) } catch {}
}

onMounted(() => load())

function goToNew() {
  router.push({ path: '/admin/tryout/new' }).catch(()=>{})
}

function onEdit(item) {
  router.push({ path: `/admin/tryout/${item.id}/edit` }).catch(()=>{})
}

function onDelete(item) {
  if (!confirm(`Hapus tryout "${item.title}"?`)) return
  tryouts.value = tryouts.value.filter(t => t.id !== item.id)
  save()
}
</script>

<style scoped>
ul { list-style: none; padding: 0; margin: 0; }
</style>