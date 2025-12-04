// Satu sumber kebenaran untuk modul kategori.
export const moduleCategories = [
  {
    id: 'upkp',
    title: 'UPKP',
    description: 'Kumpulan materi UPKP bagi PNS untuk kenaikan pangkat.',
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    image: () => import('@/assets/Picture1.png'),
    // submodules untuk UPKP
    children: [
      { slug: 'wawasan-kebangsaan', title: 'Wawasan Kebangsaan', description: 'Materi wawasan kebangsaan.' },
      { slug: 'nilai-nilai-kemenkeu', title: 'Nilai-nilai Kemenkeu', description: 'Nilai-nilai utama di Kemenkeu.' },
      { slug: 'etika-pns', title: 'Etika PNS', description: 'Standar etika bagi Aparatur Sipil Negara.' },
      { slug: 'tata-aturan-kepegawaian', title: 'Tata Aturan Kepegawaian', description: 'Regulasi dan tata aturan kepegawaian.' },
      { slug: 'fungsi-kemenkeu', title: 'Fungsi Kemenkeu', description: 'Penjelasan fungsi Kementerian Keuangan.' }
    ]
  },
  {
    id: 'tugas-belajar',
    title: 'Tugas Belajar',
    description: 'Modul seputar perizinan tugas belajar PNS.',
    gradientClass: 'from-sky-400 via-indigo-600 to-indigo-900',
    image: () => import('@/assets/Picture2.png'),
    children: [
      { slug: 'tpa', title: 'TPA', description: 'Tes Potensi Akademik.' },
      { slug: 'tbi', title: 'TBI', description: 'Tes Bahasa Inggris.' }
    ]
  }
];

// Helper cari kategori
export function findCategory(id) {
  return moduleCategories.find(c => c.id === id);
}