<template>
    <div :class="{ 'uk-flex uk-flex-middle display-inline' : field.config.contentInline }">
        <div v-for="value in values" :class="{ 'uk-flex uk-flex-middle display-inline' : field.config.fieldsInline }">
            <template v-for="nestedField in nestedFields(value)">
                <component :is="$unite.getListFieldType(nestedField)" :field="nestedField" :row="normalizedContent(value)" :view="referencedView" />
            </template>
        </div>
    </div>
</template>
<script>
    import _abstract from "./_abstract";
    import { getAdminViewByType } from "../../../plugins/unite";
    import CheckboxInput from "../../Views/Filter/Input/CheckboxInput";

    export default {

        // static filter method
        filter(field, view, unite) {

            // If this is an alias field
            if(field.id !== field.type) {
                return []
            }

            let referencedView = getAdminViewByType(unite, field.returnType);

            if(!referencedView || !referencedView.fields) {
                return [];
            }

            let referencedFilterRules = [];
            referencedView.fields.forEach((rfield) => {
                if(rfield.fieldType !== 'reference' && rfield.fieldType !== 'embedded') {
                    let component = unite.getListFieldType(rfield);
                    if(component.filter) {
                        referencedFilterRules = [...referencedFilterRules, ...component.filter(rfield, view, unite)];
                    }
                }
            });

            return referencedFilterRules.map((rule) => {
                rule.path = rule.id;
                rule.id = field.id;
                rule.searchable = field.show_in_list && rule.searchable;
                rule.label = field.name.slice(0, 1).toUpperCase() + field.name.slice(1) + ' » ' + rule.label;
                return rule;
            });
        },

        extends: _abstract,
        computed: {
            referencedView() {
                return getAdminViewByType(this.$unite, this.field.returnType);
            }
        },
        methods: {
            nestedFields(value) {
                return Object.keys(value).map((key) => {
                    return key === '__typename' ? null : this.fieldComponent(key, value.__typename)
                }).filter((field) => { return !!field });
            },
            fallbackField(key) {
                let type = 'unknown';

                if(key === 'id') {
                    type = 'id';
                }

                return {
                    type: type,
                    id: key,
                };
            },

            referencedRowView(type) {

                if(!this.referencedView) {
                    return null;
                }

                if(this.referencedView.category === 'union') {
                    let views = this.referencedView.possibleViews.filter((view) => { return view.type === type; });
                    return views.length > 0 ? views[0] : null;
                }

                return this.referencedView;
            },

            fieldComponent(key, type) {

                if(!this.referencedView) {
                    return this.fallbackField(key);
                }
                let referencedField = this.referencedRowView(type).fields.filter((field) => {
                    return field.id === key;
                });

                return referencedField.length > 0 ? referencedField[0] : this.fallbackField(key);
            },

            normalizedContent(value) {
                value._meta = Object.assign({
                    id: value.id || null,
                    deleted: false,
                    permissions: {
                        read: true,
                        update: false,
                        delete: false,
                        permanent_delete: false,
                        user_invite: false,
                    }
                }, value._meta);
                return value;
            }
        }
    }
</script>
<style scoped lang="scss">
    .display-inline {
        > * {
            margin: 0 5px;
        }
    }
</style>
