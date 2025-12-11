<template>
  <div class="min-h-screen p-6 bg-gray-50">
    <div class="max-w-[1200px] mx-auto grid grid-cols-12 gap-6">
      <!-- Left: Question area (col-span 8) -->
      <div class="col-span-12 lg:col-span-8 bg-white rounded shadow p-6">
        <div class="mb-4">
          <h2 class="text-2xl font-semibold">Soal {{ currentIndex + 1 }}</h2>
        </div>

        <div class="border rounded p-4 min-h-[160px] mb-6 bg-gray-50">
          <div class="text-sm text-slate-600">Content</div>
          <div class="mt-2 text-base">
            {{ currentQuestion.content }}
          </div>
        </div>

        <div class="mb-6">
          <fieldset>
            <legend class="sr-only">Pilihan</legend>
            <div v-for="(opt, idx) in currentQuestion.options" :key="idx" class="flex items-center gap-2 py-2">
              <input
                type="radio"
                :id="`opt-${currentIndex}-${idx}`"
                :name="`opt-${currentIndex}`"
                :value="idx"
                v-model="answers[currentIndex].selected"
                class="form-radio"
              />
              <label :for="`opt-${currentIndex}-${idx}`" class="select-none">
                {{ opt }}
              </label>
            </div>
          </fieldset>
        </div>

        <div class="flex items-center gap-3 mt-4">
          <button
            class="px-4 py-2 bg-gray-300 rounded shadow text-sm"
            :disabled="currentIndex === 0"
            @click="onBack"
          >Back</button>

          <button
            class="px-4 py-2 bg-yellow-400 rounded shadow text-sm"
            @click="onSkip"
          >Skip</button>

          <button
            class="px-4 py-2 bg-emerald-500 text-white rounded shadow text-sm"
            @click="onConfirm"
          >Confirm</button>

          <div class="ml-auto flex items-center gap-2">
            <button class="px-3 py-2 bg-red-500 text-white rounded text-sm" @click="onFinish">Finish</button>
          </div>
        </div>

        <!-- Results / Done summary (shown when finished) -->
        <div v-if="finished" class="mt-6 border-t pt-4">
          <h3 class="font-semibold">Summary</h3>
          <div class="mt-2 text-sm">
            <div>Answered (confirmed): {{ stats.confirmed }}</div>
            <div>Skipped: {{ stats.skipped }}</div>
            <div>Unanswered: {{ stats.unanswered }}</div>
            <div class="mt-2">Percent: <strong>{{ score.percent.toFixed(2) }}%</strong></div>
            <div v-if="score.display" class="mt-1">Score: <strong>{{ score.display }}</strong></div>
            <div class="mt-3">
              <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="resetAttempt">Reset (test)</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Timer + Numbers (col-span 4) -->
      <div class="col-span-12 lg:col-span-4">
        <div class="bg-gradient-to-r from-sky-700 to-slate-600 rounded-t-lg p-4 text-center text-white">
          <div class="inline-block bg-white text-slate-800 px-4 py-1 rounded">
            {{ formattedTime }}
          </div>
        </div>

        <div class="bg-gray-100 p-4 rounded-b-lg shadow">
          <div class="grid grid-cols-5 gap-2">
            <button
              v-for="n in totalQuestions"
              :key="n"
              @click="goToIndex(n-1)"
              :class="numberClass(n-1)"
              class="w-full h-10 rounded border text-sm"
            >
              {{ n }}
            </button>
          </div>

          <div class="mt-4 text-xs text-slate-600 space-y-1">
            <div><span class="inline-block w-3 h-3 bg-gray-200 mr-2 align-middle"></span> Unanswered</div>
            <div><span class="inline-block w-3 h-3 bg-yellow-400 mr-2 align-middle"></span> Skipped</div>
            <div><span class="inline-block w-3 h-3 bg-green-400 mr-2 align-middle"></span> Confirmed</div>
            <div><span class="inline-block w-3 h-3 bg-sky-400 mr-2 align-middle"></span> Current</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

