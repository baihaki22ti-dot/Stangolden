import ModuleIndex from '@/views/Adm/Modul/ModuleIndex.vue'
import ModulUPKP from '@/views/Adm/Modul/ModulUPKP.vue'
import ModulTugasBelajar from '@/views/Adm/Modul/ModulTugasBelajar.vue'
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