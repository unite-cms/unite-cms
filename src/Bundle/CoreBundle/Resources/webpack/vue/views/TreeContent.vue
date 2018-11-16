<template>
    <div class="unite-div-table">
        <div class="unite-div-table-thead">
            <div :style="rowStyle" class="unite-div-table-row">
                <base-content-header-field v-for="(field,identifier) in fields" v-if="renderField(field, identifier)"
                                           :key="identifier"
                                           :identifier="identifier"
                                           :label="field.label"
                                           :type="field.type"
                                           :isSortable="isSortable"
                                           :initialMinWidth="minWidthMap[identifier]"
                                           :sort="sortConfig"
                                           :fixedWidth="hasColumnFixedWidth(identifier)"
                                           @sortChanged="setSort"
                                           @resized="onFieldResize"
                                           :ref="'field_' + identifier"></base-content-header-field>
                <base-content-header-field v-if="showActions" v-for="i in [1]"
                                           :key="i"
                                           identifier="_actions"
                                           type="_actions"
                                           :sort="sortConfig"
                                           :initialMinWidth="minWidthMap['_actions']"
                                           label=""
                                           :fixedWidth="true"
                                           @resized="onFieldResize"
                                           ref="field__actions"
                ></base-content-header-field>
            </div>
        </div>
        <div class="unite-div-table-tbody" :uk-sortable="isSortable && updateable ? 'handle: .uk-sortable-handle' : null" v-on:moved="moved">
            <div uk-grid :style="rowStyle" class="unite-div-table-row" :data-id="row.id" :key="row.id" v-for="row in rows">
                <component v-for="(field,identifier) in fields" v-if="renderField(field, identifier)"
                           :key="identifier"
                           :is="$uniteCMSViewFields.resolve(field.type)"
                           :type="field.type"
                           :identifier="identifier"
                           :label="field.label"
                           :settings="field.settings"
                           :initialMinWidth="minWidthMap[identifier]"
                           :sortable="isSortable"
                           :row="row"
                           @resized="onFieldResize"
                           :ref="'field_' + identifier"></component>
                <base-view-row-actions v-if="showActions"
                                       :row="row"
                                       :urls="urls"
                                       identifier="_actions"
                                       :initialMinWidth="minWidthMap['_actions']"
                                       @resized="onFieldResize"
                                       ref="field__actions"
                                       :refInFor="true"
                                       :embedded="embedded"
                ></base-view-row-actions>
            </div>
        </div>
    </div>
</template>

<script>

    import UIkit from 'uikit';
    import nestable from 'uikit3-nestable';
    UIkit.mixin(nestable, 'sortable');

    import TableContent from './TableContent.vue';

    export default {
        extends: TableContent
    }
</script>

<style scoped>

</style>
