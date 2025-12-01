<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <!-- content area shifts when sidebar is open on md+ -->
    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Dashboard" description="Welcome back — here's today's overview" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard title="Peserta" :value="6481" />
          <StatCard title="Online" :value="28" />
          <StatCard title="Total Modul" :value="180" />
          <StatCard title="Total TO" :value="74" />
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

          <div class="bg-white rounded-xl shadow p-4">
            <h3 class="text-lg font-bold mb-3">Quick Insights</h3>
            <p class="text-sm text-slate-600">Some summary details, trends, or action items can go here to complement the chart.</p>
          </div>
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
import { ref } from 'vue'

const isOpen = isSidebarOpen

const rankingTB = [
  { name: "Andi F Noya", score: 459 },
  { name: "Mutiara Angelina", score: 437 },
  { name: "Annisa Sumiarti", score: 421 },
  { name: "Zulkarnain Harahab", score: 414 },
  { name: "Nanang Suparman", score: 412 }
];

const rankingUPKP = rankingTB

const chartLabels = ref(['2019', '2020', '2021', '2022']);
const chartValues = ref([25, 40, 30, 50]);

const chartColors = [
  '#F97316', '#F59E0B', '#FCD34D', '#10B981',
  '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6'
];
</script>