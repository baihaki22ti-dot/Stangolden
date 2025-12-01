<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Daftar Modul" description="Kelola modul: tambah, edit, hapus" />

      <main class="p-6 max-w-4xl w-full mx-auto space-y-6">
        <header class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-800"></h1>
          </div>

        </header>

        <section class="bg-white rounded-xl shadow p-4">
          <div v-if="modules.length === 0" class="text-sm text-slate-500 p-6 text-center">
            Modul Belum Tersedia.
          </div>

          <ul v-else class="space-y-3">
            <ModuleListItem
              v-for="(m, idx) in modules"
              :key="m.id"
              :module="m"
              :index="idx"
              :showControls="true"
            />
          </ul>
        </section>
      </main>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Sidebar from '@/views/Siswa/Components/Sidebar.vue'
import Navbar from '@/views/Siswa/Components/Navbar.vue'
import ModuleListItem from '@/views/Siswa/Components/ModulListItem.vue'
import { isSidebarOpen } from '@/stores/sidebar'

// STORAGE_KEY untuk menyimpan modul admin
const STORAGE_KEY = 'app_modul_list_v1'

const isOpen = isSidebarOpen
const modules = ref([])


function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    modules.value = raw ? JSON.parse(raw) : []
  } catch (e) {
    modules.value = []
  }

  // Jika kosong, tambahkan contoh modul (sample) agar admin melihat contoh dan bisa klik
  if (!modules.value || modules.value.length === 0) {
    const samplePdf = 'https://mozilla.github.io/pdf.js/web/compressed.tracemonkey-pldi-09.pdf' // contoh PDF publik
    const now = new Date().toISOString()
    modules.value = [
      { id: 1, name: 'Modul 1 - Pengenalan', description: 'Ringkasan pengenalan modul 1.', pdfName: 'sample-modul1.pdf', pdfData: samplePdf, createdAt: now },
      { id: 2, name: 'Modul 2 - Konsep Dasar', description: 'Ringkasan konsep dasar untuk modul 2.', pdfName: 'sample-modul2.pdf', pdfData: samplePdf, createdAt: now },
      { id: 3, name: 'Modul 3 - Praktik', description: 'Ringkasan praktik dan latihan.', pdfName: 'sample-modul3.pdf', pdfData: samplePdf, createdAt: now },
      { id: 4, name: 'Modul 4 - Studi Kasus', description: 'Ringkasan studi kasus penting.', pdfName: 'sample-modul4.pdf', pdfData: samplePdf, createdAt: now },
      { id: 5, name: 'Modul 5 - Penutup', description: 'Ringkasan penutup dan tindak lanjut.', pdfName: 'sample-modul5.pdf', pdfData: samplePdf, createdAt: now }
    ]
    save()
  }
}

function save() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(modules.value)) } catch {}
}



// called when confirm modal confirmed
function onConfirm() {
  if (typeof confirmAction === 'function') confirmAction()
  confirmAction = null
  confirmOpen.value = false
}

onMounted(() => {
  load()
})
</script>

<style scoped>
/* small spacing */
ul { list-style: none; padding: 0; margin: 0; }
</style>