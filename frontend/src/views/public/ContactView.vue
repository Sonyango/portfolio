<script setup>
import { ref, computed, watch } from 'vue';
import PublicLayout from '@/components/public/PublicLayout.vue';
import { useUiStore } from '@/stores/uiStore';
import api from '@/api/index.js';
import { useSettingsStore } from '@/stores/settingsStore';
import {
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon,
  PaperAirplaneIcon,
  ShieldCheckIcon,
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
const lastSubmitTime = ref(0)
const submitCount = ref(0)

const serverError = ref('')

// Validation rules
const validations = computed(() => ({
  name: {
    valid: form.value.name.trim().length >= 2 &&
            form.value.name.trim().length <= 50,
    message: form.value.name.trim().length === 0
      ? 'Name is required.'
      : form.value.name.trim().length < 2
        ? 'Name must be at least 2 characters.'
        : '',
  },
  email: {
    valid: /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email.trim()),
    message: form.value.email.trim().length === 0
      ? 'Email is required.'
      : 'Please enter a valid email address.',
  },
  subject: {
    valid: form.value.subject.trim().length >= 3 &&
            form.value.subject.trim().length <= 200,
    message: form.value.subject.trim().length === 0
      ? 'Subject is required.'
      : form.value.subject.trim().length < 3
        ? 'Subject must be at least 3 characters.'
        : '',
  },
  message: {
    valid: form.value.message.trim().length >= 10 &&
            form.value.message.trim().length <= 2000,
    message: form.value.message.trim().length === 0
      ? 'Message is required.'
      : form.value.message.trim().length < 10
        ? 'Message must be at least 10 characters.'
        : form.value.message.trim().length > 2000
          ? 'Message cannot exceed 2000 characters.'
          : '',
  }
}))

// Track which fields have been touched
const touched = ref({
  name: false,
  email: false,
  subject: false,
  message: false,
})

function touch(field) {
  touched.value[field] = true
}

// Show error only after field is touched
function fieldError(field) {
  // Show backend error first if it exists
  if (errors.value[field]?.[0]) {
    return errors.value[field][0]
  }
  // Then show client-side validation error if field is touched.
  if (!touched.value[field]) return ''
  return validations.value[field].valid
    ? ''
    : validations.value[field].message
}

// Form is valid if all fields pass validation
const isFormValid = computed(() => {
  return Object.values(validations.value).every(v => v.valid)
})

// Client-side rate limiting
const canSubmit = computed(() => {
  return isFormValid.value &&
         !submitting.value &&
         submitCount.value < 5
})

// Honeypot field for spam prevention
const honeypot = ref('')

