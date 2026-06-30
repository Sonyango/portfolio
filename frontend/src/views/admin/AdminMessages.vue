<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/admin/AdminLayout.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import ConfirmModal from '@/components/admin/ConfirmModal.vue';
import { useApi } from '@/composables/useApi';
import { useUiStore } from '@/stores/uiStore';
import { EnvelopeOpenIcon, TrashIcon } from '@heroicons/vue/24/outline';

const { get, patch, del } = useApi()
const uiStore   = useUiStore()
const messages  = ref([])
const selected  = ref(null)
const deleteId  = ref(null)

async function fetchMessages() {
  const { data } = await get('/admin/messages')
  if (data) messages.value  = data.data
}

async function markRead(message) {
  if (message.read_at) return
  await patch(`/admin/messages/${message.id}/read`)
  message.read_at = new Date().toISOString()
}

function openMessage(message) {
  selected.value = message
  markRead(message)
}

async function handleDelete() {
  const { success } = await del(`/admin/messages/${deleteId.value}`)
  if (success) {
    uiStore.success('Message deleted.')
    deleteId.value = null
    selected.value = null
    await fetchMessages()
  }
}

onMounted(fetchMessages)
</script>

<template>
  <AdminLayout>
    <PageHeader title="Messages" subtitle="Contact form submissions from visitors" />

    <div class="grid grid-cols-3 gap-6">

      <!--Messages list-->
      <div class="col-span-1 space-y-2">
        <div v-if="messages.length === 0"
          class="bg-slate-800 rounded-2xl border border-slate-700 p-8
                 text-center text-slate-400 text-sm">
          No messages yet.
        </div>

        <div
          v-for="msg in messages"
          :key="msg.id"
          @click="openMessage(msg)"
          :class="['bg-slate-800 rounded-xl border p-4 cursor-pointer transition-colors',
            selected?.id === msg.id
              ? 'border-indigo-500'
              : 'border-slate-700 hover:border-slate-600',
            !msg.read_at ? 'border-l-2 border-l-indigo-500' : '']">
          <div class="flex items-center justify-between mb-1">
            <p :class="['text-sm font-medium',
              !msg.read_at ? 'text-white' : 'text-slate-300']">
              {{ msg.name }}
            </p>
            <span v-if="!msg.read_at"
              class="w-2 h-2 rounded-full bg-indigo-500 shrink-0" />
          </div>
          <p class="text-slate-400 text-xs truncate">{{ msg.subject }}</p>
          <p class="text-slate-500 text-xs mt-1">{{ msg.email }}</p>
        </div>
      </div>

      <!--Message detail-->
      <div class="col-span-2">
        <div v-if="!selected"
          class="bg-slate-800 rounded-2xl border border-slate-700 p-12
                 flex items-center justify-center text-slate-400">
          <div class="text-center">
            <EnvelopeOpenIcon class="w-12 h-12 max-auto mb-3 opacity-30" />
            <p>Select a message to read it</p>
          </div>
        </div>

        <div v-else class="bg-slate-800 rounded-2xl border border-slate-700 p-6">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h3 class="text-white text-lg font-semibold">
                {{ selected.subject }}
              </h3>
              <p class="text-slate-400 text-sm mt-1">
                From <span class="text-indigo-400">{{ selected.name }}</span>
                &lt;{{ selected.email }}&gt;
              </p>
            </div>
            <button @click="deleteId = selected.id"
              class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10
                     rounded-lg transition-colors">
              <TrashIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="bg-slate-900 rounded-xl p-4 text-slate-300 text-sm
                      leading-relaxed whitespace-pre-wrap">
              {{ selected.message }}
          </div>

          <div class="mt-4 flex items-center justify-between">
            <p class="text-slate-500 text-xs">
              Received: {{ new Date(selected.created_at).toLocaleString() }}
            </p>
            <span class="text-xs text-green-400">✓ Read</span>
          </div>
        </div>
      </div>
    </div>

    <ConfirmModal
      :show="!!deleteId"
      title="Delete message?"
      message="This message will be permanently deleted."
      @confirm="handleDelete"
      @cancel="deleteId = null"
    />
  </AdminLayout>
</template>
