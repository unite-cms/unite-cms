
import { GraphQLClient } from 'graphql-request'

import cloneDeep from 'lodash/cloneDeep';

export default {

    client: null,
    queryMethod: '',
    updateMethod: '',
    updateDataObjectName: '',
    fieldQuery: [],
    filterQuery: [],

    page: 1,
    limit: 10,
    sortArgument: {
        field: null,
        order: 'ASC'
    },
    filterArgument: {},
    searchArgument: {},

    create(bag, fieldQuery = [], filterQuery = []) {
        let fetcher = cloneDeep(this);

        fetcher.fieldQuery = fieldQuery;
        fetcher.filterQuery = filterQuery;

        if(fetcher.fieldQuery.indexOf('id') < 0) {
            fetcher.fieldQuery.push('id');
        }

        let clientConfig = {
            credentials: "same-origin",
            headers: {
                "Authentication-Fallback": true,
            },
        };

        if(bag.csrf_token) {
            clientConfig.headers["X-CSRF-TOKEN"] = bag.csrf_token;
        }

        if(bag.settings.filter) {
            fetcher.filter(bag.settings.filter);
        }

        if(bag.settings.rows_per_page) {
            fetcher.limit = bag.settings.rows_per_page;
        }

        fetcher.client = new GraphQLClient(bag.endpoint, clientConfig);

        let domainMemberTypeName = bag.settings.contentType.charAt(0).toUpperCase() + bag.settings.contentType.slice(1);
        fetcher.queryMethod = 'find' + domainMemberTypeName;

        // Return a copy of this object on create.
        //return Object.assign({}, this);
        return fetcher;
    },

    sort(sort) {
        this.sortArgument = {
            field: sort.field,
            order: sort.asc ? 'ASC' : 'DESC'
        };
        return this;
    },

    filter(filter) {
        this.filterArgument = filter;
        return this;
    },

    search(term) {

        if(!term) {
            this.searchArgument = {};
            return;
        }

        this.searchArgument = { OR: [] };
        this.filterQuery.forEach((filter) => {
            if(filter) {
                this.searchArgument.OR.push({
                    field: filter.field,
                    operator: filter.operator,
                    value: filter.value(term)
                });
            }
        });
        return this;
    },

    withDeleted(deleted = true) {
        // Not supported here.
        return this;
    },

    fetch(page = null, limit = null) {

        page = page ? page : this.page;
        limit = limit ? limit : this.limit;

        let filter = this.filterArgument;
        filter = Object.keys(filter).length > 0 ? { AND: [filter, this.searchArgument] } : this.searchArgument;

        return new Promise((resolve, reject) => {
            this.client.request(`
              query(
                $limit: Int,
                $page: Int,
                $sort: [SortInput],
                $filter: FilterInput
              ) {
                result: ` + this.queryMethod + `(limit: $limit, page: $page, sort: $sort, filter: $filter) {
                    page,
                    total
                    result {
                        ` + this.fieldQuery.join(',') + `
                    }
                }
              }`, {
                limit: limit,
                page: page,
                filter: filter,
                sort: this.sortArgument.field ? [this.sortArgument] : null
            }).then(
                (data) => {
                    this.page = data.result.page;
                    resolve(data);
                }
            ).catch((err) => {
                reject(err.response.errors[0].message);
            });

        });
    },

    update(id, data) {
        // Not supported here.
    }
};
