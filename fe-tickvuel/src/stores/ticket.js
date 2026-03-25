import { defineStore } from "pinia"
import { axiosInstance } from "@/plugins/axios"
import { handleError } from "@/helpers/errorHelper"
import router from "@/router"
import { useAuthStore } from "./auth"

export const useTicketStore = defineStore("ticket", {
    state: () => ({
        tickets: [],
        loading: false,
        error: null,
        success: null
    }),

    actions: {
        async fetchTickets(params) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`tickets`, { params })

                this.tickets = response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchTicket(code) {
           this.loading = true

            try {
                const response = await axiosInstance.get(`/ticket/${code}`)

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createTicket(payload) {
            this.loading = true
            this.error = null

            try {
                const formData = new FormData()
                Object.keys(payload).forEach(key => {
                    if (payload[key] !== null) {
                        formData.append(key, payload[key])
                    }
                })

                const response = await axiosInstance.post(`/ticket`, formData)

                this.success = response.data.message
                
                router.push({ name: 'app.dashboard' })
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createTicketReply(code, payload) {
            this.loading = true
            this.error = null

            try {
                const formData = new FormData()
                Object.keys(payload).forEach(key => {
                    if (payload[key] !== null) {
                        formData.append(key, payload[key])
                    }
                })

                const response = await axiosInstance.post(`/ticket-reply/${code}`, formData)

                this.success = response.data.message

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateTicket(code, payload) {
            this.loading = true

            try {
                const response = await axiosInstance.put(`/ticket/${code}`, payload)

                this.success = response.data.message

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteTicket(code) {
            this.loading = true

            try {
                const response = await axiosInstance.delete(`/ticket/${code}`)

                this.success = response.data.message

                const authStore = useAuthStore()
                if (authStore.user?.role === 'admin') {
                    router.push({ name: 'admin.ticket' })
                } else {
                    router.push({ name: 'app.dashboard' })
                }
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateTicketReply(id, payload) {
            this.loading = true

            try {
                const response = await axiosInstance.put(`/ticket-reply/${id}`, payload)

                this.success = response.data.message

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteTicketReply(id) {
            this.loading = true

            try {
                const response = await axiosInstance.delete(`/ticket-reply/${id}`)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },
    }
})