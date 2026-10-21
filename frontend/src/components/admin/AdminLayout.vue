<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/api/index.js';
import { useAuthStore } from '@/stores/authStore';
import { useActivityTracker } from '@/composables/useActivityTracker';
import ToastNotification from '@/components/admin/ToastNotification.vue';
import {
  Squares2X2Icon,
  FolderOpenIcon,
  PencilSquareIcon,
  BoltIcon,
  BriefcaseIcon,
  WrenchScrewdriverIcon,
  PhotoIcon,
  EnvelopeIcon,
  Cog6ToothIcon,
  PowerIcon,
  Bars3Icon,
  XMarkIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// User activity tracking
useActivityTracker()

// Sidebar open state(closed by default on mobole)
const sidebarOpen = ref(false)

const unreadMessages = ref(0)
let unreadInterval = null

// Close sidebar when route changes(mobile nav)
watch(() => route.path, () => {
  sidebarOpen.value = false
})

const navItems = [
  { name: 'Dashboard', route: 'admin.dashboard', icon: Squares2X2Icon },
  { name: 'Projects', route: 'admin.projects', icon: FolderOpenIcon },
  { name: 'Blog Posts', route: 'admin.posts', icon: PencilSquareIcon },
  { name: 'Skills', route: 'admin.skills', icon: BoltIcon },
  { name: 'Experience', route: 'admin.experiences', icon: BriefcaseIcon },
  { name: 'Services', route: 'admin.services', icon: WrenchScrewdriverIcon },
  { name: 'Media', route: 'admin.media', icon: PhotoIcon },
  { name: 'Messages', route: 'admin.messages', icon: EnvelopeIcon },
  { name: 'Settings', route: 'admin.settings', icon: Cog6ToothIcon },
  { name: 'Profile', route: 'admin.profile', icon: ShieldCheckIcon },
]

async function fetchUnreadCount() {
  try {
    const { data } = await api.get('/admin/messages')
    const messages = data?.data ?? []
    unreadMessages.value = messages.filter(m => !m.read_at).length
  } catch (error) {
    console.log.error
  }
}

onMounted(() => {
  fetchUnreadCount()
  unreadInterval = setInterval(fetchUnreadCount, 2 * 60 * 1000) // Refresh unread count every 2 minutes
})

onUnmounted(() => {
  clearInterval(unreadInterval)
})

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'admin.login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex">
    <ToastNotification />

    <!-- Mobile overlay closes sidebar on tap -->
    <transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300" leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="sidebarOpen" class="fixed inset-0 bg-black/60 z-30 lg:hidden" @click="sidebarOpen = false" />
    </transition>

    <!-- Sidebar -->
    <aside :class="[
      'fixed top-0 left-0 h-full w-64 z-40',
      'bg-slate-800 border-r border-slate-700',
      'flex flex-col',
      'transition-transform duration-300 ease-in-out',
      // On mobile: translate offscreen when closed
      // On desktop (lg+): always visible
      sidebarOpen
        ? 'translate-x-0'
        : '-translate-x-full lg:translate-x-0',
    ]">

      <!-- Brand -->
      <div class="px-6 py-5 border-b border-slate-700 flex items-center justify-between">
        <div>
          <h1 class="text-lg font-bold text-white">Portfolio Admin</h1>
          <p class="text-xs text-slate-400 mt-0.5">
            {{ authStore.user?.name }}
          </p>
        </div>
        <!-- Close button, mobile only -->
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 text-slate-400 hover:text-white
                    hover:bg-slate-700 rounded-lg transition-colors">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <router-link v-for="item in navItems" :key="item.route" :to="{ name: item.route }"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors relative"
          :class="route.name === item.route ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white'">
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          <span class="flex-1">{{ item.name }}</span>

          <!-- Unread badge messages in items -->
           <span
              v-if="item.route === 'admin.messages' && unreadMessages > 0"
              class="min-w-5 h-5 px-1.5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center shrink-0">
              {{ unreadMessages > 99 ? '99+' : unreadMessages }}
            </span>
        </router-link>
      </nav>

      <!-- Logout -->
      <div class="px-3 py-4 border-t border-slate-700">
        <button @click="handleLogout"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors">
          <PowerIcon class="w-5 h-5 shrink-0" />
          Logout
        </button>
      </div>
    </aside>

    <!-- Main content area -->
    <div class="flex-1 flex flex-col min-h-screen w-full min-w-0 lg:ml-64 overflow-x-hidden">

      <!-- Top bar for mobile hamburger + page context -->
      <header class="sticky top-0 z-20 bg-slate-900 border-b border-slate-800 lg:hidden">
        <div class="flex items-center gap-4 px-4 h-14">

          <!-- Hamburger -->
          <button @click="sidebarOpen = true"
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
            aria-label="Open menu">
            <Bars3Icon class="w-6 h-6" />
          </button>

          <!-- Current page name -->
          <span class="text-white font-semibold text-sm">
            {{navItems.find(n => n.route === route.name)?.name ?? 'Admin'}}
          </span>

          <!-- Logo on right -->
          <span class="ml-auto text-slate-400 text-xs">
            Portfolio Admin
          </span>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-3 sm:p-5 lg:p-8 overflow-x-hidden w-full">
        <slot />
      </main>
    </div>
  </div>
</template>
