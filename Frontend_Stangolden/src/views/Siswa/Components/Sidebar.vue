<template>
  <!-- overlay for small screens -->
  <transition name="fade">
    <div
      v-if="isOpen && isMobile"
      class="fixed inset-0 bg-black/40 z-30"
      @click="closeSidebar"
      aria-hidden="true"
    ></div>
  </transition>

  <!-- sidebar -->
  <aside
    :class="[
      'fixed top-0 left-0 h-screen w-64 bg-gradient-to-b from-sky-400 to-indigo-900 text-white shadow-lg z-40 transform transition-transform duration-300',
      isOpen ? 'translate-x-0' : '-translate-x-full',
    ]"
    role="navigation"
    aria-label="Sidebar"
  >
    <div class="flex items-center justify-between px-4 pt-5">
      <div class="flex items-center gap-3">
        <img :src="stangolden" class="h-12 w-12 rounded-full object-cover" />
        <div>
          <h2 class="text-lg font-bold">Siswa</h2>
          <p class="text-xs text-sky-100/80">UPKP</p>
        </div>
      </div>

      <!-- close button (visible on small screens) -->
      <button
        @click="closeSidebar"
        class="md:hidden text-white/80 hover:text-white p-2 rounded"
        aria-label="Close sidebar"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <nav class="mt-6 space-y-2 font-medium text-lg px-4">
      <router-link to="/dashboard" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
        Dashboard
      </router-link>
      <router-link to="/modul" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
        Modul
      </router-link>

      <router-link to="/tryout" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
        TryOut
      </router-link>

      <router-link to="/feedback" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
        Feedback
      </router-link>
      
    </nav>

    <div class="absolute bottom-6 left-4 right-4">
      <button
        type="button"
        @click="logout"
        class="w-full bg-white/10 hover:bg-white/20 text-white text-sm font-medium px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-yellow-300 transition"
        aria-label="Logout"
      >
        Logout
      </button>
    </div>
  </aside>
</template>

<script setup>
import stangolden from '@/assets/stangolden-bg.png'
import { useRouter } from 'vue-router'
import { isSidebarOpen, closeSidebar } from '@/stores/sidebar'
import { computed } from 'vue'

const router = useRouter()
function logout() {
  router.push('/login')
}

// reactive ref from store
const isOpen = isSidebarOpen

// helper to detect mobile (used to conditionally render overlay)
const isMobile = computed(() => {
  if (typeof window === 'undefined') return false
  return window.innerWidth < 768
})
</script>

<style scoped>
/* optional fade transition for overlay */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>