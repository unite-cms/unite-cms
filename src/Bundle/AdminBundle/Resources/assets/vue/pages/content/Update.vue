<template>
    <content-detail :loading="loading || $apollo.loading" @submit="submit" :can-go-back="view.category !== 'single_content'">
        <h1 class="uk-card-title">{{ $t('content.update.headline', { contentTitle, view }) }}</h1>
        <alerts-list :alerts="globalViolations" />
        <div uk-grid v-if="fieldGroups.length > 0">
            <div class="uk-width-auto@m">
                <ul class="uk-tab-left" ref="fieldGroupContainer" uk-tab="connect: #component-tab-left; animation: uk-animation-fade">
                    <li v-for="group in fieldGroups"><a href="#"><icon v-if="group.icon" :name="group.icon" class="uk-margin-small-right" /> {{ group.name }}</a></li>
                </ul>
            </div>
            <div class="uk-width-expand@m">
                <div id="component-tab-left" class="uk-switcher">
                    <div class="form-group" :data-group-delta="delta" v-for="(group, delta) in fieldGroups">
                        <component :key="field.id" v-for="field in group.fields" :is="$unite.getFormFieldType(field)" :form-data="formData" :content-id="$route.params.id" :field="field" v-model="formData[field.id]" :violations="fieldViolations(field.id)" />
                    </div>
                </div>
            </div>
        </div>
        <component v-if="!field.form_group" :key="field.id" v-for="field in view.formFields()" :is="$unite.getFormFieldType(field)" :form-data="formData" :content-id="$route.params.id" :field="field" v-model="formData[field.id]" :violations="fieldViolations(field.id)" />
        <button slot="footer" class="uk-button uk-button-primary" type="submit" @click="checkInvalidHTML5FieldsInGroup">{{ $t('content.update.actions.submit') }}</button>
    </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";
    import ContentDetail from './_detail';
    import UIkit from 'uikit';

    export default {
        components: {Icon, ContentDetail, AlertsList},
        data(){
            return {
                loading: false,
                formData: {}
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            },
            fieldGroups() {
                return this.view.formFieldGroups();
            },
            contentTitle() {
                return this.view.contentTitle(this.formData);
            },
            globalViolations() {
                return Alerts.violationsWithoutPrefix(this.view.formFields().map(field => field.id)).map((v) => {
                    return Object.assign({}, v, { message: v.path[0] + ': ' + v.message });
                });
            },
        },
        apollo: {
            formData: {
                fetchPolicy: 'network-only',
                query() {

                    if(this.view.category === 'single_content') {
                        return gql`query {
                            get${ this.view.type } {
                                  ${ this.view.queryFormData() }
                            }
                        }`;
                    } else {
                        return gql`query($id: ID!) {
                            get${ this.view.type }(id: $id) {
                                  ${ this.view.queryFormData() }
                            }
                        }`;
                    }
                },
                variables() {
                    return {
                        id: this.$route.params.id,
                    };
                },
                update(data) {
                    return this.view.normalizeQueryData(data[`get${ this.view.type }`]);
                }
            }
        },
        methods: {
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
            submit() {
                Alerts.$emit('clear');
                this.loading = true;
                this.$apollo.mutate({
                    mutation: (this.view.category === 'single_content') ?
                        gql`mutation($persist: Boolean!, $data: ${ this.view.type }Input!) {
                            update${ this.view.type }(persist:$persist, data:$data) {
                                __typename
                            }
                        }` : gql`mutation($id: ID!, $persist: Boolean!, $data: ${ this.view.type }Input!) {
                            update${ this.view.type }(id: $id, persist:$persist, data:$data) {
                                __typename
                            }
                        }`
                    ,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                        data: this.view.normalizeMutationData(Object.assign({}, this.formData)),
                    }
                }).then((data) => {

                    if(this.view.category !== 'single_content') {
                        Route.back({updated: this.$route.params.id});
                    }

                    Alerts.$emit('push', 'success', this.$t('content.update.success', { contentTitle: this.contentTitle, view: this.view }));
                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.apolloErrorHandler(e);
                    Alerts.$emit('push', 'danger', this.$t('content.update.errors', { view: this.view, contentTitle: this.contentTitle }));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            fieldViolations(prefix) {
                return Alerts.violationsForPrefix(prefix);
            }
        }
    }
</script>
