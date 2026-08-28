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
import KlbButton from './components/klb/KlbButton.vue'
import KlbDateRangePicker from './components/klb/KlbDateRangePicker.vue'
import KlbDropdown from './components/klb/KlbDropdown.vue'
import KlbHoverAction from './components/klb/KlbHoverAction.vue'

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
app.component('KlbButton', KlbButton)
app.component('KlbDateRangePicker', KlbDateRangePicker)
app.component('KlbDropdown', KlbDropdown)
app.component('KlbHoverAction', KlbHoverAction)
app.mount('#app')
