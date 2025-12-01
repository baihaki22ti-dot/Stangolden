<template>
  <div class="bg-white shadow rounded-xl p-4">
    <div class="rounded-xl shadow p-4 flex flex-col justify-center items-center bg-gradient-to-br from-white to-slate-50">
    <h3 class="text-lg font-bold text-center mb-2">{{ title }}</h3>
    <div class="text-3xl font-extrabold text-center text-slate-800">{{ value }}</div>
  </div>
    <table class="w-full text-left text-sm">
      <!-- <tbody>
        <tr v-for="(item,i) in items" :key="i" class="border-b hover:bg-slate-50 transition">
          <td class="py-3 px-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">
              {{ (item.name || '').split(' ').map(n=>n[0]).slice(0,2).join('') }}
            </div>
            <div>
              <div class="font-medium text-slate-700">{{ item.name }}</div>
            </div>
          </td>
        </tr>
      </tbody> -->
    </table>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [Number, String]
})
</script>