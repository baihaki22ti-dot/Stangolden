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
/*
  Fixed: ensure watch is imported (it was missing and caused the runtime error)
  This view seeds richer dummy data when storage empty and seeds participants.
*/
import { ref, onMounted, watch } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import TryOutListItem from '@/views/Adm/Components/TryOutListItem.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { useRouter } from 'vue-router'

const router = useRouter()
const isOpen = isSidebarOpen

const STORAGE_KEY = 'app_tryouts_v1'
const PARTICIPANT_KEY = 'app_tryout_participants_v1'
const tryouts = ref([])

// load sample data when empty to make UI visible
function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    tryouts.value = raw ? JSON.parse(raw) : []
  } catch (e) {
    tryouts.value = []
  }

  // if no tryouts, seed with richer dummy data (with pdf links and sample descriptions)
  if (!tryouts.value || tryouts.value.length === 0) {
    const now = new Date().toISOString()
    // example public PDF for demo (CORS may apply; if issues, file will still show link)
    const samplePdf = 'https://mozilla.github.io/pdf.js/web/compressed.tracemonkey-pldi-09.pdf'

    tryouts.value = [
      {
        id: 101,
        title: 'Tryout Seleksi Calon 2025 - Sesi Pagi',
        createdAt: now,
        date: '2025-12-10',
        description: 'Tryout seleksi calon pegawai — mencakup TPA dan TBI. Durasi 120 menit.',
        pdfName: 'Materi_Tryout_Pagi.pdf',
        pdfData: samplePdf
      },
      {
        id: 102,
        title: 'Tryout Bahasa & TPA - Simulasi 1',
        createdAt: now,
        date: '2025-12-17',
        description: 'Simulasi soal bahasa & TPA untuk persiapan seleksi internal.',
        pdfName: 'Materi_Bahasa_TPA.pdf',
        pdfData: samplePdf
      },
      {
        id: 103,
        title: 'Tryout Kedisiplinan Kemenkeu',
        createdAt: now,
        date: '2026-01-05',
        description: 'Soal terkait kedisiplinan dan kebijakan internal Kemenkeu.',
        pdfName: '',
        pdfData: ''
      },
      {
        id: 104,
        title: 'Tryout Akhir Tahun - Komprehensif',
        createdAt: now,
        date: '2026-01-20',
        description: 'Tryout komprehensif yang mencakup seluruh materi tahun ini.',
        pdfName: 'Materi_Akhir_Tahun.pdf',
        pdfData: samplePdf
      }
    ]

    save()
  }

  // seed participants for demo if not present
  seedParticipantsIfEmpty()
}

function save() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(tryouts.value)) } catch {}
}

function seedParticipantsIfEmpty() {
  try {
    const raw = localStorage.getItem(PARTICIPANT_KEY)
    const existing = raw ? JSON.parse(raw) : []
    if (existing && existing.length > 0) return // don't overwrite existing participants

    const sampleParticipants = [
      // participants for tryout 101
      { id: 'p-1001', tryoutId: 101, name: 'Siti Aminah', email: 'siti.aminah@example.com', status: 'registered' },
      { id: 'p-1002', tryoutId: 101, name: 'Budi Santoso', email: 'budi.santoso@example.com', status: 'registered' },
      // participants for tryout 102
      { id: 'p-1003', tryoutId: 102, name: 'Agus Wijaya', email: 'agus.wijaya@example.com', status: 'approved' },
      { id: 'p-1004', tryoutId: 102, name: 'Rina Kusuma', email: 'rina.kusuma@example.com', status: 'approved' },
      // participants for tryout 104
      { id: 'p-1005', tryoutId: 104, name: 'Dewi Lestari', email: 'dewi.lestari@example.com', status: 'registered' }
    ]

    localStorage.setItem(PARTICIPANT_KEY, JSON.stringify(sampleParticipants))
  } catch (e) {
    // ignore
  }
}

onMounted(() => load())

// optional: watch localStorage changes from other tabs and reload tryouts
watch(
  () => localStorage.getItem(STORAGE_KEY),
  () => {
    tryouts.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  }
)

function goToNew() {
  router.push({ path: '/tryoutadm/new' }).catch(()=>{})
}

function onEdit(item) {
  router.push({ path: `/tryoutadm/${item.id}/edit` }).catch(()=>{})
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