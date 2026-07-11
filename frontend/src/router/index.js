import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'


// Public views (lazy loaded)
const HomeView = () => import('@/views/public/HomeView.vue')
const ProjectsView = () => import('@/views/public/ProjectsView.vue')
const BlogView = () => import('@/views/public/BlogView.vue')
const ContactView = () => import('@/views/public/ContactView.vue')
const ProjectDetail = () => import('@/views/public/ProjectDetai.vue')
const PostDetail  = () => import('@/views/public/PostDetail.vue')

// Admin views (lazy loaded)
const AdminLogin = () => import('@/views/admin/AdminLogin.vue')
const AdminDashboard = () => import('@/views/admin/AdminDashboard.vue')
const AdminProjects = () => import('@/views/admin/AdminProjects.vue')
const AdminPosts = () => import('@/views/admin/AdminPosts.vue')
const AdminSkills = () => import('@/views/admin/AdminSkills.vue')
const AdminExperiences = () => import('@/views/admin/AdminExperiences.vue')
const AdminServices = () => import('@/views/admin/AdminServices.vue')
const AdminSettings = () => import('@/views/admin/AdminSettings.vue')
const AdminMessages = () => import('@/views/admin/AdminMessages.vue')
const AdminMedia = () => import('@/views/admin/AdminMedia.vue')

const routes = [
  // **** Public routes ******
  { path: '/',  name: 'home', component: HomeView },
  { path: '/projects',  name: 'projects', component:  ProjectsView  },
  { path: '/blog',  name: 'blog', component:  BlogView  },
  { path: '/contact', name: 'contact',  component:  ContactView },
  { path: '/projects/:slug', name: 'project.detail', component: ProjectDetail },
  { path: '/blog/:slug',  name: 'post.detail',  component: PostDetail },

  // **** Admin routes (requires auth) ********
  { path: '/admin/login', name: 'admin.login',  component:  AdminLogin, meta: { guestOnly: true }},

  { path: '/admin', name: 'admin.dashboard',  component:  AdminDashboard, meta: { requiresAuth: true } },
  { path: '/admin/projects', name: 'admin.projects', component: AdminProjects, meta: { requiresAuth: true } },
  { path: '/admin/posts', name: 'admin.posts', component: AdminPosts, meta: { requiresAuth: true } },
  { path: '/admin/skills', name: 'admin.skills', component: AdminSkills, meta: { requiresAuth: true } },
  { path: '/admin/experiences', name: 'admin.experiences', component: AdminExperiences, meta: { requiresAuth: true } },
  { path: '/admin/services', name: 'admin.services', component: AdminServices, meta: { requiresAuth: true } },
  { path: '/admin/settings', name: 'admin.settings', component: AdminSettings, meta: { requiresAuth: true } },
  { path: '/admin/messages', name: 'admin.messages', component: AdminMessages, meta: { requiresAuth: true } },
  { path: '/admin/media', name: 'admin.media', component: AdminMedia, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})


// **** Navigation Guard *******
router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.user) {
    await authStore.fetchMe()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'admin.login' }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: 'admin.dashboard' }
  }
})

export default router
