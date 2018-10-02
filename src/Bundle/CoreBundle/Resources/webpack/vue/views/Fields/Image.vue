<template>
    <div class="view-field view-field-image">
        <img v-if="src" :src="src" />
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        data(){
            return {
                src: this.row[this.identifier] ? this.row[this.identifier].url : null
            }
        },
        methods: {

            /**
             * @inheritdoc
             */
            fieldQuery(identifier, field) {
                return identifier + ' { url }'
            },
        }
    }
</script>

<style scoped>

</style>
