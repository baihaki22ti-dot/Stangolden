<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-[1fr_280px] gap-6">
      <!-- Panel Soal -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold mb-4">Soal {{ currentIndex + 1 }}</h2>

        <div class="mb-4">
          <div class="border rounded p-3 min-h-[140px]">
            {{ currentQuestion?.content || ('Content soal #' + (currentIndex + 1)) }}
          </div>
        </div>

        <!-- Render opsi hanya jika state siap -->
        <div class="space-y-2 mb-6" v-if="currentQuestion && safeAnswer(currentIndex)">
          <label
            v-for="opt in normalizedOptions(currentQuestion.options)"
            :key="opt.key"
            class="flex items-center gap-2"
          >
            <input
              :id="`opt-${currentIndex}-${opt.key}`"
              type="radio"
              :name="`opt-${currentIndex}`"
              :value="opt.key"
              v-model="answers[currentIndex].choice"
            />
            <span>{{ opt.text }}</span>
          </label>
        </div>

        <div class="flex items-center gap-3">
          <button class="px-4 py-2 rounded border text-sm bg-slate-200" :disabled="currentIndex === 0" @click="goBack">
            Back
          </button>
          <button class="px-4 py-2 rounded text-sm bg-yellow-400" @click="skipQuestion">Skip</button>
          <button class="px-4 py-2 rounded text-sm bg-emerald-500 text-white" @click="confirmQuestion">Confirm</button>

          <!-- Selesai ujian hanya tampil di soal terakhir -->
          <button
            v-if="currentIndex === totalQuestions - 1"
            class="ml-auto px-4 py-2 rounded text-sm bg-rose-600 text-white"
            @click="openFinishModal"
          >
            Selesai Ujian
          </button>
        </div>

        <!-- Modal konfirmasi selesai -->
        <div v-if="finishModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="closeFinishModal"></div>
          <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold mb-2">Selesai Ujian?</h3>
            <p class="text-sm text-slate-600 mb-4">Apakah Anda yakin ingin menyelesaikan ujian ini?</p>
            <div class="flex items-center justify-end gap-2">
              <button class="px-3 py-2 rounded border text-sm" @click="closeFinishModal">Batal</button>
              <button class="px-3 py-2 rounded bg-rose-600 text-white text-sm" @click="finishAttempt(false)">Ya, Selesai</button>
            </div>
          </div>
        </div>

        <!-- Info submit -->
        <div v-if="finished && lastResult" class="mt-6 p-4 bg-emerald-50 border border-emerald-200 rounded">
          <div class="font-semibold">Selesai!</div>
          <div class="text-sm text-slate-700">Score: {{ lastResult?.score?.toFixed(2) || 0 }}</div>
          <div class="text-xs text-slate-600">
            Benar: {{ lastResult?.correct || 0 }}, Salah: {{ lastResult?.wrong || 0 }}, Tidak Dijawab: {{ lastResult?.unanswered || 0 }}
          </div>
        </div>
      </div>

      <!-- Panel Timer + Navigator -->
      <div class="bg-white rounded-lg shadow p-4">
        <div class="bg-gradient-to-r from-sky-600 to-sky-400 rounded-lg p-4 text-center text-white font-mono text-lg">
          {{ formattedTime }}
        </div>

        <div class="mt-4 grid grid-cols-5 gap-2">
          <button
            v-for="(st, idx) in status"
            :key="idx"
            @click="goTo(idx)"
            class="h-10 rounded border text-sm font-semibold"
            :class="[
              idx === currentIndex ? 'bg-sky-200' : '',
              st === 'confirmed' ? 'bg-emerald-500 text-white' : '',
              st === 'skipped' ? 'bg-yellow-400' : '',
            ]"
          >
            {{ idx + 1 }}
          </button>
        </div>

        <div class="mt-4 text-xs text-slate-500 space-y-1">
          <div>Klik nomor untuk melompat ke soal.</div>
          <div>Hijau: Confirmed. Kuning: Skipped.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import backendService from '@/services/backendServices'

const route = useRoute()
const router = useRouter()
const bankId = Number(route.params.bankId) || null
const seriesId = Number(route.query.seriesId) || null
const category = String(route.query.category || '').toLowerCase()

// Timer config
const CATEGORY_TIMER = {
  'tpa-verbal': 45 * 60,
  'tpa-numerik': 45 * 60,
  'tpa-figural': 30 * 60,
  'tbi-structure': 13 * 60,
  'tbi-reading': 30 * 60,
  'tskkwk': 60 * 60,
}

const totalQuestions = ref(0)
const questions = ref([]) // [{id, content, options:[{id,text}]}] dari backend
const attemptId = ref(null)

