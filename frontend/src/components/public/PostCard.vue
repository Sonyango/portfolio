<script setup>
defineProps({
  post: {type: Object, required: true }
})
</script>

<template>
  <router-link
      :to="'/blog/' + post.slug"
      class="block rounded-2xl overflow-hidden border transition-all group dark:bg-slate-900 dark:border-slate-800
            dark:hover:border-indigo-500/40 bg-[#0B2B26] border-[#1A4A42] hover:border-[#00F0A0]/40">
      <!-- Thumbnail -->
       <div class="aspect-video overflow-hidden dark:bg-slate-800 bg-[#0D3530]">
        <img
          v-if="post.thumbnail"
          :src="post.thumbnail"
          :alt="post.title"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          loading="lazy" />
        <div v-else
          class="w-full h-full flex items-center justify-center text-sm
                dark:text-slate-600 text-[#1A4A42]">
          No image
        </div>
       </div>

       <!-- Body -->
        <div class="p-5">
          <!-- Categories -->
           <div class="flex flex-wrap gap-2 mb-3">
            <span v-for="cat in post.categories ?? []"
              :key="cat.id"
              class="text-xs font-semibold uppercase tracking-wider
                    dark:text-indigo-400 text-[#00F0A0]">
              {{ cat.name }}
            </span>
           </div>

           <h3 class="font-semibold text-lg mb-2 transition-color line-clamp-2 dark:text-white
                      dark:group-hover:text-indigo-400 text-[#00F0A0] group-hover:text-white">
            {{ post.title }}
           </h3>

           <p class="text-sm leading-relaxed mb-4 line-clamp-3 dark:text-slate-400 text-[#B2DFDB]">
            {{ post.excerpt }}
           </p>

           <!-- Meta -->
            <div class="flex items-center justify-between text-xs dark:text-slate-500 text-[#7BB8B2]">
              <span>{{ post.published_at }}</span>
              <span class="font-medium dark:text-indigo-400 text-[#00F0A0]">Read more →</span>
            </div>
        </div>
  </router-link>
</template>
