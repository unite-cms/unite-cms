<template>
    <span v-if="row.can('update')" class="uk-sortable-handle" v-html="feather.icons['move'].toSvg({ width: 16, height: 16 })"></span>
</template>

<script>

    import BaseField from '../Base/AbstractRowField';
    import feather from 'feather-icons';

    export default {
        extends: BaseField,
        data() {
            return {
                feather: feather
            }
        },
        methods: {
            /**
             * @inheritdoc
             */
            filterQuery(identifier, field) {
                return null;
            },
        }

    }
</script>

