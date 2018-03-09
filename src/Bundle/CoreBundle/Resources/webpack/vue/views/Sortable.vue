<template>
    <div class="uk-card uk-card-default uk-card-body">

        <div class="uk-clearfix">
            <div class="uk-align-right" v-if="hasDeletedContent && !selectable">
                <ul class="uk-subnav uk-subnav-pill" uk-margin>
                    <li :class="{'uk-active': !deletedContent}" v-on:click="deletedContent = false"><a href="#">Active Content</a></li>
                    <li :class="{'uk-active': deletedContent}" v-on:click="deletedContent = true"><a href="#">Deleted Content</a></li>
                </ul>
            </div>
        </div>

        <div class="uk-container">

            <div class="uk-flex uk-flex-middle">
                <div class="uk-flex-none" v-if="!deletedContent">
                    <span v-if="selectable">Select</span>
                    <span v-if="!selectable">Sort</span>
                </div>
                <div class="uk-flex-auto"></div>
                <div class="uk-flex-none" v-if="!selectable">Actions</div>
            </div>

            <ul class="uk-list uk-list-striped" uk-sortable="handle: .uk-sortable-handle" v-on:moved="moved">
                <li class="uk-flex uk-flex-middle" v-for="row in content" :data-id="row.id" :key="row.id">

                    <div class="uk-flex-none" v-if="selectable && !deletedContent">
                        <button class="uk-button uk-button-secondary uk-button-small" v-on:click="select(row)">Select</button>
                    </div>

                    <div class="uk-flex-none" v-if="!selectable && !deletedContent">
                        <span class="uk-sortable-handle uk-margin-small-right" uk-icon="icon: table"></span>
                    </div>

                    <div class="uk-flex-auto" v-for="field in columnKeys">
                        <span v-if="field == 'created' || field == 'updated'">{{ formatDate(new Date(row[field] * 1000)) }}</span>
                        <span v-else>{{ row[field] }}</span>
                    </div>
                    <div class="uk-flex-none" v-if="!selectable">

                        <div class="uk-button-group" v-show="!deletedContent">
                            <a v-bind:href="getUpdateUrl(row.id)" class="uk-button uk-button-default"><span v-bind:uk-icon="'icon: file-edit'" class="uk-margin-small-right"></span>Update content</a>

                            <div class="uk-inline">
                                <button style="padding: 0 15px;" class="uk-button uk-button-default" type="button">
                                    <span uk-icon="icon: chevron-down"></span>
                                </button>
                                <div uk-dropdown="mode: click; boundary: ! .uk-button-group; boundary-align: true;">
                                    <ul class="uk-nav uk-dropdown-nav">
                                        <li><a class="uk-text-danger" v-bind:href="getDeleteUrl(row.id)"><span class="uk-margin-small-right" v-bind:uk-icon="'icon: trash'"></span> Delete content</a></li>
                                        <li><a v-bind:href="getTranslationsUrl(row.id)"><span class="uk-margin-small-right" v-bind:uk-icon="'icon: world'"></span> Translate</a></li>
                                        <li><a v-bind:href="getRevisionsUrl(row.id)"><span class="uk-margin-small-right" v-bind:uk-icon="'icon: history'"></span> Revisions</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="uk-button-group" v-show="deletedContent">
                            <a v-bind:href="getRecoverUrl(row.id)" class="uk-button uk-button-default"><span v-bind:uk-icon="'icon: bolt'" class="uk-margin-small-right"></span>Recover</a>

                            <div class="uk-inline">
                                <button style="padding: 0 15px;" class="uk-button uk-button-default" type="button">
                                    <span uk-icon="icon: chevron-down"></span>
                                </button>
                                <div uk-dropdown="mode: click; boundary: ! .uk-button-group; boundary-align: true;">
                                    <ul class="uk-nav uk-dropdown-nav">
                                        <li><a class="uk-text-danger" v-bind:href="getDeleteDefinitelyUrl(row.id)"><span class="uk-margin-small-right" v-bind:uk-icon="'icon: trash'"></span> Delete Definitely</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </li>

            </ul>

            <ul class="uk-pagination uk-flex-center" uk-margin>
                <li v-for="p in pages()" v-bind:class="{'uk-active': p.active}">
                    <a v-on:click="setPage(p.page)">{{p.page}}</a>
                </li>
            </ul>
            <div v-if="!loaded" class="uk-text-center" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; background: rgba(255,255,255,0.75);">
                <div style="position: absolute; top: 50%; margin-top: -15px;" uk-spinner></div>
            </div>
        </div>
    </div>
</template>

<script>
import { GraphQLClient } from 'graphql-request'
import UIkit from 'uikit';

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
            window.UnitedCMSEventBus.$emit('contentSelected', [ {
                contentType: this.contentType,
                view: this.view,
                row: row
            } ]);
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