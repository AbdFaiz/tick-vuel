<script setup>
import { onMounted, ref, nextTick } from 'vue';
import { useTicketStore } from '@/stores/ticket';
import { useAuthStore } from '@/stores/auth';
import { storeToRefs } from 'pinia';
import { capitalize } from 'lodash';
import feather from 'feather-icons'
import { DateTime } from 'luxon';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute()
const router = useRouter()

const ticket = ref({})
const form = ref({
    status: '',
    content: '',
    attachment: null,
})

const isEditingTicket = ref(false)
const editTicketForm = ref({
    title: '',
    description: '',
    priority: '',
    status: '',
})

const editingReplyId = ref(null)
const editReplyForm = ref({
    content: '',
})

const ticketStore = useTicketStore()
const authStore = useAuthStore()
const { success, error, loading } = storeToRefs(ticketStore)
const { user } = storeToRefs(authStore)
const { fetchTicket, createTicketReply, updateTicket, deleteTicket, updateTicketReply, deleteTicketReply } = ticketStore

const fetchTicketDetail = async () => {
    const response = await fetchTicket(route.params.code)

    ticket.value = response
    form.value.status = response.status
}

const handleSubmit = async () => {
    await createTicketReply(route.params.code, form.value)

    if (!error.value) {
        form.value.content = '' 
        form.value.attachment = null
        await fetchTicketDetail()
    }
}

const handleResolveTicket = async () => {
    if (confirm('Selesaikan tiket ini?')) {
        await updateTicket(route.params.code, { status: 'resolved' })
        await fetchTicketDetail()
    }
}

