<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

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

        <!-- Ranking (TOP 5 per domain) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <RankingTable title="TOP 5 RANKING - UPKP (Skor Tertinggi)" :items="rankingUPKPScore" />
          <RankingTable title="TOP 5 RANKING - TUBEL (Skor Tertinggi)" :items="rankingTUBELScore" />
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

        <div class="text-dark order-2 order-md-1">
          <span class="text-gray-400 fw-bold me-1">TubelStan</span>
          <a href="https://keenthemes.com" target="_blank" class="text-muted text-hover-primary fw-bold me-2 fs-6">STANGOLDEN © 2025</a>
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

// Stats ringkas
const stats = ref({
  users: 0,
  online: 0,
  modules: 0,
  tryouts: 0
})

// Ranking per domain (TOP 5)
const rankingUPKPScore = ref([])   // skor tertinggi
const rankingTUBELScore = ref([])
const rankingUPKPCorrect = ref([]) // benar terbanyak
const rankingTUBELCorrect = ref([])

// Chart registrasi
const chartLabels = ref(['2019', '2020', '2021', '2022'])
const chartValues = ref([25, 40, 30, 50])

const chartColors = [
  '#F97316', '#F59E0B', '#FCD34D', '#10B981',
  '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6'
]

function toNumber(n, def = 0) {
  const x = Number(n)
  return Number.isFinite(x) ? x : def
}

