<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="module?.name || 'Modul'" :description="module?.description || ''" />

      <main class="p-6 max-w-5xl w-full mx-auto space-y-6">
        <div v-if="!module" class="bg-white p-6 rounded shadow text-center text-slate-500">
          Modul tidak ditemukan.
        </div>

        <div v-else class="space-y-4">
          <article class="bg-white rounded-xl shadow p-4">
            <h2 class="text-lg font-semibold mb-2">{{ module.name }}</h2>
            <p class="text-sm text-slate-700">{{ module.description }}</p>
          </article>

          <article class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-3 border-b">
              <div class="flex items-center justify-between">
                <div class="text-sm text-slate-600">PDF Viewer</div>
                <div v-if="module.pdfName" class="text-sm text-slate-500">{{ module.pdfName }}</div>
              </div>
            </div>

            <div class="w-full h-[80vh] bg-black/5 flex items-center justify-center">
              <template v-if="module.pdfData">
                <!-- If pdfData is a data URL or a URL, we can use iframe/object.
                     Use sandbox or allow-same-origin as needed depending on source. -->
                <iframe
                  :src="pdfSrc"
                  class="w-full h-full"
                  frameborder="0"
                ></iframe>
              </template>
              <div v-else class="text-sm text-slate-500 p-6">Tidak ada PDF yang diunggah untuk modul ini.</div>
            </div>
          </article>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'

const route = useRoute()
const router = useRouter()
const isOpen = isSidebarOpen

const STORAGE_KEY = 'app_modul_list_v1'
const id = route.params.id || route.params.slug // accept either id param or slug if used

const module = ref(null)

function loadModule() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    const list = raw ? JSON.parse(raw) : []
    // find by numeric id (if id numeric) or by slug (string)
    const found = list.find(m => String(m.id) === String(id))
    if (found) module.value = found
    else module.value = null
  } catch (e) {
    module.value = null
  }
}

onMounted(() => {
  loadModule()
})

// compute pdf src safely (data URL or absolute url)
const pdfSrc = computed(() => {
  if (!module.value || !module.value.pdfData) return ''
  // If stored as data URL already, use it; else if it's a plain URL, try to use it directly
  return module.value.pdfData
})
</script>

<style scoped>
/* keep iframe full height */
iframe { width: 100%; height: 100%; }
</style>