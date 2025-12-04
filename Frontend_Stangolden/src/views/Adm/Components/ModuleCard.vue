<template>
  <article
    class="rounded-xl overflow-hidden shadow-sm bg-white flex flex-col transition-transform hover:scale-[1.01]"
    role="group"
    :style="cardStyle"
  >
    <!-- Image area (object-contain so image tidak terpotong) -->
    <div class="flex-none bg-slate-50 flex items-center justify-center px-3 py-2"
         :style="{ minHeight: 'var(--img-h)' }">
      <img
        :src="image"
        :alt="title"
        class="max-w-full max-h-full object-contain"
        draggable="false"
      />
    </div>

    <!-- Bottom panel: title + description (diletakkan ke kiri agar rapi) -->
    <div
      class="flex-1 flex flex-col items-start justify-between px-4 py-3 text-left text-white bg-gradient-to-b"
      :class="gradientClass"
    >
      <div class="w-full">
        <!-- CHANGED: truncate title so it won't overflow horizontally -->
        <h3 class="text-base md:text-lg font-semibold mb-1 truncate">{{ title }}</h3>

        <!-- CHANGED: description placement and clamping for neat layout -->
        <p class="text-xs md:text-sm mb-3 description-clamp">{{ description }}</p>
      </div>

      <div class="w-full flex justify-end">
        <slot name="cta">
        </slot>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  image: { type: String, required: true },
  gradientClass: { type: String, default: 'from-sky-400 via-indigo-600 to-indigo-900' },

  // CHANGED: added cardWidth prop so parent can set card width (e.g. '18rem')
  cardWidth: { type: String, default: '18rem' },

  // CHANGED: adjust overall card height and image area height easily
  cardHeight: { type: String, default: '15rem' },       // min-height for entire card
  imageAreaHeight: { type: String, default: '8rem' },

  // optional route target (if parent uses <router-link> wrapper)
  to: { type: [String, Object], default: null }
})

/* CHANGED: expose inline style including width and heights so parent can control sizing */
const cardStyle = computed(() => {
  const s = { '--img-h': props.imageAreaHeight }
  if (props.cardHeight) s.minHeight = props.cardHeight
  if (props.cardWidth) s.width = props.cardWidth
  return s
})
</script>

<style scoped>
.description-clamp {
  display: -webkit-box;
  line-clamp: 3; /* standard property for compatibility */
  -webkit-line-clamp: 3; /* maks 3 baris */
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* keep title single-line */
h3 {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>