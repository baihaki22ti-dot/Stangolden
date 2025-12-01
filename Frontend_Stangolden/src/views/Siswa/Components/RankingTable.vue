<template>
  <div class="bg-white shadow rounded-xl p-4">
    <h3 class="text-lg font-bold text-center mb-3">{{ title }}</h3>
    <table class="w-full text-left text-sm">
      <thead>
        <tr class="border-b">
          <th>Nama</th>
          <th>Skor</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item,i) in items" :key="i" class="border-b">
          <td class="py-1">{{ item.name }}</td>
          <td class="font-bold">{{ item.score }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  items: Array
})
</script>
