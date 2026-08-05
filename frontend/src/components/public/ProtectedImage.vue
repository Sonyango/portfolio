<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
  src:      { type: String, required: true },
  alt:      { type: String, default: 'Image' },
  imgClass: { type: String, default: '' },
  wrapClass: { type: String, default: '' },
});

const containerRef = ref(null);
const canvasRef = ref(null);
const imageLoaded = ref(false);
const imageError = ref(false);

// Block right-click
function blockContextMenu(e) {
  e.preventDefault(e);
  return false;
}

// Block drag
function blockDrag(e) {
  e.preventDefault(e);
  return false;
}

// Block keyboard shortcuts (Ctrl+S, Ctrl+U, Ctrl+Shift+I, F12)
function blockKeyboardShortcuts(e) {
  if (
    (e.ctrlKey && ['s', 'u', 'p'].includes(e.key.toLowerCase())) ||
    (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(e.key.toLowerCase())) ||
    e.key === 'F12'
  ) {
    e.preventDefault()
    return false
  }
}

// Render image to canvas to strip direct URL access
function renderToCanvas(imgSrc) {
  const canvas = canvasRef.value
  if (!canvas) return

  const img = new Image()
  img.crossOrigin = 'annymous'

  img.onload = () => {
    canvas.width  = img.naturalWidth
    canvas.height = img.naturalHeight
    const ctx     = canvas.getContext('2d')
    ctx.drawImage(img, 0, 0)
    imageLoaded.value = true
  }

  img.onerror = () => {
    imageError.value = true
  }
  img.src = imgSrc
}

onMounted(() => {
  document.addEventListener('keydown', blockKeyboardShortcuts)
  if (canvasRef.value) {
    renderToCanvas(canvasRef.value.dataset.src)
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', blockKeyboardShortcuts)
})
</script>

<template>
  <div
    ref="containerRef"
    :class="['relative select-none', wrapClass]"
    @contextmenu.prevent="blockContextMenu"
    @dragstart.prevent="blockDrag"
  >
  <!-- Canvas renders the image. No direct URL exposed -->
   <canvas
      ref="canvasRef"
      :data-src="src"
      :class="['block', imgClass, { 'opacity-0' : !imageLoaded && !imageError }]"
      style="pointer-events: none;"
   />

   <!-- Loading skeleton -->
    <div
      v-if="!imageLoaded && !imageError"
      :class="['absolute inset-0 animate-pulse rounded-full', 'dark:bg-slate-800 bg-[#00F0A0]/10']" />

    <!-- Error fallback -->
     <div
        v-if="imageError"
        :class="['absolute inset-0 flex items-center justify-center',
                'dark:bg-slate-800 bg-[#00F0A0]/10 rounded-full',
                'dark:text-slate-400 text-[#7BB8B2] text-xs']">
          Image unavailable
      </div>

    <!-- Transparent overlay. Blocks interaction -->
     <div
      class="absolute inset-0 z-10"
      style="background: transparent; cursor: default;"
      @contextmenu.prevent
      @dragstart.prevent
      @mousedown.prevent
    />
  </div>
</template>

<style scoped>
canvas {
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  pointer-events: none;
}
</style>
