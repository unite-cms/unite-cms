<template>
    <div class="uk-flex uk-flex-middle uk-margin-bottom">
        <div class="uk-flex-1 uk-flex uk-flex-middle">
            <h2 class="uk-margin-remove">{{ title }}</h2>

            <ul class="uk-subnav uk-subnav-divider uk-margin-left" uk-margin v-if="showDeleteToggle">
                <li :class="{'uk-active' : !deleted }"><a @click.prevent="toggleDeleted" href="#">{{
                    $t('content.list.deleted.active') }}</a></li>
                <li :class="{'uk-active' : deleted}"><a :class="{ 'uk-text-danger' : deleted }"
                                                        @click.prevent="toggleDeleted" href="#">
                    <icon class="fix-line-height" name="trash-2"/>
                    {{ $t('content.list.deleted.deleted') }}</a></li>
            </ul>
        </div>
        <view-filter :value="queryFilter" @input="updateQueryFilter" />
        <router-link :to="to('create')" class="uk-button uk-button-primary uk-margin-left" v-if="canCreate">
            <icon class="fix-line-height" name="plus"/>
            {{ labelCreate }}
        </router-link>
    </div>
</template>
<script>

    import Icon from '../../components/Icon';
    import ViewFilter from './_filter';

    export default {
        components: {Icon, ViewFilter},
        props: {
            deleted: Boolean,
            canCreate: Boolean,
            showDeleteToggle: {
                type: Boolean,
                default: true,
            },
            title: String,
            labelCreate: {
                type: String,
                default() {
                    return this.$t('content.list.actions.create');
                }
            },
            queryFilter: Object
        },
        methods: {
            to(action) {
                return this.$route.path + '/' + action;
            },
            toggleDeleted() {
                this.$emit('toggleDeleted');
            },
            updateQueryFilter(filter) {
                this.$emit('queryFilterChanged', filter);
            }
        }
    }
</script>
