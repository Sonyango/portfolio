<script setup>
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore()
</script>

<template>
  <AdminLayout>
    <div>
      <h2 class="text-2xl font-bold text-white mb-1">
        Welcome back, {{ authStore.user?.name }} 👋
      </h2>
      <p class="text-slate-400 mb-8">Here's what's happening with your portfolio.</p>

      <!-- Quick links-->
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <router-link
          v-for="link in [
            { label: 'Manage Projects', route: 'admin.projects', color: 'indigo'},
            { label: 'Write a Post', route: 'admin.posts', color: 'Violet'},
            { label: 'View Messages', route: 'admin.messages', color: 'sky'},
          ]"
          :key="link.route"
          :to="{ name: link.route }"
          class="bg-slate-800 border border-slate-700 hover:border-indigo-500 rounded-2xl p-6 text-white font-medium transition-colors"
        >
          {{ link.label }}
        </router-link>
       </div>
    </div>
  </AdminLayout>
</template>
