<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <!-- content area shifts when sidebar is open on md+ -->
    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Dashboard" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard title="Peserta" :value="stats.users" />
          <StatCard title="Online" :value="stats.online" />
          <StatCard title="Total Modul" :value="stats.modules" />
          <StatCard title="Total TO" :value="stats.tryouts" />
        </div>

        <!-- Ranking -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <RankingTable title="TOP 5 RANKING TO 10 Tugas Belajar" :items="rankingTB" />
          <RankingTable title="TOP 5 RANKING TO 10 UPKP" :items="rankingUPKP" />
        </div>

        <!-- Registered (chart) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <RegisteredChart
            :labels="chartLabels"
            :values="chartValues"
            title="Peserta Teregistrasi"
            chooseMode="fixed"
            :fixedHeightPx="240"
            :colors="chartColors"
          />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { isSidebarOpen } from '@/stores/sidebar'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import StatCard from '@/views/Adm/Components/StatCard.vue'
import RankingTable from '@/views/Adm/Components/RankingTable.vue'
import RegisteredChart from '@/views/Adm/Components/RegisteredChart.vue'
import { ref, onMounted } from 'vue'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen

// State untuk stats ringkas
const stats = ref({
  users: 0,
  online: 0,
  modules: 0,
  tryouts: 0
})

// Ranking (default kosong, akan diisi dari backend)
const rankingTB = ref([])
const rankingUPKP = ref([])

// Chart registrasi (default, akan diisi dari backend jika tersedia)
const chartLabels = ref(['2019', '2020', '2021', '2022'])
const chartValues = ref([25, 40, 30, 50])

const chartColors = [
  '#F97316', '#F59E0B', '#FCD34D', '#10B981',
  '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6'
]

// Helper: fallback aman untuk angka
function toNumber(n, def = 0) {
  const x = Number(n)
  return Number.isFinite(x) ? x : def
}

async function loadStats() {
  try {
    // Ambil total peserta (users.list) dan gunakan counts jika ada
    const { data, counts } = await backendService.users.list()
    stats.value.users = toNumber(counts?.total ?? data.length)

    // Ambil total modul dari adminModules.list
    const modules = await backendService.adminModules.list()
    stats.value.modules = toNumber(Array.isArray(modules) ? modules.length : modules?.total ?? 0)

    // Ambil total tryouts dari tryouts.list
    const tryouts = await backendService.tryouts.list()
    stats.value.tryouts = toNumber(Array.isArray(tryouts) ? tryouts.length : tryouts?.total ?? 0)

    // Online: jika backend menyediakan endpoint khusus, gunakan itu
    // Di sini kita estimasi online = jumlah peserta aktif (approved+active) yang belum expired
    const onlineCount = data.filter(u => {
      const user = u.raw ?? u
      const isActive = !!(user.active ?? user.activated ?? true)
      const isApproved = !!(user.approved ?? true)
      const exp = user.expires_at ?? user.expiresAt ?? null
      let notExpired = true
      if (exp) {
        const d = new Date(exp)
        notExpired = Date.now() <= d.getTime()
      }
      return isActive && isApproved && notExpired
    }).length
    stats.value.online = toNumber(onlineCount)
  } catch (e) {
    console.error('Gagal memuat stats:', e)
  }
}

async function loadRanking() {
  try {
    // Asumsi: backend menyediakan ranking via endpoint tryouts atau endpoint khusus.
    // Jika belum ada, kita buat estimasi dari feedbacks atau dummy.
    // Contoh: ambil 5 peserta dengan skor tertinggi untuk masing-masing kategori dari endpoint (sesuaikan jika ada).
    // Di sini kita fallback ke dummy jika backend belum ada.
    const tb = [
      { name: "Andi F Noya", score: 459 },
      { name: "Mutiara Angelina", score: 437 },
      { name: "Annisa Sumiarti", score: 421 },
      { name: "Zulkarnain Harahab", score: 414 },
      { name: "Nanang Suparman", score: 412 }
    ]
    rankingTB.value = tb
    rankingUPKP.value = tb
  } catch (e) {
    console.error('Gagal memuat ranking:', e)
    rankingTB.value = []
    rankingUPKP.value = []
  }
}

async function loadRegisteredChart() {
  try {
    // Asumsi: backend menyediakan statistik registrasi per tahun/bulan.
    // Jika belum ada endpoint, gunakan agregasi sederhana dari users.createdAt.

    const { data } = await backendService.users.list()
    // agregasi per tahun
    const map = new Map()
    data.forEach(u => {
      const created = u.createdAt ?? u.raw?.created_at
      if (!created) return
      const year = new Date(created).getFullYear()
      map.set(year, (map.get(year) ?? 0) + 1)
    })

    if (map.size > 0) {
      const sortedYears = Array.from(map.keys()).sort((a, b) => a - b)
      chartLabels.value = sortedYears.map(String)
      chartValues.value = sortedYears.map(y => toNumber(map.get(y), 0))
    }
  } catch (e) {
    console.error('Gagal memuat chart registrasi:', e)
    // tetap pakai default
  }
}

onMounted(async () => {
  await Promise.all([loadStats(), loadRanking(), loadRegisteredChart()])
})
</script>