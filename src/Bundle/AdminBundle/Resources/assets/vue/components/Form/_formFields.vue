<template>
    <div>
        <div uk-grid v-if="fieldGroups.length > 0">
            <div class="uk-width-auto@m">
                <ul class="uk-tab-left" ref="fieldGroupContainer" :uk-tab="`connect: #${groupId}; animation: uk-animation-fade`">
                    <li v-for="group in fieldGroups"><a href="#"><icon v-if="group.icon" :name="group.icon" class="uk-margin-small-right" /> {{ group.name }}</a></li>
                </ul>
            </div>
            <div class="uk-width-expand@m">
                <div :id="groupId" class="uk-switcher">
                    <div class="form-group" :data-group-delta="delta" v-for="(group, delta) in fieldGroups">
                        <component :key="field.id" v-for="field in group.fields" :is="$unite.getFormFieldType(field)" :form-data="formData" :root-form-data="rootFormData" :content-id="contentId" :field="field" :value="formData[field.id]" @input="data => updateValue(field.id, data)" :violations="fieldViolations(field.id)" :prefix="prefix" />
                    </div>
                </div>
            </div>
        </div>
        <component v-if="!field.form_group" :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field)" :form-data="formData" :root-form-data="rootFormData" :content-id="contentId" :field="field" :value="formData[field.id]" @input="data => updateValue(field.id, data)" :violations="fieldViolations(field.id)" :prefix="prefix" />
    </div>
</template>

<script>
    import Alerts from "../../state/Alerts";
    import Icon from "../Icon";
    import UIkit from 'uikit';
    import Form from "../../state/Form";

    export default {
        components: { Icon },
        props: {
            view: Object,
            formData: Object,
            rootFormData: Object,
            contentId: String,
            prefix: {
                type: Array,
                default() { return []; }
            },
        },
        mounted() {
            Form.$on('checkHTML5Valid', (event) => { this.checkInvalidHTML5FieldsInGroup(event); });
        },
        computed: {
            groupId() {
                return 'component-tab-left-' + this._uid;
            },
            fieldGroups() {
                return this.view.formFieldGroups();
            }
        },
        methods: {
            updateValue(field, data) {
                let formData = Object.assign({}, this.formData);
                formData[field] = data;
                this.$emit('input', formData);
            },
            fieldViolations(fieldId) {
                return Alerts.violationsForPrefix([...this.prefix, fieldId]);
            },
            findFormGroup(element) {
                return element.tagName === 'FORM' ? null : (
                    element.classList.contains('form-group') ? element : this.findFormGroup(element.parentElement)
                );
            },
            checkInvalidHTML5FieldsInGroup(event) {
                if(this.$refs.fieldGroupContainer) {
                    for (let i = 0; i < event.target.form.elements.length; i++) {
                        if (!event.target.form.elements[i].reportValidity()) {
                            let formGroup = this.findFormGroup(event.target.form.elements[i]);
                            if(formGroup) {
                                UIkit.tab(this.$refs.fieldGroupContainer).show(formGroup.dataset.groupDelta);
                                setTimeout(() => { formGroup.scrollIntoView({behavior: "smooth"}); }, 300);
                            }
                        }
                    }
                }
            },
        }
    }
</script>