const handleEditTicket = () => {
    editTicketForm.value = {
        title: ticket.value.title,
        description: ticket.value.description,
        priority: ticket.value.priority,
        status: ticket.value.status,
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
    <div class="p-6">
        <!-- Ticket Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 font-sans">
            <div class="p-6">
                <div v-if="!isEditingTicket" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">{{ ticket.title }}</h3>
                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wider" :class="{
                                'text-blue-700 bg-blue-50 border border-blue-200': ticket.status === 'open',
                                'text-yellow-700 bg-yellow-50 border border-yellow-200': ticket.status === 'onprogress',
                                'text-green-700 bg-green-50 border border-green-200': ticket.status === 'resolved',
                                'text-red-700 bg-red-50 border border-red-200': ticket.status === 'rejected'
                            }">
                                {{ capitalize(ticket.status) }}
                            </span>

                            <span class="px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wider" :class="{
                                'text-red-700 bg-red-50 border border-red-200': ticket.priority === 'high',
                                'text-yellow-700 bg-yellow-50 border border-yellow-200': ticket.priority === 'medium',
                                'text-green-700 bg-green-50 border border-green-200': ticket.priority === 'low'
                            }">
                                {{ capitalize(ticket.priority) }}
                            </span>

                            <span class="text-sm text-gray-500 font-medium">Dilaporkan oleh <span class="text-gray-900">{{ ticket.user?.name }}</span></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3">
                        <button @click="handleEditTicket"
                            class="p-2 text-gray-400 hover:text-blue-600 transition-colors bg-white rounded-lg border border-gray-100 shadow-sm">
                            <i data-feather="edit-2" class="w-5 h-5"></i>
                        </button>
                        <button @click="handleDeleteTicket"
                            class="p-2 text-gray-400 hover:text-red-600 transition-colors bg-white rounded-lg border border-gray-100 shadow-sm">
                            <i data-feather="trash-2" class="w-5 h-5"></i>
                        </button>
                        <a v-if="ticket.attachment" :href="ticket.attachment" target="_blank"
                            class="px-4 py-2 bg-white border border-blue-200 rounded-lg text-sm font-medium text-blue-600 hover:bg-blue-50 transition-colors flex items-center shadow-sm">
                            <i data-feather="download" class="w-4 h-4 inline-block mr-2"></i>
                            Lampiran
                        </a>
                        <button v-if="ticket.status !== 'resolved'" @click="handleResolveTicket"
                            class="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-bold shadow-md hover:bg-green-700 transition-all transform active:scale-95">
                            <i data-feather="check-circle" class="w-4 h-4 inline-block mr-2"></i>
                            Selesaikan Tiket
                        </button>
                    </div>
                </div>

                <!-- Edit Ticket Form -->
                <div v-else class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Judul Tiket</label>
                            <input v-model="editTicketForm.title" type="text"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Prioritas</label>
                            <select v-model="editTicketForm.priority"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Deskripsi</label>
                        <textarea v-model="editTicketForm.description" rows="4"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-50">
                        <button @click="isEditingTicket = false"
                            class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                        <button @click="handleUpdateTicket"
                            class="px-6 py-2.5 text-sm font-bold bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all transform active:scale-95">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discussion Thread -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 bg-gray-50/30">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i data-feather="message-circle" class="w-5 h-5 mr-2 text-blue-500"></i>
                    Percakapan
                </h3>
            </div>

            <template v-if="ticket.ticket_replies?.length > 0">
                <div v-for="reply in ticket.ticket_replies" :key="reply.id" class="p-6 border-b border-gray-50 hover:bg-gray-50/30 transition-colors">
                <div class="flex items-start space-x-4">
                    <img :src="`https://ui-avatars.com/api/?name=${reply.user.name}&background=0D8ABC&color=fff`"
                        :alt="reply.user.name" class="w-12 h-12 rounded-2xl shadow-sm">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 hover:text-blue-600 transition-colors cursor-pointer">{{ reply.user.name }}</h4>
                                <p class="text-xs text-gray-400 font-medium tracking-wide mt-0.5">
                                    {{ DateTime.fromISO(reply.created_at).toFormat('dd MMMM yyyy, HH:mm') }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2" v-if="editingReplyId !== reply.id">
                                <button @click="handleEditReply(reply)" v-if="user?.id === reply.user_id"
                                    class="p-2 text-gray-300 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                    <i data-feather="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button @click="handleDeleteReply(reply.id)"
                                    class="p-2 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                    <i data-feather="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="editingReplyId !== reply.id" class="mt-3 text-sm text-gray-700 leading-relaxed font-normal p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            {{ reply.content }}
                            <div v-if="reply.attachment" class="mt-3">
                                <a :href="reply.attachment" target="_blank" class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded border border-blue-100">
                                    <i data-feather="paperclip" class="w-3 h-3 mr-1"></i>
                                    Lihat Lampiran
                                </a>
                            </div>
                        </div>

                        <!-- Edit Reply Form -->
                        <div v-else class="mt-4 space-y-4">
                            <textarea v-model="editReplyForm.content"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                rows="3"></textarea>
                            <div class="flex justify-end space-x-2">
                                <button @click="editingReplyId = null"
                                    class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
                                <button @click="handleUpdateReply(reply.id)"
                                    class="px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-lg shadow-sm hover:bg-blue-700 transition-all">Update Balasan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </template>
            <div v-else class="p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i data-feather="message-square" class="w-8 h-8 text-gray-300"></i>
                </div>
                <p class="text-gray-400 font-medium">Belum ada tanggapan pada tiket ini</p>
            </div>

            <div class="p-8 border-t border-gray-100 bg-gray-50/20">
                <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-6">Tambah Jawaban</h4>
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Update Status Tiket</label>
                        <div class="relative w-full md:w-64">
                            <select v-model="form.status"
                                class="w-full appearance-none px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                                <option value="open">Open</option>
                                <option value="onprogress">On Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <i data-feather="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <textarea v-model="form.content" @input="error = null"
                            class="w-full px-5 py-4 border border-gray-200 rounded-2xl text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none"
                            :class="{ 'border-red-500 ring-red-500/10' : error?.content }"
                            rows="5" placeholder="Tulis jawaban atau solusi Anda di sini secara detail..."></textarea>
                        <p class="mt-2 text-xs font-bold text-red-500 flex items-center" v-if="error?.content">
                            <i data-feather="alert-circle" class="w-3 h-3 mr-1"></i>
                            {{ error?.content?.join(', ') }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between pt-4">
                        <div class="relative overflow-hidden inline-block leading-normal">
                             <button type="button" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all flex items-center">
                                <i data-feather="paperclip" class="w-4 h-4 mr-2"></i>
                                {{ form.attachment ? form.attachment.name : 'Lampiran' }}
                            </button>
                            <input type="file" @change="e => form.attachment = e.target.files[0]"
                                class="absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer" />
                        </div>
                        <button type="submit" :disabled="loading"
                            class="px-8 py-3 bg-blue-600 text-white rounded-xl text-sm font-black shadow-xl shadow-blue-500/30 hover:bg-blue-700 hover:shadow-blue-500/40 transition-all transform active:scale-95 flex items-center">
                            <i data-feather="send" class="w-4 h-4 mr-2"></i>
                            <span v-if="!loading">KIRIM JAWABAN</span>
                            <span v-else class="animate-pulse">MENGIRIM...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


</template>