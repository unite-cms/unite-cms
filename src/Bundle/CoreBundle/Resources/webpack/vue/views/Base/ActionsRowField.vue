<template>
    <ul class="uk-iconnav">
        <li v-for="action in availableActions" :key="action.key">
            <a :href="url(action)" v-html="icon(action)" :class="action.class" :title="config.t(action.name)" uk-tooltip></a>
        </li>
    </ul>
</template>

<script>

    import BaseField from '../Base/AbstractRowField';
    import feather from 'feather-icons';

    const ACTIONS = [
        { key: 'update',            permission: 'update',       icon: 'edit',           name: 'Update' },
        { key: 'delete',            permission: 'delete',       icon: 'trash',          name: 'Delete',             class: 'uk-text-danger' },
        { key: 'delete_definitely', permission: 'delete',       icon: 'trash-2',        name: 'Delete definitely',  class: 'uk-text-danger' },
        { key: 'recover',           permission: 'delete',       icon: 'refresh-ccw',    name: 'Recover',            class: 'uk-text-success' },
        { key: 'revisions',         permission: 'update',       icon: 'list',           name: 'Manage versions' },
        { key: 'translations',      permission: 'translate',    icon: 'globe',          name: 'Manage translations' },
    ];

    export default {
        extends: BaseField,
        computed: {
            availableActions() {
                return ACTIONS.filter((action) => {
                    return !this.config.showOnlyDeletedContent ^ ['delete_definitely', 'recover'].indexOf(action.key) >= 0;
                }).filter((action) => {
                    return this.row.can(action.permission);
                });
            }
        },
        methods: {
            /**
             * @inheritdoc
             */
            filterQuery(identifier, field) {
                return null;
            },

            url(action) {
                return this.config.url(action.key, this.row.id);
            },

            icon(action) {
                return feather.icons[action.icon].toSvg({width: 16, height: 16});
            }
        }

    }
</script>
<style lang="scss" scoped>
    .uk-iconnav {
        flex-wrap: nowrap;
        > li {
            > a {
                width: 24px;
                text-align: center;
            }
        }
    }
</style>


