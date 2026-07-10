<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-50 text-gray-800">

    <!-- Nav -->
    <header class="sticky top-0 z-30 backdrop-blur-md bg-white/70 border-b border-white/60">
      <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
        <span class="text-lg font-extrabold bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">
          Time Tracking
        </span>
        <div class="flex items-center gap-2">
          <router-link
            to="/login"
            class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-indigo-600 transition-colors"
          >
            Sign in
          </router-link>
          <router-link
            to="/register"
            class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-md hover:shadow-lg transition-all"
          >
            Get started
          </router-link>
        </div>
      </div>
    </header>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-5 pt-20 pb-16 text-center">
      <span class="inline-block text-xs font-semibold tracking-wide uppercase text-indigo-500 bg-indigo-100/70 rounded-full px-3 py-1 mb-5">
        Your time, organized
      </span>
      <h1 class="text-4xl sm:text-6xl font-extrabold leading-tight tracking-tight">
        Track time.
        <span class="bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">Ship tasks.</span>
        <br class="hidden sm:block" />
        Stay in flow.
      </h1>
      <p class="mt-6 text-lg text-gray-500 max-w-2xl mx-auto">
        A personal, single-user workspace that pairs a Kanban board with one-click
        time tracking — plus reports, automations, and a daily email summary that
        keeps you honest.
      </p>
      <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3">
        <router-link
          to="/register"
          class="w-full sm:w-auto px-7 py-3.5 text-base font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-lg hover:shadow-xl transition-all"
        >
          Get started — it's free
        </router-link>
        <router-link
          to="/login"
          class="w-full sm:w-auto px-7 py-3.5 text-base font-semibold text-indigo-600 bg-white border border-indigo-100 rounded-2xl shadow-sm hover:shadow-md transition-all"
        >
          I already have an account
        </router-link>
      </div>

      <!-- Board mock -->
      <div class="mt-16 max-w-4xl mx-auto">
        <div class="rounded-3xl bg-white/80 backdrop-blur-sm shadow-2xl ring-1 ring-black/5 p-4 sm:p-6">
          <div class="flex items-center gap-1.5 mb-4 px-1">
            <span class="w-3 h-3 rounded-full bg-red-400"></span>
            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
            <span class="w-3 h-3 rounded-full bg-green-400"></span>
          </div>
          <div class="grid grid-cols-3 gap-3 sm:gap-4 text-left">
            <div v-for="col in mockBoard" :key="col.name" class="bg-slate-50 rounded-2xl p-3">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ col.name }}</span>
                <span class="text-[10px] font-semibold text-gray-400 bg-white rounded-full px-2 py-0.5">{{ col.cards.length }}</span>
              </div>
              <div class="space-y-2">
                <div
                  v-for="card in col.cards"
                  :key="card.title"
                  class="bg-white rounded-xl p-2.5 shadow-sm border-l-2"
                  :style="{ borderColor: card.color }"
                >
                  <div class="text-xs font-semibold text-gray-700">{{ card.title }}</div>
                  <div class="text-[10px] text-gray-400 mt-1">{{ card.meta }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-5 py-16">
      <h2 class="text-center text-3xl font-extrabold mb-3">Everything in one place</h2>
      <p class="text-center text-gray-500 mb-12 max-w-xl mx-auto">
        No sprawling tool. Just the pieces you actually use to plan work and see where your hours go.
      </p>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="f in features"
          :key="f.title"
          class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-sm ring-1 ring-black/5 hover:shadow-lg hover:-translate-y-0.5 transition-all"
        >
          <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xl mb-4">
            {{ f.icon }}
          </div>
          <h3 class="font-bold text-gray-800 mb-1.5">{{ f.title }}</h3>
          <p class="text-sm text-gray-500 leading-relaxed">{{ f.body }}</p>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="max-w-6xl mx-auto px-5 pb-20">
      <div class="rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 px-8 py-14 text-center shadow-xl">
        <h2 class="text-3xl font-extrabold text-white">Ready to see where your time goes?</h2>
        <p class="mt-3 text-indigo-100 max-w-lg mx-auto">
          Set up your first board in under a minute and start tracking today.
        </p>
        <router-link
          to="/register"
          class="inline-block mt-8 px-8 py-3.5 text-base font-semibold text-indigo-600 bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all"
        >
          Create your account
        </router-link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/60">
      <div class="max-w-6xl mx-auto px-5 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-400">
        <span class="font-semibold bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">Time Tracking</span>
        <span>Personal time tracking &amp; task management.</span>
        <div class="flex gap-4">
          <router-link to="/login" class="hover:text-indigo-600 transition-colors">Sign in</router-link>
          <router-link to="/register" class="hover:text-indigo-600 transition-colors">Get started</router-link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
const features = [
  { icon: '🗂️', title: 'Kanban boards', body: 'Organize work into columns and drag cards through your workflow. Labels, checklists, subtasks, and due dates included.' },
  { icon: '⏱️', title: 'One-click time tracking', body: 'Start a timer on any task or project. Idle timers stop themselves so your hours stay accurate.' },
  { icon: '📊', title: 'Reports & export', body: 'See time logged per project and task, then export to CSV whenever you need the raw numbers.' },
  { icon: '⚙️', title: 'Automations', body: 'Rules that move cards, set due dates, and run on a schedule — so routine board upkeep happens for you.' },
  { icon: '📧', title: 'Daily email reports', body: 'A morning summary per board: what’s overdue, due this week, time tracked, and what you completed.' },
  { icon: '🔒', title: 'Private by design', body: 'A single-user workspace that’s yours alone — your boards, your data, no team clutter.' },
];

const mockBoard = [
  {
    name: 'To do',
    cards: [
      { title: 'Draft Q3 roadmap', meta: 'Due Fri · High', color: '#ef4444' },
      { title: 'Review invoices', meta: 'Due Mon', color: '#6366f1' },
    ],
  },
  {
    name: 'In progress',
    cards: [
      { title: 'Landing page', meta: '2h 15m tracked', color: '#8b5cf6' },
    ],
  },
  {
    name: 'Done',
    cards: [
      { title: 'Ship email reports', meta: 'Completed', color: '#22c55e' },
      { title: 'Fix board switcher', meta: 'Completed', color: '#22c55e' },
    ],
  },
];
</script>
