<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index.js';

const services  = ref([])
const loading   = ref(true)

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
  <section id="services" class="py-24 px-4 bg-slate-900/50">
    <div class="max-w-6xl mx-auto">

      <div class="text-center mb-16">
        <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
          What I Offer
        </p>
        <h2 class="font-display text-4xl font-bold text-white">Services</h2>
        <p class="text-slate-400 mt-4 max-w-xl mx-auto">
          Available for freelance projects, contracts, and full-time opportunities.
        </p>
      </div>

      <div v-if="loading" class="text-center text-slate-400">
        Loading services...
      </div>

      <div v-else-if="services.length === 0" class="text-center text-slate-400">
        No services added yet.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="service in services"
          :key="services.id"
          class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500/30 transition-all group">
          <!-- Icon Placeholder-->
           <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center mb-5 group-hover:bg-indigo-500/20 transition-colors">
            <span class="text-indigo-400 text-xl">⚡</span>
           </div>

           <h3 class="text-white font-semibold text-lg mb-2">{{ service.title }}</h3>
           <p class="text-slate-400 text-sm leading-relaxed mb-4">
            {{ service.description }}
           </p>

           <div v-if="service.price_range"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 rounded-full text-sm text-slate-300">
            💰 {{ service.price_range }}
          </div>
        </div>
      </div>

      <!-- CTA -->
       <div class="text-center mt-12">
        <router-link to="/contact"
          class="inline-flex items-center gap-2 px-8 py-3.5 bg-indigo-600
                hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
          Work With Me →
        </router-link>
       </div>
       
    </div>
  </section>
</template>
