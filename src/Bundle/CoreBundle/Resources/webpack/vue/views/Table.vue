<template>
    <div class="unite-card-table">

        <ul class="unite-card-table-tabs" uk-tab v-if="hasDeletedContent && !selectable">
            <li :class="{'uk-active': !deletedContent}" v-on:click="deletedContent = false"><a href="#">Active Content</a></li>
            <li :class="{'uk-active': deletedContent}" v-on:click="deletedContent = true"><a href="#">Deleted Content</a></li>
        </ul>

        <table class="uk-table uk-table-justify uk-table-divider uk-table-hover">
            <thead>
            <tr>
                <th v-if="selectable"></th>
                <th v-for="(title, header) in columns" v-on:click="setSort(header)" class="sortable">
                    {{ title }}
                    <span v-html="sortArrow(header)"></span>
                </th>
                <th v-if="!selectable">Actions</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="row in content">

                <td v-if="selectable">
                    <button class="uk-button uk-button-secondary uk-button-small" v-on:click="select(row)">Select</button>
                </td>

                <td v-for="field in columnKeys">
                    <span v-if="field == 'created' || field == 'updated'">{{ formatDate(new Date(row[field] * 1000)) }}</span>
                    <span v-else>{{ row[field] }}</span>
                </td>
                <td v-if="!selectable" class="actions">

                    <button class="uk-button uk-button-default actions-dropdown" type="button" v-html="feather.icons['more-horizontal'].toSvg()"></button>
                    <div uk-dropdown="mode: click; pos: bottom-right; offset: 5">
                        <ul class="uk-nav uk-dropdown-nav">
                            <li v-for="action in contentActions(row)"><a :href="action.url" :class="action.class ? action.class : ''"><span class="uk-margin-small-right" v-html="action.icon"></span>{{ action.name }}</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <ul class="uk-pagination uk-flex-center" uk-margin>
            <li v-for="p in pages()" v-bind:class="{'uk-active': p.active}">
                <a v-on:click="setPage(p.page)">{{p.page}}</a>
            </li>
        </ul>
        <div v-if="!loaded" class="uk-text-center" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; background: rgba(255,255,255,0.75);">
            <div style="position: absolute; top: 50%; margin-top: -15px;" uk-spinner></div>
        </div>
    </div>
</template>

<script>
import { GraphQLClient } from 'graphql-request'
import feather from 'feather-icons';

