<template>
    <div class="view-field view-field-sortindex">
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
        }

    }
</script>

<style scoped lang="scss">

    @import "../../../../../node_modules/uikit/src/scss/variables";

    .view-field-sortindex {
        width: 50px;
        height: 50px;
        line-height: 50px;
        padding: 0;
        position: absolute;
        top: 50px;
        right: 0;
        text-align: center;
    }

    @media (min-width: $breakpoint-small) {
        .view-field-sortindex {
            position: relative;
            margin: 0;
            height: auto;
            line-height: normal;
            top: auto;
            bottom: auto;
            left: auto;
            right: auto;
            padding-left: 10px;
        }
    }

</style>
