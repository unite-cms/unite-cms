<template>
  <component :is="$unite.getViewType(view.viewType)" :view="view" />
</template>

<script>
    export default {
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        }
    }
</script>
