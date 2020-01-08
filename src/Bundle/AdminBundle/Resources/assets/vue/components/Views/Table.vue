<template>
    <section class="uk-section uk-position-relative">
        <div class="uk-container">

            <view-header :can-create="!embedded && view.actions.create && is_granted('create')" :view="view" :title="title" :query-filter="queryFilter" :deleted="deleted" @toggleDeleted="toggleDeleted" @queryFilterChanged="f => queryFilter = f" />

            <inline-create v-if="embedded && view.actions.create && is_granted('create') && hasInlineCreateForm && !deleted" :view="view" @onCreate="onInstantCreate" :initial-data="initialCreateData" />

            <div v-if="items.result.length > 0" class="uk-overflow-auto table-overflow-container" :class="{ 'with-overflow': overflow }" @scroll="overflow = true">
                <table class="uk-table uk-table-small uk-table-divider uk-table-middle">
                    <thead>
                    <tr>
                        <th v-if="select"></th>
                        <th v-for="field in view.listFields()">{{ field.name }}</th>
                        <th v-if="!select"></th>
                    </tr>
                    </thead>
                    <tbody class="uk-card uk-card-default uk-table-striped">
                    <tr v-for="row in items.result" :class="{ updated: highlightRow === row._meta.id }" :key="row._meta.id" :id="'row-' + row._meta.id">
                        <td v-if="select" class="uk-table-shrink">
                            <button @click.prevent="selectRow(row._meta.id)" class="uk-icon-button uk-icon-button-small" :class="isSelected(row._meta.id) ? 'uk-button-primary' : 'uk-button-default'" uk-icon="check" :title="$t('content.list.selection.select')">
                                <icon v-if="isSelected(row._meta.id)" name="check" />
                            </button>
                        </td>
                        <td v-for="field in view.listFields()">
                            <component :is="$unite.getListFieldType(field)" :view="view" :row="row" :field="field" :embedded="embedded" />
                        </td>
                        <td v-if="!select" class="uk-table-shrink"><actions-field :view="view" :row="row" id="_actions" :embedded="embedded" /></td>
                    </tr>
                    </tbody>
                    <tfoot v-if="pagination && items.result.length < items.total">
                    <tr>
                        <td :colspan="view.listFields().length + 1">
                            <view-pagination :count="items.result.length" :total="items.total" :offset="offset" :limit="view.limit" @change="updateOffset" />
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div v-else-if="!hasInlineCreateForm || !is_granted('create') || deleted" class="uk-card uk-card-default uk-card-body uk-margin uk-padding uk-text-center">
                <div class="uk-placeholder">
                    <icon name="maximize" :width="128" :height="128" style="opacity: 0.125" />
                    <p class="uk-margin">{{ $t('content.list.empty_placeholder') }}</p>
                    <p v-if="is_granted('create') && !embedded">
                        <router-link :to="to('create')" class="uk-button uk-button-default uk-button-small"><icon name="plus" /> {{ $t('content.list.actions.create') }}</router-link>
                    </p>
                </div>
            </div>

            <div class="uk-overlay-default uk-position-cover" v-if="$apollo.loading">
                <div uk-spinner class="uk-position-center"></div>
            </div>
        </div>

        <div class="uk-position-fixed uk-position-bottom uk-background-primary uk-dark uk-padding-small uk-text-center" v-if="select === 'MULTIPLE' && (selection.length > 0 || (initialSelection && initialSelection.length > 0))">
            <button class="uk-button uk-button-default uk-button-small" @click.prevent="confirmSelection">{{ $t('content.list.selection.confirm', { count: selection.length }) }}</button>
        </div>

    </section>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from '../../components/Icon';
    import actionsField from '../Fields/List/_actions';
    import ViewHeader from "./_header";
    import ViewPagination from "./_pagination";
    import _abstract from "./_abstract";
    import InlineCreate from "./_inlineCreate";

    export default {
        extends: _abstract,
        components: { InlineCreate, ViewHeader, ViewPagination, actionsField, Icon },
        data() {
            return {
                overflow: false,
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
            '$route'(route){
                this.overflow = false;
                this.queryFilter = {};
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

            updateOffset(page) {

                if (this.embedded) {
                    this.reloadItems();

                } else {
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
