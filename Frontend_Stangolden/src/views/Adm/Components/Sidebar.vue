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
        <img :src="stangolden" class="h-12 w-12 rounded-full object-cover" alt="avatar" />

        <!-- Header user info: loading -> spinner; loaded -> username -->
        <div>
          <!-- Loading state: spinner and placeholder -->
          <template v-if="loading">
            <div class="flex items-center gap-2">
              <svg class="h-4 w-4 animate-spin text-white/90" viewBox="0 0 24 24" aria-label="Loading user">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3A5 5 0 007 12H4z"></path>
              </svg>
              <span class="text-sm text-white/90">Memuat...</span>
            </div>
            <p class="text-xs text-sky-100/80 mt-0.5">Mohon tunggu</p>
          </template>

          <!-- Loaded state: show username/name/email -->
          <template v-else>
            <h2 class="text-lg font-bold" :title="userDisplayName">
              {{ userDisplayName }}
            </h2>
            <p class="text-xs text-sky-100/80">
              {{ isAdminUser ? 'Administrator' : 'Participant' }}
            </p>
          </template>
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

    <!-- Nav: loading skeleton -->
    <nav v-if="loading" class="mt-6 space-y-2 font-medium text-lg px-4" aria-busy="true" aria-label="Memuat menu">
      <div class="h-9 rounded bg-white/10 animate-pulse" />
      <div class="h-9 rounded bg-white/10 animate-pulse" />
      <div class="h-9 rounded bg-white/10 animate-pulse" />
      <div class="h-9 rounded bg-white/10 animate-pulse" />
    </nav>

    <!-- Nav: loaded -->
    <template v-else>
      <nav v-if="isAdminUser" class="mt-6 space-y-2 font-medium text-lg px-4">
        <router-link to="/admin/dashboard" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
          Dashboard
        </router-link>

        <router-link to="/peserta" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
          Peserta
        </router-link>

        <router-link to="/moduladm" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
          Modul
        </router-link>

        <router-link to="/tryoutadm" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
          TryOut
        </router-link>

        <router-link to="/feedbackadm" class="block px-3 py-2 rounded hover:bg-white/10 transition" active-class="bg-white/20">
          Feedback
        </router-link>
      </nav>

      <nav v-else class="mt-6 space-y-2 font-medium text-lg px-4">
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
    </template>

    <div class="absolute bottom-6 left-4 right-4">
      <button
        type="button"
        @click="logout"
        class="w-full bg-white/10 hover:bg-white/20 text-white text-sm font-medium px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-yellow-300 transition"
        aria-label="Logout"
        :disabled="loading"
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
import { computed, ref, onMounted } from 'vue'
import backendService from '@/services/backendServices'

const router = useRouter()
async function logout() {
  try {
    if (backendService?.auth?.logout) {
      await backendService.auth.logout()
    }
  } catch (e) {
    // silent
  } finally {
    router.push('/login')
  }
}

// reactive refs
const isOpen = isSidebarOpen

// loading and user
const loading = ref(true)
const me = ref(null)
const error = ref(null)

const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})

// Username display: prefer username -> name -> email -> fallback
const userDisplayName = computed(() => {
  return (
    me.value?.username ||
    me.value?.name ||
    me.value?.email ||
    'Pengguna'
  )
})

// fetch current user without defaulting to siswa during loading
onMounted(async () => {
  try {
    error.value = null
    loading.value = true
    me.value = await backendService.auth.user()
  } catch (e) {
    error.value = e?.message || 'Gagal memuat user'
    // tetap biarkan me=null agar tidak tampil "Siswa" default saat loading/error
  } finally {
    loading.value = false
  }
})

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