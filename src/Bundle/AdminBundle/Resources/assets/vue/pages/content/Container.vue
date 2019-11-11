<template>
  <section class="uk-section">
    <h2>{{ type ? type.name : null }}</h2>
    <router-view></router-view>

    <div class="uk-overlay-default uk-position-cover" v-if="loading">
      <div uk-spinner class="uk-position-center"></div>
    </div>
  </section>
</template>

<script>
    import ContentTypes from "../../state/ContentTypes";

    export default {
        data() {
            return {
                loading: true,
            };
        },
        mounted() {
            ContentTypes.$on('loaded', () => { this.loading = false; });
            if(ContentTypes.loaded) {
                this.loading = false;
            }
        },
        computed: {
            type() {
                return ContentTypes.get(this.$route.params.type);
            }
        }
    }
</script>

<style scoped>

</style>
