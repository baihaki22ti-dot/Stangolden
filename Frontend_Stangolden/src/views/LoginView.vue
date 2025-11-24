<script setup>
import { ref } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import stangolden from "@/assets/stangolden-bg.png";

const router = useRouter();

const isRegister = ref(false);

const email = ref("");
const password = ref("");

async function handleLogin() {
  try {
    const res = await axios.post("http://localhost:8000/api/login", {
      email: email.value,
      password: password.value,
    });

    const user = res.data.user;

    router.push(user.role === "admin" ? "/admin/dashboard" : "/dashboard");

  } catch (err) {
    alert("Email atau password salah!");
  }
}

const reg = ref({
  nama: "",
  email: "",
  sandi: "",
  konfirmasi: "",
  hp: "",
  kota: "",
});

async function handleRegister() {
  if (reg.value.sandi !== reg.value.konfirmasi) {
    alert("Sandi dan konfirmasi tidak cocok!");
    return;
  }

  try {
    await axios.post("http://localhost:8000/api/register", reg.value);
    alert("Registrasi berhasil!");
    isRegister.value = false;
  } catch (err) {
    alert("Gagal registrasi!");
  }
}
</script>

<template>
  <div
    class="w-full min-h-screen flex items-center justify-center 
    bg-[linear-gradient(180deg,rgba(111,181,202,1)_0%,rgba(55,59,100,1)_100%)] p-6"
  >

    <!-- WRAPPER -->
    <div
      class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden"
    >

      <!-- FLEX CONTAINER DUA PANEL -->
      <div class="grid md:grid-cols-2 relative">

        <!-- =============== FORM PANEL =============== -->
        <div
          class="p-10 transition-all duration-500 ease-in-out"
          :class="[
            isRegister
              ? 'translate-x-full opacity-0 pointer-events-none'
              : 'translate-x-0 opacity-100'
          ]"
        >
          <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-8 text-center">
            Login
          </h1>

          <form @submit.prevent="handleLogin" class="space-y-5 max-w-sm mx-auto">

            <div class="flex flex-col">
              <label class="font-medium mb-1">Email</label>
              <input
                v-model="email"
                type="email"
                placeholder="Masukkan email"
                class="border-b border-gray-600 bg-transparent py-2 outline-none"
              />
            </div>

            <div class="flex flex-col">
              <label class="font-medium mb-1">Password</label>
              <input
                v-model="password"
                type="password"
                placeholder="Masukkan password"
                class="border-b border-gray-600 bg-transparent py-2 outline-none"
              />
            </div>

            <button
              type="submit"
              class="w-full py-3 rounded-xl text-white font-semibold text-lg
              bg-gradient-to-b from-[#357796] via-[#57957A] to-[#6Fbeb6]
              hover:brightness-110 transition"
            >
              Masuk
            </button>

            <p class="text-center text-gray-700 text-sm">
              Belum punya akun?
              <span
                class="text-blue-700 font-semibold cursor-pointer"
                @click="isRegister = true"
              >Daftar</span>
            </p>

          </form>
        </div>

        <!-- =============== REGISTER PANEL =============== -->
        <div
          class="p-10 transition-all duration-500 ease-in-out bg-gray-50"
          :class="[
            isRegister
              ? 'translate-x-0 opacity-100'
              : '-translate-x-full opacity-0 pointer-events-none'
          ]"
        >
          <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-8 text-center">
            Daftar Akun
          </h1>

          <form @submit.prevent="handleRegister" class="space-y-4 max-w-sm mx-auto">

            <input v-model="reg.nama" placeholder="Nama lengkap"
              class="border-b border-gray-600 bg-transparent w-full py-2" />

            <input v-model="reg.email" placeholder="Email"
              class="border-b border-gray-600 bg-transparent w-full py-2" />

            <input v-model="reg.sandi" type="password" placeholder="Sandi"
              class="border-b border-gray-600 bg-transparent w-full py-2" />

            <input v-model="reg.konfirmasi" type="password" placeholder="Konfirmasi sandi"
              class="border-b border-gray-600 bg-transparent w-full py-2" />

            <input v-model="reg.hp" placeholder="Nomor HP"
              class="border-b border-gray-600 bg-transparent w-full py-2" />

            <input v-model="reg.kota" placeholder="Asal Kota"
              class="border-b border-gray-600 bg-transparent w-full py-2" />

            <button
              type="submit"
              class="w-full py-3 rounded-xl text-white font-semibold text-lg
              bg-gradient-to-b from-[#357796] via-[#57957A] to-[#6FBEB6]
              hover:brightness-110 transition"
            >
              Registrasi
            </button>

            <p class="text-center text-gray-700 text-sm">
              Sudah punya akun?
              <span
                class="text-blue-700 font-semibold cursor-pointer"
                @click="isRegister = false"
              >Login</span>
            </p>

          </form>
        </div>

      </div>

<!-- =============== INFO CARD ALWAYS CONSISTENT =============== -->
<transition name="fade-slide" mode="out-in">
  <div
    :key="isRegister"
    class="absolute top-0 bottom-0 w-1/2 hidden md:flex flex-col items-center text-white p-10 transition-all duration-500 text-left"
    :style="{
      left: isRegister ? '0' : '50%',
      right: isRegister ? '50%' : '0'
    }"
    :class="[
      isRegister
        ? 'bg-gradient-to-b from-[#357796] via-[#57957A] to-[#6FBEB6]'
        : 'bg-gradient-to-b from-[#57957A] via-[#6FBEB6] to-[#357796]'
    ]"
  >
    <!-- LOGO -->
    <img :src="stangolden" class="w-44 mx-auto mb-6" />

    <!-- TITLE -->
    <h2 class="text-3xl font-bold mb-4">
      {{ isRegister ? "Selamat Datang di Stangolden! 👋" : "Halo, Selamat Datang di Stangolden! 👋" }}
    </h2>

    <!-- DESCRIPTION -->
    <p v-if="!isRegister" class="text-lg mb-2">
      Kami hadir untuk mendukung langkahmu menuju masa depan yang lebih cerah.
    </p>

    <p v-if="!isRegister" class="text-lg mb-4">
      Di sini, kamu bisa belajar secara fleksibel, mengakses modul 
      pembelajaran, mengerjakan ujian, dan memantau perkembanganmu secara langsung.
    </p>

    <p v-if="!isRegister" class="text-lg italic mb-4">
      “Belajar bukan tentang seberapa cepat kamu sampai, 
      tapi tentang seberapa konsisten kamu melangkah.”
    </p>

    <p v-if="isRegister" class="text-lg mb-4">
      Terima kasih telah memilih Stangolden sebagai teman belajarmu.
    </p>

    <p v-if="isRegister" class="text-lg mb-4">
      Daftarkan dirimu sekarang untuk mengakses berbagai materi 
      pembelajaran, mengikuti ujian berbasis sistem, dan terus berkembang bersama kami.
    </p>

    <p v-if="isRegister" class="text-lg italic mb-4">
      "Langkah kecil hari ini adalah awal dari masa depan yang besar."
    </p>

  </div>
</transition>



    </div>
  </div>
</template>

<style>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.5s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(0px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}
</style>
