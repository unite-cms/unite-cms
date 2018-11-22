<template>
    <div v-if="sortable" class="view-field view-field-sortindex fixed-width">
        <div class="uk-sortable-handle" v-html="feather.icons['move'].toSvg({ width: 16, height: 16 })"></div>
    </div>
</template>

<script>

    import BaseField from '../Base/BaseField.vue';
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

<style scoped lang="scss">

    @import "../../../../../node_modules/uikit/src/scss/variables";

    @media (max-width: $breakpoint-small) {
        .view-field.view-field-sortindex {
            width: 50px;
            height: 50px;
            line-height: 50px;
            padding: 0;
            position: absolute;
            top: 50px;
            right: 0;
            text-align: center;
        }
    }

    .uk-sortable-handle {
        display: inline-block;
        width: 30px;
    }


</style>
