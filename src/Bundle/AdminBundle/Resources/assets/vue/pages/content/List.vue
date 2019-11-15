<template>
  <component v-if="loaded" :is="$unite.getViewType(view.viewType)" :view="view" />
</template>

<script>
    export default {
        data() {
            return {
                loaded: false,
            }
        },
        created(){
            this.$unite.$on('loaded', () => {
                this.loaded = true;
            });
            if(this.$unite.loaded) {
                this.loaded = true;
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        }
    }
</script>
