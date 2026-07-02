<template>
  <div class="app-layout" :class="{ 'sidebar-collapsed': ui.sidebarCollapsed }">
    <AppSidebar />
    <div class="main-area">
      <AppTopbar />
      <main class="page-content">
        <transition name="fade" mode="out-in">
          <router-view />
        </transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopbar  from '@/components/layout/AppTopbar.vue'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
</script>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
  --sidebar: var(--sidebar-w);
}
.app-layout.sidebar-collapsed { --sidebar: var(--sidebar-collapsed-w); }
.main-area {
  margin-left: var(--sidebar);
  flex: 1;
  display: flex;
  flex-direction: column;
  transition: margin-left .25s ease;
  min-width: 0;
}
.page-content {
  flex: 1;
  padding: 24px;
  overflow-x: hidden;
}
@media (max-width: 768px) {
  .main-area { margin-left: 0; }
}
</style>
