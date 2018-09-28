<template>
    <header class="uk-card-header uk-flex">
        <h2 class="uk-flex-1">{{ title }}</h2>
        <a :href="createUrl" class="uk-button uk-button-primary">
            <span class="uk-margin-small-right-small" v-html="feather.icons['plus'].toSvg()"></span>
            <i data-feather="plus" class=""></i>
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
            'createLabel',
            'createUrl'
        ],
    }
</script>

<style scoped>

</style>