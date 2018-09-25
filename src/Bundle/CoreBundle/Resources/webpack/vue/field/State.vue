<template>
    <div>
        <button class="current-state uk-button uk-button-default" type="button">
            <span class="text">
                <span class="uk-label" :class='"uk-label-" + state_category'>{{ state_label }}</span>
                <span class="to-state" v-if="transition_value">
                    <span v-html="feather.icons['arrow-right'].toSvg({ width: 18, height: 18 })"></span>
                    <span class="uk-label" :class='"uk-label-" + current_transition_category'>{{ current_transition_label }}</span>
                </span>
            </span>
            <span class="chevron" v-html="feather.icons['chevron-down'].toSvg()"></span>
        </button>
        <div uk-dropdown="mode: click">
            <ul class="uk-nav uk-dropdown-nav">
                <li>
                    <a v-on:click.prevent="transition_value = ''">{{ transition_placeholder }}</a>
                </li>
                <li v-for="transition in transitions">
                    <span class="disabled" v-if="transition.disabled">{{ transition.name }}</span>
                    <a v-else v-on:click.prevent="transition_value = transition.value">
                        {{ transition.name }} <span class="uk-label" :class='"uk-label-" + transition.category'>{{ transition.name }}</span>
                    </a>
                </li>
            </ul>
        </div>
        <input type="hidden" :name="state_name" :value="state_value" />
        <input type="hidden" :name="transition_name" :value="transition_value" />
    </div>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {

            let state_config = JSON.parse(this.state);
            let transition_config = JSON.parse(this.transition);

            console.log(state_config);
            console.log(transition_config);

            return {
                feather: feather,
                state_label: state_config.label,
                state_name: state_config.name,
                state_value: state_config.value,
                state_category: state_config.category,
                transition_name: transition_config.name,
                transition_value: transition_config.value,
                transition_placeholder: transition_config.placeholder,
                transitions: [
                    {
                        name: "Back to draft",
                        value: 'to_draft',
                        category: 'primary',
                        disabled: true,
                    },
                    {
                        name: "To review",
                        value: 'to_review',
                        category: 'warning',
                        disabled: false,
                    },
                    {
                        name: "Publish",
                        value: 'publish',
                        category: 'success',
                        disabled: false,
                    }
                ]
            };
        },
        computed: {
            current_transition_label() {
                if(!this.transition_value) {
                    return "";
                }

                return this.transitions
                    .filter((t) => { return t.value === this.transition_value })
                    .map((t) => { return t.name })[0];
            },
            current_transition_category() {
                if(!this.transition_value) {
                    return "";
                }

                return this.transitions
                    .filter((t) => { return t.value === this.transition_value })
                    .map((t) => { return t.category })[0];
            }
        },
        props: [
            'state',
            'transition',
        ]
    };
</script>

<style lang="scss">
    unite-cms-core-state-field {
        position: relative;
        display: block;
        padding: 5px 0;

        button.current-state.uk-button.uk-button-default {
            padding: 0 10px;
            background: white;
            border: 1px solid #D8D8D8;
            box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.06);

            .text {
                display: inline-block;
                text-align: left;
                vertical-align: text-bottom;
                line-height: 0;
                color: #666;

                .uk-label {
                    text-align: center;
                    min-width: 70px;
                    line-height: 1.3;
                }

                .to-state {
                    padding: 0 10px;

                    svg {
                      margin-right: 10px;
                    }
                }
            }

            .chevron {
                svg {
                    opacity: 0.25;
                }
            }

            &.uk-open,
            &:hover {
                .chevron {
                    svg {
                        opacity: 1;
                    }
                }
            }

            &.uk-open {
                .chevron {
                    svg {
                        transform: rotate(180deg);
                    }
                }
            }
        }
    }
</style>
