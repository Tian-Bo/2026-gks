import Antd from 'ant-design-vue'
import { createApp } from 'vue'
import 'ant-design-vue/dist/reset.css'
import 'virtual:uno.css'
import './style.css'
import './assets/iconfont/iconfont.css'
import App from './App.vue'
import KlbButton from './components/klb/KlbButton.vue'
import KlbDateRangePicker from './components/klb/KlbDateRangePicker.vue'

const app = createApp(App)
app.use(Antd)
app.component('KlbButton', KlbButton)
app.component('KlbDateRangePicker', KlbDateRangePicker)
app.mount('#app')
