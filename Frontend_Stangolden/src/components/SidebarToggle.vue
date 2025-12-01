<template>
  <button
    @click="toggleSidebar"
    class="fixed top-4 left-4 p-2 rounded bg-white shadow md:hidden z-[60]"
    aria-label="Toggle sidebar"
  >
    <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>
</template>

<script setup>
import { toggleSidebar } from '@/stores/sidebar'
</script>