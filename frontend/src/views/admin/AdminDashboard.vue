<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import { useAuthStore } from '@/stores/authStore';
import { useApi } from '@/composables/useApi';
import { FolderOpenIcon, PencilSquareIcon,
          EnvelopeIcon, BoltIcon,
 } from '@heroicons/vue/24/outline';

const authStore = useAuthStore()
const { get }   = useApi()

const stats = ref({
  projects: 0, posts: 0, messages: 0, skills: 0
})

async function fetchStats() {
  const [projects, posts, messages, skills] = await Promise.all([
    get('/admin/projects'),
    get('/admin/posts'),
    get('/admin/messages'),
    get('/admin/skills'),
  ])

  stats.value = {
    projects: projects.data?.data?.length || 0,
    posts:    posts.data?.data?.length    || 0,
    messages: messages.data?.data?.length || 0,
    skills:   skills.data?.data?.length   || 0,
  }
}

const statCards = [
  { label: 'Projects', key: 'projects', icon: FolderOpenIcon,
    color: 'text-indigo-400', bg: 'bg-indigo-500/10', route: 'admin.projects'
  },
  { label: 'Blog Post', key: 'posts', icon: PencilSquareIcon,
    color: 'text-violet-400', bg: 'bg-violet-500/10', route: 'admin.posts'
  },
  { label: 'Messages', key: 'messages', icon: EnvelopeIcon,
    color: 'text-sky-400', bg: 'bg-sky-500/10', route: 'admin.messages'
  },
  { label: 'Skills', key: 'skills', icon: BoltIcon,
    color: 'text-amber-400', bg: 'bg-amber-500/10', route: 'admin.skills'
  }
]

onMounted(fetchStats)
</script>

<template>
  <AdminLayout>
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-white">
        Welcome back, {{ authStore.user?.name }} 👋
      </h2>
      <p class="text-slate-400 mt-1">Here's your portfolio at a glance.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <router-link
        v-for="card in statCards"
        :key="card.key"
        :to="{ name: card.route }"
        class="bg-slate-800 border border-slate-700 hover:border-slate-600
               rounded-2xl p-5 transition-colors group"
      >
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3',
                       card.bg]">
          <component :is="card.icon" :class="['w-5 h-5', card.color]" />
        </div>
        <p class="text-3xl font-bold text-white mb-1">{{ stats[card.key] }}</p>
        <p class="text-slate-400 text-sm">{{ card.label }}</p>
      </router-link>
    </div>

    <!-- Quick Actions -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
      <h3 class="text-white font-semibold mb-4">Quick Actions</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <router-link
          v-for="action in [
            { label: '+ Add Project',    route: 'admin.projects' },
            { label: '+ Write Post',     route: 'admin.posts' },
            { label: '+ Add Skill',      route: 'admin.skills' },
            { label: '+ Add Experience', route: 'admin.experiences' },
            { label: '+ Add Service',    route: 'admin.services' },
            { label: '⚙ Settings',       route: 'admin.settings' },
          ]"
          :key="action.route"
          :to="{ name: action.route }"
          class="px-4 py-2.5 border border-slate-700 hover:border-indigo-500
                 hover:text-indigo-400 text-slate-300 rounded-xl text-sm
                 font-medium transition-colors text-center"
        >
          {{ action.label }}
        </router-link>
      </div>
    </div>
  </AdminLayout>
</template>
