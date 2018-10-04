<template>
    <header class="uk-card-header uk-flex">
        <h2 class="uk-flex-1 uk-flex uk-flex-middle">
            <span class="uk-margin-small-right">{{ title }}</span>
            <span v-if="subTitle" class="uk-text-meta"> / {{ subTitle }}</span>
        </h2>
        <a :href="createUrl" class="uk-button uk-button-primary">
            <span v-html="feather.icons['plus'].toSvg()"></span>
            {{ createLabel }}
        </a>
    </header>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                feather: feather
            }
        },
        props: [
            'title',
            'subTitle',
            'createLabel',
            'createUrl'
        ],
    }
</script>

<style scoped>

</style>
