// simple reactive sidebar store (Vue 3)
// Usage:
// import { isSidebarOpen, toggleSidebar, closeSidebar, openSidebar } from '@/stores/sidebar'
import { ref } from 'vue'

const defaultOpen = typeof window !== 'undefined' ? window.innerWidth >= 768 : true
export const isSidebarOpen = ref(!!defaultOpen)

export function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value
}
export function closeSidebar() {
  isSidebarOpen.value = false
}
export function openSidebar() {
  isSidebarOpen.value = true
}