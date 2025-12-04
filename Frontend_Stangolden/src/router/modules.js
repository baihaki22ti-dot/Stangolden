import ModuleIndex from '@/views/Adm/ModuleIndex.vue'
import ModulUPKP from '@/views/Adm/ModulUPKP.vue'
import ModulTugasBelajar from '@/views/Adm/ModulTugasBelajar.vue'
import ModuleDetail from '@/views/Adm/ModuleDetail.vue'

export default [
  {
    path: '/moduladm',
    name: 'ModuleIndex',
    component: ModuleIndex
  },
  {
    path: '/moduladm/upkp',
    name: 'ModulUPKP',
    component: ModulUPKP
  },
  {
    path: '/moduladm/tugas-belajar',
    name: 'ModulTugasBelajar',
    component: ModulTugasBelajar
  },
  {
    path: '/moduladm/:kategori/:slug',
    name: 'ModuleDetail',
    component: ModuleDetail,
    props: true
  }
]