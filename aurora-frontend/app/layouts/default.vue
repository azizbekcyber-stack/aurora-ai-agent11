<script setup lang="ts">
import { Bot, FileText, LayoutDashboard, Radio, Settings, UserRound } from '@lucide/vue'

const nav = [
  { to: '/dashboard', label: 'Overview', icon: LayoutDashboard },
  { to: '/dashboard/drafts', label: 'Drafts', icon: FileText },
  { to: '/dashboard/channels', label: 'Channel', icon: Radio },
  { to: '/dashboard/brand-profile', label: 'Brand', icon: Settings },
  { to: '/login', label: 'Access', icon: UserRound }
]
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <NuxtLink class="brand-mark" to="/dashboard">
        <span class="brand-glyph">
          <Bot :size="20" aria-hidden="true" />
        </span>
        <span>Aurora</span>
      </NuxtLink>

      <nav class="nav-list" aria-label="Dashboard">
        <NuxtLink v-for="item in nav" :key="item.to" class="nav-link" :to="item.to">
          <component :is="item.icon" :size="18" aria-hidden="true" />
          <span>{{ item.label }}</span>
        </NuxtLink>
      </nav>
    </aside>

    <main class="main">
      <slot />
    </main>
  </div>
</template>
