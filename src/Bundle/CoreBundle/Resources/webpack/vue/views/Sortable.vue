<template>
    <div class="unite-card-table">

        <ul class="unite-card-table-tabs" uk-tab v-if="hasDeletedContent && !selectable">
            <li :class="{'uk-active': !deletedContent}" v-on:click="deletedContent = false"><a href="#">Active Content</a></li>
            <li :class="{'uk-active': deletedContent}" v-on:click="deletedContent = true"><a href="#">Deleted Content</a></li>
        </ul>

        <div class="unite-card-div-table uk-table">
            <div class="unite-card-div-table-thead">
                <div>
                    <div v-if="!deletedContent">
                        <span v-if="selectable">Select</span>
                        <span v-if="!selectable">Sort</span>
                    </div>
                    <div v-for="field in columnKeys"></div>
                    <div v-if="!selectable">Actions</div>
                </div>
            </div>

            <div class="unite-card-div-table-tbody" uk-sortable="handle: .uk-sortable-handle; cls-drag: table-div-ghost-row" v-on:moved="moved">
                <div v-for="row in content" :data-id="row.id" :key="row.id">

                    <div v-if="selectable && !deletedContent" class="select">
                        <button v-on:click="select(row)" v-html="selectIcon(row)"></button>
                    </div>

                    <div class="uk-sortable-handle" v-if="!selectable && !deletedContent" v-html="feather.icons['move'].toSvg({
                        width: 16,
                        height: 16
                    })"></div>

                    <div v-for="field in columnKeys">
                        <span v-if="field == 'created' || field == 'updated'">{{ formatDate(new Date(row[field] * 1000)) }}</span>
                        <span v-else>{{ row[field] }}</span>
                    </div>
                    <div class="actions" v-if="!selectable">
                        <button class="uk-button uk-button-default actions-dropdown" type="button" v-html="feather.icons['more-horizontal'].toSvg()"></button>
                        <div uk-dropdown="mode: click; pos: bottom-right; offset: 5">
                            <ul class="uk-nav uk-dropdown-nav">
                                <li v-for="action in contentActions(row)"><a :href="action.url" :class="action.class ? action.class : ''"><span class="uk-margin-small-right" v-html="action.icon"></span>{{ action.name }}</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>

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
import UIkit from 'uikit';
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
            sortField: bag.settings.sort_field,
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
            selected: [],
            feather: feather,
            csrf_token: bag.csrf_token
        };
    },
    props: ['parameters'],
    created: function () {
        this.client = new GraphQLClient(this.endpoint, {
            credentials: "same-origin",
            headers: {
                "Authentication-Fallback": true,
                "X-CSRF-TOKEN": this.csrf_token
            },
        });

        this.loadData();
    },
    mounted: function(){
        let findModal = (element) => {
            if(element.hasAttribute('uk-modal')) {
                return element;
            }

            if(!element.parentElement) {
                return null;
            }

            return findModal(element.parentElement);
        };
        let modal = findModal(this.$el);
        if(modal) {
            UIkit.util.on(modal, 'beforeshow', () => {
                this.selected = [];
            });
        }
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
        contentActions: function(row) {
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
            let queryMethod = 'find' + this.contentType.charAt(0).toUpperCase() + this.contentType.slice(1);

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
                    field: this.sortField,
                    order: 'ASC'
                }]
            }).then((data) => {
                this.rows = data[queryMethod].result;
                this.page = data[queryMethod].page;
                this.total = data[queryMethod].total;
                this.loaded = true;
            });
        },
        select: function(row) {
            if(this.selected.includes(row)) {
                this.selected.splice(this.selected.indexOf(row), 1);
            } else {
                this.selected.push(row);

                // For the moment, we only support single element selection.
                window.UnitedCMSEventBus.$emit('contentSelected', [ {
                    contentType: this.contentType,
                    view: this.view,
                    row: row
                } ]);
            }
        },
        selectIcon: function(row) {
            return feather.icons[(this.selected.includes(row) ? 'check-circle' : 'circle')].toSvg();
        },
        moved: function(event) {

            this.loaded = false;
            let queryMethod = 'update' + this.contentType.charAt(0).toUpperCase() + this.contentType.slice(1);
            let id = event.detail[1].dataset.id;
            let position = UIkit.util.index(event.detail[1]);

            this.client.request(`
              mutation(
                $id: ID!,
                $position: Int
              ) {
                ` + queryMethod + `(id: $id, data: {` + this.sortField + `: $position}) {
                    ` + this.sortField + `
                }
              }`, {
                id: id,
                position: position,
            }).then((data) => {

                if(data[queryMethod][this.sortField] !== position) {
                    UIkit.modal.alert('ERROR: Position could not be saved!');
                }

                this.loadData();
            });
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
    /*th.sortable {
        cursor: pointer;
    }*/
</style>