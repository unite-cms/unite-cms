<template>
  <component :is="$unite.getViewType(view.viewType)"
             :view="view"
             :order-by="view.orderBy"
             :filter="view.filter"
             :deleted="!!$route.query.deleted"
             :highlight-row="$route.query.updated"
             :offset="parseInt($route.query.offset || 0)"
             @toggleDeleted="toggleDeleted" />
</template>

<script>
    export default {
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        },
        methods: {
            toggleDeleted() {
                let query = Object.assign({}, this.$route.query);

                if(this.$route.query.deleted) {
                    delete query.deleted;
                } else {
                    query.offset = 0;
                    query.deleted = true;
                }

                this.$router.replace({
                    path: this.$route.path,
                    query: query,
                })
            }
        }
    }
</script>
