<template>
    <div>
        <div class="collection-add-button-wrapper">
            <button  class="uk-button uk-button-default" v-on:click.prevent="addRowBefore" v-html="feather.icons['plus'].toSvg({ width: 20, height: 20 })"></button>
        </div>
        <div class="uk-placeholder uk-padding-small">
            <div class="uk-sortable-handle" v-html="feather.icons['menu'].toSvg({ width: 16, height: 16 })"></div>
            <div :class="formLayout" v-if="prototype" v-html="prototype"></div>
            <button class="close-button" v-html="feather.icons['x'].toSvg({ width: 20, height: 20 })" v-on:click.prevent="removeRow"></button>
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
            'labelHidden'
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