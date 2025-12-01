<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <!-- content area shifts when sidebar is open on md+ -->
    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Dashboard" description="Welcome back — here's today's overview" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard title="Peserta" :value="stats.totalParticipants" />
          <StatCard title="Online" :value="stats.online" />
          <StatCard title="Total Modul" :value="stats.totalModules" />
          <StatCard title="Total TO" :value="stats.totalTryouts" />
        </div>

        <!-- Ranking -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <RankingTable title="TOP 5 RANKING TO 10 Tugas Belajar" :items="rankingTB" />
          <RankingTable title="TOP 5 RANKING TO 10 UPKP" :items="rankingUPKP" />
        </div>

        <!-- Middle area: left rankings, right big module box -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="space-y-6 lg:col-span-1">
            <RankingList title="TOP 5 RANKING TO TUGAS BELAJAR" :items="rankingTugasBelajar" />
            <RankingList title="TOP 5 RANKING UPKP" :items="rankingUPKPList" />
          </div>

          <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow p-4">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">MODUL</h3>
                <div class="text-sm text-slate-500">Progress keseluruhan</div>
              </div>

              <div class="grid grid-cols-1 gap-6">
                <div>
                  <h4 class="text-sm font-medium mb-2">TUGAS BELAJAR</h4>
                  <ModuleProgress :items="modules.tugasBelajar" />
                </div>

                <div class="mt-4">
                  <h4 class="text-sm font-medium mb-2">UPKP</h4>
                  <ModuleProgress :items="modules.upkp" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Bottom lists: TUGAS BELAJAR and UPKP (top 10 style) -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <ScoreTable title="TUGAS BELAJAR" :rows="tugasBelajarTop10" />
          <ScoreTable title="UPKP" :rows="upkpTop10" />
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
/*
  Cleaned & improved SiswaDashboard:
  - Add missing imports (ref, computed, onMounted).
  - Remove unnecessary/unused variables.
  - Provide `stats` computed to avoid hardcoded values.
  - Provide robust fallbacks if no storage present (seeding for demo).
  - Ensure ranking & top10 lists are computed consistently.
*/

import { ref, computed, onMounted } from 'vue'
import { isSidebarOpen } from '@/stores/sidebar'
import Sidebar from '@/views/Siswa/Components/Sidebar.vue'
import Navbar from '@/views/Siswa/Components/Navbar.vue'
import StatCard from '@/views/Siswa/Components/StatCard.vue'
import RankingTable from '@/views/Siswa/Components/RankingTable.vue'
import RankingList from '@/views/Siswa/Components/RankingList.vue'
import ModuleProgress from '@/views/Siswa/Components/ModuleProgress.vue'
import ScoreTable from '@/views/Siswa/Components/ScoreTable.vue'

// reactive UI state
const isOpen = isSidebarOpen

// storage keys used across the app
const PARTICIPANT_KEY = 'app_peserta_v1'
const MODULE_KEY = 'app_modul_list_v1'
const TRYOUT_KEY = 'app_tryouts_v1'
const PARTICIPANT_SCORE_KEY = 'app_participant_scores_v1' // optional scores per category
const MODULE_PROGRESS_KEY = 'app_module_progress_v1' // optional progress store per participant

// reactive data
const modulesList = ref([])
const tryouts = ref([])
const scores = ref([]) // expected shape: [{ userId, name, category, score }]
const moduleProgressStore = ref({}) // { [key]: percent }

// simple top-5 sample (fallback)
const rankingTB = [
  { name: "Andi F Noya", score: 459 },
  { name: "Mutiara Angelina", score: 437 },
  { name: "Annisa Sumiarti", score: 421 },
  { name: "Zulkarnain Harahab", score: 414 },
  { name: "Nanang Suparman", score: 412 }
]
const rankingUPKP = rankingTB

// load data from localStorage (non-throwing)
function loadStorage() {
  try { modulesList.value = JSON.parse(localStorage.getItem(MODULE_KEY) || '[]') } catch { modulesList.value = [] }
  try { tryouts.value = JSON.parse(localStorage.getItem(TRYOUT_KEY) || '[]') } catch { tryouts.value = [] }
  try { scores.value = JSON.parse(localStorage.getItem(PARTICIPANT_SCORE_KEY) || '[]') } catch { scores.value = [] }
  try { moduleProgressStore.value = JSON.parse(localStorage.getItem(MODULE_PROGRESS_KEY) || '{}') } catch { moduleProgressStore.value = {} }
}

