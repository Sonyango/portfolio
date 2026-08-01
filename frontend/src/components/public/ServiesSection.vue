<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index.js';

const services = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/services')
    services.value = data.data ?? []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section id="services" class="py-24 px-4 dark:bg-slate-900 bg-[#0D3530]">
    <div class="max-w-6xl mx-auto">

      <div class="text-center mb-16">
        <p class="text-sm font-semibold tracking-widest uppercase mb-3
                dark:text-indigo-400 text-[#00F0A0]">
          What I Offer
        </p>
        <h2 class="font-display text-4xl font-bold dark:text-white text-[#00F0A0]">
          Services
        </h2>
        <p class="mt-4 max-w-xl mx-auto dark:text-slate-400 text-[#B2DFDB]">
          Available for freelance projects, contracts, and full-time opportunities.
        </p>
      </div>

      <div v-if="loading" class="text-center dark:text-slate-400 text-[#B2DFDB]">
        Loading services...
      </div>

      <div v-else-if="services.length === 0" class="text-center dark:text-slate-400 text-[#B2DFDB]">
        No services added yet.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="service in services" :key="services.id" class="rounded-2xl p-6 border transition-all group dark:bg-slate-900 dark:border-slate-800
                dark:hover:border-indigo-500/30 bg-[#0B2B26] border-[#1A4A42] hover:border-[#00F0A0]/40">
          <!-- Icon Placeholder-->
          <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5 transition-colors dark:bg-indigo-500/10
                      dark:group-hover:bg-indigo-500/20 bg-[#00F0A0]/10 group-hover:bg-[#00F0A0]/20">
            <span class="text-xl dark:text-indigo-400 text-[#00F0A0]">⚡</span>
          </div>

          <h3 class="font-semibold text-lg mb-2 dark:text-white text-[#00F0A0]">{{ service.title }}</h3>
          <p class="text-sm leading-relaxed mb-4 dark:text-slate-400 text-[#B2DFDB]">
            {{ service.description }}
          </p>

          <div v-if="service.price_range"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm border
                  dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 bg-[#1A4A42] text-[#B2DFDB] border-[#1A4A42]">
            💰 {{ service.price_range }}
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="text-center mt-12">
        <router-link to="/contact"
          class="inline-flex items-center gap-2 px-8 py-3.5 font-medium rounded-xl transition-colors
                dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
          Work With Me →
        </router-link>
      </div>

    </div>
  </section>
</template>
