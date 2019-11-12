<template>
  <section class="uk-position-relative">
    <div class="uk-overflow-auto">
      <table class="uk-table uk-table-small uk-table-divider uk-table-striped uk-table-hover uk-table-middle">
        <thead>
          <tr>
            <th v-for="column in columns">{{ column.label }}</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items.result">
            <td v-for="column in columns">
              <component :is="column.type.listComponent" :row="row" :id="column.id" />
            </td>
            <td>TODO: Actions</td>
          </tr>
        </tbody>
        <tfoot v-if="items.result.length < items.total">
          <tr>
            <td :colspan="columns.length + 1">
              <pagination :count="items.result.length" :total="items.total" :offset="offset" :limit="limit" @change="(page) => { offset = page.offset }" />
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="uk-overlay-default uk-position-cover" v-if="$apollo.loading">
      <div uk-spinner class="uk-position-center"></div>
    </div>
  </section>
</template>

<script>
    import gql from 'graphql-tag';
    import ContentTypes from '../../../state/ContentTypes';
    import pagination from "./_pagination";

    export default {
        components: { pagination },
        data() {
            return {
                items: {
                    total: 0,
                    result: [],
                },
                offset: 0,
                limit: 5
            }
        },
        apollo: {
            items: {
                query() { return this.query; },
                update(data) { return data[`find${ this.type.id }`] || { total: 0, results: [] }; },
                skip() { return !this.type; },
                variables() {
                    return {
                        offset: this.offset,
                        limit: this.limit,
                    }
                },
            }
        },
        computed: {
            query() {
                return gql`query($offset: Int!, $limit: Int!) {
                    find${ this.type.id }(offset:$offset, limit:$limit) {
                        total
                        result {
                            ${ this.fields }
                        }
                    }
                }`;
            },
            type() {
                return ContentTypes.get(this.$route.params.type);
            },
            fields() {
                return this.columns.map((column) => {
                    return column.type.fieldQuery(column.id);
                }).join("\n");
            },
            columns() {
                return this.type ? this.type.listFields() : [];
            },
        }
    }
</script>
