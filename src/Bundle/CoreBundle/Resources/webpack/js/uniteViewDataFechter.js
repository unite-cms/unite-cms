
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
    deletedArgument: false,

    create(bag, fieldQuery = [], filterQuery = []) {
        let fetcher = cloneDeep(this);

        fetcher.fieldQuery = fieldQuery;
        fetcher.filterQuery = filterQuery;

        if(fetcher.fieldQuery.indexOf('id') < 0) {
            fetcher.fieldQuery.push('id');
        }

        if(fetcher.fieldQuery.indexOf('deleted') < 0) {
            fetcher.fieldQuery.push('deleted');
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

        let contentTypeName = bag.settings.contentType.charAt(0).toUpperCase() + bag.settings.contentType.slice(1);
        fetcher.queryMethod = 'find' + contentTypeName;
        fetcher.updateMethod = 'update' + contentTypeName;
        fetcher.updateDataObjectName = contentTypeName + 'ContentInput';

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
        this.deletedArgument = deleted;
        return this;
    },

    fetch(page = null, limit = null) {

        page = page ? page : this.page;
        limit = limit ? limit : this.limit;

        let filter = this.filterArgument;
        filter = Object.keys(filter).length > 0 ? { AND: [filter, this.searchArgument] } : this.searchArgument;

        if(this.deletedArgument) {
            let deletedFilter = { field: "deleted", operator: "IS NOT NULL" };
            filter = Object.keys(filter).length > 0 ? { AND: [filter, deletedFilter] } : deletedFilter;
        }

        return new Promise((resolve, reject) => {
            this.client.request(`
              query(
                $limit: Int,
                $page: Int,
                $sort: [SortInput],
                $filter: FilterInput,
                $deleted: Boolean
              ) {
                result: ` + this.queryMethod + `(limit: $limit, page: $page, sort: $sort, filter: $filter, deleted: $deleted) {
                    page,
                    total,
                    _permissions {
                        CREATE_CONTENT
                    },
                    result {
                        _permissions {
                            UPDATE_CONTENT,
                            DELETE_CONTENT
                        },
                        ` + this.fieldQuery.join(',') + `
                    }
                },
                deleted: ` + this.queryMethod + `(limit: 1, filter: { field: "deleted", operator: "IS NOT NULL" }, deleted: true) {
                    total
                }
              }`, {
                limit: limit,
                page: page,
                filter: filter,
                deleted: this.deletedArgument,
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
        return new Promise((resolve, reject) => {
            this.client.request(`
              mutation($id: ID!, $data: ` + this.updateDataObjectName + `!) {
                ` + this.updateMethod + `(id: $id, data: $data, persist: true) {
                    _permissions {
                        UPDATE_CONTENT,
                        DELETE_CONTENT
                    },
                    ` + this.fieldQuery.join(',') + `
                }
              }`, {
                id: id,
                data: data
            }).then(
                (data) => {
                    resolve(data[this.updateMethod]);
                }
            ).catch((err) => {
                reject(err.response.errors[0].message);
            });

        });
    }
};
