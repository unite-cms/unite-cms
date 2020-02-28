<template>
    <section class="uk-section uk-position-relative">
        <div class="uk-container uk-container-expand">

            <component :is="headerComponent" :can-create="!embedded && view.actions.create && is_granted('create')" :view="view" :title="title" :query-filter="queryFilter" :deleted="deleted" @toggleDeleted="toggleDeleted" @queryFilterChanged="changeQueryFilter" />
            <component :is="inlineCreateComponent" v-if="embedded && view.actions.create && is_granted('create') && hasInlineCreateForm && !deleted" :view="view" @onCreate="onInstantCreate" :initial-data="initialCreateData" />

            <component :is="tableDataComponent" v-if="items.result.length > 0" :fields="view.listFields()" :view="view" :rows="items" :highlightRow="highlightRow" :offset="offset" :select="select" :embedded="embedded" :pagination="pagination" :selection="selection" @updateOffset="updateOffset" @selectRow="selectRow" />
            <component :is="tablePlaceholderComponent" v-else-if="!hasInlineCreateForm || !is_granted('create') || deleted" :create="is_granted('create') && view.actions.create && !embedded" :create-route="to('create')" />

            <component :is="loadingComponent" v-if="$apollo.loading" />
        </div>

        <component :is="confirmSelectionComponent" v-if="select === 'MULTIPLE' && (selection.length > 0 || (initialSelection && initialSelection.length > 0))" :count="selection.length" @confirmSelection="confirmSelection" />

    </section>
</template>

<script>
    import gql from 'graphql-tag';
    import ViewHeader from "./_header";
    import TableData from './_tableData';
    import TablePlaceholder from './_tablePlaceholder';
    import _abstract from "./_abstract";
    import InlineCreate from "./_inlineCreate";
    import LoadingOverlay from "../LoadingOverlay";
    import ConfirmSelection from "./_confirmSelection";

    export default {
        extends: _abstract,
        data() {
            return {
                queryFilter: {},
                items: {
                    total: 0,
                    result: [],
                }
            }
        },
        fragments: {
            adminView: gql`fragment TableAdminViewFragment on TableAdminView {
              limit
              orderBy { field, order },
              filter { field, value, operator, path, cast }
              actions {
                create
                toggle_delete
                filter
                update
                delete
                translate
                revert
                recover
                permanent_delete
                user_invite
              }
              miniPager
          }`
        },
        apollo: {
            items: {
                fetchPolicy: 'network-only',
                query() { return this.query; },
                update(data) {
                    let result = data[`find${ this.view.type }`];
                    return {
                        total: result.total || 0,
                        result: result.result || [],
                    };
                },
                error(error) {
                    console.log(error);
                },
                result() {
                    this.$nextTick(() => {
                        if(this.highlightRow) {
                            let row = this.$el.querySelector('#row-' + this.highlightRow);

                            // If the created form is on this page, scroll to it.
                            if(row) {
                                row.scrollIntoView({behavior: "smooth"});
                            }
                        }
                    })
                },
                variables() {
                    return {
                        offset: this.offset,
                        limit: this.view.limit,
                        filter: this.activeFilter,
                        orderBy: this.orderBy,
                        showDeleted: this.deleted,
                    }
                },
            }
        },
        computed: {

            headerComponent() { return ViewHeader },
            inlineCreateComponent() { return InlineCreate },
            tableDataComponent() { return TableData },
            tablePlaceholderComponent() { return TablePlaceholder },
            loadingComponent() { return LoadingOverlay },
            confirmSelectionComponent() { return ConfirmSelection },

            activeFilter(){
                let deletedFilter = { field: "deleted", operator: this.deleted ? 'NEQ' : 'EQ', value: null };
                return {
                    AND: [deletedFilter, this.filter, this.queryFilter]
                };
            },

            hasInlineCreateForm() {
                return this.view.fields.filter(field => field.inline_create).length > 0;
            },

            query() {
                return gql`
                    ${ this.view.fragment }
                    query($offset: Int!, $limit: Int!, $filter: UniteFilterInput, $orderBy: [UniteOrderByInput!], $showDeleted: Boolean!) {
                      find${ this.view.type }(offset:$offset, limit:$limit, filter: $filter, orderBy: $orderBy, includeDeleted: $showDeleted) {
                          total
                          result {
                              _meta {
                                  id
                                  deleted
                                  permissions {
                                      read
                                      update
                                      delete
                                      permanent_delete
                                      user_invite
                                  }
                              }
                              ... ${ this.view.id }
                          }
                      }
                  }`;
            },
        },
        watch: {
            '$route'(route, oldRoute){
                if(route.path !== oldRoute.path) {
                    this.queryFilter = {};
                }
                this.reloadItems();
            }
        },
        methods: {

            reloadItems() {
                this.items = {
                    total: 0,
                    result: [],
                };
                this.$apollo.queries.items.refresh();
            },

            toggleDeleted() {
                this.$emit('toggleDeleted');
            },

            changeQueryFilter(filter) {
                this.queryFilter = filter;
                this.updateOffset(0);
            },

            updateOffset(page) {
                if(!this.embedded) {
                    let query = Object.assign({}, this.$route.query);
                    query.offset = page.offset;

                    this.$router.push({
                        path: this.$route.path,
                        query: query,
                    });
                }
                this.$emit('onOffsetChanged', page.offset);
            },

            onInstantCreate(id) {

                if (this.embedded) {
                    this.reloadItems();

                } else {
                    let query = Object.assign({}, this.$route.query);
                    query.updated = id;

                    this.$router.push({
                        path: this.$route.path,
                        query: query,
                    });
                }

                this.$emit('onCreate', id);
            },
        }
    }
</script>
