<template>
  <section class="uk-section uk-position-relative">
    <div class="uk-container">
      <div class="uk-flex uk-flex-middle uk-margin-bottom">
        <div class="uk-flex-1 uk-flex uk-flex-middle">
          <h2 class="uk-margin-remove">{{ view.name }}</h2>
          <a href="" class="uk-icon-button uk-margin-small-left" uk-tooltip :title="$t('content.list.actions.toggle_deleted')" :class="{ 'uk-button-danger': $route.query.deleted }" @click.prevent="toggleDeleted"><icon name="trash-2" /></a>
        </div>
        <router-link :to="to('create')" class="uk-button uk-button-primary uk-margin-left"><icon name="plus" /> {{ $t('content.list.actions.create') }}</router-link>
      </div>
      <div class="uk-card uk-card-default uk-overflow-auto">
        <table class="uk-table uk-table-small uk-table-divider uk-table-striped uk-table-middle">
          <thead>
            <tr>
              <th v-for="field in view.listFields()">{{ field.name }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in items.result" :class="{ updated: $route.query.updated === row._meta.id }" :key="row._meta.id">
              <td v-for="field in view.listFields()">
                <component :is="$unite.getListFieldType(field.type)" :row="row" :field="field" />
              </td>
              <td class="uk-table-shrink"><actions-field :row="row" id="_actions" /></td>
            </tr>
          </tbody>
          <tfoot v-if="items.result.length < items.total">
            <tr>
              <td :colspan="view.listFields().length + 1">
                <pagination :count="items.result.length" :total="items.total" :offset="offset" :limit="view.limit" @change="(page) => { offset = page.offset }" />
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="uk-overlay-default uk-position-cover" v-if="$apollo.loading">
        <div uk-spinner class="uk-position-center"></div>
      </div>
    </div>
  </section>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from '../../components/Icon';
    import actionsField from '../Fields/List/_actions';
    import pagination from "./_pagination";
    import _abstract from "./_abstract";

    export default {
        extends: _abstract,
        components: { pagination, actionsField, Icon },
        data() {
            return {
                items: {
                    total: 0,
                    result: [],
                },
                customFilter: null,
                customOrderBy: [],
                offset: 0
            }
        },
        fragments: {
            adminView: gql`fragment TableAdminViewFragment on TableAdminView {
              limit
              orderBy { field, order },
              filter { field, value, operator }
          }`
        },
        apollo: {
            items: {
                fetchPolicy: 'network-only',
                query() { return this.query; },
                update(data) {return data[`find${ this.view.type }`] || { total: 0, results: [] }; },
                variables() {
                    return {
                        offset: this.offset,
                        limit: this.view.limit,
                        filter: this.activeFilter,
                        orderBy: this.customOrderBy || this.view.orderBy,
                        showDeleted: !!this.$route.query.deleted,
                    }
                },
            }
        },
        computed: {
            activeFilter(){
                let filter = this.customFilter || this.view.filter || {};
                let deletedFilter = { field: "deleted", operator: !!this.$route.query.deleted ? 'NEQ' : 'EQ', value: null };
                return {
                    AND: [deletedFilter, filter]
                };
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
                                  version
                                  deleted
                                  permissions {
                                      read
                                      update
                                      delete
                                      permanent_delete
                                  }
                              }
                              ... ${ this.view.id }
                          }
                      }
                  }`;
            },
        },
        methods: {
            toggleDeleted() {

                let query = Object.assign({}, this.$route.query);

                if(this.$route.query.deleted) {
                    delete query.deleted;
                } else {
                    query.deleted = true;
                }

                this.$router.replace({
                    path: this.$route.path,
                    query: query,
                })
            }
        }
    }
</script>
