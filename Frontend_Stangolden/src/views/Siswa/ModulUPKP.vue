<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="UPKP" description="Kumpulan modul UPKP" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-6 flex-1 items-center ">

        <!-- center the grid and make cards slightly larger for readability -->
        <div class="flex justify-center items-center">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6 justify-center items-stretch"
               style="width: max-content;">
            <!--
              NOTE: each card is wrapped with router-link so whole card is clickable.
              Routes like /modul/upkp/:slug are placeholders — create detail pages later if needed.
            -->
            <router-link
              v-for="(m, i) in modules"
              :key="m.id ?? i"
              :to="`/modul/upkp/${m.slug}`"
              class="block"
            >
              <ModuleCard
                :title="m.title"
                :description="m.description"
                :image="m.image"
                :gradientClass="m.gradientClass"
                :cardWidth="'19rem'"          
                :cardHeight="'15rem'"         
                :imageAreaHeight="'8rem'"     
                :to="`/modul/upkp/${m.slug}`"
              />
            </router-link>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '@/views/Siswa/Components/Sidebar.vue'
import Navbar from '@/views/Siswa/Components/Navbar.vue'
import ModuleCard from '@/views/Siswa/Components/ModuleCard.vue'
import { isSidebarOpen } from '@/stores/sidebar'

// placeholder images — replace with real assets if available
import img1 from '@/assets/Picture1.png'
import img2 from '@/assets/Picture2.png'
import img3 from '@/assets/Picture2.png'
import img4 from '@/assets/Picture1.png'
import img5 from '@/assets/Picture2.png'

const isOpen = isSidebarOpen

// 5 UPKP cards as requested; descriptions are placeholders for you to edit later.
// Each card has slug used in the route /modul/upkp/:slug
const modules = ref([
  {
    id: 1,
    title: 'Wawasan Kebangsaan',
    description: 'Deskripsi singkat tentang wawasan kebangsaan (edit nanti).',
    image: img1,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'wawasan-kebangsaan'
  },
  {
    id: 2,
    title: 'Nilai-nilai Kemenkeu',
    description: 'Deskripsi singkat tentang nilai-nilai Kemenkeu (edit nanti).',
    image: img2,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'nilai-nilai-kemenkeu'
  },
  {
    id: 3,
    title: 'Etika PNS',
    description: 'Deskripsi singkat tentang etika PNS (edit nanti).',
    image: img3,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'etika-pns'
  },
  {
    id: 4,
    title: 'Tata Aturan Kepegawaian',
    description: 'Deskripsi singkat tentang tata aturan kepegawaian (edit nanti).',
    image: img4,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'tata-aturan-kepegawaian'
  },
  {
    id: 5,
    title: 'Fungsi Kemenkeu',
    description: 'Deskripsi singkat tentang fungsi Kemenkeu (edit nanti).',
    image: img5,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'fungsi-kemenkeu'
  }
])
</script>

<style scoped>
/* keep grid shrink-wrapped so it centers nicely (same pattern as ModulView) */
.grid {
  width: max-content;
}
</style>