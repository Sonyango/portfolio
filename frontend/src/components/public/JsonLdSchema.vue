<script setup>
import { useHead } from '@unhead/vue';
import { useSettingsStore } from '@/stores/settingsStore';

const settingsStore = useSettingsStore()

useHead({
  script: [
    {
      type: 'application/ld+json',
      innerHTML: JSON.stringify({
        '@context': 'https://schema.org',
        '@type':    'Person',
        name:       settingsStore.get('site_name', 'Stephen'),
        email:      settingsStore.get('email', ''),
        url:        window.location.origin,
        jobTitle:   'Full Stack Developer & ICT Professional',
        address:  {
          '@type':          'PostalAddress',
          addressLocality:  settingsStore.get('location', 'Nairobi'),
          addressCountry:   'KE',
        },
        sameAs: [
          settingsStore.get('github_url', ''),
          settingsStore.get('linkedin_url', ''),
          settingsStore.get('twitter_url', ''),
        ].filter(Boolean),
      }),
    },
  ],
})
</script>

<template>
  <span />
</template>
