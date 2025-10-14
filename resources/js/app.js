import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import axios from './Plugins/axios'
import vuetify from './Plugins/vuetify'
import toast from './Plugins/toast'
import helpers from './Common/helpers'
import Vue3Charts from 'vue3-charts'

const pinia = createPinia()
const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel'

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ provide: { helpers: helpers }, render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(Vue3Charts)

    const plugins = [axios, vuetify, toast]

    plugins.forEach((plugin) => app.use(plugin))

    return app.mount(el)
  },
  progress: {
    color: '#4CAF50',
  },
})
