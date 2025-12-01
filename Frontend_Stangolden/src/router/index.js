import { createRouter, createWebHistory } from 'vue-router'
import LandingPage from '../views/LandingPage.vue'
import LoginView from '../views/LoginView.vue'
import AdminDashboard from '../views/Adm/AdminDashboard.vue'
import SiswaDashboard from '../views/Siswa/SiswaDashboard.vue'

//lazy-loaded views admin
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

//lazy-loaded views siswa

const TryOutSiswa = () => import('@/views/Siswa/TryOutView.vue')
const FeedbackSiswa = () => import('@/views/Siswa/FeedbackView.vue')
const ModulViewSiswa = () => import('@/views/Siswa/ModuleView.vue')
// const PesertaViewSiswa = () => import('@/views/Siswa/PesertaView.vue')
const ModulViewerSiswa = () => import('@/views/Siswa/Components/ModulViewer.vue')
// const TryOutListSiswa = () => import('@/views/Siswa/TryOutList.vue')
// const TryOutFormSiswa = () => import('@/views/Siswa/TryOutForm.vue')
const TryOutDetailSiswa = () => import('@/views/Siswa/TryOutDetail.vue')
const ModulUPKPSiswa = () => import('@/views/Siswa/ModulUPKP.vue')
const ModulTugasBelajarSiswa = () => import('@/views/Siswa/ModulTugasBelajar.vue')
const ModuleDetailSiswa = () => import('@/views/Siswa/ModulDetail.vue')

const routes = [
  { path: '/', name: 'landing', component: LandingPage },
  { path: '/login', name: 'login', component: LoginView },

  { path: '/admin/dashboard', name: 'AdminDashboard', component: AdminDashboard },
  { path: '/dashboard', name: 'SiswaDashboard', component: SiswaDashboard },

  //lazy-loaded modul viewer admin
  { path: '/admin/modul/content/:id', name: 'ModulContent', component: ModulViewerAdm, props: true },
  { path: '/admin/modul', name: 'Modul', component: ModulViewAdm },
  { path: '/admin/peserta', name: 'peserta', component: PesertaViewAdm },
  { path: '/admin/modul/upkp', name: 'ModulUPKP', component: ModulUPKPAdm },
  { path: '/admin/modul/upkp/:slug', name: 'ModulUPKPDetail', component: ModuleDetailAdm, props: true },
  { path: '/admin/modul/tugas-belajar', name: 'ModulTugasBelajar', component: ModulTugasBelajarAdm },
  { path: '/admin/modul/tugas-belajar/:slug', name: 'ModulTugasBelajarDetail', component: ModuleDetailAdm, props: true },
  { path: '/admin/tryout', name: 'TryOut', component: TryOutAdm },
  { path: '/admin/feedback', name: 'Feedback', component: FeedbackAdm },
  { path: '/admin/modul/:slug', name: 'ModulDetailGeneric', component: ModuleDetailAdm, props: true },
  { path: '/admin/tryout/new', name: 'TryOutNew', component: TryOutFormAdm },
  { path: '/admin/tryout/:id/edit', name: 'TryOutEdit', component: TryOutFormAdm, props: true },
  { path: '/admin/tryout/:id', name: 'TryOutDetail', component: TryOutDetailAdm, props: true },

  //lazy-loaded modul viewer siswa
  { path: '/modul/content/:id', name: 'ModulContent', component: ModulViewerSiswa, props: true },
  { path: '/modul', name: 'Modul', component: ModulViewSiswa },
  { path: '/modul/upkp', name: 'ModulUPKP', component: ModulUPKPSiswa },
  { path: '/modul/upkp/:slug', name: 'ModulUPKPDetail', component: ModuleDetailSiswa, props: true },
  { path: '/modul/tugas-belajar', name: 'ModulTugasBelajar', component: ModulTugasBelajarSiswa },
  { path: '/modul/tugas-belajar/:slug', name: 'ModulTugasBelajarDetail', component: ModuleDetailSiswa, props: true },
  { path: '/tryout', name: 'TryOut', component: TryOutSiswa },
  { path: '/feedback', name: 'Feedback', component: FeedbackSiswa },
  { path: '/modul/:slug', name: 'ModulDetailGeneric', component: ModuleDetailSiswa, props: true },
  { path: '/tryout/:id', name: 'TryOutDetail', component: TryOutDetailSiswa, props: true }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router