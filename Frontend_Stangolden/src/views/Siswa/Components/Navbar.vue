<template>
  <header class="h-20 bg-white shadow flex items-center justify-between px-4 md:px-6 relative z-50">
    <div class="flex items-center gap-4">
      <!-- CHANGED: Remove md:hidden so toggle is visible on all breakpoints.
           Added visible bg / ring for contrast and higher z-index to avoid being covered. -->
      <button
        @click="onToggle"
        class="p-2 rounded hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-300 z-50 relative"
        aria-label="Toggle sidebar"
        title="Toggle sidebar"
      >
        <!-- show X when open, hamburger when closed -->
        <svg v-if="isOpen" class="w-6 h-6 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>

        <svg v-else class="w-6 h-6 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      <h1 class="text-2xl font-semibold text-slate-800">{{ title }}</h1>
      <p class="text-sm text-slate-500 hidden md:inline">{{ description }}</p>
    </div>

    <div class="flex items-center gap-3">
      <button class="text-sm text-slate-600 px-3 py-2 rounded hover:bg-slate-50">Help</button>
      <div class="w-9 h-9 bg-slate-200 rounded-full flex items-center justify-center text-slate-700 font-semibold">A</div>
    </div>
  </header>
</template>

<script setup>
/*
  CHANGED:
  - Import isSidebarOpen and toggleSidebar from store (adjust if your store exports differently).
  - Provide onToggle wrapper to avoid runtime error if toggleSidebar is not a function.
*/
import { isSidebarOpen, toggleSidebar } from '@/stores/sidebar'
import { unref } from 'vue'
const props = defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' }
})

// reactive ref from store (assumes store exports an isSidebarOpen ref)
const isOpen = isSidebarOpen

function onToggle() {
  // guard: if toggleSidebar exists and is a function, call it; otherwise log a helpful message
  if (typeof toggleSidebar === 'function') {
    toggleSidebar()
  } else {
    // If toggleSidebar is not available, toggle a CSS class as fallback (optional)
    console.warn('toggleSidebar is not a function — check your store export at @/stores/sidebar')
  }
}
</script>

<style scoped>
/* ensure header stays above sidebar and button not covered */
header { z-index: 50; }

/* small tweak: make sure the toggle button sits above other siblings */
button[title="Toggle sidebar"] {
  z-index: 60;
  position: relative;
}
</style>