<template>
    <div class="unite-div-table-row">
        <base-content-header-field v-for="(field,identifier) in fields" v-if="renderField(field, identifier)"
                                   :key="identifier"
                                   :identifier="identifier"
                                   :label="field.label"
                                   :type="field.type"
                                   :isSortable="isSortable"
                                   :initialMinWidth="0"
                                   :sort="sortConfig"
                                   @sortChanged="setSort"
                                   :ref="'field_' + identifier"></base-content-header-field>
        <base-content-header-field v-if="showActions" v-for="i in [1]"
                                   :key="i"
                                   identifier="_actions"
                                   type="_actions"
                                   :sort="sortConfig"
                                   :initialMinWidth="0"
                                   label=""
                                   ref="field__actions"
        ></base-content-header-field>
    </div>
</template>

<script>

    import TableContentRow from './TableContentRow.vue';
    import BaseViewContentHeaderField from './Base/BaseViewContentHeaderField.vue';

    export default {
        extends: TableContentRow,
        methods: {
            setSort(identifier) {
                if(!this.isSortable) {
                    this.sortConfig.field = identifier;
                    this.sortConfig.asc = this.sortConfig.field === identifier ? !this.sortConfig.asc : true;
                    this.$emit('updateSort', this.sortConfig);
                }
            },
        },
        components: {
            'base-content-header-field': BaseViewContentHeaderField
        }
    }
</script>

<style scoped>

</style>