<template>
    <div class="unite-div-table-row" :data-id="row.id">
        <component v-for="(field,identifier) in fields" v-if="renderField(field, identifier)"
                   :key="identifier"
                   :is="$uniteCMSViewFields.resolve(field.type)"
                   :type="field.type"
                   :identifier="identifier"
                   :label="field.label"
                   :settings="field.settings"
                   :sortable="isSortable"
                   :row="row"></component>
        <base-view-row-actions v-if="showActions"
                               :row="row"
                               :urls="urls"
                               identifier="_actions"
                               :embedded="embedded"
        ></base-view-row-actions>
    </div>
</template>

<script>

    import BaseViewRowActions from './Base/BaseViewRowActions.vue';

    export default {
        props: ['fields', 'row', 'isSortable', 'isUpdateable', 'showActions', 'sortConfig', 'urls', 'embedded'],
        methods: {
            renderField(field, identifier) {

                if(!this.isSortable && !this.isUpdateable && this.sortConfig.field === field.identifier) {
                    return false;
                }

                if(!this.isUpdateable && field.type === 'selectrow') {
                    return false;
                }

                return true;
            }
        },
        components: {
            'base-view-row-actions': BaseViewRowActions
        }
    }
</script>

<style scoped>

</style>