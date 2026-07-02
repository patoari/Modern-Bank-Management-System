import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const sidebarOpen    = ref(true)
  const sidebarCollapsed = ref(false)
  const loading        = ref(false)

  function toggleSidebar()   { sidebarOpen.value = !sidebarOpen.value }
  function toggleCollapse()  { sidebarCollapsed.value = !sidebarCollapsed.value }
  function setLoading(val)   { loading.value = val }

  return { sidebarOpen, sidebarCollapsed, loading, toggleSidebar, toggleCollapse, setLoading }
})
