<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { useTicketStore } from '@/stores/ticket';
import { useAuthStore } from '@/stores/auth';
import { storeToRefs } from 'pinia';
import { capitalize } from 'lodash';
import feather from 'feather-icons'
import { DateTime } from 'luxon';
import { useRoute } from 'vue-router';

const ticketStore = useTicketStore()
const authStore = useAuthStore()
const { success, error, loading } = storeToRefs(ticketStore)
const { user } = storeToRefs(authStore)
const { fetchTicket, createTicketReply, updateTicket, deleteTicket, updateTicketReply, deleteTicketReply } = ticketStore

const route = useRoute()

const ticket = ref({})
const form = ref({
    content: '',
    attachment: null
})

const isEditingTicket = ref(false)
const editTicketForm = ref({
    title: '',
    description: '',
    priority: '',
})

const editingReplyId = ref(null)
const editReplyForm = ref({
    content: '',
})

const fetchTicketDetail = async () => {
    const response = await fetchTicket(route.params.code)

    ticket.value = response
    form.value.status = response.status
}

const handleSubmit = async () => {
    await createTicketReply(route.params.code, form.value)

    error.value = null
    form.value.content = '' 
        
    await fetchTicketDetail()
}

const handleEditTicket = () => {
    editTicketForm.value = {
        title: ticket.value.title,
        description: ticket.value.description,
        priority: ticket.value.priority,
    }
    isEditingTicket.value = true
    nextTick(() => feather.replace())
}

const handleUpdateTicket = async () => {
    await updateTicket(route.params.code, editTicketForm.value)
    if (!error.value) {
        isEditingTicket.value = false
        await fetchTicketDetail()
    }
}

const handleDeleteTicket = async () => {
    if (confirm('Apakah Anda yakin ingin menghapus tiket ini?')) {
        await deleteTicket(route.params.code)
    }
}

const handleEditReply = (reply) => {
    editingReplyId.value = reply.id
    editReplyForm.value.content = reply.content
    nextTick(() => feather.replace())
}

const handleUpdateReply = async (id) => {
    await updateTicketReply(id, editReplyForm.value)
    if (!error.value) {
        editingReplyId.value = null
        await fetchTicketDetail()
    }
}

const handleDeleteReply = async (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus balasan ini?')) {
        await deleteTicketReply(id)
        await fetchTicketDetail()
    }
}

onMounted(async () => {
    await fetchTicketDetail()

    feather.replace()
})
</script>

