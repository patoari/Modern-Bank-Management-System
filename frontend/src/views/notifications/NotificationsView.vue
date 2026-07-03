<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
        <p class="text-gray-600 mt-1">Your account alerts and messages</p>
      </div>
      <div class="flex gap-2">
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="px-4 py-2 text-sm border border-blue-600 text-blue-600 rounded hover:bg-blue-50"
        >
          Mark all as read
        </button>
        <router-link
          to="/settings"
          class="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50"
        >
          Preferences
        </router-link>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
      <button
        v-for="tab in ['all', 'unread', 'read']"
        :key="tab"
        @click="activeTab = tab"
        :class="activeTab === tab ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
        class="px-4 py-2 font-medium capitalize"
      >
        {{ tab }}
        <span v-if="tab === 'unread'" class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
          {{ unreadCount }}
        </span>
      </button>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
      <div
        v-for="notification in filteredNotifications"
        :key="notification.id"
        :class="{
          'bg-blue-50 border-l-4 border-blue-600': !notification.read_at,
          'bg-white': notification.read_at,
        }"
        class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition cursor-pointer"
        @click="selectNotification(notification)"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <h3 class="font-semibold text-gray-900">{{ notification.title }}</h3>
            <p class="text-gray-700 text-sm mt-1">{{ notification.message }}</p>
            <div class="flex gap-4 mt-2 text-xs text-gray-500">
              <span>{{ formatDate(notification.created_at) }}</span>
              <span class="bg-gray-200 px-2 py-1 rounded">{{ notification.type }}</span>
            </div>
          </div>
          <div class="flex gap-2 ml-4">
            <button
              v-if="!notification.read_at"
              @click.stop="markAsRead(notification.id)"
              class="text-blue-600 hover:text-blue-800 text-sm"
              title="Mark as read"
            >
              ✓
            </button>
            <button
              @click.stop="deleteNotification(notification.id)"
              class="text-red-600 hover:text-red-800 text-sm"
              title="Delete"
            >
              ✕
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredNotifications.length === 0" class="text-center py-12">
      <p class="text-gray-600">No notifications</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">Loading...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)
const activeTab = ref('all')

const filteredNotifications = computed(() => {
  if (activeTab.value === 'unread') {
    return notifications.value.filter(n => !n.read_at)
  } else if (activeTab.value === 'read') {
    return notifications.value.filter(n => n.read_at)
  }
  return notifications.value
})

const formatDate = (date) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now - d

  if (diff < 60000) return 'Just now'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`
  
  return d.toLocaleDateString()
}

onMounted(() => {
  loadNotifications()
  loadUnreadCount()
  // Refresh every 30 seconds
  setInterval(loadNotifications, 30000)
})

const loadNotifications = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/notifications')
    notifications.value = response.data.data.data || response.data.data
  } catch (err) {
    toast.error('Failed to load notifications')
  } finally {
    loading.value = false
  }
}

const loadUnreadCount = async () => {
  try {
    const response = await axios.get('/api/v1/notifications/unread-count')
    unreadCount.value = response.data.data.unread_count
  } catch (err) {
    // ignore
  }
}

const markAsRead = async (id) => {
  try {
    await axios.post(`/api/v1/notifications/${id}/read`)
    loadNotifications()
    loadUnreadCount()
  } catch (err) {
    toast.error('Failed to mark as read')
  }
}

const markAllAsRead = async () => {
  try {
    await axios.post('/api/v1/notifications/mark-all-read')
    loadNotifications()
    loadUnreadCount()
    toast.success('All notifications marked as read')
  } catch (err) {
    toast.error('Failed to mark all as read')
  }
}

const deleteNotification = async (id) => {
  try {
    await axios.delete(`/api/v1/notifications/${id}`)
    loadNotifications()
  } catch (err) {
    toast.error('Failed to delete notification')
  }
}

const selectNotification = (notification) => {
  if (!notification.read_at) {
    markAsRead(notification.id)
  }
}
</script>
