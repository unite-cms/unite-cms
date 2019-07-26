<template>
    <span v-if="row.can('update')" class="uk-sortable-handle" v-html="feather.icons['move'].toSvg({ width: 16, height: 16 })"></span>
</template>

<script>

    import BaseField from '../Base/AbstractRowField';
    import feather from 'feather-icons';

    export default {
        FIELD_WIDTH_COLLAPSED: true,
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
<style lang="scss" scoped>
    span {
        height: 30px;
        width: 40px;
        line-height: 30px;
        display: block;
        text-align: center;
        margin-left: -13px;
    }
</style>
