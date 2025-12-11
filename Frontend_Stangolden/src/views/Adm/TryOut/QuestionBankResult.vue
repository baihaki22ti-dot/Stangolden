<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow p-6">
      <h2 class="text-2xl font-semibold mb-4">Hasil Pengerjaan</h2>
      <div class="space-y-2 text-slate-700">
        <div><span class="font-medium">Kategori:</span> {{ category }}</div>
        <div><span class="font-medium">Score:</span> {{ data.score?.toFixed(2) }}</div>
        <div><span class="font-medium">Benar:</span> {{ data.benar }}</div>
        <div><span class="font-medium">Salah:</span> {{ data.salah }}</div>
        <div><span class="font-medium">Tidak Dijawab:</span> {{ data.tidakDijawab }}</div>
        <div class="text-xs text-slate-500">Selesai: {{ data.finishedAt }}</div>
      </div>

      <div class="mt-6 flex items-center gap-3">
        <button class="px-4 py-2 rounded border text-sm" @click="goBack()">Kembali</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import backendService from '@/services/backendServices'

const route = useRoute()
const router = useRouter()
const bankId = Number(route.params.bankId) || null
const seriesId = Number(route.query.seriesId) || null
const category = String(route.query.category || '').toLowerCase()

const data = ref({
  score: 0,
  benar: 0,
  salah: 0,
  tidakDijawab: 0,
  finishedAt: '',
})

onMounted(async () => {
  // 1) coba ambil dari localStorage (langsung setelah finish)
  const raw = localStorage.getItem(`bank:${bankId}:score`)
  if (raw) {
    const obj = JSON.parse(raw)
    data.value = {
      score: Number(obj?.score || 0),
      benar: Number(obj?.benar || obj?.correct || 0),
      salah: Number(obj?.salah || obj?.wrong || 0),
      tidakDijawab: Number(obj?.tidakDijawab || obj?.unanswered || 0),
      finishedAt: obj?.finishedAt || '',
    }
    return
  }

  // 2) fallback: ambil attempt terbaru dari server
  const attempts = await backendService.participant.myAttempts({ series_id: seriesId })
  const last = attempts.find(a => Number(a.bank_id) === bankId)
  if (last) {
    data.value = {
      score: Number(last.score || 0),
      benar: Number(last.correct_count || 0),
      salah: Number(last.wrong_count || 0),
      tidakDijawab: Number(last.unanswered_count || 0),
      finishedAt: last.finished_at || '',
    }
  }
})

function goBack() {
  // kembali ke halaman series detail
  router.replace({ name: 'TryoutAdminSeriesDetail', params: { seriesId } })
}
</script>