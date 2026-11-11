<script setup>
import { useSettingsStore } from '@/stores/settingsStore';
import { ArrowDownIcon } from '@heroicons/vue/24/outline';
import { ref, onMounted } from 'vue';
import ProtectedImage from '@/components/public/ProtectedImage.vue';

const settingsStore = useSettingsStore()
const imageError = ref(false)
const imageLoaded = ref(false)

const getProfileImageUrl = () => {
  const imagePath = settingsStore.get('profile_image');
  if (!imagePath) return null;

  // If the path is already a full URL, return it as is
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath;
  }

  // Otherwise, construct the storage URL
  return `/storage/${imagePath}`;
}
</script>

<template>
  <section class="min-h-[calc(100vh-4rem)] w-full flex items-center justify-center xl:items-start xl:pt-12 px-4 relative overflow-hidden
                  dark:bg-slate-950 bg-[#0B2B26]">

    <!-- Background glow-->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-72 h-72
                dark:bg-indigo-600/10 bg-[#00F0A0]/5 rounded-full blur-3xl" />
    </div>

    <div v-if="settingsStore.loading" class="text-[#7BB8B2] dark:text-slate-500 text-sm animate-pulse">
      Loading...
    </div>

    <div v-else class="mx-auto relative z-10 w-full py-3 lg:py-3">

      <!-- Desktop badge, centered above both cards -->
       <div class="mb-4 hidden justify-center lg:flex">
        <div
          v-if="settingsStore.get('available_for_work') === 'true'"
          class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium
                 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400
                 border-[#00F0A0]/30 bg-[#00F0A0]/10 text-[#00F0A0]">
          <span class="h-2 w-2 animate-pulse rounded-full dark:bg-green-400 bg-[#00F0A0]" />
          <span class="animate-pulse">Available for work</span>
        </div>
       </div>

      <!-- Left and right columns -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <!-- Left: content card on desktop -->
        <div class="order-2 flex w-full flex-col justify-center rounded-2xl border p-6 text-center
+        lg:order-1 lg:p-8 lg:text-left dark:border-slate-800 dark:bg-slate-900
+        border-[#1A4A42] bg-[#0D3530]">
          <!-- Availability badge on mobile -->
           <div v-if="settingsStore.get('available_for_work') === 'true'"
             class="mb-8 inline-flex items-center gap-2 self-center rounded-full border px-4 py-2 text-sm font-medium lg:hidden
                   dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400 border-[#00F0A0]/30 bg-[#00F0A0]/10 text-[#00F0A0]">
            <span class="h-2 w-2 animate-pulse rounded-full dark:bg-green-400 bg-[#00F0A0]" />
            <span class="animate-pulse">Available for work</span>
          </div>

          <h1 class="mb-6 font-display text-5xl font-bold leading-tight md:text-7xl dark:text-white text-[#00F0A0]">
            {{ settingsStore.get('hero_title', 'Hi, I\'m a Full Stack Developer') }}
          </h1>

          <p class="mb-10 max-w-2xl text-xl leading-relaxed lg:max-w-none dark:text-slate-400 text-[#B2DFDB]">
            {{ settingsStore.get('hero_subtitle', 'I build modern web applications, Networking, and providing ICT solutions.') }}
          </p>
          <div class="flex flex-col justify-center gap-4 sm:flex-row sm:items-center lg:justify-center lg:items-center">
            <router-link
              to="/projects"
              class="rounded-xl px-8 py-3.5 text-sm font-medium transition-colors
                     dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white
                     bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
              View My Work
            </router-link>
            <router-link
              to="/contact"
               class="rounded-xl border px-8 py-3.5 text-sm font-medium transition-colors
                     dark:border-slate-700 dark:hover:border-slate-500 dark:text-slate-300
                     dark:hover:text-white border-white/30 hover:border-white text-white">
              Get In Touch
            </router-link>
          </div>
          <!-- Social links below content on mobile -->
           <div class="mt-10 flex items-center justify-center gap-4 lg:hidden">
              <a
                v-if="settingsStore.get('github_url')"
                :href="settingsStore.get('github_url')"
                target="_blank"
                class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white
                      text-[#B2DFDB] hover:text-[#00F0A0]"
              >
                GitHub
              </a>
              <span class="dark:text-slate-700 text-[#1A4A42]">.</span>
                <a
                v-if="settingsStore.get('linkedin_url')"
                :href="settingsStore.get('linkedin_url')"
                target="_blank"
                class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white
                      text-[#B2DFDB] hover:text-[#00F0A0]"
              >
                LinkedIn
              </a>
              <span class="dark:text-slate-700 text-[#1A4A42]">.</span>
              <a
                v-if="settingsStore.get('email')"
                :href="`mailto:${settingsStore.get('email')}`"
                class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white
                      text-[#B2DFDB] hover:text-[#00F0A0]"
              >
                Email
              </a>
           </div>
        </div>

        <!-- Right: profile image and social links  -->
        <div class="order-1 flex shrink flex-col items-center justify-center lg:order-2 lg:rounded-2xl
                 lg:border lg:p-8 dark:lg:border-slate-800 dark:lg:bg-slate-900 lg:border-[#1A4A42] lg:bg-[#0D3530]">
          <div v-if="getProfileImageUrl()" class="relative h-48 w-48 md:h-64 md:w-64 lg:h-80 lg:w-80">
            <div v-if="!imageLoaded && !imageError"
            class="absolute inset-0 animate-pulse rounded-full bg-[#00F0A0]/10 dark:bg-slate-800" />

            <ProtectedImage
              :src="getProfileImageUrl()"
              :alt="settingsStore.get('site_name', 'Profile')"
              img-class="w-full h-full rounded-full object-cover border-4 dark:border-slate-700 border-[#00F0A0]/30 shadow-lg shadow-[#00F0A0]/20"
              wrap-class="w-full h-full"
            />
          </div>
          <div
            v-else
            class="flex h-48 w-48 items-center justify-center rounded-full border-4 bg-[#00F0A0]/10 dark:bg-slate-800
                 dark:border-slate-700 border-[#00F0A0]/30 md:h-64 md:w-64 lg:h-80 lg:w-80">
            <span class="text-6xl font-bold text-[#00F0A0] dark:text-slate-400 md:text-7xl lg:text-8xl">
              {{ (settingsStore.get('site_name', '?') || '?').charAt(0).toUpperCase() }}
            </span>
          </div>

          <!-- Social links move below the profile on desktop -->
          <div class="mt-8 hidden items-center justify-center gap-4 lg:flex">
            <a
            v-if="settingsStore.get('github_url')" :href="settingsStore.get('github_url')" target="_blank"
            class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white  text-[#B2DFDB] hover:text-[#00F0A0]">
              GitHub
            </a>
            <span class="dark:text-slate-700 text-[#1A4A42]">.</span>

            <a
            v-if="settingsStore.get('linkedin_url')" :href="settingsStore.get('linkedin_url')" target="_blank"
            class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white  text-[#B2DFDB] hover:text-[#00F0A0]">
              LinkedIn
            </a>
            <span class="dark:text-slate-700 text-[#1A4A42]">.</span>

            <a
            v-if="settingsStore.get('email')" :href="`mailto:${settingsStore.get('email')}`"
            class="text-sm transition-colors dark:text-slate-400 dark:hover:text-white  text-[#B2DFDB] hover:text-[#00F0A0]">
              Email
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2
                animate-bounce dark:text-slate-600 text-[#1A4A42]">
      <ArrowDownIcon class="w-5 h-5" />
    </div>
  </section>
</template>
