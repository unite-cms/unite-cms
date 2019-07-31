<template>
    <div>
        <div v-for="(alert, delta) in alerts" :key="delta" class="unite-table-error uk-flex uk-flex-middle" :class="{
            'uk-alert-danger': alert.type === 'danger',
            'uk-alert-warning': alert.type === 'warning',
            'uk-alert-success': alert.type === 'success',
        }">
                <div v-html="feather.icons[alert.type === 'success' ? 'check-circle' : 'alert-triangle'].toSvg({width: 24, height: 24})"></div>
                <div class="uk-flex-1 uk-padding-small">{{ alert.message }}</div>
                <button v-if="alert.action" class="uk-button" :class="{
                    'uk-button-danger': alert.type === 'danger',
                    'uk-button-warning': alert.type === 'warning',
                    'uk-button-success': alert.type === 'success',
                }" v-on:click.prevent="alert.action.callback">{{ alert.action.label }}</button>
            </div>
    </div>
</template>
<script>

import feather from 'feather-icons';

export default {
    data() {
        return {
            feather: feather
        }
    },
    props: {
        alerts: Array,
    }
}
</script>

