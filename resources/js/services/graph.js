import api from './api'

const graph = {
    async create(websiteUrl) {
        const response = await api.post('/graphs', {
            website_url: websiteUrl
        })
        return response
    },

    async getAll() {
        const response = await api.get('/graphs')
        return response.graphs || []
    },

    async getById(id) {
        const response = await api.get(`/graphs/${id}`)
        return response
    },

    async getSources(graphId, params = {}) {
        const queryString = new URLSearchParams(params).toString()
        const response = await api.get(`/graphs/${graphId}/sources${queryString ? '?' + queryString : ''}`)
        return response
    }
}

export default graph

