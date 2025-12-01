<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Tugas Belajar" description="Modul Tugas Belajar" />

    <main class="p-6 max-w-7xl w-full mx-auto space-y-6 flex-1 pt-20">

        <div class="flex justify-center">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-6 justify-center items-stretch"
               style="width: max-content;">
            <!-- two cards: TPA and TBI -->
            <router-link to="/admin/modul/tugas-belajar/tpa" class="block">
              <ModuleCard
                title="TPA"
                description="Deskripsi singkat TPA (edit nanti)."
                :image="img1"
                gradientClass="from-sky-400 via-indigo-600 to-indigo-900"
                :cardWidth="'19rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'8rem'"
                :to="'/admin/modul/tugas-belajar/tpa'"
              />
            </router-link>

            <router-link to="/admin/modul/tugas-belajar/tbi" class="block">
              <ModuleCard
                title="TBI"
                description="Deskripsi singkat TBI (edit nanti)."
                :image="img2"
                gradientClass="from-sky-400 via-indigo-600 to-indigo-900"
                :cardWidth="'19rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'8rem'"
                :to="'/admin/modul/tugas-belajar/tbi'"
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
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import ModuleCard from '@/views/Adm/Components/ModuleCard.vue'
import { isSidebarOpen } from '@/stores/sidebar'

import img1 from '@/assets/Picture1.png'
import img2 from '@/assets/Picture2.png'

const isOpen = isSidebarOpen

// Note: descriptions are placeholders — you said you'll decide the exact text later
</script>

<style scoped>
.grid {
  width: max-content;
}
</style>