// Detect suspecious content
function containsSuspeciousContent(text) {
  const patterns = [
    /<script/i,
    /javascript:/i,
    /on\w+\s*=/i,
    /\beval\s*\(/i,
    /\bdocument\./i,
    /\bwindow\./i,
    /https?:\/\/.{3,}/i,
  ]
  return patterns.some(p => p.test(text))
}

// Submit handler
async function handleSubmit() {
  if (!canSubmit.value) return

  // Touch all fields to show any remaining errors
  Object.keys(touched.value).forEach(f => touched.value[f] = true)

  if (!isFormValid.value) return

  // Honeypot check
  if (honeypot.value) {
    // Silently pretend success without alerting the bot it failed
    submitted.value = true
    return
  }

  // Client-side rate limit
  const now = Date.now()
  const elapsed = now - lastSubmitTime.value
  if (elapsed < 3000) {
    uiStore.error('Please wait a moment before sending anothet message.')
    return
  }

  // Check for suspecious content
  const fieldsToCheck = [
    form.value.name,
    form.value.subject,
    form.value.message,
  ]
  if (fieldsToCheck.some(containsSuspeciousContent)) {
    errors.value = { message: ['Your message contains invalid content.'] }
    //uiStore.error('Your message contains invalid content. please remove them and try again.')
    return
  }

  submitting.value  = true
  lastSubmitTime.value = now
  errors.value      = {}
  serverError.value = ''

  try {
    await api.post('/contact', {
      name: form.value.name.trim(),
      email: form.value.email.trim().toLowerCase(),
      subject: form.value.subject.trim(),
      message: form.value.message.trim(),
    })

    submitCount.value++
    submitted.value = true
    //uiStore.success('Message sent! I will get back to you soon.')
    form.value = { name: '', email: '', subject: '', message: ''}
    touched.value = { name: false, email: false, subject: false, message: false }

  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {}
      //uiStore.error('Please fix the errors below and try again.')
      serverError.value = err.response.data.message || 'Please fix the errors below and try again.'
    }
    else if (err.response?.status === 429) {
      //uiStore.error('Too many messages sent. please wait before sending another message.')
      serverError.value = err.response.data.message ||
  'Too many messages sent. please wait before sending another message.'
    } else {
      //uiStore.error('Failed to send message. Please try again.')
      serverError.value = err.response.data.message || 'Failed to send message. Please try again.'
    }
  } finally {
    submitting.value = false
  }

  // try {
  //   await api.post('/contact', form.value)
  //   submitted.value = true
  //   uiStore.success('Message sent! I will get back to you soon.')
  //   form.value = { name: '', email: '', subject: '', message: ''}
  // } catch (err) {
  //     if (err.response?.status === 422) {
  //       errors.value = err.response.data.errors ?? {}
  //     } else if (err.response?.status === 429) {
  //       uiStore.error('Too many requests. please wait a moment and try again.')
  //     } else {
  //       uiStore.error('Failed to send message. Please try again.')
  //     }
  // } finally {
  //   submitting.value = false
  // }

  watch(() => form.value, () => {
    if (serverError.value) {
      serverError.value = ''
    }
  }, { deep: true })
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
            class="flex items-start gap-4 rounded-2xl p-5 border min-w-0 break-all
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

           <!-- Security badge -->
            <div class="flex items-center gap-2 px-4 py-3 rounded-xl border
                        dark:bg-slate-900/50 dark:border-slate-800 bg-[#0D3530]/50 border-[#1A4A42]">
                <ShieldCheckIcon class="w-4 h-4 shrink-0 dark:text-green-400 text-[#00F0A0]" />
                <p class="text-xs dark:text-slate-400 text-[#7BB8B2]">
                  Your information is safe and secure. I respect your privacy and will never share your details with third parties.
                </p>
            </div>
           </div>

           <!--Contact form-->
           <div class="lg:col-span-2">

            <!-- Rate limiting warnning -->
             <div v-if="submitCount >= 5"
                class="rounded-2xl p-6 text-center border mb-4 dark:bg-amber-500/10 dark:border-amber-500/20
                      bg-[#00F0A0]/5 border-[#00F0A0]/20">
                <p class="font-semibold mb-1 dark:text-amber-400 text-[#00F0A0]">
                  Message limit reached!!
                </p>
                <p class="text-sm dark:text-slate-400 text-[#B2DFDB]">
                  You've sent the maximum number of messages for this session.
                  Please try again later or contact me directly by email or phone.
                </p>
              </div>

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

              <!-- Server error banner - shows backend errors prominently -->
               <transition
                  enter-active-class="transition-all duration-300 eas-out"
                  enter-from-class="opacity-0 -translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition-all duration-200 ease-in"
                  leave-from-class="opacity-100 translate-y-0"
                  leave-to-class="opacity-0 -translate-y-2"
               >
               <div v-if="serverError"
                    class="mb-6 flex items-start gap-3 px-4 py-4 rounded-xl border
                          bg-red-500/10 border-red-500/30">
                    <div class="w-5 h-5 rounded-full bg-red-500/20 flex items-center justify-center shrink-0 mt-0.5">
                      <span class="text-red-400 text-xs font-bold">!</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-red-400 mb-0.5">
                        Unable to send message
                      </p>
                      <p class="text-xs text-red-300/80">
                        {{ serverError }}
                      </p>
                    </div>
                    <button
                      @click="serverError = ''"
                       class="text-red-400 hover:text-red-300 transition-colors text-lg leading-none shrink-0"
                       title="Dismiss">
                       x
                    </button>
                </div>
              </transition>

              <!-- Honeypot, hidden from real users filled by bots -->
               <div class="absolute opacity-0 pointer-events-none h-0 overflow-hidden"
                    aria-hidden="true">
                  <input
                    v-model="honeypot"
                    type="text"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                  />
                </div>

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
                      maxlength="50"
                      autocomplete="name"
                      @blur="touch('name')"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                             'focus:outline-none focus:ring-2 transition-colors',
                             'dark:bg-slate-800 dark:text-white',
                             'dark:placeholder-slate-500',
                             'bg-[#0B2B26] text-[#B2DFDB]',
                             'placeholder-[#7BB8B2]',
                             fieldError('name')
                                ? 'border-red-500 focus:ring-red-500/50'
                                : touched.name && validations.name.valid
                                  ? 'dark:border-green-600 border-[#00F0A0]/50 focus:ring-indigo-500'
                                  : 'dark:border-slate-700 border-[#1A4A42] focus:ring-[#00F0A0]/50']"
                      />
                      <p v-if="fieldError('name')" class="mt-1 text-xs text-red-400">
                        {{ fieldError('name') }}
                      </p>
                      <p v-else-if="touched.name && validations.name.valid"
                        class="mt-1 text-xs dark:text-green-400 text-[#00F0A0]">
                        Valid name
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
                      maxlength="100"
                      autocomplete="email"
                      @blur="touch('email')"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                             'focus:outline-none focus:ring-2 transition-colors',
                             'dark:bg-slate-800 dark:text-white',
                             'dark:placeholder-slate-500',
                             'bg-[#0B2B26] text-[#B2DFDB]',
                             'placeholder-[#7BB8B2]',
                             fieldError('email')
                                ? 'border-red-500 focus:ring-red-500/50'
                                : touched.email && validations.email.valid
                                  ? 'dark:border-green-600 border-[#00F0A0]/50 focus:ring-indigo-500'
                                  : 'dark:border-slate-700 border-[#1A4A42] focus:ring-[#00F0A0]/50']"
                      />
                      <p v-if="fieldError('email')" class="mt-1 text-xs text-red-400">
                        {{ fieldError('email') }}
                      </p>
                      <p v-else-if="touched.email && validations.email.valid"
                          class="mt-1 text-xs dark:text-green-400 text-[#00F0A0]">
                        Valid email
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
                      maxlength="200"
                      @blur="touch('subject')"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                           'focus:outline-none focus:ring-2 transition-colors',
                           'dark:bg-slate-800 dark:text-white',
                           'dark:placeholder-slate-500',
                           'bg-[#0B2B26] text-[#B2DFDB]',
                           'placeholder-[#7BB8B2]',
                           fieldError('subject')
                              ? 'border-red-500 focus:ring-red-500/50'
                              : touched.subject && validations.subject.valid
                                ? 'dark:border-green-600 border-[#00F0A0]/50 focus:ring-indigo-500'
                                : 'dark:border-slate-700 border-[#1A4A42] focus:ring-[#00F0A0]/50']"
                    />
                    <p v-if="fieldError('subject')" class="mt-1 text-xs text-red-400">
                      {{ fieldError('subject') }}
                    </p>
                    <p v-else-if="touched.subject && validations.subject.valid"
                          class="mt-1 text-xs dark:text-green-400 text-[#00F0A0]">
                        Valid subject
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
                      maxlength="2000"
                      @blur="touch('message')"
                      :class="['w-full border rounded-xl px-4 py-3 text-sm',
                           'resize-none focus:outline-none focus:ring-2',
                           'transition-colors',
                           'dark:bg-slate-800 dark:text-white',
                           'dark:placeholder-slate-500',
                           'bg-[#0B2B26] text-[#B2DFDB]',
                           'placeholder-[#7BB8B2]',
                           fieldError('message')
                              ? 'border-red-500 focus:ring-red-500/50'
                              : touched.message && validations.message.valid
                                ? 'dark:border-green-600 border-[#00F0A0]/50 focus:ring-indigo-500'
                                : 'dark:border-slate-700 border-[#1A4A42] focus:ring-[#00F0A0]/50']"
                    />
                    <div class="flex items-center justify-between mt-1">
                      <p v-if="fieldError('message')" class="text-xs text-red-400">
                        {{ fieldError('message') }}
                      </p>
                      <p v-else-if="touched.message && validations.message.valid"
                          class="text-xs dark:text-green-400 text-[#00F0A0]">
                          Valid message
                      </p>
                      <p v-else class="text-xs invisible">placeholder</p>
                      <!-- Character counter -->
                      <p class="text-xs ml-auto"
                          :class="form.message.length > 1800
                            ? 'text-red-400'
                            : 'dark:text-slate-500 text-[#7BB8B2]'"
                      >
                        {{ form.message.length }} / 2000
                      </p>
                    </div>
                  </div>

                  <!-- Form progress indicator -->
                   <div class="mb-5">
                    <div class="flex items-center justify-between mb-1">
                      <p class="text-xs dark:text-slate-500 text-[#7BB8B2]">
                        Form Progress
                      </p>
                      <p class="text-xs font-medium dark:text-slate-400 text-[#B2DFDB]">
                        {{ Object.values(validations).filter(v => v.valid).length }}
                        / {{ Object.keys(validations).length }} fields complete
                      </p>
                    </div>
                    <div class="w-full rounded-full h-1.5 dark:bg-slate-800 bg-[#1A4A42]">

                      <div class="h-1.5 rounded-full transition-all duration-500 dark:bg-indigo-500 bg-[#00F0A0]"
                            :style="{
                              width: (Object.values(validations).filter(v => v.valid).length /
                            Object.keys(validations).length * 100) + '%'
                            }" />
                    </div>
                   </div>

                  <!-- Submit -->
                   <button
                    @click="handleSubmit"
                    :disabled="!canSubmit"
                    :title="!isFormValid
                      ? 'Please fill in all fields correctly'
                      : submitCount >= 5
                        ? 'message limit reached'
                        : ''"
                    :class="['w-full flex items-center justify-center gap-2',
                              'py-3.5 font-semibold rounded-xl transition-all',
                              canSubmit
                                ? 'dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white bg-[#00F0A0] hover:bg-white text-[#0B2B26] shadow-lg cursor-pointer'
                                : 'dark:bg-slate-700 dark:text-slate-500 bg-[#1A4A42] text-[#7BB8B2] cursor-not-allowed opacity-60']"
                    >
                       <PaperAirplaneIcon class="w-4 h-4" />
                       {{ submitting ? 'Sending...' : 'Send Message' }}
                    </button>

                    <!-- Helper text below button -->
                     <p v-if="!isFormValid"
                        class="text-center text-xs mt-3 dark:text-slate-500 text-[#7BB8B2]">
                        Please fill in all required fields correctly to send your message.
                     </p>
               </div>
           </div>
         </div>
      </div>
    </section>
  </PublicLayout>
</template>
