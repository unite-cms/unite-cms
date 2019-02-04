<template>
    <div>
        <div v-if="!readOnly" class="collection-add-button-wrapper">
          <a href="#" class="uk-button uk-button-default" v-on:click.prevent="addRowBefore" v-html="feather.icons['plus'].toSvg({ width: 20, height: 20 })"></a>
        </div>
        <div class="uk-placeholder uk-padding-small">
            <div v-if="!readOnly" class="uk-sortable-handle" v-html="feather.icons['menu'].toSvg({ width: 16, height: 16 })"></div>
            <div :class="formLayout" v-if="prototype" v-html="prototype"></div>
            <a v-if="!readOnly" href="#" class="close-button" v-html="feather.icons['x'].toSvg({ width: 20, height: 20 })" v-on:click.prevent="removeRow"></a>
        </div>
    </div>
</template>

<script>
    import feather from 'feather-icons';

    export default {
        data() {
            return {
                feather: feather
            };
        },
        mounted() {
            feather.replace();
        },
        props: [
            'delta',
            'prototype',
            'formLayout',
            'labelHidden',
            'readOnly'
        ],
        methods: {
            removeRow() {
                this.$emit('remove', { delta: this.delta });
            },
            addRowBefore() {
                this.$emit('add', { delta: this.delta });
            }
        }
    };
</script>

<style lang="scss"></style>
