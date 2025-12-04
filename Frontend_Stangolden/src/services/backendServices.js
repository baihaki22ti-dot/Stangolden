import axios from '@/plugins/axios'

function normalizeUser(u = {}) {
  return {
    id: u.id,
    name: u.name ?? '',
    email: u.email ?? '',
    expiresAt: u.expires_at ?? u.expiresAt ?? null,
    createdAt: u.created_at ?? u.createdAt ?? null,
    approved: !!u.approved,
    active: !!u.active,
    categories: {
      upkp: !!(u.upkp ?? u.categories?.upkp),
      tugasBelajar: !!(u.tugas_belajar ?? u.categories?.tugasBelajar),
    },
    raw: u
  }
}

const backendService = {
  // Modules (publik) read-only → /api/modules
  modules: {
    list(params = {}) { return axios.get('/api/modules', { params }).then(r => r.data) },
    get(id) { return axios.get(`/api/modules/${id}`).then(r => r.data) },
  },

  // Tryouts (publik) → /api/tryouts
  tryouts: {
    list(params = {}) { return axios.get('/api/tryouts', { params }).then(r => r.data) },
    get(id) { return axios.get(`/api/tryouts/${id}`).then(r => r.data) },
    create(formData) { return axios.post('/api/tryouts', formData).then(r => r.data) },
    update(id, formData) { return axios.post(`/api/tryouts/${id}`, formData, { params: { _method: 'PUT' } }).then(r => r.data) },
    remove(id) { return axios.delete(`/api/tryouts/${id}`).then(r => r.data) }
  },

  // Feedback
  feedbacks: {
    list(params = {}) { return axios.get('/api/feedbacks', { params }).then(r => r.data) },
    get(id) { return axios.get(`/api/feedbacks/${id}`).then(r => r.data) },
    async create(payload) {
      const fd = new FormData()
      fd.append('category', payload.category ?? 'umum')
      fd.append('title', payload.title ?? '')
      fd.append('message', payload.message ?? '')
      fd.append('priority', payload.priority ?? 'medium')
      if (payload.attachment instanceof File) fd.append('attachment', payload.attachment)
      const res = await axios.post('/api/feedbacks', fd)
      return res.data
    },
    async update(id, payload) {
      const fd = new FormData()
      if (payload.category !== undefined) fd.append('category', payload.category)
      if (payload.title !== undefined) fd.append('title', payload.title)
      if (payload.message !== undefined) fd.append('message', payload.message)
      if (payload.priority !== undefined) fd.append('priority', payload.priority)
      if (payload.resolved !== undefined) fd.append('resolved', payload.resolved ? '1' : '0')
      if (payload.attachment instanceof File) fd.append('attachment', payload.attachment)
      const res = await axios.post(`/api/feedbacks/${id}`, fd, { params: { _method: 'PUT' } })
      return res.data
    },
    remove(id) { return axios.delete(`/api/feedbacks/${id}`).then(r => r.data) },
    toggle(id) { return axios.patch(`/api/feedbacks/${id}/toggle`).then(r => r.data) }
  },

  // Auth
  auth: {
    csrf() { return axios.get('/sanctum/csrf-cookie') },
    async login(payload) {
      const res = await axios.post('/api/login', payload)
      const data = res.data
      if (data?.token) localStorage.setItem('auth_token', data.token)
      return data
    },
    logout() {
      localStorage.removeItem('auth_token')
      return axios.post('/api/logout').then(r => r.data)
    },
    user() { return axios.get('/api/user').then(r => r.data) },
    register(payload) { return axios.post('/api/register', payload).then(r => r.data) }
  },

  // Users (admin)
  users: {
    async list(params = {}) {
      const res = await axios.get('/api/admin/users', { params })
      const body = res.data ?? {}
      let usersRaw = []
      let counts = {}
      if (Array.isArray(body)) {
        usersRaw = body
      } else if (Array.isArray(body.data)) {
        usersRaw = body.data
        counts = body.counts || {}
      } else if (Array.isArray(res.data?.users)) {
        usersRaw = res.data.users
        counts = res.data.counts || {}
      } else {
        usersRaw = []
        counts = {}
      }
      const data = usersRaw.map(normalizeUser)
      return { data, counts }
    },
    createFake(payload = {}) { return axios.post('/api/admin/users/fake', payload).then(r => r.data) },
    update(id, payload) { return axios.put(`/api/admin/users/${id}`, payload).then(r => r.data) },
    approve(id) { return axios.post(`/api/admin/users/${id}/approve`).then(r => r.data) },
    revoke(id) { return axios.post(`/api/admin/users/${id}/revoke`).then(r => r.data) },
    toggleActive(id) { return axios.post(`/api/admin/users/${id}/toggle-active`).then(r => r.data) },
    setExpiry(id, expires_at) { return axios.post(`/api/admin/users/${id}/expiry`, { expires_at }).then(r => r.data) },
    destroy(id) { return axios.delete(`/api/admin/users/${id}`).then(r => r.data) }
  },

  // Admin Modules (upload/edit/hapus)
  adminModules: {
    list(params = {}) { return axios.get('/api/admin/modules', { params }).then(r => r.data) },
    get(id) { return axios.get(`/api/admin/modules/${id}`).then(r => r.data) },
    async create(payload) {
      if (!payload.group) throw new Error('Group wajib diisi')
      if (!(payload?.pdfFile instanceof File)) throw new Error('File PDF wajib diunggah')
      const fd = new FormData()
      fd.append('name', payload.name ?? '')
      fd.append('group', payload.group)
      if (payload.sub_group) fd.append('sub_group', payload.sub_group)
      fd.append('description', payload.description ?? '')
      fd.append('pdf', payload.pdfFile)
      const res = await axios.post('/api/admin/modules', fd)
      return res.data
    },
    async update(id, payload) {
      const fd = new FormData()
      if (payload.name !== undefined) fd.append('name', payload.name)
      if (payload.group !== undefined && payload.group !== '') fd.append('group', payload.group)
      if (payload.sub_group !== undefined) fd.append('sub_group', payload.sub_group ?? '')
      if (payload.description !== undefined) fd.append('description', payload.description ?? '')
      if (payload.pdfFile instanceof File) fd.append('pdf', payload.pdfFile)
      const res = await axios.post(`/api/admin/modules/${id}`, fd, { params: { _method: 'PUT' } })
      return res.data
    },
    remove(id) { return axios.delete(`/api/admin/modules/${id}`).then(r => r.data) }
  },

  raw: axios
}

export default backendService