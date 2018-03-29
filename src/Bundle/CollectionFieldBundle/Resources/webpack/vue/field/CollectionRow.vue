<template>
    <div class="uk-placeholder uk-padding-small">
        <div v-if="prototype" v-html="prototype"></div>
        <button class="close-button" v-html="feather.icons['x'].toSvg({ width: 20, height: 20 })" v-on:click.prevent="removeRow"></button>
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
        props: [
            'delta',
            'prototype'
        ],
        methods: {
            removeRow() {
                this.$emit('remove', { delta: this.delta});
            }
        }
    };
</script>

<style lang="scss"></style>