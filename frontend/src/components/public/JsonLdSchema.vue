<script setup>
import { onMounted } from 'vue';
import { useSettingsStore } from '@/stores/settingsStore';

const settingsStore = useSettingsStore()

onMounted(() => {
  const existing = document.getElementById('json-ld-schema')
  if (existing) existing.remove()

  const script       = document.createElement('script')
  script.id          = 'json-ld-schema'
  script.type        = 'application/ld+json'
  script.textContent = JSON.stringify({
    '@context': 'https://schema.org',
    '@type':    'Person',
    name:       settingsStore.get('site_name', 'Stephen'),
    email:      settingsStore.get('email', ''),
    url:        window.location.origin,
    jobTitle:   'Full Stack Developer & ICT Professional',
    address: {
      '@type':         'PostalAddress',
      addressLocality: settingsStore.get('location', 'Nairobi'),
      addressCountry:  'KE',
    },
    sameAs: [
      settingsStore.get('github_url', ''),
      settingsStore.get('linkedin_url', ''),
      settingsStore.get('twitter_url', ''),
    ].filter(Boolean),
  })

  document.head.appendChild(script)
})
</script>

<template>
  <span />
</template>
