<script setup>
defineProps({
  post: {type: Object, required: true }
})
</script>

<template>
  <router-link
      :to="'/blog/' + post.slug"
      class="block bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden hover:border-indigo-slate-500/40 transition-all group">
      <!-- Thumbnail -->
       <div class="aspect-video bg-slate-800 overflow-hidden">
        <img
          v-if="post.thumbnail"
          :src="post.thumbnail"
          :alt="post.title"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          loading="lazy" />
        <div v-else
          class="w-full h-full flex items-center justify-center text-slate-600 text-sm">
          No image
        </div>
       </div>

       <!-- Body -->
        <div class="p-5">
          <!-- Categories -->
           <div class="flex flex-wrap gap-2 mb-3">
            <span v-for="cat in post.categories ?? []"
              :key="cat.id"
              class="text-indigo-400 text-xs font-semibold uppercase tracking-wider">
              {{ cat.name }}
            </span>
           </div>

           <h3 class="text-white font-semibold text-lg mb-2 group-hover:text-indigo-400 transition-color line-clamp-2">
            {{ post.title }}
           </h3>

           <p class="text-slate-400 text-sm leading-relaxed mb-4 line-clamp-3">
            {{ post.excerpt }}
           </p>

           <!-- Meta -->
            <div class="flex items-center justify-between text-xs text-slate-500">
              <span>{{ post.published_at }}</span>
              <span class="text-indigo-400 font-medium">Read more →</span>
            </div>
        </div>
  </router-link>
</template>