// seed demo data only if empty (helps visual parity with design)
function seedDemoIfEmpty() {
  if (!modulesList.value || modulesList.value.length === 0) {
    modulesList.value = [
      { id: 'tpa', name: 'TPA', group: 'tugasBelajar' },
      { id: 'tbi', name: 'TBI', group: 'tugasBelajar' },
      { id: 'wawasan-kebangsaan', name: 'Wawasan Kebangsaan', group: 'upkp' },
      { id: 'nilai-nilai-kemenkeu', name: 'Nilai-Nilai Kemenkeu', group: 'upkp' },
      { id: 'etika-pns', name: 'Etika PNS', group: 'upkp' },
      { id: 'tata-aturan-kepegawaian', name: 'Tata Aturan Kepegawaian', group: 'upkp' },
      { id: 'fungsi-kemenkeu', name: 'Fungsi Kemenkeu', group: 'upkp' }
    ]
    localStorage.setItem(MODULE_KEY, JSON.stringify(modulesList.value))
  }

  if (!tryouts.value || tryouts.value.length === 0) {
    const now = new Date().toISOString()
    tryouts.value = [
      { id: 101, title: 'TO 10', createdAt: now, date: '2025-12-10' },
      { id: 102, title: 'TO 9', createdAt: now, date: '2025-12-17' },
      { id: 103, title: 'TO 8', createdAt: now, date: '2026-01-05' },
      { id: 104, title: 'TO 7', createdAt: now, date: '2026-01-20' },
      { id: 105, title: 'TO 6', createdAt: now, date: '2026-02-02' },
      { id: 106, title: 'TO 5', createdAt: now, date: '2026-02-10' },
      { id: 107, title: 'TO 4', createdAt: now, date: '2026-02-20' },
      { id: 108, title: 'TO 3', createdAt: now, date: '2026-03-05' },
      { id: 109, title: 'TO 2', createdAt: now, date: '2026-03-20' },
      { id: 110, title: 'TO 1', createdAt: now, date: '2026-04-01' }
    ]
    localStorage.setItem(TRYOUT_KEY, JSON.stringify(tryouts.value))
  }

  if (!scores.value || scores.value.length === 0) {
    scores.value = [
      { userId: 1, name: 'Andi F Noya', category: 'tugasBelajar', score: 459 },
      { userId: 2, name: 'Zulkarnain Harahab', category: 'tugasBelajar', score: 437 },
      { userId: 3, name: 'Annisa Sumiarti', category: 'tugasBelajar', score: 421 },
      { userId: 4, name: 'Mutiara Angelina', category: 'tugasBelajar', score: 414 },
      { userId: 5, name: 'Nanang Suparman', category: 'tugasBelajar', score: 412 },
      { userId: 1, name: 'Andi F Noya', category: 'upkp', score: 459 },
      { userId: 2, name: 'Zulkarnain Harahab', category: 'upkp', score: 437 },
      { userId: 3, name: 'Annisa Sumiarti', category: 'upkp', score: 421 },
      { userId: 4, name: 'Mutiara Angelina', category: 'upkp', score: 414 },
      { userId: 5, name: 'Nanang Suparman', category: 'upkp', score: 412 },
      // top10 demo entries for TO lists
      { userId: 'to-101', name: 'TO 10', category: 'to_tugasBelajar', score: 420 },
      { userId: 'to-102', name: 'TO 9', category: 'to_tugasBelajar', score: 411 },
      { userId: 'to-103', name: 'TO 8', category: 'to_tugasBelajar', score: 451 },
      { userId: 'to-104', name: 'TO 7', category: 'to_tugasBelajar', score: 380 },
      { userId: 'to-105', name: 'TO 6', category: 'to_tugasBelajar', score: 0 },
      { userId: 'to-106', name: 'TO 5', category: 'to_tugasBelajar', score: 0 },
      { userId: 'to-107', name: 'TO 4', category: 'to_tugasBelajar', score: 0 },
      { userId: 'to-108', name: 'TO 3', category: 'to_tugasBelajar', score: 432 },
      { userId: 'to-109', name: 'TO 2', category: 'to_tugasBelajar', score: 401 },
      { userId: 'to-110', name: 'TO 1', category: 'to_tugasBelajar', score: 411 },
      // duplicate for UPKP top10
      { userId: 'to-101', name: 'TO 10', category: 'to_upkp', score: 420 },
      { userId: 'to-102', name: 'TO 9', category: 'to_upkp', score: 411 },
      { userId: 'to-103', name: 'TO 8', category: 'to_upkp', score: 451 },
      { userId: 'to-104', name: 'TO 7', category: 'to_upkp', score: 380 },
      { userId: 'to-105', name: 'TO 6', category: 'to_upkp', score: 0 },
      { userId: 'to-106', name: 'TO 5', category: 'to_upkp', score: 0 },
      { userId: 'to-107', name: 'TO 4', category: 'to_upkp', score: 0 },
      { userId: 'to-108', name: 'TO 3', category: 'to_upkp', score: 432 },
      { userId: 'to-109', name: 'TO 2', category: 'to_upkp', score: 401 },
      { userId: 'to-110', name: 'TO 1', category: 'to_upkp', score: 411 }
    ]
    localStorage.setItem(PARTICIPANT_SCORE_KEY, JSON.stringify(scores.value))
  }

  if (!moduleProgressStore.value || Object.keys(moduleProgressStore.value).length === 0) {
    moduleProgressStore.value = { 'tpa': 100, 'tbi': 20 }
    localStorage.setItem(MODULE_PROGRESS_KEY, JSON.stringify(moduleProgressStore.value))
  }
}