export default {
    data() {
        let bag = JSON.parse(this.parameters);
        return {
            limit: 10,
            page: 1,
            total: 0,
            rows: [],
            loaded: false,
            deletedContent: false,
            selectable: !bag.select.is_mode_none,
            sort: bag.settings.sort.asc,
            sortFieldKey: bag.settings.sort.field,
            filter: bag.settings.filter,
            contentType: bag.settings.contentType,
            hasTranslations: bag.settings.hasTranslations,
            view: bag.settings.view,
            columns: bag.settings.columns,
            columnKeys: Object.keys(bag.settings.columns),
            endpoint: bag.urls.api,
            updateUrlPattern: bag.urls.update,
            deleteUrlPattern: bag.urls.delete,
            recoverUrlPattern: bag.urls.recover,
            deleteDefinitelyUrlPattern: bag.urls.delete_definitely,
            revisionsUrlPattern: bag.urls.revisions,
            translationsUrlPattern: bag.urls.translations,
            feather: feather
        };
    },
    props: ['parameters'],
    created: function () {
        this.client = new GraphQLClient(this.endpoint, {
            credentials: "same-origin",
            headers: {
                "Authentication-Fallback": true
            },
        });

        this.loadData();
    },
    methods: {
        pages: function(){
            var pages = [];
            for(var i = 1; i <= Math.ceil(this.total / this.limit); i++) {
                pages.push({
                    page: i,
                    active: (this.page === i)
                });
            }
            return pages;
        },
        getUpdateUrl: function(id) {
            return this.updateUrlPattern.replace('__id__', id);
        },
        getDeleteUrl: function(id) {
            return this.deleteUrlPattern.replace('__id__', id);
        },
        getRecoverUrl: function(id) {
            return this.recoverUrlPattern.replace('__id__', id);
        },
        getDeleteDefinitelyUrl: function(id) {
            return this.deleteDefinitelyUrlPattern.replace('__id__', id);
        },
        getTranslationsUrl: function(id) {
            return this.translationsUrlPattern.replace('__id__', id);
        },
        getRevisionsUrl: function(id) {
            return this.revisionsUrlPattern.replace('__id__', id);
        },
        setPage: function(page) {
            this.page = page;
            this.loadData();
        },
        setSort: function(field) {
            if(this.sortFieldKey === field) {
                this.sort = !this.sort;
            } else {
                this.sortFieldKey = field;
                this.sort = true;
            }
            this.loadData();
        },
        sortArrow: function(header) {

            if(header !== this.sortFieldKey) {
                return '';
            }

            return feather.icons[(this.sort ? 'arrow-down' : 'arrow-up')].toSvg({
                width: 16,
                height: 16
            });
        },
        contentActions: function(row){
            let actions = [];
            if(!this.deletedContent) {
                actions.push({ url: this.getUpdateUrl(row.id), icon: feather.icons['edit'].toSvg({ width: 24, height: 16 }), name: 'Update content' });

                if(this.hasTranslations) {
                    actions.push({ url: this.getTranslationsUrl(row.id), icon: feather.icons['globe'].toSvg({ width: 24, height: 16 }), name: 'Translate content' });
                }

                actions.push({ url: this.getRevisionsUrl(row.id), icon: feather.icons['skip-back'].toSvg({ width: 24, height: 16 }), name: 'Revisions' });
                actions.push({ url: this.getDeleteUrl(row.id), icon: feather.icons['trash-2'].toSvg({ width: 24, height: 16 }), name: 'Delete content', class: 'uk-text-danger' });
            } else {
                actions.push({ url: this.getRecoverUrl(row.id), icon: feather.icons['rotate-ccw'].toSvg({ width: 24, height: 16 }), name: 'Recover' });
                actions.push({ url: this.getDeleteDefinitelyUrl(row.id), icon: feather.icons['x-circle'].toSvg({ width: 24, height: 16 }), name: 'Delete definitely', class: 'uk-text-danger' });
            }
            return actions;
        },
        formatDate: function(date) {
            return date.getDate()  + "." + (date.getMonth()+1) + "." + date.getFullYear() + " " +
                date.getHours() + ":" + date.getMinutes();
        },
        loadData: function () {
            this.loaded = false;

            var queryMethod = 'find' + this.contentType.charAt(0).toUpperCase() + this.contentType.slice(1);

            this.client.request(`
              query(
                $limit: Int,
                $page: Int,
                $sort: [SortInput],
                $filter: FilterInput
              ) {
                ` + queryMethod + `(limit: $limit, page: $page, sort: $sort, filter: $filter, deleted: true) {
                    page,
                    total,
                    result {
                        id,
                        deleted,
                        ` + this.columnKeys.join(',\n') + `
                    }
                }
              }`, {
                limit: this.limit,
                page: this.page,
                filter: this.filter,
                sort: [{
                    field: this.sortFieldKey,
                    order: (this.sort ? 'ASC' : 'DESC')
                }]
            }).then((data) => {
                this.rows = data[queryMethod].result;
                this.page = data[queryMethod].page;
                this.total = data[queryMethod].total;
                this.loaded = true;
            });
        },
        select: function(row) {
            window.UnitedCMSEventBus.$emit('contentSelected', [ {
                contentType: this.contentType,
                view: this.view,
                row: row
            } ]);
        }
    },
    computed: {
        content() {
            if(this.deletedContent) {
                return this.rows.filter((row) => {
                    return row.deleted !== null;
                });
            }
            else {
                return this.rows.filter((row) => {
                    return row.deleted === null;
                });
            }
        },

        hasDeletedContent() {
            return this.rows.filter((row) => {
                return row.deleted !== null;
            }).length > 0;
        }
    }
};
</script>

<style lang="scss" scoped>
    th.sortable {
        cursor: pointer;
    }
</style>