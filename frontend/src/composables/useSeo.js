import { useHead } from "@unhead/vue";

export function useSeo({
  title       = 'Portfolio',
  description = 'Full Stack Developer & ICT Professional',
  image       = '',
  url         = '',
  type        = 'website',
} = {}) {
  const siteName  = 'Stephen\'s Portfolio'
  const fullTitle = title === siteName ? title : `${title} | ${siteName}`

  useHead({
    title: fullTitle,
    meta: [
      { name: 'description',  content: description },

      //Open graph
      { property: 'og:title',       content: fullTitle },
      { property: 'og:description', content:  description },
      { property: 'og:type',        content:  type },
      { property: 'og:url',         content:  url },
      { property: 'og:image',       content:  image },
      { property: 'og:site_name',   content:  siteName },

      // Twitter card
      { name: 'twitter:card',         content: 'summary_large_image' },
      { name: 'twitter:title',        content: fullTitle },
      { name: 'twitter:description',  content: description },
      { name: 'twitter:image',        content: image },
    ],
    link: [
      { rel: 'canonical', href: url },
    ],
  })
}
