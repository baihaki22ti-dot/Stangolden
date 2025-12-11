<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Import Soal" description="Tambahkan soal dari file Word (DOCX) berdasarkan kategori." />
      <main class="p-6 max-w-3xl w-full mx-auto space-y-6">
        <section class="bg-white rounded-xl shadow p-4 space-y-3">
          <div class="text-sm text-slate-600">Series ID: {{ seriesId }}</div>

          <label class="text-sm">Kategori Soal
            <select v-model="category" class="border rounded px-3 py-2 text-sm w-full">
              <option value="tskkwk">TSKKWK</option>
              <option value="tpa-verbal">TPA - Verbal</option>
              <option value="tpa-numerik">TPA - Numerik</option>
              <option value="tpa-figural">TPA - Figural</option>
            </select>
          </label>

          <label class="text-sm">File Word (DOCX)
            <input type="file" accept=".docx" @change="onFile" class="border rounded px-3 py-2 text-sm w-full" />
          </label>

          <div class="flex items-center gap-2">
            <button @click="importDocx" class="px-3 py-2 bg-sky-600 text-white rounded text-sm">Import DOCX ke Bank</button>
            <button @click="generateSessionsFromBank" class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">Generate TO dari Bank</button>
          </div>

          <div class="text-xs text-slate-500">
            Catatan: Import DOCX memasukkan soal ke Bank kategori tersebut. Setelah itu, tombol Generate akan membuat TO (sessions) untuk series ini sesuai kategori pilihan.
          </div>
        </section>

        <div v-if="loading" class="text-sm text-slate-600">Loading...</div>
        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
      </main>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const route = useRoute()
const seriesId = Number(route.params.seriesId)

const category = ref('tskkwk')
const file = ref(null)
const loading = ref(false)
const error = ref('')

function onFile(e) { file.value = e.target.files?.[0] || null }

async function importDocx() {
  if (!file.value) return alert('Pilih file DOCX')
  try {
    // Find or create bank for this category.
    // For simplicity, we create a bank per category under shared domain or infer domain as needed.
    const fd = new FormData()
    fd.append('file', file.value)
    fd.append('domain', 'shared') // or upkp/tubel based on series context if you track it
    fd.append('session_key', category.value)
    // Optionally pass existing bank_id if you have one selected
    const resp = await backendService.admin.import.docx(fd)
    alert(`Import selesai, bank_id=${resp?.bank_id}, created=${resp?.created}`)
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal import DOCX')
  }
}

async function generateSessionsFromBank() {
  try {
    // Ensure a session for this category exists (create if not)
    // In a real app, you'd lookup existing sessions for series and category; here we just create one if missing.
    const titleMap = {
      'tskkwk': 'TO TSKKWK',
      'tpa-verbal': 'TO TPA Verbal',
      'tpa-numerik': 'TO TPA Numerik',
      'tpa-figural': 'TO TPA Figural',
    }
    const payload = { key: category.value, title: titleMap[category.value] || category.value, duration_minutes: 60 }
    const sessionCreate = await backendService.admin.tryout.createSession(seriesId, payload)
    const sessionId = sessionCreate?.data?.id || sessionCreate?.id

    // Now generate snapshot from any bank with matching session_key.
    const banks = await backendService.admin.banks.index({ session_key: category.value })
    const bankList = banks?.data || banks
    if (!Array.isArray(bankList) || bankList.length === 0) return alert('Bank kategori ini belum ada')
    const bankId = bankList[0].id

    await backendService.admin.tryout.generateSessionQuestions(sessionId, { bank_id: bankId, count: 20 })
    alert('TO berhasil dibuat dari bank')
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || e?.message || 'Gagal generate TO')
  }
}
</script>