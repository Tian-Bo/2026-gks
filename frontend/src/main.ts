import Antd from 'ant-design-vue'
import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import 'ant-design-vue/dist/reset.css'
import 'virtual:uno.css'
import './style.css'
import './assets/iconfont/iconfont.css'
import Root from './Root.vue'
import Chat from './App.vue'
import AiHome from './views/AiHome.vue'
import AiHistory from './views/AiHistory.vue'
import KlButton from './components/kl/KlButton.vue'
import KlDateRangePicker from './components/kl/KlDateRangePicker.vue'
import KlDropdown from './components/kl/KlDropdown.vue'
import KlHoverAction from './components/kl/KlHoverAction.vue'

const app = createApp(Root)
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: AiHome },
    { path: '/chat', component: Chat },
    { path: '/history', component: AiHistory },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})
app.use(Antd)
app.use(router)
app.component('KlButton', KlButton)
app.component('KlDateRangePicker', KlDateRangePicker)
app.component('KlDropdown', KlDropdown)
app.component('KlHoverAction', KlHoverAction)
app.mount('#app')
