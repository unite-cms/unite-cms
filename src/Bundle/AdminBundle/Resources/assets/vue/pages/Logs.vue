<template>
    <div class="uk-background-secondary uk-height-viewport uk-flex uk-flex-column">
        <div>
            <button class="load-more load-more-new uk-button uk-button-small" @click="loadNew" :disabled="$apollo.loading">
                <div v-if="$apollo.loading" class="uk-margin-small-right" uk-spinner="ratio: 0.4"></div>
                Refresh
            </button>
        </div>
        <div ref="container" class="container uk-flex-1">
            <virtual-list class="virtual-list" ref="list" :size="25" :remain="itemsToShow" :item="logEntry" :itemcount="logs.length" :itemprops="getLogEntry" />
        </div>
        <div>
            <button class="load-more load-more-old uk-button uk-button-small" @click="loadOld" :disabled="$apollo.loading">
                <div v-if="$apollo.loading" class="uk-margin-small-right" uk-spinner="ratio: 0.4"></div>
                Load older logs
            </button>
        </div>
    </div>
</template>

<script>
    import virtualList from 'vue-virtual-scroll-list';
    import LogEntry from "../components/LogEntry";
    import Icon from "../components/Icon";
    import gql from 'graphql-tag';

    export default {
        components: {virtualList, Icon},
        data() {
            return {
                itemsToShow: 10,
                logEntry: LogEntry,
                logs: [],
            };
        },
        apollo: {
            logs: {
                fetchPolicy: 'network-only',
                query: gql`query($before: DateTime!, $after: DateTime) {
                unite {
                  logs(before: $before, after: $after) {
                    level
                    created
                    message
                    username
                  }
                }
              }`,
                update: data => data.unite.logs,
                variables: {
                    before: 'now',
                    after: null,
                }
            }
        },
        mounted() {
            this.itemsToShow = parseInt(this.$refs.container.clientHeight / 25);
        },
        methods: {
            getLogEntry(index) {
                return {
                    props: {
                        number: index + 1,
                        log: this.logs[index],
                    }
                };
            },
            loadOld() {
                this.$apollo.queries.logs.fetchMore({
                    variables: {
                        before: this.logs.length > 0 ? this.logs[this.logs.length - 1].created : null
                    },
                    updateQuery: (previousResult, { fetchMoreResult }) => {
                        return {
                            unite: {
                                __typename: previousResult.unite.__typename,
                                logs: [...previousResult.unite.logs, ...fetchMoreResult.unite.logs]
                            }
                        };
                    },
                });
            },
            loadNew() {
                this.$apollo.queries.logs.fetchMore({
                    variables: {
                        after: this.logs.length > 0 ? this.logs[0].created : null,
                        before: 'now',
                    },
                    updateQuery: (previousResult, { fetchMoreResult }) => {
                        return {
                            unite: {
                                __typename: previousResult.unite.__typename,
                                logs: [...fetchMoreResult.unite.logs, ...previousResult.unite.logs]
                            }
                        };
                    },
                });
            }
        }
    }
</script>
<style scoped lang="scss">

    .container {
        overflow: hidden;
    }

    .virtual-list {
        overflow-y: auto !important;
    }

    .load-more {
        background: none;
        margin: 5px;
    }
</style>