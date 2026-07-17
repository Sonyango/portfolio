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
    <SkillsSection />
    <ExperienceSection />
    <ServiesSection />
  </PublicLayout>
</template>
