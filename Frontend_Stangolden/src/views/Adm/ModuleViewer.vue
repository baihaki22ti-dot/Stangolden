<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />

    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="module?.name || 'Modul'" :description="module?.description || ''" />

      <main class="p-6 max-w-5xl w-full mx-auto space-y-6">
        <div v-if="loading" class="bg-white p-6 rounded shadow text-center text-slate-500">Memuat modul...</div>

        <div v-else-if="!module" class="bg-white p-6 rounded shadow text-center text-slate-500">
          <div class="mb-4 font-semibold">Modul tidak ditemukan.</div>
          <div class="text-sm text-slate-600 mb-3">Cek hal berikut:</div>
          <ul class="text-sm text-slate-500 list-disc list-inside text-left max-w-xl mx-auto mb-4">
            <li>Periksa URL dan pastikan parameter (id / slug) benar.</li>
            <li>Periksa localStorage key "app_modul_list_v1" berisi modul yang sesuai.</li>
            <li>Pastikan router memiliki route yang cocok dengan URL (contoh: <code>/admin/modul/content/:id</code> atau <code>/admin/modul/:slug</code>).</li>
          </ul>

          <details class="text-left max-w-xl mx-auto bg-slate-50 p-3 rounded">
            <summary class="cursor-pointer text-sm font-medium">Info debug (klik untuk buka)</summary>
            <div class="text-xs text-slate-600 mt-2">
              <div><strong>Route params:</strong> {{ debug.params }}</div>
              <div class="mt-2"><strong>Available module ids/names in storage:</strong></div>
              <ul class="mt-1">
                <li v-for="m in debug.available" :key="m.id" class="text-xs text-slate-700">
                  {{ m.id }} — {{ m.name || m.title || '(no title)' }}
                </li>
                <li v-if="debug.available.length===0" class="text-xs text-red-600">(no modules in localStorage key "app_modul_list_v1")</li>
              </ul>
            </div>
          </details>

          <div class="mt-4">
            <router-link to="/admin/modul" class="inline-block px-4 py-2 bg-sky-600 text-white rounded">Kembali ke daftar modul</router-link>
          </div>
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
import { useRoute } from 'vue-router'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'

const route = useRoute()
const isOpen = isSidebarOpen

const STORAGE_KEY = 'app_modul_list_v1'
const loading = ref(true)
const module = ref(null)
const debug = ref({ params: {}, available: [] })

function loadModule() {
  loading.value = true
  // read stored modules (try multiple keys if you used different keys)
  let list = []
  try {
    list = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  } catch (e) {
    list = []
  }

  // expose available modules for debug
  debug.value.available = Array.isArray(list) ? list : []

  // try to find module by various params
  const params = { ...route.params }
  debug.value.params = params

  // candidates to match: id, slug, contentId etc.
  const candidates = []
  if (params.id) candidates.push(String(params.id))
  if (params.slug) candidates.push(String(params.slug))
  if (params.contentId) candidates.push(String(params.contentId))
  // also try last path segment if route used numeric id in different param name
  if (!candidates.length) {
    const pathSegments = route.path.split('/').filter(Boolean)
    if (pathSegments.length) candidates.push(pathSegments[pathSegments.length - 1])
  }

  // try numeric id match first
  let found = null
  for (const c of candidates) {
    // exact id match (number or string)
    found = list.find(m => String(m.id) === String(c))
    if (found) break
    // title/name match (slug-ish)
    found = list.find(m => String(m.slug) === String(c) || String(m.name) === String(c) || String(m.title) === String(c))
    if (found) break
  }

  module.value = found || null
  loading.value = false
}

onMounted(() => {
  loadModule()
})
// also reload when route changes
watch(() => route.fullPath, () => loadModule())
</script>

<style scoped>
iframe { width: 100%; height: 100%; }
</style>