async function loadStats() {
  try {
    // Prefer a single backend endpoint if available
    if (backendService?.admin?.dashboard?.stats) {
      const s = await backendService.admin.dashboard.stats()
      stats.value.users = toNumber(s?.users ?? 0)
      stats.value.online = toNumber(s?.online ?? 0)
      stats.value.modules = toNumber(s?.modules ?? 0)
      stats.value.tryouts = toNumber(s?.tryouts ?? 0)
      return
    }

    // Fallback aggregation
    const { data, counts } = await backendService.users.list()
    stats.value.users = toNumber(counts?.total ?? data.length)

    // Modules
    const modules = await backendService.adminModules.list()
    stats.value.modules = toNumber(Array.isArray(modules) ? modules.length : modules?.total ?? 0)

    // Tryouts: count series across all groups
    let tryoutTotal = 0
    try {
      // If there is an endpoint returning all series across groups
      const resAllGroups = await backendService.admin.tryout.listGroups()
      const groups = Array.isArray(resAllGroups?.data) ? resAllGroups.data : (Array.isArray(resAllGroups) ? resAllGroups : [])
      for (const g of groups) {
        const series = await backendService.admin.tryout.listSeries(g.id)
        const count = Array.isArray(series?.data) ? series.data.length : (Array.isArray(series) ? series.length : 0)
        tryoutTotal += count
      }
    } catch {
      // Fallback to public tryouts list if available
      const tryouts = await backendService.tryouts.list()
      tryoutTotal = toNumber(Array.isArray(tryouts) ? tryouts.length : tryouts?.total ?? 0)
    }
    stats.value.tryouts = tryoutTotal

    // Online: estimated from users list (active + approved + not expired)
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

async function loadRankingDomain(domainKey) {
  // domainKey: 'upkp' | 'tubel'
  try {
    const limit = 5
    // Prefer backend endpoints with domain scoping: /api/admin/attempts/top?domain=upkp
    let topScore = []
    let topCorrect = []

    if (backendService?.admin?.attempts?.top) {
      topScore = await backendService.admin.attempts.top({ domain: domainKey, limit })
    }
    if (backendService?.admin?.attempts?.topCorrect) {
      topCorrect = await backendService.admin.attempts.topCorrect({ domain: domainKey, limit })
    }

    // If backend does not support domain filters, fallback to list and filter by domain in FE using tryout_name or category
    if ((!topScore || topScore.length === 0) || (!topCorrect || topCorrect.length === 0)) {
      try {
        const res = await backendService.admin.attempts.list({ limit: 1000 })
        const items = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
        const normalized = items.map(a => ({
          name: a.user_name ?? a.user?.name ?? a.username ?? 'Peserta',
          score: Number(a.score ?? 0),
          correct: Number(a.correct ?? a.correct_count ?? 0),
          wrong: Number(a.wrong ?? a.wrong_count ?? 0),
          domain: (a.domain ?? a.tryout_domain ?? a.series_domain ?? '').toLowerCase(), // expect backend to include domain
          tryout_name: a.tryout_title ?? a.series_title ?? a.bank_title ?? ''
        }))
        const filtered = normalized.filter(x => {
          // FE heuristic: match domain field; if absent, infer from tryout/bank title keywords
          if (x.domain) return x.domain === domainKey
          const t = (x.tryout_name || '').toLowerCase()
          if (domainKey === 'upkp') return t.includes('upkp')
          if (domainKey === 'tubel') return t.includes('tubel') || t.includes('tugas belajar')
          return false
        })
        topScore = filtered.sort((x, y) => y.score - x.score).slice(0, limit)
        topCorrect = filtered.sort((x, y) => y.correct - x.correct).slice(0, limit)
      } catch {
        // Final fallback dummy
        const dummy = [
          { name: 'Andi F Noya', score: 459, correct: 88, tryout_name: domainKey.toUpperCase() },
          { name: 'Mutiara Angelina', score: 437, correct: 85, tryout_name: domainKey.toUpperCase() },
          { name: 'Annisa Sumiarti', score: 421, correct: 83, tryout_name: domainKey.toUpperCase() },
          { name: 'Zulkarnain Harahab', score: 414, correct: 82, tryout_name: domainKey.toUpperCase() },
          { name: 'Nanang Suparman', score: 412, correct: 82, tryout_name: domainKey.toUpperCase() },
        ]
        topScore = dummy
        topCorrect = dummy.map(d => ({ name: d.name, correct: d.correct, tryout_name: d.tryout_name }))
      }
    }

    const scoreItems = (topScore || []).map(r => ({
      name: r.name,
      score: toNumber(r.score),
      detail: r.tryout_name ? `(${r.tryout_name})` : ''
    }))
    const correctItems = (topCorrect || []).map(r => ({
      name: r.name,
      score: toNumber(r.correct),
      detail: 'Jawaban Benar'
    }))

    if (domainKey === 'upkp') {
      rankingUPKPScore.value = scoreItems
      rankingUPKPCorrect.value = correctItems
    } else {
      rankingTUBELScore.value = scoreItems
      rankingTUBELCorrect.value = correctItems
    }
  } catch (e) {
    console.error(`Gagal memuat ranking domain ${domainKey}:`, e)
    if (domainKey === 'upkp') {
      rankingUPKPScore.value = []
      rankingUPKPCorrect.value = []
    } else {
      rankingTUBELScore.value = []
      rankingTUBELCorrect.value = []
    }
  }
}

async function loadRegisteredChart() {
  try {
    // If backend provides stats, use them: GET /api/admin/dashboard/registered
    if (backendService?.admin?.dashboard?.stats) {
      // Assuming stats() might also include registered by year e.g., { registered: { '2023': 120, '2024': 90 } }
      const s = await backendService.admin.dashboard.stats()
      if (s?.registered && typeof s.registered === 'object') {
        const entries = Object.entries(s.registered).sort((a, b) => Number(a[0]) - Number(b[0]))
        chartLabels.value = entries.map(([year]) => String(year))
        chartValues.value = entries.map(([, count]) => toNumber(count, 0))
        return
      }
    }

    // Fallback aggregation from users list
    const { data } = await backendService.users.list()
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
  }
}

onMounted(async () => {
  await loadStats()
  await Promise.all([
    loadRankingDomain('upkp'),
    loadRankingDomain('tubel'),
    loadRegisteredChart(),
  ])
})
</script>