<template>

    <header class="uk-card-header">
        <div class="uk-flex uk-flex-middle unite-table-header">
            <div class="uk-flex uk-flex-1 uk-flex-middle">
                <h2 class="uk-flex-0 uk-flex uk-flex-middle">
                    <span class="unite-table-headline">{{ config.title }}</span>
                    <span v-if="config.subTitle" class="uk-text-meta"> / {{ config.subTitle }}</span>
                </h2>
                <ul class="uk-flex-0 unite-table-tabs uk-tab" v-if="config.hasDeletedContent">
                    <li :class="{ 'uk-active': !config.showOnlyDeletedContent }"><a href="#" v-on:click.prevent="config.showOnlyDeletedContent = false">{{ config.t('Active') }}</a></li>
                    <li :class="{ 'uk-active': config.showOnlyDeletedContent }"><a href="#" v-on:click.prevent="config.showOnlyDeletedContent = true">{{ config.t('Deleted') }}</a></li>
                </ul>
            </div>
            <a v-if="config.can('create') && !config.selectable()" :target="config.embedded ? '_blank' : '_self'" :href="config.url('create')" class="uk-button uk-button-primary">
                <span v-html="feather.icons['plus'].toSvg()"></span>
                {{ config.t('create') }}
            </a>
        </div>
    </header>

</template>

<script>

    import feather from 'feather-icons';
    import debounce from 'lodash/debounce';

    export default {
        data() {
            let d = debounce(this.onSearch, 250);
            return {
                debounceSearch: d,
                searchTerm: '',
                feather: feather
            }
        },
        props: {
            config: Object
        },
        methods: {
            onDebouncedSearch(e) {
                if(e.key !== 'Enter') {
                    this.debounceSearch();
                }
            },
            onSearch() {
                this.$emit('search', this.searchTerm);
            },
            onClear() {
                this.searchTerm = '';
                this.onSearch();
            }
        }
    }
</script>
