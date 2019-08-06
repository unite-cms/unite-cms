<template>
    <span class="field-type-choices">
        <span class="uk-label" :key="delta" v-for="(v, delta) in values">{{ v }}</span>
    </span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

    export default {
        extends: BaseField,
        computed: {
            values() {

                if(!this.value) {
                    return [];
                }
                let values = typeof this.value === 'string' ? [this.value] : this.value;
                return values.map((value) => {
                    if(typeof this.field.settings.choices[value] !== 'undefined') {
                        return this.field.settings.choices[value];
                    }
                    return value;
                });
            },
        }
    }
</script>

<style scoped lang="scss">
    .field-type-choices {
        white-space: nowrap;
        text-align: center;

        .uk-label {
            margin-right: 5px;

            &:last-child {
                margin-right: 0;
            }
        }
    }
</style>
