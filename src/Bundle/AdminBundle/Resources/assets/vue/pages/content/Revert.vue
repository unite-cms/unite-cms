<template>
  <content-detail :loading="loading || $apollo.loading" :card="false">
    <h3>{{ $t('content.revert.headline', view) }}</h3>
    <div class="uk-overflow-auto">
      <table class="uk-table uk-table-small uk-table-divider uk-table-middle">
      <thead>
      <tr>
        <th>{{ $t('content.revert.header.version') }}</th>
        <th>{{ $t('content.revert.header.operation') }}</th>
        <th>{{ $t('content.revert.header.meta') }}</th>
        <th v-for="field in view.listFields()">{{ field.name }}</th>
        <th></th>
      </tr>
      </thead>
        <tbody class="uk-card uk-card-default uk-table-striped">
      <tr v-for="revision in meta.revisions" :key="revision.version" :class="{ updated: revertedVersion === revision.version }">
        <td class="uk-table-shrink uk-text-nowrap">{{ revision.version }}</td>
        <td class="uk-table-shrink uk-text-nowrap"><span class="uk-label" :class="labelClass(revision)">{{ revision.operation }}</span></td>
        <td class="uk-table-shrink uk-text-small">
          <span class="uk-text-nowrap">{{ $d(new Date(revision.operationTime), 'full') }}</span><br />
          <span class="uk-text-nowrap">{{ revision.operatorName }}</span>
        </td>
        <td v-for="field in view.listFields()">
          <component :is="$unite.getListFieldType(field.fieldType)" :row="revision.content" :field="field" />
        </td>
        <td class="uk-table-shrink">
          <span class="uk-label uk-label-muted" v-if="meta.version === revision.version">{{ $t('content.revert.label.current') }}</span>
          <ul class="uk-iconnav uk-flex-center" v-else>
            <li><a @click="revertToVersion(revision.version)" class="uk-text-success" uk-tooltip :title="$t('content.revert.actions.revert')"><icon name="rotate-ccw" /></a></li>
          </ul>
        </td>
      </tr>
      </tbody>
    </table>
    </div>

  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import UIkit from 'uikit';
    import Alerts from "../../state/Alerts";
    import Icon from "../../components/Icon";
    import ContentDetail from './_detail';

    export default {
        components: { Icon, ContentDetail },
        data() {
            return {
                revertedVersion: null,
                loading: false,
                meta: []
            };
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        },
        apollo: {
            meta: {
                fetchPolicy: 'network-only',
                query() {
                    return gql`
                        ${ this.view.fragment }
                        query($id: ID!) {
                            get${ this.view.type }(id: $id) {
                                ... ${ this.view.id }
                                _meta {
                                    id
                                    version
                                    revisions {
                                        version
                                        operation
                                        operatorId
                                        operatorName
                                        operatorType
                                        operationTime
                                        content {
                                          ... ${ this.view.id }
                                        }
                                    }
                                }
                            }
                        }`;
                },
                variables() {
                    return {
                        id: this.$route.params.id,
                    };
                },
                update(data) {
                    let revisionData = data[`get${ this.view.type }`]._meta;
                    revisionData.revisions = revisionData.revisions.map((revision) => {
                        revision.content = Object.assign(revision.content, {
                            _meta: {
                                id: revisionData.id
                            }
                        });
                        return revision;
                    });
                    return revisionData;
                }
            }
        },
        methods: {
            labelClass(revision) {
                switch (revision.operation) {
                    case 'DELETE':
                    case 'PERMANENT_DELETE':
                        return 'uk-label-danger';

                    case 'REVERT':
                        return 'uk-label-warning';

                    case 'RECOVER':
                        return 'uk-label-success';

                    default:
                        return 'uk-label-muted';
                }
            },
            revertToVersion(version){
                UIkit.modal.confirm(this.$t('content.revert.confirm', { version })).then(() => {

                    this.revertedVersion = null;
                    this.loading = true;

                    this.$apollo.mutate({
                        mutation: gql`mutation($id: ID!, $persist: Boolean!, $version: Int!) {
                        revert${ this.view.type }(id: $id, persist:$persist, version:$version) {
                            _meta {
                                version
                            }
                        }
                    }`,
                        variables: {
                            id: this.$route.params.id,
                            persist: true,
                            version
                        }
                    }).then((data) => {
                        this.revertedVersion = data.data[`revert${ this.view.type }`]._meta.version;
                        Alerts.$emit('push', 'success', this.$t('content.revert.success', { name: this.view.name, version: version }));
                        this.$apollo.queries.meta.refetch();
                    }).finally(() => { this.loading = false })
                });
            }
        }
    }
</script>
