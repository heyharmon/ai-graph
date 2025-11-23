import api from './api'

const knowledgeGraph = {
    async create(websiteUrl) {
        const response = await api.post('/knowledge-graphs', {
            website_url: websiteUrl
        })
        return response
    },

    async getAll() {
        const response = await api.get('/knowledge-graphs')
        return response.knowledge_graphs || []
    },

    async getById(id) {
        const response = await api.get(`/knowledge-graphs/${id}`)
        return response
    }
}

export default knowledgeGraph

