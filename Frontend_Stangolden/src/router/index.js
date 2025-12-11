import { createRouter, createWebHistory } from 'vue-router'

// Public
import LandingPage from '../views/LandingPage.vue'
import LoginView from '../views/LoginView.vue'
import AdminDashboard from '../views/Adm/Dashboard.vue'

// Lazy-loaded TryOut views
const TryOutView = () => import('@/views/Adm/TryOut/TryOutView.vue')
const TryOutGroups = () => import('@/views/Adm/TryOut/TryOutGroups.vue')
const TryOutSeriesList = () => import('@/views/Adm/TryOut/TryOutSeriesList.vue')
const TryOutSeriesDetail = () => import('@/views/Adm/TryOut/TryOutSeriesDetail.vue')
const TryOutQuestionImport = () => import('@/views/Adm/TryOut/TryOutQuestionImport.vue')
const TryOutFormAdm = () => import('@/views/Adm/TryOut/TryOutForm.vue')

// Bank attempt/result
const QuestionBankAttempt = () => import('@/views/Adm/TryOut/QuestionBankAttempt.vue')
const QuestionBankResult  = () => import('@/views/Adm/TryOut/QuestionBankResult.vue')

// Other admin views
const FeedbackAdm = () => import('@/views/Adm/FeedbackView.vue')
const ModulViewAdm = () => import('@/views/Adm/Modul/ModuleView.vue')
const PesertaViewAdm = () => import('@/views/Adm/PesertaView.vue')
const ModulViewerAdm = () => import('@/views/Adm/Components/ModulViewer.vue')
const ModulUPKPAdm = () => import('@/views/Adm/Modul/ModulUPKP.vue')
const ModulTugasBelajarAdm = () => import('@/views/Adm/Modul/ModulTugasBelajar.vue')
const ModuleDetailAdm = () => import('@/views/Adm/Modul/ModulDetail.vue')

const routes = [
  // Public
  { path: '/',      name: 'landing', component: LandingPage },
  { path: '/login', name: 'login',   component: LoginView },

  // Dashboards
  { path: '/admin/dashboard', name: 'AdminDashboard', component: AdminDashboard },
  { path: '/dashboard',       name: 'SiswaDashboard', component: AdminDashboard },

  // Attempt & Result (UPKP & TUBEL reuse)
  {
    path: '/tryoutadm/banks/:bankId/attempt',
    name: 'TryoutAdminBankAttempt',
    component: QuestionBankAttempt,
    props: true,
  },
  {
    path: '/tryoutadm/banks/:bankId/result',
    name: 'TryoutAdminBankResult',
    component: QuestionBankResult,
    props: true,
  },

  // Modul (admin)
  { path: '/moduladm/content/:id', name: 'ModulContentAdmin', component: ModulViewerAdm, props: true },
  { path: '/moduladm',             name: 'Moduladmin',        component: ModulViewAdm },
  { path: '/peserta',              name: 'peserta',           component: PesertaViewAdm },
  { path: '/moduladm/upkp',        name: 'ModulUPKPadmin',    component: ModulUPKPAdm },
  { path: '/moduladm/tugas-belajar', name: 'ModulTugasBelajaradmin', component: ModulTugasBelajarAdm },
  { path: '/moduladm/:group/:slug', name: 'ModulDetailAdmin', component: ModuleDetailAdm, props: true },

  // Modul (siswa)
  { path: '/modul/content/:id', name: 'ModulContentSiswa', component: ModulViewerAdm, props: true },
  { path: '/modul',             name: 'Modulsiswa',        component: ModulViewAdm },
  { path: '/modul/:group/:slug', name: 'ModulDetailSiswa', component: ModuleDetailAdm, props: true },

  // TryOut admin & siswa home (cards)
  { path: '/tryoutadm', name: 'TryoutAdminHome', component: TryOutView },
  { path: '/tryout',    name: 'TryOutsiswa',     component: TryOutView },

  // Admin TryOut CRUD (keep edit/new; REMOVE conflicting /tryoutadm/:id detail)
  { path: '/tryoutadm/new',      name: 'TryOutNewadmin',  component: TryOutFormAdm },
  { path: '/tryoutadm/:id/edit', name: 'TryOutEditadmin', component: TryOutFormAdm, props: true },

  // Siswa CRUD (reuse)
  { path: '/tryout/new',      name: 'TryOutNewsiswa',  component: TryOutFormAdm },
  { path: '/tryout/:id/edit', name: 'TryOutEditsiswa', component: TryOutFormAdm, props: true },

  // TryOut hierarchy (SPECIFIC routes MUST come BEFORE the generic :domain)
  { path: '/tryoutadm/group/:groupId/series', name: 'TryoutAdminSeriesList',    component: TryOutSeriesList,    props: true },
  { path: '/tryoutadm/series/:seriesId',      name: 'TryoutAdminSeriesDetail',  component: TryOutSeriesDetail,  props: true },
  { path: '/tryoutadm/series/:seriesId/import', name: 'TryoutAdminQuestionImport', component: TryOutQuestionImport, props: true },

  // GENERIC domain route last (captures /tryoutadm/upkp, /tryoutadm/tubel, /tryoutadm/tugas-belajar, /tryoutadm/tb)
  { path: '/tryoutadm/:domain', name: 'TryoutAdminDomain', component: TryOutGroups, props: true },

  // Feedback (admin/siswa)
  { path: '/feedbackadm', name: 'Feedbackadmin', component: FeedbackAdm },
  { path: '/feedback',    name: 'Feedbacksiswa', component: FeedbackAdm },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router