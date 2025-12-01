<template>
  <div class="bg-white rounded-xl shadow p-4 text-center w-full">
    <div class="text-xl font-semibold mb-3">{{ title }}</div>

    <div ref="wrapper" class="w-full">
      <canvas ref="canvas" class="w-full block" aria-label="Bar chart showing registered participants"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({
  labels: { type: Array, default: () => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei'] },
  values: { type: Array, default: () => [12, 19, 7, 14, 9] },
  title: { type: String, default: 'Peserta Teregistrasi' },
  chooseMode: { type: String, default: 'aspect' },
  aspectRatio: { type: Number, default: 2 },
  fixedHeightPx: { type: Number, default: 220 },
  // warna per bar (default palette). Anda bisa override dengan props.colors
  colors: {
    type: Array,
    default: () => [
      '#F97316', '#F59E0B', '#FCD34D', '#10B981',
      '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6',
      '#EC4899', '#FB7185', '#EF4444', '#06D6A0'
    ]
  }
});

const canvas = ref(null);
const wrapper = ref(null);
let chart = null;

function resolveColors(count) {
  if (!props.colors || props.colors.length === 0) return [];
  const out = [];
  for (let i = 0; i < count; i++) {
    out.push(props.colors[i % props.colors.length]);
  }
  return out;
}

function createChart() {
  if (!canvas.value) return;
  const ctx = canvas.value.getContext('2d');

  const options = {
    responsive: true,
    maintainAspectRatio: props.chooseMode === 'aspect',
    aspectRatio: props.chooseMode === 'aspect' ? props.aspectRatio : undefined,
    plugins: {
      legend: { display: false },
      tooltip: { mode: 'index', intersect: false }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: '#374151' }
      },
      y: {
        beginAtZero: true,
        ticks: { color: '#374151' },
        grid: { color: 'rgba(0,0,0,0.05)' }
      }
    }
  };

  const barColors = resolveColors(props.values.length);

  chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: [
        {
          label: 'Jumlah',
          data: props.values,
          backgroundColor: barColors,
          borderRadius: 6,
          barThickness: 'flex'
        }
      ]
    },
    options
  });
}

onMounted(async () => {
  // fixed mode: pastikan canvas memiliki tinggi sebelum chart dibuat
  if (props.chooseMode === 'fixed' && canvas.value) {
    canvas.value.style.height = `${props.fixedHeightPx}px`;
  }

  createChart();

  await nextTick();
  if (chart && typeof chart.resize === 'function') chart.resize();
});

watch(
  () => [props.labels, props.values, props.chooseMode, props.aspectRatio, props.fixedHeightPx, props.colors],
  async () => {
    if (!chart) return;
    chart.data.labels = props.labels;
    chart.data.datasets[0].data = props.values;
    chart.data.datasets[0].backgroundColor = resolveColors(props.values.length);

    if (props.chooseMode === 'fixed' && canvas.value) {
      canvas.value.style.height = `${props.fixedHeightPx}px`;
    } else if (props.chooseMode === 'aspect' && canvas.value) {
      canvas.value.style.height = null;
    }

    chart.options.maintainAspectRatio = props.chooseMode === 'aspect';
    if (props.chooseMode === 'aspect') chart.options.aspectRatio = props.aspectRatio;
    chart.update();

    await nextTick();
    if (chart && typeof chart.resize === 'function') chart.resize();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  if (chart) {
    chart.destroy();
    chart = null;
  }
});
</script>

<style scoped>
/* jika anda mau memaksa tinggi, gunakan chooseMode="fixed" dan fixedHeightPx */
</style>