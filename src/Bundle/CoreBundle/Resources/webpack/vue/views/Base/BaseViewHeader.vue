<template>
    <header class="uk-card-header">
        <div class="uk-flex">
            <h2 class="uk-flex-1 uk-flex uk-flex-middle">
                <span class="uk-margin-small-right">{{ title }}</span>
                <span v-if="subTitle" class="uk-text-meta"> / {{ subTitle }}</span>
            </h2>
            <a :href="createUrl" class="uk-button uk-button-primary">
                <span v-html="feather.icons['plus'].toSvg()"></span>
                {{ createLabel }}
            </a>
        </div>
        <ul class="unite-card-table-tabs uk-tab" v-if="deleted.hasDeleted">
            <li :class="{ 'uk-active': !deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = false">Active Content</a></li>
            <li :class="{ 'uk-active': deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = true">Deleted Content</a></li>
        </ul>
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
            'deleted',
            'title',
            'subTitle',
            'createLabel',
            'createUrl'
        ],
    }
</script>

<style scoped>

</style>
