<script setup>
import axios from 'axios'
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Arahkan ke LoginView
const goToLogin = () => {
  router.push('/login')
}

onMounted(async () => {
  try {
    await axios.get('http://localhost:8000/sanctum/csrf-cookie', {
      withCredentials: true
    })
    console.log('✅ Koneksi ke backend berhasil!')
  } catch (error) {
    console.error('❌ Gagal konek ke backend:', error)
  }
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-100 via-white to-blue-50 flex flex-col font-sans">
    <!-- Navbar -->
    <nav class="flex justify-between items-center px-8 py-4 bg-white shadow-md sticky top-0 z-10">
      <h1 class="text-2xl font-extrabold text-blue-700">Stangolden</h1>
      <div class="space-x-6 text-gray-700 font-medium">
        <a href="#" class="hover:text-blue-600">Beranda</a>
        <a href="#" class="hover:text-blue-600">Tentang</a>
        <a href="#" class="hover:text-blue-600">Kontak</a>

        <!-- Tombol Masuk -->
        <button 
          @click="goToLogin"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          Masuk
        </button>
      </div>
    </nav>
    
    <!-- Hero -->
    <section class="flex flex-col md:flex-row items-center justify-between flex-1 px-8 md:px-16 py-16">
      <div class="md:w-1/2 space-y-6">
        <h2 class="text-5xl font-extrabold text-gray-800 leading-tight">
          Belajar Lebih Mudah Bersama <span class="text-blue-600">Stangolden</span>
        </h2>
        <p class="text-lg text-gray-600">
          Platform e-learning interaktif untuk membantu kamu mempersiapkan diri menghadapi ujian STAN dan UPKP dengan lebih percaya diri.
        </p>
        <div class="space-x-4">
          <button class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 shadow-md">
            Mulai Belajar
          </button>
          <button class="border border-blue-600 text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50">
            Pelajari Lebih Lanjut
          </button>
        </div>
      </div>

      <div class="md:w-1/2 mt-10 md:mt-0 flex justify-center">
        <img src="https://cdn-icons-png.flaticon.com/512/906/906175.png" alt="Belajar" class="w-80 h-80 animate-bounce" />
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-4 text-center text-gray-500 border-t">
      © 2025 Stangolden — Semua hak cipta dilindungi.
    </footer>
  </div>
</template>

<style>
body {
  font-family: 'Inter', sans-serif;
}
</style>