/**
 * QuestionPlayer.vue
 * - Static local player for a question bank
 * - Props can be passed in a production flow; here we accept a bank object or fallback sample
 *
 * Behavior:
 * - Right panel shows timer and numbered grid
 * - Confirm marks current question as confirmed (green), Skip marks as skipped (yellow)
 * - Back navigates to previous question
 * - Clicking a number jumps to that question
 * - Timer counts down; when 0, auto-finish and compute score
 * - Score computation (per-bank) uses the per-question weights provided by user
 * - Results are stored in localStorage (per bank id) so "done" state persists in local dev
 */

// ----- Props / initial bank -----
const props = defineProps({
  // bank: { id, title, category, total_questions }  -- optional
  bank: {
    type: Object,
    required: false,
    default: () => ({
      id: 'demo-1',
      title: 'Demo Bank - TPA Verbal',
      category: 'tpa-verbal', // tpa-verbal | tpa-numerik | tpa-figural | tbi-structure | tbi-reading | tskkwk
      total_questions: 20,
    }),
  },
})

/* Timer presets (in minutes) as provided */
const TIMER_PRESETS = {
  'tpa-verbal': { minutes: 45, questions: 40 },
  'tpa-numerik': { minutes: 45, questions: 40 },
  'tpa-figural': { minutes: 30, questions: 40 },
  'tbi-structure': { minutes: 13, questions: 20 },
  'tbi-reading': { minutes: 30, questions: 30 },
  'tskkwk': { minutes: 60, questions: 60 },
}

// Per-question values to compute percent (user-provided)
const QUESTION_VALUE = {
  'tpa-verbal': 2.5,
  'tpa-numerik': 2.5,
  'tpa-figural': 2.5,
  'tbi-structure': 5.0,
  'tbi-reading': 3.3,
  'tskkwk': 1.6,
}

// ----- State -----
const totalQuestions = props.bank.total_questions || TIMER_PRESETS[props.bank.category]?.questions || 20
const category = props.bank.category || 'tpa-verbal'
const bankId = props.bank.id || `bank-${category}`

const currentIndex = ref(0)
const finished = ref(false)
const timeLeft = ref((TIMER_PRESETS[category]?.minutes || 30) * 60) // seconds

// Initialize placeholder questions array (static)
const questions = Array.from({ length: totalQuestions }, (_, i) => ({
  id: i + 1,
  content: `Placeholder content untuk soal nomor ${i + 1}. (Category: ${category})`,
  options: ['Option 1', 'Option 2', 'Option 3', 'Option 4'],
}))

// answers state: for each question we track { selected: null|index, status: 'unanswered'|'skipped'|'confirmed' }
const answers = ref(questions.map(() => ({ selected: null, status: 'unanswered' })))

const currentQuestion = computed(() => questions[currentIndex.value])

// stats & score computed
const stats = computed(() => {
  const confirmed = answers.value.filter(a => a.status === 'confirmed').length
  const skipped = answers.value.filter(a => a.status === 'skipped').length
  const unanswered = answers.value.filter(a => a.status === 'unanswered').length
  return { confirmed, skipped, unanswered }
})

// Score computation for the bank (percent 0-100)
function computeBankPercent(correctCount) {
  const per = QUESTION_VALUE[category] ?? 1
  const pct = correctCount * per
  return Math.min(100, pct)
}

// For demo we consider "confirmed" as correct (static). In real app, correctness comes from server.
// So confirmedCount = stats.confirmed
const score = computed(() => {
  const confirmedCount = stats.value.confirmed
  const percent = computeBankPercent(confirmedCount)
  // Scaled display: per-bank we show percent (0-100). Additional group formulas are provided below.
  // For convenience show a human readable display for bank:
  let display = `${percent.toFixed(2)}%`
  return { percent, display }
})

// Timer formatting
const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0')
  const s = Math.floor(timeLeft.value % 60).toString().padStart(2, '0')
  return `${m}:${s}`
})

// Interval handle
let timerHandle = null

function startTimer() {
  if (timerHandle) return
  timerHandle = setInterval(() => {
    if (timeLeft.value <= 0) {
      clearInterval(timerHandle)
      timerHandle = null
      onTimeUp()
      return
    }
    timeLeft.value -= 1
  }, 1000)
}