// computed: stats
const stats = computed(() => {
  const totalParticipants = (() => {
    try {
      const arr = JSON.parse(localStorage.getItem(PARTICIPANT_KEY) || '[]')
      return Array.isArray(arr) ? arr.length : 6481
    } catch { return 6481 }
  })()

  const online = (() => {
    try {
      const arr = JSON.parse(localStorage.getItem(PARTICIPANT_KEY) || '[]')
      return Array.isArray(arr) ? arr.filter(p => p.active).length : 28
    } catch { return 28 }
  })()

  const totalModules = modulesList.value.length || 180
  const totalTryouts = tryouts.value.length || 74
  return { totalParticipants, online, totalModules, totalTryouts }
})

// ranking lists used by RankingList components
const rankingTugasBelajar = computed(() => {
  const arr = scores.value.filter(s => s.category === 'tugasBelajar').sort((a,b)=>b.score-a.score).slice(0,5)
  if (arr.length) return arr.map((s,i)=>({ rank: i+1, name: s.name, score: s.score }))
  return rankingTB.map((r,i)=>({ rank: i+1, name: r.name, score: r.score }))
})

const rankingUPKPList = computed(() => {
  const arr = scores.value.filter(s => s.category === 'upkp').sort((a,b)=>b.score-a.score).slice(0,5)
  if (arr.length) return arr.map((s,i)=>({ rank: i+1, name: s.name, score: s.score }))
  return rankingUPKP.map((r,i)=>({ rank: i+1, name: r.name, score: r.score }))
})

// modules structure for ModuleProgress component
const modules = computed(() => {
  const findPercent = (key, fallback) => {
    const val = moduleProgressStore.value[key]
    return typeof val === 'number' ? val : fallback
  }

  return {
    tugasBelajar: [
      { label: 'TPA', percent: findPercent('tpa', 100), color: 'bg-emerald-400' },
      { label: 'TBI', percent: findPercent('tbi', 20), color: 'bg-emerald-400' }
    ],
    upkp: [
      { label: 'Wawasan Kebangsaan', percent: findPercent('wawasan-kebangsaan', 0) },
      { label: 'Nilai-Nilai Kemenkeu', percent: findPercent('nilai-nilai-kemenkeu', 0) },
      { label: 'Etika PNS', percent: findPercent('etika-pns', 0) },
      { label: 'Tata Aturan Kepegawaian', percent: findPercent('tata-aturan-kepegawaian', 0) },
      { label: 'Fungsi Kemenkeu', percent: findPercent('fungsi-kemenkeu', 0) }
    ]
  }
})

// top10 lists for bottom area (TUGAS BELAJAR and UPKP), based on scores with category like 'to_tugasBelajar' and 'to_upkp'
const tugasBelajarTop10 = computed(() => {
  const arr = scores.value.filter(s => s.category === 'to_tugasBelajar').sort((a,b)=>b.score-a.score)
  if (arr.length) return arr.slice(0,10).map((r,i)=>({ rank: i+1, name: r.name, score: r.score }))
  return tryouts.value.slice(0,10).map((t,i)=>({ rank: i+1, name: t.title, score: 0 }))
})

const upkpTop10 = computed(() => {
  const arr = scores.value.filter(s => s.category === 'to_upkp').sort((a,b)=>b.score-a.score)
  if (arr.length) return arr.slice(0,10).map((r,i)=>({ rank: i+1, name: r.name, score: r.score }))
  return tryouts.value.slice(0,10).map((t,i)=>({ rank: i+1, name: t.title, score: 0 }))
})

onMounted(() => {
  loadStorage()
  seedDemoIfEmpty()
  // reload after seeding (ensures computed pick up seeded values)
  loadStorage()
})
</script>

<style scoped>
/* small global tuning to mimic the example layout */
.main-grid { gap: 1rem; }
</style>