const currentIndex = ref(0)
const status = ref([]) // 'none' | 'skipped' | 'confirmed'
const answers = ref([]) // [{ question_id, choice, status }]

const finished = ref(false)
const finishModal = ref(false)
const lastResult = ref(null)

// Timer
const initialSeconds = CATEGORY_TIMER[category] ?? 30 * 60
const remaining = ref(initialSeconds)
let tick = null
const formattedTime = computed(() => {
  const h = Math.floor(remaining.value / 3600).toString().padStart(2, '0')
  const m = Math.floor((remaining.value % 3600) / 60).toString().padStart(2, '0')
  const s = Math.floor(remaining.value % 60).toString().padStart(2, '0')
  return `${h}:${m}:${s}`
})

function startTimer() {
  if (tick || finished.value) return
  tick = setInterval(() => {
    if (remaining.value > 0) {
      remaining.value -= 1
    } else {
      stopTimer()
      finishAttempt(true)
    }
  }, 1000)
}

function stopTimer() { if (tick) { clearInterval(tick); tick = null } }

const currentQuestion = computed(() => questions.value[currentIndex.value] || null)

// Guard untuk template agar aman
function safeAnswer(idx) {
  return Array.isArray(answers.value) && answers.value[idx] && typeof answers.value[idx] === 'object'
}

// Normalisasi opsi: backend kirim [{id:'A', text:'...'}]
function normalizedOptions(opts) {
  if (!Array.isArray(opts)) return []
  return opts.map(o => ({
    key: String(o.id ?? o.key ?? o.label ?? o.value ?? '').toUpperCase(),
    text: String(o.text ?? o.content ?? '')
  })).filter(o => o.key !== '')
}

// Inisialisasi jawaban & status sesuai jumlah soal
function initAnswerState() {
  const count = totalQuestions.value
  status.value = Array.from({ length: count }, () => 'none')
  answers.value = questions.value.map(q => ({ question_id: q.id, choice: '', status: 'none' }))
}

// Bootstrap: start attempt + fetch questions + init state + start timer
async function bootstrap() {
  const start = await backendService.participant.startAttempt(bankId)
  attemptId.value = start.attempt_id

  const qs = await backendService.participant.listQuestions(bankId)
  questions.value = Array.isArray(qs?.questions) ? qs.questions : []
  totalQuestions.value = Number(qs?.count || questions.value.length || 0)

  initAnswerState()
  startTimer()
}

onMounted(async () => {
  // PENTING: jangan paksa fullscreen di mounted (butuh gesture user) → hapus requestFullscreenOnce
  await bootstrap()
})

onBeforeUnmount(() => {
  stopTimer()
})

// Navigasi & aksi
function goTo(idx) { if (!finished.value && idx >= 0 && idx < totalQuestions.value) currentIndex.value = idx }
function goBack() { if (!finished.value && currentIndex.value > 0) currentIndex.value -= 1 }

function skipQuestion() {
  if (finished.value || !safeAnswer(currentIndex.value)) return
  status.value[currentIndex.value] = 'skipped'
  answers.value[currentIndex.value].status = 'skipped'
  if (currentIndex.value < totalQuestions.value - 1) currentIndex.value += 1
}

function confirmQuestion() {
  if (finished.value || !safeAnswer(currentIndex.value)) return
  // Pastikan user memilih option
  const choice = (answers.value[currentIndex.value].choice || '').toString().trim()
  if (!choice) {
    alert('Pilih jawaban terlebih dahulu')
    return
  }
  status.value[currentIndex.value] = 'confirmed'
  answers.value[currentIndex.value].status = 'confirmed'
  if (currentIndex.value < totalQuestions.value - 1) currentIndex.value += 1
}

function openFinishModal() { if (!finished.value) finishModal.value = true }
function closeFinishModal() { finishModal.value = false }

// Submit ke backend dan redirect
async function finishAttempt(auto = false) {
  if (finished.value) return
  finished.value = true
  stopTimer()

  try {
    const res = await backendService.participant.finishAttempt(attemptId.value, answers.value)
    lastResult.value = res

    // simpan ringkas untuk label "done"
    localStorage.setItem(`bank:${bankId}:score`, JSON.stringify({
      bankId,
      seriesId,
      category,
      score: Number(res?.score || 0),
      benar: Number(res?.correct || 0),
      salah: Number(res?.wrong || 0),
      tidakDijawab: Number(res?.unanswered || 0),
      finishedAt: res?.finished_at || new Date().toISOString(),
      autoSubmit: !!res?.auto_submit,
    }))

    // ke halaman hasil
    router.replace({
      name: 'TryoutAdminBankResult',
      params: { bankId },
      query: { seriesId, category }
    })
  } catch (e) {
    alert(e?.response?.data?.message || e?.message || 'Gagal submit hasil')
  }
}
</script>