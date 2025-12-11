<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar title="Tryout" description="Asah kemampuanmu dengan mengerjakan soal" />

      <main class="p-6 max-w-7xl w-full mx-auto space-y-5 flex-1">
        <div class="flex justify-center items-center">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-6 justify-center items-stretch">
            <!-- Render semua kartu; locked akan ditangani click handler -->
            <div
              v-for="(m, i) in modules"
              :key="m.id ?? i"
              class="relative"
              @click="onCardClick(m)"
            >
              <!-- Card konten -->
              <TryoutCard
                :title="m.title"
                :description="m.description"
                :image="m.image"
                :gradientClass="m.gradientClass"
                :cardWidth="'25rem'"
                :cardHeight="'16rem'"
                :imageAreaHeight="'25rem'"
                :class="isLocked(m) ? 'pointer-events-none opacity-60 grayscale' : 'cursor-pointer'"
              />

              <!-- Overlay untuk kartu terkunci -->
              <div
                v-if="isLocked(m)"
                class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-xl"
              >
                <div class="flex flex-col items-center gap-2 text-slate-100">
                  <!-- Ikon rantai + gembok -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M16 8V6a4 4 0 10-8 0v2M7 8h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6a2 2 0 012-2z" />
                  </svg>
                  <div class="flex items-center gap-2 text-sm">
                    <span class="inline-block h-4 w-4 bg-slate-200 rounded-sm"></span>
                    <span class="inline-block h-4 w-4 bg-slate-300 rounded-sm"></span>
                    <span class="inline-block h-4 w-4 bg-slate-400 rounded-sm"></span>
                  </div>
                  <p class="text-xs">Terkunci</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pop-up notifikasi jika klik kartu terkunci -->
        <transition name="fade">
          <div
            v-if="lockPopupOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            @click.self="lockPopupOpen=false"
          >
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5">
              <div class="flex items-center gap-3 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M16 8V6a4 4 0 10-8 0v2M7 8h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6a2 2 0 012-2z" />
                </svg>
                <h3 class="text-lg font-semibold text-slate-800">Akses Terkunci</h3>
              </div>
              <p class="text-sm text-slate-600">
                Silahkan hubungi admin untuk mengakses fitur tryout ini.
              </p>
              <div class="mt-4 flex justify-end gap-2">
                <button class="px-3 py-1.5 text-sm rounded bg-slate-100" @click="lockPopupOpen=false">Tutup</button>
              </div>
            </div>
          </div>
        </transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import TryoutCard from '@/views/Adm/Components/TryoutCard.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import backendService from '@/services/backendServices'
import img1 from '@/assets/Picture1.png'
import img2 from '@/assets/Picture2.png'

const isOpen = isSidebarOpen
const router = useRouter()

// Ambil user
const me = ref(null)
onMounted(async () => {
  try {
    me.value = await backendService.auth.user()
  } catch (e) {
    console.warn('Gagal memuat user, default siswa:', e)
    me.value = { role: 'siswa', is_admin: false, upkp: 0, tugas_belajar: 0 }
  }
})

const isAdminUser = computed(() => {
  const role = String(me.value?.role || '').toLowerCase()
  const flag = !!me.value?.is_admin
  return role === 'admin' || flag
})

// Daftar modul TryOut (domain)
const modules = ref([
  {
    id: 1,
    key: 'upkp',
    title: 'Tryout UPKP',
    description: 'Asah kemampuan UPKP.',
    image: img1,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'upkp'
  },
  {
    id: 2,
    key: 'tugas-belajar',
    title: 'Tryout Tugas Belajar',
    description: 'Tryout untuk Tugas Belajar.',
    image: img2,
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    slug: 'tubel' // gunakan slug tubel untuk domain
  }
])

// Status akses: 1 berarti terbuka
const upkpFlag = computed(() => Number(me.value?.upkp ?? me.value?.status_upkp ?? 0) === 1)
const tbFlag = computed(() => Number(me.value?.tugas_belajar ?? me.value?.status_tugas_belajar ?? 0) === 1)

// Menentukan apakah modul terkunci
function isLocked(m) {
  if (isAdminUser.value) return false // admin bebas
  if (m.key === 'upkp') return !upkpFlag.value
  if (m.key === 'tugas-belajar') return !tbFlag.value
  return true
}

// Popup terkunci
const lockPopupOpen = ref(false)

// Helper route exist
function hasRoute(name) {
  return !!router.getRoutes().find(r => r.name === name)
}

// Bangun tujuan navigasi untuk domain TryOut yang terbuka
function buildTo(m) {
  const domain = m.slug // 'upkp' atau 'tubel'
  // Gunakan route TryoutAdminDomain yang sudah ada
  if (hasRoute('TryoutAdminDomain')) {
    return { name: 'TryoutAdminDomain', params: { domain } }
  }
  // Fallback: langsung ke path
  return { path: `/tryoutadm/${domain}` }
}

// Klik kartu: jika terkunci → popup; jika tidak → navigate
function onCardClick(m) {
  if (isLocked(m)) {
    lockPopupOpen.value = true
    return
  }
  const to = buildTo(m)
  router.push(to)
}
</script>

<style scoped>
.grid { width: max-content; }
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>