function stopTimer() {
  if (timerHandle) {
    clearInterval(timerHandle)
    timerHandle = null
  }
}

// Navigation & actions
function goToIndex(idx) {
  if (idx < 0) idx = 0
  if (idx >= totalQuestions) idx = totalQuestions - 1
  currentIndex.value = idx
}

function onBack() {
  if (currentIndex.value === 0) return
  currentIndex.value -= 1
}

function onSkip() {
  answers.value[currentIndex.value].status = 'skipped'
  // move to next
  if (currentIndex.value < totalQuestions - 1) currentIndex.value += 1
}

function onConfirm() {
  answers.value[currentIndex.value].status = 'confirmed'
  // move to next automatically if possible
  if (currentIndex.value < totalQuestions - 1) {
    currentIndex.value += 1
  } else {
    // last question, optionally finish
  }
}

function onFinish() {
  stopTimer()
  finished.value = true
  persistResult()
}

function onTimeUp() {
  // mark remaining unanswered as skipped and finish
  for (let i = 0; i < totalQuestions; i++) {
    if (answers.value[i].status === 'unanswered') answers.value[i].status = 'skipped'
  }
  finished.value = true
  persistResult()
}

// Color classes for numbered grid
function numberClass(idx) {
  const state = answers.value[idx].status
  const classes = ['bg-white']
  if (idx === currentIndex.value) classes.push('bg-sky-300')
  if (state === 'confirmed') classes.push('bg-green-400')
  if (state === 'skipped') classes.push('bg-yellow-400')
  // border always present
  return classes.join(' ')
}

// Persist result to localStorage (static simulation)
function persistResult() {
  try {
    const storageKey = `question_bank_result_${bankId}`
    const payload = {
      bankId,
      category,
      totalQuestions,
      answers: answers.value,
      finishedAt: new Date().toISOString(),
      score: score.value,
    }
    localStorage.setItem(storageKey, JSON.stringify(payload))
  } catch (e) {
    // ignore
  }
}

// Reset helper (for testing)
function resetAttempt() {
  for (let i = 0; i < answers.value.length; i++) {
    answers.value[i] = { selected: null, status: 'unanswered' }
  }
  currentIndex.value = 0
  finished.value = false
  timeLeft.value = (TIMER_PRESETS[category]?.minutes || 30) * 60
  stopTimer()
  startTimer()
}

// compute helpers for combining groups (static utilities you can call externally)
// Combined TPA formula (expects array of three percent numbers [verbal, numerik, figural])
function computeCombinedTPA(percentages = []) {
  if (!Array.isArray(percentages) || percentages.length === 0) return null
  const avg = percentages.reduce((s, v) => s + v, 0) / percentages.length
  // Final TPA as user provided: ((rata/100) × 600) + 200
  const finalTPA = ((avg / 100) * 600) + 200
  return { avg, finalTPA }
}

// Combined TBI (expects [structurePercent, readingPercent])
function computeCombinedTBI(structurePercent = 0, readingPercent = 0) {
  // simplest: average percent then scale to max 675
  const avg = (structurePercent + readingPercent) / 2
  const final = (avg / 100) * 675
  return { avg, final }
}

// TSKKWK per-bank percent already in computeBankPercent
// Example: computeCombinedUPKP etc can be composed by caller

// Start timer on mounted
onMounted(() => {
  // restore persisted result if exists: show done state and load answers
  try {
    const storageKey = `question_bank_result_${bankId}`
    const raw = localStorage.getItem(storageKey)
    if (raw) {
      const st = JSON.parse(raw)
      if (st?.answers) {
        // only restore shape if same length
        if (Array.isArray(st.answers) && st.answers.length === answers.value.length) {
          for (let i = 0; i < answers.value.length; i++) {
            answers.value[i] = st.answers[i]
          }
          finished.value = true
        }
      }
    }
  } catch (e) {
    // ignore
  }

  if (!finished.value) startTimer()
})

onBeforeUnmount(() => {
  stopTimer()
})
</script>

<style scoped>
/* Minimal styling to match requested visual states */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.bg-sky-300 { background-color: #7dd3fc; }
.bg-green-400 { background-color: #86efac; }
.bg-yellow-400 { background-color: #facc15; }
</style>