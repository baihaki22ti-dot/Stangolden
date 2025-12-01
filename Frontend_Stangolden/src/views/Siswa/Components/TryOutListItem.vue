<template>
  <li class="flex items-center justify-between gap-4 p-3 rounded-md border hover:shadow-sm">
    <div class="min-w-0">
      <router-link :to="`/tryout/${item.id}`" class="text-sm font-semibold text-slate-800 block truncate">
        {{ index + 1 }}. {{ item.title }}
      </router-link>
      <div class="text-xs text-slate-500 mt-1">
        Dibuat: {{ item.createdAt ? new Date(item.createdAt).toLocaleString() : '-' }} • Tanggal: {{ item.date || '-' }}
      </div>
      <div class="text-xs text-slate-600 mt-1 line-clamp-2">{{ item.description }}</div>
    </div>

  </li>
</template>

<script setup>
const props = defineProps({
  item: { type: Object, required: true },
  index: { type: Number, default: 0 }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>