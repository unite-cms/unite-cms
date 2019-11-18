<template>
  <section class="uk-height-1-1">
    <router-view></router-view>
    <div class="uk-overlay-default uk-position-cover" v-if="loading">
      <div uk-spinner class="uk-position-center"></div>
    </div>
  </section>
</template>

<script>
    export default {
        data() {
            return {
                loading: true,
            };
        },
        mounted() {
            this.$unite.$on('loaded', () => { this.loading = false; });
            if(this.$unite.loaded) {
                this.loading = false;
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        }
    }
</script>

<style scoped>

</style>
