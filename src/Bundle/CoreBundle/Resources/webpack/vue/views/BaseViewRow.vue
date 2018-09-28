<template>
    <div class="unite-view-row">
        <div v-for="field in fields"></div>
    </div>
</template>

<script>
    export default {

        data() {

            let fields = Object.keys(this.data).map((key) => {
                return {
                    key: key,
                    value: this.data.value,
                    type: this.resolveType(key),
                };
            });

            return {
                'fields': fields
            };
        },
        props: [
            'data',
            'fieldTypes'
        ],
        methods: {
            resolveType() {

            }
        }
    }
</script>

<style scoped>

</style>