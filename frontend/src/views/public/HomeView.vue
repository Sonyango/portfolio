<script setup>
import { onMounted } from 'vue';
import { useSettingsStore } from '@/stores/settingsStore';
import { useSeo } from '@/composables/useSeo';
import PublicLayout from '@/components/public/PublicLayout.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import AboutSection from '@/components/public/AboutSection.vue';
import SkillsSection from '@/components/public/SkillsSection.vue';
import ExperienceSection from '@/components/public/ExperienceSection.vue';
import ServiesSection from '@/components/public/ServiesSection.vue';
import JsonLdSchema from '@/components/public/JsonLdSchema.vue';

const settingsStore = useSettingsStore()

useSeo({
  title: 'Home',
  description: 'Full Stack Developer & ICT Professional based in Nairobi, Kenya.',
  url:    window.location.href,
})

onMounted(async () => {
  await settingsStore.fetchSettings(true)
})
</script>

<template>
  <PublicLayout>
    <JsonLdSchema />
    <HeroSection />
    <AboutSection />
    <div class="mx-auto grid grid-cols-1 items-stretch gap-6 px-4 py-8 lg:grid-cols-2">
      <ExperienceSection />
      <ServiesSection />
    </div>
  </PublicLayout>
</template>
