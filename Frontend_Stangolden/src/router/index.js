import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import LoginView from '../views/LoginView.vue'
import AdminDashboard from '../views/AdminDashboard.vue'
import SiswaDashboard from '../views/SiswaDashboard.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'landing', component: LandingPage },
    { path: '/LoginView', name: 'login', component: LoginView },
    { path: '/admin/dashboard', component: AdminDashboard },
    { path: '/dashboard', component: SiswaDashboard },
  ]
})

export default router
