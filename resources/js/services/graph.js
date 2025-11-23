import api from './api'

const graph = {
    async index() {
        return await api.get('/graphs')
    },

    async show(id) {
        return await api.get(`/graphs/${id}`)
    },

    async create(data) {
        return await api.post('/graphs', data)
    },

    async update(id, data) {
        return await api.put(`/graphs/${id}`, data)
    },

    async delete(id) {
        return await api.delete(`/graphs/${id}`)
    },
}

export default graph

