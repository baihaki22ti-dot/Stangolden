import axios from '@/plugins/axios'

// Helpers: safe wrappers (do not throw, return null on error)
async function safeGet(url, config = {}) {
  try { const res = await axios.get(url, config); return res.data } catch { return null }
}
async function safePost(url, data = {}, config = {}) {
  try { const res = await axios.post(url, data, config); return res.data } catch { return null }
}
async function safeDelete(url, config = {}) {
  try { const res = await axios.delete(url, config); return res.data } catch { return null }
}

const backendService = {
  // Public participant endpoints (if present)
  participant: {
    async listQuestions(bankId) {
      const res = await safeGet(`/api/banks/${bankId}/questions`)
      const body = res ?? {}
      const questions = Array.isArray(body?.questions) ? body.questions : (Array.isArray(body) ? body : [])
      return {
        bank_id: body.bank_id ?? bankId,
        category: body.category ?? '',
        count: Number(body.count ?? questions.length ?? 0),
        questions
      }
    },
    startAttempt(bankId) { return safePost('/api/attempts', { bank_id: bankId }) },
    finishAttempt(attemptId, answers = []) { return safePost(`/api/attempts/${attemptId}/finish`, { answers }) },
    async myAttempts(params = {}) {
      const res = await safeGet('/api/attempts/me', { params })
      if (Array.isArray(res?.data)) return res.data
      if (Array.isArray(res)) return res
      return []
    },
  },

  // Public modules (non-admin) — keep if you use them
  modules: {
    list(params = {}) { return safeGet('/api/modules', { params }) },
    get(id) { return safeGet(`/api/modules/${id}`) },
  },

  // Public tryouts (if any)
  tryouts: {
    list(params = {}) { return safeGet('/api/tryouts', { params }) },
    get(id) { return safeGet(`/api/tryouts/${id}`) },
    create(formData) { return safePost('/api/tryouts', formData) },
    update(id, formData) { return safePost(`/api/tryouts/${id}`, formData, { params: { _method: 'PUT' } }) },
    remove(id) { return safeDelete(`/api/tryouts/${id}`) },
  },

  // Feedbacks
  feedbacks: {
    list(params = {}) { return safeGet('/api/feedbacks', { params }) },
    get(id) { return safeGet(`/api/feedbacks/${id}`) },
    async create(payload) {
      const fd = new FormData()
      fd.append('category', payload.category ?? 'umum')
      fd.append('title', payload.title ?? '')
      fd.append('message', payload.message ?? '')
      fd.append('priority', payload.priority ?? 'medium')
      if (payload.attachment instanceof File) fd.append('attachment', payload.attachment)
      return safePost('/api/feedbacks', fd)
    },
    async update(id, payload) {
      const fd = new FormData()
      if (payload.category !== undefined) fd.append('category', payload.category)
      if (payload.title !== undefined) fd.append('title', payload.title)
      if (payload.message !== undefined) fd.append('message', payload.message)
      if (payload.priority !== undefined) fd.append('priority', payload.priority)
      if (payload.resolved !== undefined) fd.append('resolved', payload.resolved ? '1' : '0')
      if (payload.attachment instanceof File) fd.append('attachment', payload.attachment)
      return safePost(`/api/feedbacks/${id}`, fd, { params: { _method: 'PUT' } })
    },
    remove(id) { return safeDelete(`/api/feedbacks/${id}`) },
    toggle(id) { return safePost(`/api/feedbacks/${id}/toggle`) },
  },

  // Auth
  auth: {
    csrf() { return axios.get('/sanctum/csrf-cookie') },
    async login(payload) {
      const res = await safePost('/api/login', payload)
      if (res?.token) localStorage.setItem('auth_token', res.token)
      return res
    },
    logout() {
      localStorage.removeItem('auth_token')
      return safePost('/api/logout')
    },
    user() { return safeGet('/api/user') },
    register(payload) { return safePost('/api/register', payload) },
  },

  // Users — since /api/admin/users and /api/users don’t exist, return empty safely
  users: {
  async list(params = {}) {
    const res = await axios.get('/api/admin/users', { params }).then(r => r.data)
    const raw = Array.isArray(res?.data) ? res.data : []
    const counts = res.counts || {}
    const data = raw.map(u => ({
      id: u.id,
      name: u.name ?? '',
      email: u.email ?? '',
      categories: {
        upkp: !!(u.categories?.upkp),
        tugasBelajar: !!(u.categories?.tugasBelajar),
      },
      expiresAt: u.expiresAt ?? null,
      approved: !!u.approved,
      active: !!u.active,
      createdAt: u.createdAt ?? null,
      raw: u,
    }))
    return { data, counts }
  },

  // IMPORTANT: plain POST (no _method), Content-Type: application/json
  update(id, payload) {
    const mapped = { ...payload }
    if (payload?.categories) {
      mapped.upkp = payload.categories.upkp
      mapped.tugas_belajar = payload.categories.tugasBelajar
      delete mapped.categories
    }
    if (payload?.expiresAt !== undefined) {
      mapped.expires_at = payload.expiresAt
      delete mapped.expiresAt
    }
    return axios.post(`/api/admin/users/${id}`, mapped).then(r => r.data)
  },

  approve(id, payload = {}) {
    const mapped = {}
    if (payload.expires_at) mapped.expires_at = payload.expires_at
    if (payload.upkp !== undefined) mapped.upkp = payload.upkp
    if (payload.tugas_belajar !== undefined) mapped.tugas_belajar = payload.tugas_belajar
    return axios.post(`/api/admin/users/${id}/approve`, mapped).then(r => r.data)
  },

  revoke(id) { return axios.post(`/api/admin/users/${id}/revoke`).then(r => r.data) },
  toggleActive(id) { return axios.post(`/api/admin/users/${id}/toggle-active`).then(r => r.data) },
  setExpiry(id, expires_at) { return axios.post(`/api/admin/users/${id}/expiry`, { expires_at }).then(r => r.data) },
  destroy(id) { return axios.delete(`/api/admin/users/${id}`).then(r => r.data) },
},

  // Admin Modules — these routes exist in your app
  adminModules: {
    list(params = {}) { return safeGet('/api/admin/modules', { params }) },
    get(id) { return safeGet(`/api/admin/modules/${id}`) },
    async create(payload) {
      if (!payload.group) throw new Error('Group wajib diisi')
      if (!(payload?.pdfFile instanceof File)) throw new Error('File PDF wajib diunggah')
      const fd = new FormData()
      fd.append('name', payload.name ?? '')
      fd.append('group', payload.group)
      if (payload.sub_group) fd.append('sub_group', payload.sub_group)
      fd.append('description', payload.description ?? '')
      fd.append('pdf', payload.pdfFile)
      if (payload.youtube_url !== undefined) fd.append('youtube_url', payload.youtube_url ?? '')
      return safePost('/api/admin/modules', fd)
    },
    async update(id, payload) {
      const fd = new FormData()
      if (payload.name !== undefined) fd.append('name', payload.name)
      if (payload.group !== undefined && payload.group !== '') fd.append('group', payload.group)
      if (payload.sub_group !== undefined) fd.append('sub_group', payload.sub_group ?? '')
      if (payload.description !== undefined) fd.append('description', payload.description ?? '')
      if (payload.youtube_url !== undefined) fd.append('youtube_url', payload.youtube_url ?? '')
      if (payload.pdfFile instanceof File) fd.append('pdf', payload.pdfFile)
      return safePost(`/api/admin/modules/${id}`, fd, { params: { _method: 'PUT' } })
    },
    remove(id) { return safeDelete(`/api/admin/modules/${id}`) },
  },

  // Admin area (only call routes if they exist; otherwise fallback)
  admin: {
    // Dashboard stats — computed client-side from available endpoints
    dashboard: {
      async statsSafe() {
        // Users
        const { data, counts } = await backendService.users.list()
        const users = Number(counts?.total ?? data.length ?? 0)

        // Modules
        const modulesRes = await backendService.adminModules.list()
        const modules = Number(Array.isArray(modulesRes) ? modulesRes.length : modulesRes?.total ?? 0)

        // Tryouts: count series across groups if possible
        let tryouts = 0
        const groupsRes = await safeGet('/api/admin/tryout/groups')
        if (groupsRes) {
          const groups = Array.isArray(groupsRes?.data) ? groupsRes.data : (Array.isArray(groupsRes) ? groupsRes : [])
          for (const g of groups) {
            const seriesRes = await safeGet(`/api/admin/tryout/groups/${g.id}/series`)
            const count = Array.isArray(seriesRes?.data) ? seriesRes.data.length : (Array.isArray(seriesRes) ? seriesRes.length : 0)
            tryouts += count
          }
        } else {
          const tr = await backendService.tryouts.list()
          tryouts = Number(Array.isArray(tr) ? tr.length : tr?.total ?? 0)
        }

        // Online estimate
        const online = data.filter(u => {
          const user = u.raw ?? u
          const isActive = !!(user.active ?? user.activated ?? true)
          const isApproved = !!(user.approved ?? true)
          const exp = user.expires_at ?? user.expiresAt ?? null
          let notExpired = true
          if (exp) {
            const d = new Date(exp)
            notExpired = Date.now() <= d.getTime()
          }
          return isActive && isApproved && notExpired
        }).length

        // Registered per year
        const registeredMap = new Map()
        data.forEach(u => {
          const created = u.createdAt ?? u.raw?.created_at
          if (!created) return
          const year = new Date(created).getFullYear()
          registeredMap.set(year, (registeredMap.get(year) ?? 0) + 1)
        })
        const registered = Object.fromEntries([...registeredMap.entries()].sort((a, b) => a[0] - b[0]))

        return { users, online, modules, tryouts, registered }
      },
    },

    // Attempts — avoid calling non-existent admin endpoints; compute ranking from whatever is available
    attempts: {
      async list(params = {}) {
        let res = await safeGet('/api/admin/attempts', { params }) // if exists
        if (!res) res = await safeGet('/api/attempts', { params }) // public fallback if exists
        return res ?? []
      },
      async topByScore({ domain = 'upkp', limit = 5 } = {}) {
        const res = await backendService.admin.attempts.list({ limit: 1000 })
        const items = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
        const normalized = items.map(a => ({
          name: a.user_name ?? a.user?.name ?? a.username ?? 'Peserta',
          score: Number(a.score ?? 0),
          correct: Number(a.correct ?? a.correct_count ?? 0),
          domain: (a.domain ?? a.tryout_domain ?? a.series_domain ?? '').toLowerCase(),
          tryout_name: a.tryout_title ?? a.series_title ?? a.bank_title ?? ''
        }))
        const filtered = normalized.filter(x => {
          if (x.domain) return x.domain === domain
          const t = (x.tryout_name || '').toLowerCase()
          if (domain === 'upkp') return t.includes('upkp')
          if (domain === 'tubel') return t.includes('tubel') || t.includes('tugas belajar')
          return false
        })
        return filtered.sort((x, y) => y.score - x.score).slice(0, limit)
      },
      async topByCorrect({ domain = 'upkp', limit = 5 } = {}) {
        const res = await backendService.admin.attempts.list({ limit: 1000 })
        const items = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
        const normalized = items.map(a => ({
          name: a.user_name ?? a.user?.name ?? a.username ?? 'Peserta',
          correct: Number(a.correct ?? a.correct_count ?? 0),
          domain: (a.domain ?? a.tryout_domain ?? a.series_domain ?? '').toLowerCase(),
          tryout_name: a.tryout_title ?? a.series_title ?? a.bank_title ?? ''
        }))
        const filtered = normalized.filter(x => {
          if (x.domain) return x.domain === domain
          const t = (x.tryout_name || '').toLowerCase()
          if (domain === 'upkp') return t.includes('upkp')
          if (domain === 'tubel') return t.includes('tubel') || t.includes('tugas belajar')
          return false
        })
        return filtered.sort((x, y) => y.correct - x.correct).slice(0, limit)
      },
    },

    // Tryout hierarchy (only if exists)
    tryout: {
      listGroups(params = {}) { return safeGet('/api/admin/tryout/groups', { params }) },
      getGroup(groupId) { return safeGet(`/api/admin/tryout/groups/${groupId}`) },
      createGroup(payload) { return safePost('/api/admin/tryout/groups', payload) },
      destroyGroup(id) { return safeDelete(`/api/admin/tryout/groups/${id}`) },

      listSeries(groupId) { return safeGet(`/api/admin/tryout/groups/${groupId}/series`) },
      createSeries(groupId, payload) { return safePost(`/api/admin/tryout/groups/${groupId}/series`, payload) },
      destroySeries(id) { return safeDelete(`/api/admin/tryout/series/${id}`) },

      listSessions(seriesId) { return safeGet(`/api/admin/tryout/series/${seriesId}/sessions`) },
      createSession(seriesId, payload) { return safePost(`/api/admin/tryout/series/${seriesId}/sessions`, payload) },
      destroySession(id) { return safeDelete(`/api/admin/tryout/sessions/${id}`) },

      generateSessionQuestions(sessionId, payload) { return safePost(`/api/admin/tryout/sessions/${sessionId}/generate`, payload) },
    },

    // Banks (only if exists)
    banks: {
      index(params = {}) {
        return safeGet('/api/admin/banks', { params }).then(res => {
          if (Array.isArray(res)) return res
          if (Array.isArray(res?.data)) return res.data
          return []
        })
      },
      store(payload) { return safePost('/api/admin/banks', payload) },
      update(id, payload) { return safePost(`/api/admin/banks/${id}`, payload, { params: { _method: 'PUT' } }) },
      destroy(id) { return safeDelete(`/api/admin/banks/${id}`) },
      listQuestions(bankId) { return safeGet(`/api/admin/banks/${bankId}/questions`) },
      storeQuestion(bankId, payload) { return safePost(`/api/admin/banks/${bankId}/questions`, payload) },
      updateQuestion(questionId, payload) { return safePost(`/api/admin/questions/${questionId}`, payload, { params: { _method: 'PUT' } }) },
      destroyQuestion(questionId) { return safeDelete(`/api/admin/questions/${questionId}`) },
      async clearQuestions(bankId) {
        const res = await safePost('/api/admin/questions/clear', { bank_id: bankId })
        if (res) return res
        return safeDelete('/api/admin/questions', { data: { bank_id: bankId } })
      },
    },

    // Import (only if exists)
    import: {
      docx(formData) { return safePost('/api/admin/import/docx', formData, { headers: { 'Content-Type': 'multipart/form-data' } }) },
      csv(formData) { return safePost('/api/admin/import/csv', formData, { headers: { 'Content-Type': 'multipart/form-data' } }) },
      async csvToBank(bankId, formData) {
        let res = await safePost(`/api/admin/banks/${bankId}/import/csv`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        if (res) return res
        return safePost('/api/admin/import/csv', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
      },
    },
  },

  raw: axios,
}

export default backendService