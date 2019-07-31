<template>
    <span class="uk-text-meta">{{ value }}</span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

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
                    operator: 'ILIKE',
                    value: (value) => { return '%' + value + '%' }
                };
            }
        },

        computed: {

            /**
             * {@inheritdoc}
             */
            value() {
                return this.row.get(this.field.identifier, {}).text;
            },
        },
    }
</script>
