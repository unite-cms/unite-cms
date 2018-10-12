<template>
    <header class="uk-card-header">
        <div class="uk-flex">
            <h2 class="uk-flex-1 uk-flex uk-flex-middle">
                <span class="uk-margin-small-right">{{ title }}</span>
                <span v-if="subTitle" class="uk-text-meta"> / {{ subTitle }}</span>
            </h2>
            <a v-if="!selectable" :href="createUrl" class="uk-button uk-button-primary">
                <span v-html="feather.icons['plus'].toSvg()"></span>
                {{ createLabel }}
            </a>
        </div>
        <div class="uk-flex">
            <ul class="uk-flex-1 unite-div-table-tabs uk-tab" v-if="deleted.hasDeleted">
                <li :class="{ 'uk-active': !deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = false">Active</a></li>
                <li :class="{ 'uk-active': deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = true">Deleted</a></li>
            </ul>
        </div>
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
            'createUrl',
            'selectable',
        ],
    }
</script>

<style scoped>

</style>
