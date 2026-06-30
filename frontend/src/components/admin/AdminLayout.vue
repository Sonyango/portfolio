<script setup>
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
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
} from '@heroicons/vue/24/outline';

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

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
]

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'admin.login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex">
    <ToastNotification />

    <!-- Sidebar -->
     <aside class="w-64 bg-slate-800 border-r border-slate-700 flex flex-col fixed h-full">

      <!-- Brand -->
       <div class="px-6 py-5 border-b border-slate-700">
        <h1 class="text-lg font-bold text-white">Portfolio Admin</h1>
        <p class="text-xs text-slate-400 mt-0 5">{{ authStore.user?.name }}</p>
       </div>

       <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <router-link
            v-for="item in navItems"
            :key="item.route"
            :to="{ name: item.route }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
            :class="route.name === item.route ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white'"
          >
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.name }}
          </router-link>
        </nav>

        <!-- Logout -->
         <div class="px-3 py-4 border-t border-slate-700">
          <button
            @click="handleLogout"
            class="w-full flex items-center gap-3 px-3 py-2 5 rounded-xl text-sm font-medium text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors">
            <PowerIcon class="w-5 h-5 shrink-0" />
            Logout
          </button>
         </div>
     </aside>

     <!-- Main content -->
      <main class="flex-1 ml-64 p-8">
        <slot />
      </main>
  </div>
</template>
