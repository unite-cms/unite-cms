
import cloneDeep from 'lodash/cloneDeep';
import { createFetcher } from './ViewFetcher';
import { createRow, updateRow } from './ViewRow';

export const SELECT_MODE_NONE = 'SELECT_MODE_NONE';

export const ViewConfig = {
    init: function(parameters){
        let config = cloneDeep(this);
        config.title = parameters.title;
        config.subTitle = parameters.subTitle;
        config._urlPatterns = Object.assign({}, config._urlPatterns, parameters.urls);
        config.selectMode = parameters.select.mode || SELECT_MODE_NONE;
        config.csrfToken = parameters.csrf_token;
        config.contentType = parameters.settings.contentType;
        config.fieldableContentType = config.contentType.substr(-6) === 'Member' ? 'Member' : 'Content';
        config.translatable = parameters.settings.hasTranslations;
        config.deletable = !config.selectable();
        config.view = parameters.settings.View;

        if(parameters.settings.sort) {
            config.sort = Object.assign({}, config.sort, parameters.settings.sort);
        }

        if(parameters.settings.fields) {
            config.fields = Object.keys(parameters.settings.fields).map((identifier) => {
                let field = parameters.settings.fields[identifier];
                field.identifier = identifier;
                return field;
            });
        }

        config.fetcher = createFetcher(config);
        return config;
    },
    _translations: {},
    _urlPatterns: {
        api: null,
        create: null,
        update: null,
        delete: null,
        recover: null,
        delete_definitely: null,
        translations: null,
        revisions: null,
    },
    _permissions: {
        create: false,
    },
    _filterQueryFields: [],
    _staticFilter: {},
    _dynamicFilter: {},
    page: 1,
    limit: 20,
    total: 0,
    loading: false,
    title: null,
    subTitle: null,
    view: null,
    embedded: false,
    fetcher: null,
    contentType: null,
    fieldableContentType: null,
    selectMode: SELECT_MODE_NONE,
    showOnlyDeletedContent: false,
    hasDeletedContent: false,
    hasTranslations: false,
    selectable: function() {
        return this.selectMode !== SELECT_MODE_NONE;
    },
    csrfToken: null,
    sort: {
        field: null,
        asc: true,
        sortable: false,
    },
    fields: [],


    url(name, id = null) {
        let url = this._urlPatterns[name] || null;

        if(!url) {
            return null;
        }
        return (name == 'api' || name == 'create') ? url : url.replace('__id__', id);
    },

    t(phrase) {
        return this._translations[phrase] || phrase;
    },

    can(permission) {
        return this._permissions[permission] || false;
    },

    loadRows() {
        let filter = {
            AND: [{ field: "deleted", operator: this.showOnlyDeletedContent ? "IS NOT NULL" : "IS NULL" }]
        };

        if(Object.values(this._staticFilter).length > 0) {
            filter.AND.push(this._staticFilter);
        }

        if(Object.values(this._dynamicFilter).length > 0) {
            filter.AND.push(this._staticFilter);
        }

        this.loading = true;
        return this.fetcher.findContent(this.page, this.limit, filter, this.sort)
            .then((result) => {
                this.hasDeletedContent = result.deleted && result.deleted.total > 0;
                this.total = result.result.total;

                this._permissions.create = result.result._permissions[
                    this.fieldableContentType === 'Content' ? 'CREATE_CONTENT' : 'CREATE_MEMBER'
                ];

                // Create view row objects out of response.
                return result.result.result.map((graphql_row) => {
                    return createRow(graphql_row, this.fieldableContentType);
                });
            })
            .finally(() => this.loading = false);
    },

    updateRow(row, data) {
        this.loading = true;
        return this.fetcher.updateContent(row.id, data)
            .then((result) => {
                updateRow(row, result[this.fetcher._updateQuery]);
                return row;
            })
            .finally(() => this.loading = false);
    },
};

export const createConfig = function(parameters, $uniteCMSViewFields = null){
    parameters = typeof parameters === 'object' ? parameters : JSON.parse(parameters);
    let config = ViewConfig.init(parameters);

    if($uniteCMSViewFields) {
        config.fetcher._fieldsQueryFields = config.fields.map((field) => {
            return $uniteCMSViewFields.resolveFieldQueryFunction(field.type)(field.identifier, field, $uniteCMSViewFields);
        }).filter(f => f !== null);
        config._filterQueryFields = config.fields.map((field) => {
            return $uniteCMSViewFields.resolveFilterQueryFunction(field.type)(field.identifier, field, $uniteCMSViewFields);
        }).filter(f => f !== null);
    }

    return config;
};

export default ViewConfig;
