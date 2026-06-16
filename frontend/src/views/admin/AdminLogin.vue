<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const error = ref('');

async function handleLogin() {
  error.value = '';
  const result = await authStore.login(email.value, password.value);
  if (result.success) {
    router.push({ name: 'admin.dashboard' })
  } else {
    error.value = result.message
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 flex items-center justify-center px-4">
    <div class="w-full max-w-md">

      <!-- Logo / title -->
       <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white font-display">Portfolio</h1>
        <p class="text-slate-400 mt-1">Admin Dashboard</p>
       </div>

       <!-- Card -->
        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">
          <h2 class="text-xl font-semibold text-white mb-6">Sign in</h2>

          <!-- Error -->
           <div v-if="error"
              class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
            {{ error }}
           </div>

           <!-- Form -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Email</label>
                <input
                  v-model="email"
                  type="email"
                  placeholder="admin@portfolio.test"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5
                        text-white placeholder-slate-500 focus:outline-none
                        focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Password</label>
                <input
                  v-model="password"
                  type="password"
                  placeholder="••••••••"
                  @keyup.enter="handleLogin"
                  class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5
                        text-white placeholder-slate-500 focus:outline-none
                        focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
              </div>

              <button
                @click="handleLogin"
                :disabled="authStore.loading"
                class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50
                      text-white font-medium py-2.5 rounded-xl transition-colors mt-2">
                {{ authStore.loading ? 'Signing in...' : 'Sign in' }}
              </button>
            </div>
        </div>
    </div>
  </div>
</template>
