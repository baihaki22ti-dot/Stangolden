<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Modul" description="Pilih kategori modul" />
      <main class="p-6 max-w-7xl w-full mx-auto space-y-6 flex-1">
        <div class="flex justify-center">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 w-max">
            <router-link v-if="isAdminUser"
              v-for="cat in categories"
              :key="`admin-${cat.id}`"
              :to="`/moduladm/${cat.id}`"
              class="block focus:outline-none focus:ring-2 focus:ring-indigo-600 rounded-lg"
              :aria-label="`Buka kategori ${cat.title}`"
            >
              <ModuleCard
                :title="cat.title"
                :description="cat.description"
                :gradientClass="cat.gradientClass"
                :image="catImages[cat.id]"
                :cardWidth="'23rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'10rem'"
              />
            </router-link>

            <router-link v-if="isAdminUser"
              v-for="cat in categories"
              :key="`admin-${cat.id}`"
              :to="`/moduladm/${cat.id}`"
              class="block focus:outline-none focus:ring-2 focus:ring-indigo-600 rounded-lg"
              :aria-label="`Buka kategori ${cat.title}`"
            >
              <ModuleCard
                :title="cat.title"
                :description="cat.description"
                :gradientClass="cat.gradientClass"
                :image="catImages[cat.id]"
                :cardWidth="'23rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'10rem'"
              />
            </router-link>
            <router-link v-else
              v-for="cat in categories"
              :key="`user-${cat.id}`"
              :to="`/modul/${cat.id}`"
              class="block focus:outline-none focus:ring-2 focus:ring-indigo-600 rounded-lg"
              :aria-label="`Buka kategori ${cat.title}`"
            >
              <ModuleCard
                :title="cat.title"
                :description="cat.description"
                :gradientClass="cat.gradientClass"
                :image="catImages[cat.id]"
                :cardWidth="'23rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'10rem'"
              />
            </router-link>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import ModuleCard from '@/views/Adm/Components/ModuleCard.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { moduleCategories } from '@/config/modules.config'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const categories = ref(moduleCategories)
const me = ref(null) 
const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})
onMounted(async () => {
  try {
    me.value = await backendService.auth.user()
  } catch (e) {
    // tanpa fallback: jika gagal memuat user, asumsikan siswa
    console.warn('Gagal memuat user, default siswa:', e)
    me.value = { role: 'siswa', is_admin: false }
  }
})
// Lazy load gambar (image() adalah dynamic import di config)
const catImages = ref({})
onMounted(async () => {
  for (const c of categories.value) {
    try {
      const mod = await c.image()
      catImages.value[c.id] = mod.default
    } catch {
      catImages.value[c.id] = ''
    }
  }
})
</script>

<style scoped>
/* Grid shrink-wrap agar mudah di-center */
</style>