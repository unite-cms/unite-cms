<template>
    <div class="view-field view-field-text"><p>{{ value }}</p></div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        methods: {

            /**
             * {@inheritdoc}
             */
            fieldQuery(identifier, field) {
                return identifier + ' { text }';
            },

            /**
             * {@inheritdoc}
             */
            filterQuery(identifier, field) {
                return {
                    field: identifier + '.text',
                    operator: 'LIKE',
                    value: (value) => { return '%' + value + '%' }
                };
            }
        },

        computed: {

            /**
             * {@inheritdoc}
             */
            value() {
                return this.row[this.identifier]['text'];
            },
        },
    }
</script>

<style scoped lang="scss">
    .view-field-text {
        white-space: nowrap;

        p {
            display: inline-block;
        }
    }
</style>
