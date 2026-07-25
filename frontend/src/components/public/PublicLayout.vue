<script setup>
import { ref } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore';
import { Bars3Icon, SunIcon, MoonIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import ToastNotification from '@/components/admin/ToastNotification.vue';
import { useDarkMode } from '@/composables/useDarkMode';
import BackToTop from '@/components/public/BackToTop.vue';

const { isDark, toggle } = useDarkMode()
const settingsStore = useSettingsStore()
const mobileOpen    = ref(false)

const navLinks = [
  { label: 'Home', to: '/' },
  { label: 'Projects',  to: '/projects' },
  { label: 'Blog',      to: '/blog' },
  { label: 'Contact',   to: '/contact' },
]

function closeMobile() {
  mobileOpen.value = false
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950
              text-slate-900 dark:text-white transition-colors duration-300">

    <ToastNotification />

    <!-- Navbar -->
     <header class="fixed top-0 left-0 right-0 z-50 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md
                    border-b border-slate-200 dark:border-slate-800 transition-colors duration-300">
      <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">

        <!-- Logo -->
         <router-link to="/"
         class="font-display text-lg font-bold text-slate-900 dark:text-white hover:text-indigo-600
                 dark:hover:text-indigo-400 transition-colors">
          {{ settingsStore.get('site_name', 'Portfolio') }}
        </router-link>

        <!-- Desktop nav -->
         <nav class="hidden md:flex items-center ga-8">
          <router-link
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="text-sm font-medium px-1 text-slate-600 dark:text-slate-400 hover:text-slate-900
                   dark:hover:text-white transition-colors"
            active-class="text-indigo-600 dark:text-indigo-400 font-semibold">
            {{ link.label }}
          </router-link>

          <!-- Dark mode toggle -->
           <button
              @click="toggle"
              class="p-2 rounded-xl transition-colors text-slate-500 dark:text-slate-400
                      hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800"
              :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
              <SunIcon v-if="isDark" class="w-5 h-5" />
              <MoonIcon v-else       class="w-5 h-5" />
            </button>

          <a
            v-if="settingsStore.get('cv_url')"
            :href="settingsStore.get('cv_url')"
            target="_blank"
            class="px-5 py-2 ml-2 bg-indigo-600 hover:bg-indigo-700 text-white
                   text-sm font-medium rounded-xl transition-colors"
          >
            Download CV
          </a>
         </nav>

         <!-- Mobile menu button right side -->
          <div class="flex md:hidden items-center gap-2">
            <!-- dark mode toggle mobile -->
             <button
                @click="toggle"
                class="p-2 rounded-xl transition-colors text-slate-500 dark:text-slate-400
                        hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800"
                :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                <SunIcon v-if="isDark" class="w-5 h-5" />
                <MoonIcon v-else       class="w-5 h-5" />
              </button>

              <!-- Humburger -->
               <button
                  @click="mobileOpen = !mobileOpen"
                  class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-900
                          dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                  <Bars3Icon v-if="!mobileOpen" class="h-6 w-6" />
                  <XMarkIcon v-else class="h-6 w-6" />
              </button>
          </div>
      </div>

      <!-- Mobile menu -->
       <transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2">

          <div
            v-if="mobileOpen"
            class="md:hidden border-t border-slate-200 dark:border-slate-800
                   bg-white dark:bg-slate-950 px-4 py-4 space-y-1">
              <router-link
                v-for="link in navLinks"
                :key="link.to"
                :to="link.to"
                @click="closeMobile"
                class="block px-4 py-2.5 rounded-xl text-sm font-medium transition-colors text-slate-600 dark:text-slate-300
                    hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800"
                active-class="bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                {{ link.label }}
              </router-link>

              <a
                v-if="settingsStore.get('cv_url')"
                :href="settingsStore.get('cv_url')"
                target="_blank"
                @click="closeMobile"
                class="block px-4 py-2.5 mt-2 bg-indigo-600 hover:bg-indigo-700
                    text-white rounded-xl text-sm font-medium transition-colors
                    text-center">
                Download CV
              </a>
          </div>
        </transition>


     </header>

     <!-- Page content -->
      <main class="pt-16">
        <slot />
      </main>

    <!-- Footer -->
     <footer class="border-t border-slate-200 dark:border-slate-800
                    py-10 mt-20 transition-colors duration-300">
      <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-slate-500 dark:text-slate-400 text-sm">
          &copy; {{ new Date().getFullYear() }}
          {{ settingsStore.get('site_name', 'Portfolio') }}
          All rights reserved.
        </p>
        <div class="flex items-center gap-4">
          <a
            v-if="settingsStore.get('github_url')"
            :href="settingsStore.get('github_url')"
            target="_blank"
            class="text-slate-500 hover:text-slate-900 dark:hover:text-white text-sm transition-colors">
            GitHub
          </a>

          <a
            v-if="settingsStore.get('linkedIn_url')"
            :href="settingsStore.get('linkedIn_url')"
            target="_blank"
            class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-sm transition-colors">
            LinkedIn
          </a>

          <a
            v-if="settingsStore.get('twitter_url')"
            :href="settingsStore.get('twitter_url')"
            target="_blank"
            class="text-slate-500 dark:text-slate-400 hover:text-slate-900
                    dark:hover:text-white text-sm transition-colors">
            Twitter / X
          </a>
        </div>
      </div>
     </footer>

     <BackToTop />
  </div>
</template>
