<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar
        title="Tugas Belajar"
        description="Pilih submodul Tugas Belajar"
      />
      <main class="p-6 max-w-7xl w-full mx-auto space-y-6 flex-1">
        <div class="flex justify-center">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 w-max">
            <router-link v-if="isAdminUser"
              v-for="child in category.children"
              :key="`admin-${child.slug}`"
              :to="`/moduladm/tugas-belajar/${child.slug}`"
              class="block focus:outline-none focus:ring-2 focus:ring-indigo-600 rounded-lg"
              :aria-label="`Buka submodul ${child.title}`"
            >
              <ModuleCard
                :title="child.title"
                :description="child.description"
                :gradientClass="category.gradientClass"
                :image="image"
                :cardWidth="'19rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'8rem'"
              />
            </router-link>
            <router-link v-else
              v-for="child in category.children"
              :key="`user-${child.slug}`"
              :to="`/modul/tugas-belajar/${child.slug}`"
              class="block focus:outline-none focus:ring-2 focus:ring-indigo-600 rounded-lg"
              :aria-label="`Buka submodul ${child.title}`"
            >
              <ModuleCard
                :title="child.title"
                :description="child.description"
                :gradientClass="category.gradientClass"
                :image="image"
                :cardWidth="'19rem'"
                :cardHeight="'15rem'"
                :imageAreaHeight="'8rem'"
              />
            </router-link>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted,computed} from 'vue'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import ModuleCard from '@/views/Adm/Components/ModuleCard.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import { findCategory } from '@/config/modules.config'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const category = findCategory('tugas-belajar')
const image = ref('')

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

onMounted(async () => {
  try {
    const mod = await category.image()
    image.value = mod.default
  } catch {
    image.value = ''
  }
})
</script>

<style scoped>
</style>