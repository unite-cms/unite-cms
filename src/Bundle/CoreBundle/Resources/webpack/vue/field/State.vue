<template>
    <div>
        <a href="#" class="current-state uk-button uk-button-default" type="button">
            <span class="text">
                <span class="meta">{{ currentLabel }}:</span>
                <span class="uk-label" :class='"uk-label-" + state_category'>{{ state_label }}</span>
                <span class="to-state" v-if="transition_value">
                    <span v-html="feather.icons['arrow-right'].toSvg({ width: 18, height: 18 })"></span>
                    <span class="meta">{{ toLabel }}:</span>
                    <span class="uk-label" :class='"uk-label-" + current_transition_category'>{{ current_transition_label }}</span>
                </span>
            </span>
            <span class="chevron" v-html="feather.icons['chevron-down'].toSvg()"></span>
        </a>
        <div class="transitions-dropdown">
            <ul class="uk-nav uk-dropdown-nav">
                <li>
                    <a :class='{active: transition_value == ""}' v-on:click.prevent="setTransition('')">
                        <span>{{ transition_placeholder }}</span>
                    </a>
                </li>
                <li class="uk-nav-divider"></li>
                <li v-for="transition in transitions">
                    <span class="disabled" v-if="transition.disabled">
                        <span>{{ transition.name }}</span> <span class="uk-label uk-label-disabled">{{ transition.stateLabel }}</span>
                    </span>
                    <a v-else :class='{active: transition_value == transition.value}' v-on:click.prevent="setTransition(transition.value)">
                        <span>{{ transition.name }}</span> <span class="uk-label" :class='"uk-label-" + transition.category'>{{ transition.stateLabel }}</span>
                    </a>
                </li>
            </ul>
        </div>
        <input type="hidden" :name="state_name" :value="state_value" />
        <input type="hidden" :name="transition_name" :value="transition_value" />
    </div>
</template>

<script>

    import UIkit from 'uikit';
    import feather from 'feather-icons';

    export default {
        data() {

            let state_config = JSON.parse(this.state);
            let transition_config = JSON.parse(this.transition);

            return {
                feather: feather,
                state_label: state_config.label,
                state_name: state_config.name,
                state_value: state_config.value,
                state_category: state_config.category,
                transition_name: transition_config.name,
                transition_value: transition_config.value,
                transition_placeholder: transition_config.placeholder,
                transitions: transition_config.transitions.map((t) => {
                    return {
                        name: t.label,
                        value: t.value,
                        stateLabel: typeof t.attr['data-state-label'] !== 'undefined' ? t.attr['data-state-label'] : 'Undefined',
                        category: typeof t.attr['data-category'] !== 'undefined' ? t.attr['data-category'] : null,
                        disabled: typeof t.attr['disabled'] !== 'undefined' ? t.attr['disabled'] === 'disabled' : false,
                    };
                }),
            };
        },
        computed: {
            current_transition_label() {
                if(!this.transition_value) {
                    return "";
                }

                return this.transitions
                    .filter((t) => { return t.value === this.transition_value })
                    .map((t) => { return t.stateLabel })[0];
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
            'currentLabel',
            'toLabel',
        ],
        mounted() {
            this.dropdown = UIkit.dropdown(this.$el.querySelector('.transitions-dropdown'), {
                mode: 'click',
                delayHide: 0
            });
        },
        methods: {
            setTransition(transition) {
                this.transition_value = transition;
                this.dropdown.hide();
            }
        }
    };
</script>

<style lang="scss">
    unite-cms-core-state-field {
        position: relative;
        display: block;
        padding: 5px 0;

        a.current-state.uk-button.uk-button-default {
            padding: 0 10px;
            background: white;

            .text {
                display: inline-block;
                text-align: left;
                vertical-align: text-bottom;
                line-height: 0;
                color: #666;

                .meta {
                    font-size: 0.6rem;
                    line-height: normal;
                    color: #bfbfbf;
                    text-transform: uppercase;
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

        .transitions-dropdown {
            li {
                > a, > span.disabled {
                    display: flex;
                    align-items: center;
                    padding: 15px 25px;
                    color: #666;
                    opacity: 0.75;
                    margin: 0 -25px;

                    > span:first-child {
                        flex: 1;
                        padding-right: 20px;
                    }
                }

                > a {
                    &.active,
                    &:hover {
                        opacity: 1;
                        > span:first-child {
                            color: #242424;
                        }
                    }

                    &.active {
                        > span:first-child {
                            font-weight: bold;
                        }
                    }

                    &:hover {
                        background: #ffd;
                    }
                }

                > span.disabled {
                    color: #bfbfbf;
                    font-style: italic;
                }

                &:first-child { > a, > span.disabled { margin-top: -15px; } }
                &:last-child { > a, > span.disabled { margin-bottom: -15px; } }
            }
        }
    }
</style>
