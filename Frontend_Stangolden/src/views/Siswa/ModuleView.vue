<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Modul" description="Pilih modul yang ingin Anda pelajari" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-5 flex-1">

        <!-- CHANGED: wrapper flex + grid justify-center to center the whole grid -->
        <!-- CHANGED: pass larger cardWidth/cardHeight to make cards sedikit lebih besar -->
        <div class="flex justify-center items-center">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-6 justify-center items-stretch">
            <router-link
              v-for="(m, i) in modules"
              :key="m.id ?? i"
              :to="`/modul/${m.slug}`"
              class="block"
            >
              <ModuleCard
                :title="m.title"
                :description="m.description"
                :image="m.image"
                :gradientClass="m.gradientClass"
                :cardWidth="'25rem'"          
                :cardHeight="'16rem'"         
                :imageAreaHeight="'25rem'"   
                :to="`/modul/${m.slug}`"
              />
            </router-link>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import Sidebar from '@/views/Siswa/Components/Sidebar.vue';
import Navbar from '@/views/Siswa/Components/Navbar.vue';
import ModuleCard from '@/views/Siswa/Components/ModuleCard.vue';
import { isSidebarOpen } from '@/stores/sidebar'

import img1 from '@/assets/Picture1.png';
import img2 from '@/assets/Picture2.png';

const isOpen = isSidebarOpen

const modules = ref([
  {
    id: 1,
    title: 'UPKP',
    description: 'Ujian wajib bagi PNS untuk kenaikan pangkat setelah menyelesaikan tugas belajar.',
    image: img1,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'upkp'
  },
  {
    id: 2,
    title: 'Tugas Belajar',
    description: 'Izin belajar bagi PNS untuk melanjutkan pendidikan tanpa kehilangan status kepegawaiannya.',
    image: img2,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'tugas-belajar'
  }
])
</script>

<style scoped>
/* CHANGED: small visual tweak so centered grid doesn't stretch full width on large screens */
.grid {
  /* allow grid to shrink-wrap its content so the outer flex can center it */
  width: max-content;
}
</style>