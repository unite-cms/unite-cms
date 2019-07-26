
import cloneDeep from 'lodash/cloneDeep';
import { GraphQLClient } from 'graphql-request'

const createClient = function(apiUrl, csrf_token = null) {
    let clientConfig = {
        credentials: "same-origin",
        headers: {
            "Authentication-Fallback": true,
        },
    };
    if(csrf_token) {
        clientConfig.headers["X-CSRF-TOKEN"] = csrf_token;
    }
    return new GraphQLClient(apiUrl, clientConfig);
};

export const ViewFetcher = {
    init: function(apiUrl, contentType, csrf_token = null, deletable = false, translatable = false){
        let fetcher = cloneDeep(this);
        
        fetcher._apiUrl = apiUrl;
        fetcher.client = createClient(apiUrl, csrf_token);

        let contentTypeName = contentType.charAt(0).toUpperCase() + contentType.slice(1);

        fetcher._fieldableContentType = contentType.substr(-6) === 'Member' ? 'Member' : 'Content';
        fetcher._translatable = translatable;
        fetcher._deletable = deletable;
        fetcher._softdeletable = fetcher._fieldableContentType === 'Content';
        fetcher._findQuery = 'find' + contentTypeName;
        fetcher._updateQuery = 'update' + contentTypeName;
        fetcher._inputTypeName = contentTypeName + fetcher._fieldableContentType + 'Input';
        fetcher._deletedQueryFragment = fetcher._softdeletable ? `
            deleted: ` + fetcher._findQuery + `(limit: 1, filter: { field: "deleted", operator: "IS NOT NULL" }, deleted: true) {
                total
            }` : '';
        
        fetcher._resultPermissions = [
            'CREATE_' + fetcher._fieldableContentType.toUpperCase(),
        ];
        fetcher._contentPermissions = [
            'UPDATE_' + fetcher._fieldableContentType.toUpperCase(),
            'DELETE_' + fetcher._fieldableContentType.toUpperCase(),
        ];

        if(fetcher._translatable) {
            fetcher._contentPermissions.push('TRANSLATE_' + fetcher._fieldableContentType.toUpperCase());
        }

        return fetcher;
    },

    _translatable: false,
    _softdeletable: false,
    _deletable: false,
    _fieldsQueryFields: [],
    _fieldableContentType: 'Content',
    _apiUrl: '',
    _findQuery: '',
    _updateQuery: '',
    _inputTypeName: '',
    _deletedQueryFragment: '',
    _resultPermissions: [],
    _contentPermissions: [],

    findContent(page = 1, limit = 20, filter = {}, sort = null) {

        if(sort) {
            sort = [{
                field: sort.field,
                order: sort.asc ? 'ASC' : 'DESC',
                ignore_case: sort.field !== '_name',
            }];
        }

        if(this._fieldsQueryFields.indexOf('id') < 0) {
            this._fieldsQueryFields.push('id');
        }

        return this.client.request(`query($limit: Int, $page: Int, $sort: [SortInput], $filter: FilterInput) {
            result: ` + this._findQuery + `(limit: $limit, page: $page, sort: $sort, filter: $filter` + (this._softdeletable ? ', deleted: true' : '') + `) {
                page
                total
                _permissions { ` + this._resultPermissions.join(',') + ` },
                result {
                    _permissions { ` + this._contentPermissions.join(',') + ` }, ` + this._fieldsQueryFields.join(',') + `
                }
            },
            ` + this._deletedQueryFragment + `
        }`, {limit, page, filter, sort});
    },
    updateContent(id, data = {}) {

        if(this._fieldsQueryFields.indexOf('id') < 0) {
            this._fieldsQueryFields.push('id');
        }

        return this.client.request(`mutation($id: ID!, $data: ` + this._inputTypeName + `!) {
            ` + this._updateQuery + `(id: $id, data: $data, persist: true) {
                _permissions { ` + this._contentPermissions.join(',') + ` },
                ` + this._fieldsQueryFields.join(',') + `
            }
            }`, { id, data });
    },
};

export const createFetcher = function(config){
    return ViewFetcher.init(config.url('api'), config.contentType, config.csrfToken, config.deletable, config.translatable);
};

export default ViewFetcher;
