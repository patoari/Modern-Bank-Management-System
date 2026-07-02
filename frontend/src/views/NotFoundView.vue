<template>
  <div class="not-found">
    <div class="not-found-inner">
      <div class="nf-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9.5L12 4l9 5.5V20H3V9.5z"/>
          <rect x="9" y="14" width="6" height="6" rx="0.5"/>
        </svg>
      </div>
      <h1>404</h1>
      <p>Page not found</p>
      <router-link to="/dashboard" class="btn btn-primary">← Back to Dashboard</router-link>
    </div>
  </div>
</template>

<style scoped>
.not-found { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--gray-50); }
.not-found-inner { text-align:center; }
.nf-icon { width:80px; height:80px; margin: 0 auto 16px; color: var(--primary); opacity:.4; }
.nf-icon svg { width:100%; height:100%; }
h1 { font-size:6rem; font-weight:800; color:var(--primary); line-height:1; margin-bottom:8px; }
p  { font-size:1.2rem; color:var(--gray-500); margin-bottom:24px; }
</style>
