<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar />
    <div :class="['flex-1 min-h-screen flex flex-col transition-all duration-300', isOpen ? 'md:ml-64' : 'md:ml-0']">
      <Navbar :title="moduleTitle" description="Pratinjau modul (PDF & Video)" />
      <main class="p-6 max-w-5xl w-full mx-auto space-y-6">
        <!-- Preview YouTube -->
        <section class="bg-white rounded-xl shadow p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-700">Preview Video YouTube</h3>
            <a
              v-if="mod?.youtube_url"
              :href="mod.youtube_url"
              target="_blank"
              rel="noopener"
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded text-sm bg-red-600 text-white hover:bg-red-700"
              title="Buka video di YouTube (tab baru)"
            >
              Buka di YouTube
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-90" viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 3h8a1 1 0 011 1v8h-2V6.41l-9.3 9.3-1.41-1.42L15.58 5H10V3z"/><path d="M5 7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5h-2v5H5V9h5V7H5z"/>
              </svg>
            </a>
          </div>

          <div v-if="embedUrl" class="w-full">
            <div class="aspect-video w-full rounded overflow-hidden border bg-black/5">
              <iframe
                :src="embedUrl"
                title="YouTube video preview"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
                class="w-full h-full"
              ></iframe>
            </div>
          </div>
          <div v-else class="text-sm text-slate-500">Link YouTube belum tersedia.</div>
        </section>

        <!-- Preview PDF -->
        <section class="bg-white rounded-xl shadow p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-700">Preview PDF</h3>
            <a
              v-if="mod?.pdf_url"
              :href="mod.pdf_url"
              target="_blank"
              rel="noopener"
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded text-sm bg-sky-600 text-white hover:bg-sky-700"
              title="Buka PDF di tab baru"
            >
              Buka PDF
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-90" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9l-6-6zm0 2l4 4h-4V5z"/>
              </svg>
            </a>
          </div>

          <div v-if="mod?.pdf_url" class="w-full">
            <iframe
              :src="mod.pdf_url"
              class="w-full h-[70vh] border rounded"
              title="PDF preview"
            ></iframe>
            <div class="mt-2 text-xs text-slate-600">
              {{ mod.pdf_name || 'PDF' }}
            </div>
          </div>
          <div v-else class="text-sm text-slate-500">PDF belum tersedia.</div>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from '@/views/Adm/Components/Sidebar.vue'
import Navbar from '@/views/Adm/Components/Navbar.vue'
import { isSidebarOpen } from '@/stores/sidebar'
import backendService from '@/services/backendServices'

const isOpen = isSidebarOpen
const route = useRoute()

const mod = ref(null)
const moduleTitle = computed(() => mod.value?.name || 'Modul')
const embedUrl = computed(() => {
  const raw = String(mod.value?.youtube_url || '').trim()
  if (!raw) return ''
  const short = raw.match(/youtu\.be\/([A-Za-z0-9_-]{6,})/)
  const long = raw.match(/[?&]v=([A-Za-z0-9_-]{6,})/)
  const shorts = raw.match(/youtube\.com\/shorts\/([A-Za-z0-9_-]{6,})/)
  const id = (short && short[1]) || (long && long[1]) || (shorts && shorts[1]) || ''
  return id ? `https://www.youtube.com/embed/${id}` : ''
})

async function load() {
  const id = route.params.id
  if (!id) return
  try {
    const data = await backendService.adminModules.get(id)
    mod.value = data?.data || data
  } catch (e) {
    console.error('Gagal memuat modul', e)
    mod.value = null
  }
}

onMounted(load)
watch(() => route.params.id, load)
</script>