<template>
    <div class="view-field view-field-textarea">
        <p v-if="teaser.length > 0 && full.length > 100" class="uk-text-truncate uk-text-meta" :title="full" uk-tooltip="pos: bottom">{{ teaser }}</p>
        <p v-if="teaser.length > 0 && full.length <= 100" class="uk-text-truncate uk-text-meta">{{ teaser }}</p>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        computed: {
            teaser() {
                let teaser = this.value;
                let virtualDiv = document.createElement("div");
                virtualDiv.innerHTML = teaser;
                teaser = (virtualDiv.textContent || virtualDiv.innerText);
                return teaser.substr(0, 100);
            },
            full() {
                let full = this.value;
                let virtualDiv = document.createElement("div");
                virtualDiv.innerHTML = full;
                full = (virtualDiv.textContent || virtualDiv.innerText);
                return full.substr(0, 500) + (full.length > 500 ? '...' : '')
            }
        }
    }
</script>

<style scoped>
    p[title] {
        cursor: pointer;
        max-width: 300px;
        min-width: 100px;
        display: inline-block;
    }
</style>
