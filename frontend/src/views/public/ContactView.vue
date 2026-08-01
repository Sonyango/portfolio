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
    <section class="py-24 px-4 dark:bg-slate-950 bg-[#0B2B26]">
      <div class="max-w-6xl mx-auto">

        <!-- Header -->
         <div class="text-center mb-16">
          <p class="text-sm font-semibold tracking-widest uppercase mb-3
                    dark:text-indigo-400 text-[#00F0A0]">
            Get In Touch
          </p>
          <h1 class="font-display text-5xl font-bold mb-4
                     dark:text-white text-[#00F0A0]">
                     Contact Me
          </h1>
          <p class="max-w-xl mx-auto
                    dark:text-slate-400 text-[#B2DFDB]">
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
            class="flex items-start gap-4 rounded-2xl p-5 border
                     dark:bg-slate-900 dark:border-slate-800
                     bg-[#0D3530] border-[#1A4A42]">
            <div class="w-10 h-10 rounded-xl flex items-center
                          justify-center shrink-0
                          dark:bg-indigo-500/10
                          bg-[#00F0A0]/10">
              <component :is="info.icon" class="w-5 h-5 dark:text-indigo-400 text-[#00F0A0]" />
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wider mb-1
                          dark:text-slate-400 text-[#7BB8B2]">
                {{ info.label }}
              </p>
              <a v-if="info.href"
                :href="info.href"
                class="text-sm font-medium transition-colors
                         dark:text-white dark:hover:text-indigo-400
                         text-[#B2DFDB] hover:text-[#00F0A0]">
                {{ info.value }}
              </a>
              <p v-else class="text-sm font-medium dark:text-white text-[#B2DFDB]">
                {{ info.value }}
              </p>
            </div>
          </div>

          <!-- Social links -->
           <div class="rounded-2xl p-5 border
                        dark:bg-slate-900 dark:border-slate-800
                        bg-[#0D3530] border-[#1A4A42]">
            <p class="text-xs font-medium uppercase tracking-wider mb-4
                        dark:text-slate-400 text-[#7BB8B2]">
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
                class="flex items-center justify-between px-3 py-2.5
                         rounded-xl text-sm transition-colors
                         dark:text-slate-300 dark:hover:text-white
                         dark:hover:bg-slate-800
                         text-[#B2DFDB] hover:text-[#00F0A0]
                         hover:bg-[#1A4A42]">
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
                class="rounded-2xl p-10 text-center border
                     dark:bg-green-500/10 dark:border-green-500/20
                     bg-[#00F0A0]/10 border-[#00F0A0]/20">
                <div class="w-14 h-14 rounded-full flex items-center
                          justify-center mx-auto mb-4
                          dark:bg-green-500/20 bg-[#00F0A0]/20">
                  <PaperAirplaneIcon class="w-7 h-7 dark:text-green-400 text-[#00F0A0]" />
                </div>
                <h3 class="font-semibold text-xl mb-2
                         dark:text-white text-[#00F0A0]">
                         Message sent!
                </h3>
                <p class="text-sm mb-6
                        dark:text-slate-400 text-[#B2DFDB]">
                  Thank you for reaching out. I'll get back to you as soon as possible.
                </p>
                <button
                  @click="submitted = false"
                  class="px-6 py-2.5 rounded-xl text-sm font-medium
                       transition-colors border
                       dark:border-slate-700 dark:text-slate-300
                       dark:hover:text-white dark:hover:border-slate-500
                       border-[#00F0A0]/30 text-[#B2DFDB]
                       hover:border-[#00F0A0] hover:text-[#00F0A0]">
                  Send Another Message
                </button>
              </div>

              <!-- Form -->
               <div v-else class="rounded-2xl p-8 border
                     dark:bg-slate-900 dark:border-slate-800
                     bg-[#0D3530] border-[#1A4A42]">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">

                  <!-- Name -->
                   <div>
                    <label class="block text-sm font-medium mb-1
                                dark:text-slate-300 text-[#B2DFDB]">
                      Name <span class="text-red-400">*</span>
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      placeholder="Your full name"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                             'focus:outline-none focus:ring-2 transition-colors',
                             'dark:bg-slate-800 dark:text-white',
                             'dark:placeholder-slate-500',
                             'dark:focus:ring-indigo-500',
                             'bg-[#0B2B26] text-[#B2DFDB]',
                             'placeholder-[#7BB8B2]',
                             'focus:ring-[#00F0A0]/50',
                             errors.name
                               ? 'border-red-500'
                               : 'dark:border-slate-700 border-[#1A4A42]']" />
                      <p v-if="errors.name" class="mt-1 text-xs text-red-400">
                        {{ errors.name[0] }}
                      </p>
                   </div>
                   <!-- Email -->
                    <div>
                      <label class="block text-sm font-medium mb-1
                                dark:text-slate-300 text-[#B2DFDB]">
                        Email <span class="text-red-400">*</span>
                      </label>
                      <input
                      v-model="form.email"
                      type="email"
                      placeholder="your@email.com"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                             'focus:outline-none focus:ring-2 transition-colors',
                             'dark:bg-slate-800 dark:text-white',
                             'dark:placeholder-slate-500',
                             'dark:focus:ring-indigo-500',
                             'bg-[#0B2B26] text-[#B2DFDB]',
                             'placeholder-[#7BB8B2]',
                             'focus:ring-[#00F0A0]/50',
                             errors.email
                               ? 'border-red-500'
                               : 'dark:border-slate-700 border-[#1A4A42]']" />
                      <p v-if="errors.email" class="mt-1 text-xs text-red-400">
                        {{ errors.email[0] }}
                      </p>
                    </div>
                </div>

                <!-- Subject -->
                 <div class="mb-5">
                  <label class="block text-sm font-medium mb-1
                              dark:text-slate-300 text-[#B2DFDB]">
                    Subject <span class="text-red-400">*</span>
                  </label>
                    <input
                      v-model="form.subject"
                      type="text"
                      placeholder="What's this about?"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                           'focus:outline-none focus:ring-2 transition-colors',
                           'dark:bg-slate-800 dark:text-white',
                           'dark:placeholder-slate-500',
                           'dark:focus:ring-indigo-500',
                           'bg-[#0B2B26] text-[#B2DFDB]',
                           'placeholder-[#7BB8B2]',
                           'focus:ring-[#00F0A0]/50',
                           errors.subject
                             ? 'border-red-500'
                             : 'dark:border-slate-700 border-[#1A4A42]']"
                    />
                    <p v-if="errors.subject" class="mt-1 text-xs text-red-400">
                      {{ errors.subject[0] }}
                    </p>
                 </div>
                 <!-- Message -->
                  <div class="mb-6">
                    <label class="block text-sm font-medium mb-1
                              dark:text-slate-300 text-[#B2DFDB]">
                      Message <span class="text-red-400">*</span>
                    </label>
                        <textarea
                      v-model="form.message"
                      :rows="6"
                      placeholder="Tell me about your project or question..."
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                           'resize-none focus:outline-none focus:ring-2',
                           'transition-colors',
                           'dark:bg-slate-800 dark:text-white',
                           'dark:placeholder-slate-500',
                           'dark:focus:ring-indigo-500',
                           'bg-[#0B2B26] text-[#B2DFDB]',
                           'placeholder-[#7BB8B2]',
                           'focus:ring-[#00F0A0]/50',
                           errors.message
                             ? 'border-red-500'
                             : 'dark:border-slate-700 border-[#1A4A42]']"
                    />
                    <div class="flex items-center justify-between mt-1">
                      <p v-if="errors.message" class="text-xs text-red-400">
                        {{ errors.message[0] }}
                      </p>
                      <p class="text-xs ml-auto
                            dark:text-slate-500 text-[#7BB8B2]">
                        {{ form.message.length }} / 2000
                      </p>
                    </div>
                  </div>
                  <!-- Submit -->
                   <button
                    @click="handleSubmit"
                    :disabled="submitting"
                    class="w-full flex items-center justify-center gap-2
                       py-3.5 font-semibold rounded-xl transition-colors
                       disabled:opacity-50
                       dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white
                       bg-[#00F0A0] hover:bg-white text-[#0B2B26]">
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
