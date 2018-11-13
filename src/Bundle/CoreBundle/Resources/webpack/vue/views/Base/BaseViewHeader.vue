<template>
    <header class="uk-card-header">
        <div class="uk-flex uk-flex-middle">
            <div class="uk-flex uk-flex-1 uk-flex-middle">
                <h2 v-if="showTitle" class="uk-flex-0 uk-flex uk-flex-middle">
                    <span>{{ title }}</span>
                    <span v-if="subTitle" class="uk-text-meta"> / {{ subTitle }}</span>
                </h2>
                <ul class="uk-flex-0 unite-div-table-tabs uk-tab uk-margin-right" v-if="deleted.hasDeleted">
                    <li :class="{ 'uk-active': !deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = false">Active</a></li>
                    <li :class="{ 'uk-active': deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = true">Deleted</a></li>
                </ul>
            </div>
            <form v-if="!sortable" class="unite-div-table-search uk-search uk-search-default uk-margin-right uk-flex-1" v-on:submit.prevent="onSearch">
                <a v-if="searchTerm.length > 0" v-on:click.prevent="onClear" href="" class="uk-search-icon clear" v-html="feather.icons['x'].toSvg()"></a>
                <a v-on:click.prevent="onSearch" href="" class="uk-search-icon-flip" uk-search-icon></a>
                <input v-model="searchTerm" class="uk-search-input" type="search" placeholder="Search..." v-on:keyup="onDebouncedSearch">
            </form>
            <a v-if="!selectable" :target="embedded ? '_blank' : '_self'" :href="createUrl" class="uk-button uk-button-primary">
                <span v-html="feather.icons['plus'].toSvg()"></span>
                {{ createLabel }}
            </a>
        </div>
        <!--div class="unite-div-table-filter" v-if="deleted.hasDeleted || !sortable">
            <ul class="uk-flex-1 unite-div-table-tabs uk-tab" v-if="deleted.hasDeleted">
                <li :class="{ 'uk-active': !deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = false">Active</a></li>
                <li :class="{ 'uk-active': deleted.showDeleted }"><a href="#" v-on:click.prevent="deleted.showDeleted = true">Deleted</a></li>
            </ul>
            <div v-if="!sortable" :class="{ 'uk-flex-0': deleted.hasDeleted, 'uk-flex-1 uk-flex uk-flex-right': !deleted.hasDeleted }">
                <form class="unite-div-table-search uk-search uk-search-default" v-on:submit.prevent="onSearch">
                    <a v-if="searchTerm.length > 0" v-on:click.prevent="onClear" href="" class="uk-search-icon clear" v-html="feather.icons['x'].toSvg()"></a>
                    <a v-on:click.prevent="onSearch" href="" class="uk-search-icon-flip" uk-search-icon></a>
                    <input v-model="searchTerm" class="uk-search-input" type="search" placeholder="Search..." v-on:keyup="onDebouncedSearch">
                </form>
            </div>
        </div-->
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
        props: [
            'deleted',
            'title',
            'subTitle',
            'showTitle',
            'createLabel',
            'createUrl',
            'selectable',
            'sortable',
            'embedded'
        ],
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

<style scoped>

</style>
