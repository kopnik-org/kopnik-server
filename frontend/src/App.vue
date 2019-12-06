<template>
  <v-app>
<!--    <v-content>-->
      <NetMap/>
<!--    </v-content>-->
  </v-app>
</template>

<script>
import HelloWorld from './components/HelloWorld'
// import NetMap from './components/NetMap'

export default {
  name: 'App',
  components: {
    HelloWorld,
    NetMap
  },
  data () {
    return {
      //
    }
  }
}
</script>