<template>
    <!-- Back Button -->
    <div class="mb-6">
        <RouterLink :to="{ name: 'app.dashboard' }"
            class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800">
            <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
            Kembali ke Daftar Tiket
        </RouterLink>
    </div>

    <!-- Ticket Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-6">
            <div v-if="!isEditingTicket" class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ ticket.title }}</h1>
                    <div class="mt-2 flex items-center space-x-4">
                        <span class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full">
                            {{ capitalize(ticket.status) }}
                        </span>
                        <span class="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 rounded-full">
                            {{ capitalize(ticket.priority) }}
                        </span>
                        <span class="text-sm text-gray-500">{{ ticket.code }}</span>
                        <span class="text-sm text-gray-500">
                            Dibuat pada {{ DateTime.fromISO(ticket.created_at).toFormat('dd MMMM yyyy, HH:mm') }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="handleEditTicket" v-if="user?.id === ticket.user_id"
                        class="p-2 text-gray-400 hover:text-blue-600 transition-colors">
                        <i data-feather="edit-2" class="w-5 h-5"></i>
                    </button>
                    <button @click="handleDeleteTicket" v-if="user?.id === ticket.user_id || user?.role === 'admin'"
                        class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                        <i data-feather="trash-2" class="w-5 h-5"></i>
                    </button>
                    <a v-if="ticket.attachment" :href="ticket.attachment" target="_blank"
                        class="px-4 py-2 border border-blue-200 bg-blue-50 rounded-lg text-sm text-blue-600 hover:bg-blue-100 transition-colors">
                        <i data-feather="download" class="w-4 h-4 inline-block mr-2"></i>
                        Lampiran
                    </a>
                </div>
            </div>

            <!-- Edit Ticket Form -->
            <div v-else class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Tiket</label>
                        <input v-model="editTicketForm.title" type="text"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <select v-model="editTicketForm.priority"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea v-model="editTicketForm.description" rows="4"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button @click="isEditingTicket = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                    <button @click="handleUpdateTicket"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Discussion Thread -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <!-- Thread Header -->
        <div class="p-6 border-b border-gray-100" v-for="reply in ticket.ticket_replies" :key="reply.id">
            <div class="flex items-start space-x-4">
                <img :src="`https://ui-avatars.com/api/?name=${reply.user.name}&background=0D8ABC&color=fff`" alt="User"
                    class="w-10 h-10 rounded-full">
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-800">{{ reply.user.name }}</h4>
                            <p class="text-xs text-gray-500">
                                {{ DateTime.fromISO(reply.created_at).toFormat('dd MMMM yyyy, HH:mm') }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2" v-if="editingReplyId !== reply.id">
                            <button @click="handleEditReply(reply)" v-if="user?.id === reply.user_id && reply.user.role !== 'admin'"
                                class="p-1 text-gray-400 hover:text-blue-600 transition-colors">
                                <i data-feather="edit-2" class="w-4 h-4"></i>
                            </button>
                            <button @click="handleDeleteReply(reply.id)" v-if="(user?.id === reply.user_id && reply.user.role !== 'admin') || user?.role === 'admin'"
                                class="p-1 text-gray-400 hover:text-red-600 transition-colors">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="editingReplyId !== reply.id" class="mt-3 text-sm text-gray-800">
                        <p>{{ reply.content }}</p>
                        
                        <div v-if="reply.attachment" class="mt-3">
                            <a :href="reply.attachment" target="_blank" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded border border-blue-100">
                                <i data-feather="paperclip" class="w-3 h-3 mr-1"></i>
                                Lihat Lampiran
                            </a>
                        </div>
                    </div>

                    <!-- Edit Reply Form -->
                    <div v-else class="mt-3 space-y-3">
                        <textarea v-model="editReplyForm.content"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500"
                            rows="3"></textarea>
                        <div class="flex justify-end space-x-2">
                            <button @click="editingReplyId = null"
                                class="px-3 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                            <button @click="handleUpdateReply(reply.id)"
                                class="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reply Form -->
        <div class="p-6 border-t border-gray-100">
            <h4 class="text-sm font-medium text-gray-800 mb-4">Tambah Balasan</h4>
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div v-if="user?.role === 'admin'" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Tiket</label>
                    <select v-model="form.status"
                        class="w-full md:w-1/3 px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                        <option value="open">Open</option>
                        <option value="onprogress">On Progress</option>
                        <option value="rejected">Rejected</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="group">
                    <textarea v-model="form.content"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500 ring-red-500': error?.content }" rows="4"
                        placeholder="Tulis balasan Anda di sini..."></textarea>
                    <p class="mt-1 text-xs text-red-500" v-if="error?.content">
                        {{ error?.content?.join(', ') }}
                    </p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="relative overflow-hidden inline-block leading-normal">
                             <button type="button" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 flex items-center">
                                <i data-feather="paperclip" class="w-4 h-4 mr-2"></i>
                                {{ form.attachment ? form.attachment.name : 'Lampiran' }}
                            </button>
                            <input type="file" @change="e => form.attachment = e.target.files[0]"
                                class="absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer" />
                        </div>
                        <p v-if="form.attachment" class="text-xs text-gray-500 italic truncate max-w-[150px]">
                            {{ form.attachment.name }}
                        </p>
                    </div>
                    <button class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                        <i data-feather="send" class="w-4 h-4 inline-block mr-2"></i>
                        <span v-if="!loading">
                                Kirim Balasan
                            </span>
                            <span v-else>
                                Loading...
                            </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>