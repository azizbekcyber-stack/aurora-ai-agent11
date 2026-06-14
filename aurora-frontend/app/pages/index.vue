<script setup lang="ts">
import { Bot, CheckCircle, FileText, Radio, ShieldCheck, Sparkles } from '@lucide/vue'

useSeoMeta({
  title: 'Aurora AI Telegram Publisher',
  description: 'Create AI-assisted Telegram posts from the web dashboard or Telegram bot, approve the best variant, and publish to one connected channel.',
  ogTitle: 'Aurora AI Telegram Publisher',
  ogDescription: 'AI-assisted Telegram post creation with approval-first publishing.',
  twitterCard: 'summary_large_image'
})
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">AI Telegram publisher</p>
        <h1>Aurora</h1>
        <p class="muted" style="margin: 8px 0 0">
          Generate Telegram post variants from a web dashboard or bot prompt, approve the best one, and publish to your connected channel.
        </p>
      </div>
      <div class="page-actions">
        <NuxtLink class="btn primary" to="/dashboard">
          <Sparkles :size="16" aria-hidden="true" />
          Open dashboard
        </NuxtLink>
        <NuxtLink class="btn" to="/dashboard/channels">
          <Radio :size="16" aria-hidden="true" />
          Channel setup
        </NuxtLink>
      </div>
    </div>

    <div class="metric-grid">
      <div class="metric">
        <span class="metric-icon"><FileText :size="17" aria-hidden="true" /></span>
        <span class="muted">Create</span>
        <strong>Web</strong>
      </div>
      <div class="metric">
        <span class="metric-icon blue"><Bot :size="17" aria-hidden="true" /></span>
        <span class="muted">Capture</span>
        <strong>Bot</strong>
      </div>
      <div class="metric">
        <span class="metric-icon amber"><ShieldCheck :size="17" aria-hidden="true" /></span>
        <span class="muted">Review</span>
        <strong>Approve</strong>
      </div>
      <div class="metric">
        <span class="metric-icon"><CheckCircle :size="17" aria-hidden="true" /></span>
        <span class="muted">Publish</span>
        <strong>Telegram</strong>
      </div>
    </div>

    <div class="content-grid">
      <section class="panel">
        <div class="panel-head">
          <h2>What Aurora does</h2>
        </div>
        <div class="panel-body">
          <div class="timeline">
            <div class="timeline-row">
              <span class="timeline-dot">1</span>
              <div>
                <strong>Turn ideas into variants</strong>
                <p class="muted" style="margin: 3px 0 0">Write a prompt or send a Telegram message. Aurora creates three post options.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">2</span>
              <div>
                <strong>Keep publishing controlled</strong>
                <p class="muted" style="margin: 3px 0 0">Nothing is sent until a variant is selected and approved.</p>
              </div>
            </div>
            <div class="timeline-row">
              <span class="timeline-dot">3</span>
              <div>
                <strong>Publish to one channel</strong>
                <p class="muted" style="margin: 3px 0 0">Web and bot drafts publish to the connected Telegram channel.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2>Production ready path</h2>
        </div>
        <div class="panel-body stack">
          <div class="destination-strip">
            <div class="destination-main">
              <span class="metric-icon">
                <ShieldCheck :size="16" aria-hidden="true" />
              </span>
              <div>
                <span class="destination-title">Secure dashboard token</span>
                <span class="destination-meta">Protects the MVP dashboard before full user accounts.</span>
              </div>
            </div>
          </div>
          <div class="destination-strip">
            <div class="destination-main">
              <span class="metric-icon">
                <Radio :size="16" aria-hidden="true" />
              </span>
              <div>
                <span class="destination-title">HTTPS Telegram webhook</span>
                <span class="destination-meta">Keeps the bot online without localhost or ngrok.</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>
