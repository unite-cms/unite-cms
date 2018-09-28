
import { GraphQLClient } from 'graphql-request'

class uniteViewDataFechter {

};

export default {

    client: null,
    queryMethod: '',

    page: 1,
    limit: 10,
    sortArgument: {},
    filterArgument: {},
    deletedArgument: false,

    create(bag) {
        this.client = new GraphQLClient(bag.endpoint, {
            credentials: "same-origin",
            headers: {
                "Authentication-Fallback": true
            },
        });

        this.queryMethod = 'find' + bag.settings.contentType.charAt(0).toUpperCase() + bag.settings.contentType.slice(1);
        this.sort(bag.settings.sort.field, bag.settings.sort.asc);
        return this;
    },

    sort(field, asc = true) {
        this.sortArgument = {
            field: field,
            order: asc ? 'ASC' : 'DESC'
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
                        id,
                        deleted
                    }
                }
              }`, {
                limit: limit,
                page: page,
                filter: this.filterArgument,
                deleted: this.deletedArgument,
                sort: [this.sortArgument]
            }).then(
                (data) => {
                    this.page = data[this.queryMethod].page;
                    resolve(data[this.queryMethod]);
                }
            ).catch((err) => {
                reject(err.response.errors[0].message);
            });

        });
    }
};