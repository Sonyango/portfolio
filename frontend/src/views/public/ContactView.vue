<script setup>
import { ref } from 'vue';
import PublicLayout from '@/components/public/PublicLayout.vue';
import { useUiStore } from '@/stores/uiStore';
import api from '@/api/index.js';
import { useSettingsStore } from '@/stores/settingsStore';
import {
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon,
  PaperAirplaneIcon,
 } from '@heroicons/vue/24/outline';
 import { useSeo } from '@/composables/useSeo';

 useSeo({
  title:        'Contact',
  description:  'Get in touch for freelance, collaboraton, or job opportunities.',
  url:          window.location.href,
 })

const uiStore         = useUiStore()
const settingsStore   = useSettingsStore()

const form = ref({
  name:     '',
  email:    '',
  subject:  '',
  message:  '',
})

const submitting  = ref(false)
const submitted   = ref(false)
const errors      = ref({})

async function handleSubmit() {
  submitting.value  = true
  errors.value      = {}

  try {
    await api.post('/contact', form.value)
    submitted.value = true
    uiStore.success('Message sent! I will get back to you soon.')
    form.value = { name: '', email: '', subject: '', message: ''}
  } catch (err) {
      if (err.response?.status === 422) {
        errors.value = err.response.data.errors ?? {}
      } else if (err.response?.status === 429) {
        uiStore.error('Too many requests. please wait a moment and try again.')
      } else {
        uiStore.error('Failed to send message. Please try again.')
      }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <PublicLayout>
    <section class="py-24 px-4">
      <div class="max-w-6xl mx-auto">

        <!-- Header -->
         <div class="text-center mb-16">
          <p class="text-indigo-400 text-sm font-semibold tracking-widest uppercase mb-3">
            Get In Touch
          </p>
          <h1 class="font-display text-5xl font-bold text-white mb-4">Contact Me</h1>
          <p class="text-slate-400 max-w-xl mx-auto">
            Have a project in mind, a question, or just want to say hi?
            I'd love to hear from you.
          </p>
         </div>

         <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
          <!-- Contact info -->
           <div class="space-y-6">
            <div
            v-for="info in [
              {
                  icon: EnvelopeIcon,
                  label: 'Email',
                  value: settingsStore.get('email'),
                  href: 'mailto:' + settingsStore.get('email'),
                },
                {
                  icon: PhoneIcon,
                  label: 'Phone',
                  value: settingsStore.get('phone'),
                  href: 'tel:' + settingsStore.get('phone'),
                },
                {
                  icon: MapPinIcon,
                  label: 'Location',
                  value: settingsStore.get('location'),
                  href: null,
                },
            ].filter(i => i.value)"
            :key="info.label"
            class="flex items-start gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center shrink-0">
              <component :is="info.icon" class="=w-5 h-5 text-indigo-400" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-1">
                {{ info.label }}
              </p>
              <a v-if="info.href"
                :href="info.href"
                class="text-white hover:text-indigo-400 transition-colors text-sm font-medium">
                {{ info.value }}
              </a>
              <p v-else class="text-white text-sm font-medium">
                {{ info.value }}
              </p>
            </div>
          </div>

          <!-- Social links -->
           <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <p class="text-slate-400 text-xs font-medium uppercase tracking-wider mb-4">
              Connect
            </p>
            <div class="space-y-2">
              <a
                v-for="social in [
                  { label: 'GitHub',     key: 'github_url' },
                  { label: 'LinkedIn',   key: 'linkedin_url' },
                  { label: 'Twitter/X',  key: 'twitter_url' },
                ].filter(s => settingsStore.get(s.key))"
                :key="social.key"
                :href="settingsStore.get(social.key)"
                target="_blank"
                class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-slate-800 text-slate-300 hover:text-white transition-colors text-sm">
                {{ social.label }}
                <span class="text-slate-500 text-xs">↗</span>
              </a>
            </div>
           </div>
           </div>

           <!--Contact form-->
           <div class="lg:col-span-2">
            <!-- Success state -->
             <div v-if="submitted"
                class="bg-green-500/10 border border-green-500/20 rounded-2xl p-10 text-center">
                <div class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-4">
                  <PaperAirplaneIcon class="w-7 h-7 text-green-400" />
                </div>
                <h3 class="text-white font-semibold text-xl mb-2">Message sent!</h3>
                <p class="text-slate-400 text-sm mb-6">
                  Thank you for reaching out. I'll get back to you as soon as possible.
                </p>
                <button
                  @click="submitted = false"
                  class="px-6 py-2.5 border border-slate-700 text-slate-300 hover:text-white hover:border-slate-500 rounded-xl text-sm font-medium transition-colors">
                  Send Another Message
                </button>
              </div>

              <!-- Form -->
               <div v-else class="bg-slate-900 border border-slate-800 rounded-2xl p-8">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">

                  <!-- Name -->
                   <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                      Name <span class="text-red-400">*</span>
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      placeholder="Your full name"
                      :class="['w-full bg-slate-800 border rounded-xl px-4 py-3 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                      errors.name ? 'border-red-500' : 'border-slate-700']" />
                      <p v-if="errors.name" class="mt-1 text-xs text-red-400">
                        {{ errors.name[0] }}
                      </p>
                   </div>
                   <!-- Email -->
                    <div>
                      <label class="block text-sm font-medium text-slate-300 mb-1">
                        Email <span class="text-red-400">*</span>
                      </label>
                      <input
                      v-model="form.email"
                      type="email"
                      placeholder="your@email.com"
                      :class="['w-full bg-slate-800 border rounded-xl px-4 py-3 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                      errors.email ? 'border-red-500' : 'border-slate-700']" />
                      <p v-if="errors.email" class="mt-1 text-xs text-red-400">
                        {{ errors.email[0] }}
                      </p>
                    </div>
                </div>

                <!-- Subject -->
                 <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1">
                    Subject <span class="text-red-400">*</span>
                  </label>
                    <input
                      v-model="form.subject"
                      type="text"
                      placeholder="What's this about?"
                      :class="['w-full bg-slate-800 border rounded-xl px-4 py-3 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                        errors.subject ? 'border-red-500' : 'border-slate-700']"
                    />
                    <p v-if="errors.subject" class="mt-1 text-xs text-red-400">
                      {{ errors.subject[0] }}
                    </p>
                 </div>
                 <!-- Message -->
                  <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-1">
                      Message <span class="text-red-400">*</span>
                    </label>
                        <textarea
                      v-model="form.message"
                      :rows="6"
                      placeholder="Tell me about your project or question..."
                      :class="['w-full bg-slate-800 border rounded-xl px-4 py-3 text-white placeholder-slate-500 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500',
                        errors.message ? 'border-red-500' : 'border-slate-700']"
                    />
                    <div class="flex items-center justify-between mt-1">
                      <p v-if="errors.message" class="text-xs text-red-400">
                        {{ errors.message[0] }}
                      </p>
                      <p class="text-xs text-slate-500 ml-auto">
                        {{ form.message.length }} / 2000
                      </p>
                    </div>
                  </div>
                  <!-- Submit -->
                   <button
                    @click="handleSubmit"
                    :disabled="submitting"
                    class="w-full flex items-center justify-center gap-2 py-3.5
                       bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50
                       text-white font-medium rounded-xl transition-colors">
                       <PaperAirplaneIcon class="w-4 h-4" />
                       {{ submitting ? 'Sending...' : 'Send Message' }}
                    </button>
               </div>
           </div>
         </div>
      </div>
    </section>
  </PublicLayout>
</template>
