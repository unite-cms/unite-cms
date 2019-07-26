
import cloneDeep from 'lodash/cloneDeep';

export const ViewRow = {
    init: function(id, data, permissions, fieldableContentType){
        let row = cloneDeep(this);
        row.id = id;
        row._fieldableContentType = fieldableContentType;
        row._data = data;
        row._permissions = Object.assign({}, row.permission, permissions);
        return row;
    },
    _fieldableContentType: null,
    _permissions: {
        update: false,
        delete: false,
        translate: false,
    },
    _data: {},
    id: null,
    can(permission) {
        return this._permissions[permission] || false;
    },
    get(field, default_value = null) {

        if(field === 'id') {
            return this.id || default_value;
        }

        return this._data[field] || default_value;
    }
};

export const createRow = function(graphql_row, fieldableContentType){
    let permissions = {};

    if(fieldableContentType === 'Content') {
        permissions.update = graphql_row._permissions.UPDATE_CONTENT;
        permissions.delete = graphql_row._permissions.DELETE_CONTENT;
        permissions.translate = graphql_row._permissions.TRANSLATE_CONTENT;
    }

    if(fieldableContentType === 'Member') {
        permissions.update = graphql_row._permissions.UPDATE_MEMBER;
        permissions.delete = graphql_row._permissions.DELETE_MEMBER;
    }

    let data = {};

    Object.keys(graphql_row).forEach((f) => {
        if(['id', '_permissions'].indexOf(f) < 0) {
            data[f] = graphql_row[f];
        }
    });

    return ViewRow.init(graphql_row.id, data, permissions, fieldableContentType);
};

export const updateRow = function(row, graphql_row) {
    let updatedRow = createRow(graphql_row, row._fieldableContentType);
    row._id = updatedRow._id;
    row._data = updatedRow._data;
    row._permissions = updatedRow._permissions;
    return row;
}

export default ViewRow;
