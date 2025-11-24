<template>
  <div class="min-h-screen bg-gradient-to-br from-green-200 to-green-400 text-white flex flex-col items-center justify-center">
    <h1 class="text-4xl font-bold mb-4">Dashboard Siswa</h1>
    <p class="text-lg">Selamat datang di platform belajar <span class="font-semibold text-yellow-300">Stangolden</span> 📚</p>
    <button @click="logout" class="mt-6 bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100">
      Logout
    </button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()
function logout() {
  router.push('/LoginView')
}
</script>
