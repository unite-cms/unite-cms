<template>
    <div class="view-field view-field-textarea">
        <p v-if="teaser.length > 0" class="uk-text-truncate" :title="full" uk-tooltip="pos: bottom">{{ teaser }}</p>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        data(){

            let content = this.row[this.identifier] ? this.row[this.identifier] : '';
            let virtualDiv = document.createElement("div");
            virtualDiv.innerHTML = content;
            content = (virtualDiv.textContent || virtualDiv.innerText);

            return {
                teaser: content.substr(0, 300),
                full: content.substr(0, 500) + (content.length > 500 ? '...' : '')
            }
        },
        extends: BaseField
    }
</script>

<style scoped>

</style>
