<template>
  <div class="auth-layout">
    <div class="auth-left">
      <div class="auth-brand">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="brand-svg">
          <path d="M3 9.5L12 4l9 5.5V20H3V9.5z"/><rect x="9" y="14" width="6" height="6" rx="0.5"/>
        </svg>
        <span class="brand-text">MBMS</span>
      </div>
      <h1>Modern Bank<br><span>Management System</span></h1>
      <p>Enterprise-grade digital banking platform — secure, scalable, and feature-rich.</p>
      <div class="auth-features">
        <div v-for="f in features" :key="f.text" class="auth-feature">
          <svg v-if="f.icon==='lock'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <svg v-if="f.icon==='zap'"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
          <svg v-if="f.icon==='bar'"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
          <svg v-if="f.icon==='globe'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0"><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 010 18M12 3a14.5 14.5 0 000 18"/></svg>
          <span>{{ f.text }}</span>
        </div>
      </div>
    </div>
    <div class="auth-right">
      <router-view />
    </div>
  </div>
</template>

<script setup>
const features = [
  { icon: 'lock',    text: 'Bank-grade security & 2FA' },
  { icon: 'zap',     text: 'Real-time transaction processing' },
  { icon: 'bar',     text: 'Advanced analytics & reporting' },
  { icon: 'globe',   text: 'Multi-branch, multi-currency' },
]
</script>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
}
.auth-left {
  background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--secondary) 100%);
  padding: 60px 48px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  color: white;
}
.auth-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 48px;
}
.brand-svg { width:28px; height:28px; color:var(--accent); flex-shrink:0; }
.brand-text { font-size: 1.3rem; font-weight: 800; letter-spacing: .1em; color: var(--accent); }
.auth-left h1 {
  font-size: 2.4rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 16px;
}
.auth-left h1 span { color: var(--accent); }
.auth-left p { color: rgba(255,255,255,.7); font-size: 1rem; margin-bottom: 40px; line-height: 1.7; }
.auth-features { display: flex; flex-direction: column; gap: 14px; }
.auth-feature {
  display: flex;
  align-items: center;
  gap: 12px;
  color: rgba(255,255,255,.85);
  font-size: .9rem;
}
.auth-right {
  background: var(--gray-50);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}
@media (max-width: 768px) {
  .auth-layout { grid-template-columns: 1fr; }
  .auth-left { display: none; }
}
</style>
