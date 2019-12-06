import Vue from 'vue'
import './plugins/vuetify'
import VueResource from 'vue-resource';
import App from './App.vue'

Vue.use(VueResource);
Vue.config.productionTip = false
Vue.http.options.emulateJSON = true

new Vue({
  render: function (h) { return h(App) },
}).$mount('#app')
