export function useSeo({
  title       = 'Portfolio',
  description = 'Full Stack Developer & ICT Professional',
  image       = '',
  url         = '',
} = {}) {
  const siteName  = 'Stephen\'s Portfolio'
  const fullTitle = title === siteName ? title : `${title} | ${siteName}`

  document.title = fullTitle

  function setMeta(attr, attrVal, content) {
    let el = document.querySelector(`meta[${attr}="${attrVal}"]`)
    if (!el) {
      el = document.createElement('meta')
      el.setAttribute(attr, attrVal)
      document.head.appendChild(el)
    }
    el.setAttribute('content', content)
  }

  setMeta('name',     'description',         description)
  setMeta('property', 'og:title',            fullTitle)
  setMeta('property', 'og:description',      description)
  setMeta('property', 'og:type',             'website')
  setMeta('property', 'og:url',              url || window.location.href)
  setMeta('property', 'og:image',            image)
  setMeta('property', 'og:site_name',        siteName)
  setMeta('name',     'twitter:card',        'summary_large_image')
  setMeta('name',     'twitter:title',       fullTitle)
  setMeta('name',     'twitter:description', description)
  setMeta('name',     'twitter:image',       image)
}
