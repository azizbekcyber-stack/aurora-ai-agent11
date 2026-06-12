<script setup lang="ts">
import { Bot, FileText, LayoutDashboard, Radio, Settings, Sparkles, UserRound, Workflow } from '@lucide/vue'

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
          <Sparkles :size="20" aria-hidden="true" />
        </span>
        <span>
          <span class="brand-title">Aurora</span>
          <span class="brand-subtitle">AI Telegram publisher</span>
        </span>
      </NuxtLink>

      <div class="sidebar-rail">
        <div class="rail-label">
          <strong>Approval-first</strong>
          <span class="muted" style="font-size: 0.82rem">Web and bot drafts</span>
        </div>
        <span class="metric-icon">
          <Workflow :size="17" aria-hidden="true" />
        </span>
      </div>

      <nav class="nav-list" aria-label="Dashboard">
        <NuxtLink v-for="item in nav" :key="item.to" class="nav-link" :to="item.to">
          <component :is="item.icon" :size="18" aria-hidden="true" />
          <span>{{ item.label }}</span>
        </NuxtLink>
      </nav>

      <div class="sidebar-footer">
        <div class="button-row" style="margin-bottom: 8px">
          <Bot :size="17" aria-hidden="true" />
          <strong>One publishing flow</strong>
        </div>
        <p class="muted" style="margin: 0; font-size: 0.86rem">
          Create from the dashboard or send prompts to the bot. Both flows publish to the same connected channel.
        </p>
      </div>
    </aside>

    <main class="main">
      <slot />
    </main>
  </div>
</template>
