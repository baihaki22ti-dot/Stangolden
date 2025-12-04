import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import LoginView from '../views/LoginView.vue'
import AdminDashboard from '../views/Adm/AdminDashboard.vue'

// lazy-loaded views admin
const TryOutAdm = () => import('@/views/Adm/TryOutView.vue')
const FeedbackAdm = () => import('@/views/Adm/FeedbackView.vue')
const ModulViewAdm = () => import('@/views/Adm/ModuleView.vue')
const PesertaViewAdm = () => import('@/views/Adm/PesertaView.vue')
const ModulViewerAdm = () => import('@/views/Adm/Components/ModulViewer.vue')
const TryOutListAdm = () => import('@/views/Adm/TryOutList.vue')
const TryOutFormAdm = () => import('@/views/Adm/TryOutForm.vue')
const TryOutDetailAdm = () => import('@/views/Adm/TryOutDetail.vue')
const ModulUPKPAdm = () => import('@/views/Adm/ModulUPKP.vue')
const ModulTugasBelajarAdm = () => import('@/views/Adm/ModulTugasBelajar.vue')
const ModuleDetailAdm = () => import('@/views/Adm/ModulDetail.vue')

const routes = [
  { path: '/', name: 'landing', component: LandingPage },
  { path: '/login', name: 'login', component: LoginView },

  { path: '/admin/dashboard', name: 'AdminDashboard', component: AdminDashboard },
  { path: '/dashboard', name: 'SiswaDashboard', component: AdminDashboard },

  // modul viewer admin
  { path: '/moduladm/content/:id', name: 'ModulContentAdmin', component: ModulViewerAdm, props: true },
  { path: '/moduladm', name: 'Moduladmin', component: ModulViewAdm },
  { path: '/peserta', name: 'peserta', component: PesertaViewAdm },
  { path: '/moduladm/upkp', name: 'ModulUPKPadmin', component: ModulUPKPAdm },
  { path: '/moduladm/tugas-belajar', name: 'ModulTugasBelajaradmin', component: ModulTugasBelajarAdm },

  // gunakan route detail modul dengan parameter group + slug
  { path: '/moduladm/:group/:slug', name: 'ModulDetailAdmin', component: ModuleDetailAdm, props: true },

  // rute lain admin
  { path: '/tryoutadm', name: 'TryOutadmin', component: TryOutAdm },
  { path: '/feedbackadm', name: 'Feedbackadmin', component: FeedbackAdm },
  { path: '/tryoutadm/new', name: 'TryOutNewadmin', component: TryOutFormAdm },
  { path: '/tryoutadm/:id/edit', name: 'TryOutEditadmin', component: TryOutFormAdm, props: true },
  { path: '/tryoutadm/:id', name: 'TryOutDetailadmin', component: TryOutDetailAdm, props: true },

  // modul viewer Siswa
  { path: '/modul/content/:id', name: 'ModulContentSiswa', component: ModulViewerAdm, props: true },
  { path: '/modul', name: 'Modulsiswa', component: ModulViewAdm },

  // gunakan route detail modul dengan parameter group + slug
  { path: '/modul/:group/:slug', name: 'ModulDetailSiswa', component: ModuleDetailAdm, props: true },

  // rute lain Siswa
  { path: '/tryout', name: 'TryOutsiswa', component: TryOutAdm },
  { path: '/feedback', name: 'Feedbacksiswa', component: FeedbackAdm },
  { path: '/tryout/new', name: 'TryOutNewsiswa', component: TryOutFormAdm },
  { path: '/tryout/:id/edit', name: 'TryOutEditsiswa', component: TryOutFormAdm, props: true },
  { path: '/tryout/:id', name: 'TryOutDetailsiswa', component: TryOutDetailAdm, props: true },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router