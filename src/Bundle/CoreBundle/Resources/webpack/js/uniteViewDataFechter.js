
import { GraphQLClient } from 'graphql-request'

export default {

    client: null,
    queryMethod: '',
    updateMethod: '',
    updateDataObjectName: '',
    fieldQuery: [],

    page: 1,
    limit: 10,
    sortArgument: {
        field: null,
        order: 'ASC'
    },
    filterArgument: {},
    deletedArgument: false,

    create(bag, fieldQuery = []) {
        this.fieldQuery = fieldQuery;

        if(this.fieldQuery.indexOf('id') < 0) {
            this.fieldQuery.push('id');
        }

        if(this.fieldQuery.indexOf('deleted') < 0) {
            this.fieldQuery.push('deleted');
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

        this.client = new GraphQLClient(bag.endpoint, clientConfig);

        let contentTypeName = bag.settings.contentType.charAt(0).toUpperCase() + bag.settings.contentType.slice(1);
        this.queryMethod = 'find' + contentTypeName;
        this.updateMethod = 'update' + contentTypeName;
        this.updateDataObjectName = contentTypeName + 'ContentInput';
        return this;
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

    withDeleted(deleted = true) {
        this.deletedArgument = deleted;
        return this;
    },

    fetch(page = null, limit = null) {

        page = page ? page : this.page;
        limit = limit ? limit : this.limit;

        let filter = this.filterArgument;

        if(this.deletedArgument) {
            let deletedFilter = { field: "deleted", operator: "IS NOT NULL" };
            filter = filter ? { AND: [filter, deletedFilter] } : deletedFilter;
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
                ` + this.queryMethod + `(limit: $limit, page: $page, sort: $sort, filter: $filter, deleted: $deleted) {
                    page,
                    total,
                    result {
                        ` + this.fieldQuery.join(',') + `
                    }
                }
              }`, {
                limit: limit,
                page: page,
                filter: filter,
                deleted: this.deletedArgument,
                sort: this.sortArgument.field ? [this.sortArgument] : null
            }).then(
                (data) => {
                    this.page = data[this.queryMethod].page;
                    resolve(data[this.queryMethod]);
                }
            ).catch((err) => {
                reject(err.response.errors[0].message);
            });

        });
    },

    countDeleted() {
        return new Promise((resolve, reject) => {
            this.client.request(`
                query($filter: FilterInput) { ` + this.queryMethod + `(limit: 1, filter: $filter, deleted: true) {
                    total
                } }`, { filter: { field: "deleted", operator: "IS NOT NULL" } })
                .then((data) => { resolve(data[this.queryMethod].total); })
                .catch((err) => {reject(err.response.errors[0].message);});

        });
    },

    update(id, data) {
        return new Promise((resolve, reject) => {
            this.client.request(`
              mutation($id: ID!, $data: ` + this.updateDataObjectName + `!) {
                ` + this.updateMethod + `(id: $id, data: $data, persist: true) {
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
