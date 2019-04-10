<template>
    <div class="uk-form-controls uk-form-controls-text uk-form-controls-checkbox">

        <div class="uk-search uk-search-default" v-on:click="showDropdown">
            <button v-if="!searchDone" v-on:click.prevent="search" class="uk-search-icon-flip" uk-search-icon></button>
            <button v-if="searchDone" v-on:click.prevent="clear" class="uk-search-icon-flip uk-search-icon uk-icon" v-html="feather.icons['x'].toSvg({ width: 20, height: 20 })"></button>

            <input @keypress.13.prevent="search" class="uk-search-input" type="search" placeholder="Search..." v-model="query">
        </div>
        <div uk-dropdown="mode:null;delay-hide:0">
            <div v-if="queryResults.length === 0" class="uk-placeholder uk-text-center">
                <small>No locations found.</small>
            </div>
            <ul v-else class="uk-nav uk-dropdown-nav">
                <li v-for="result in queryResults">
                    <a v-on:click.prevent="selectResult(result)" href="#">{{ result.display_name }} <span class="uk-label">{{ result.osm_type }}</span></a>
                </li>
                <li class="uk-nav-divider"></li>
                <li><small>{{ queryResults[0].licence }}</small></li>
            </ul>
        </div>


        <input v-for="(value, key) in fieldData" v-if="key !== 'admin_levels'" type="hidden" :name="name + '[' + key + ']'" :value="value" />
        <template v-for="(row, delta) in fieldData.admin_levels">
            <input type="hidden" :name="name + '[admin_levels][' + delta + '][name]'" :value="row.name" />
            <input type="hidden" :name="name + '[admin_levels][' + delta + '][code]'" :value="row.code" />
            <input type="hidden" :name="name + '[admin_levels][' + delta + '][level]'" :value="row.level" />
        </template>
    </div>
</template>

<script>

    import feather from 'feather-icons';
    import UIkit from 'uikit';

    export default {
        data(){

            let locationData = JSON.parse(this.value);
            return {
                query: locationData.display_name || '',
                searchDone: !!locationData.id,
                queryResults: [],
                fieldData: locationData,
                feather: feather
            };
        },
        props: [
            'id',
            'label',
            'name',
            'value',
        ],
        mounted() {
            this.dropdown = UIkit.dropdown(this.$el.querySelector('*[uk-dropdown]'));
        },
        methods: {
            clear() {
                this.searchDone = false;
                this.query = '';
                this.fieldData = {
                    admin_levels: [],
                    bound_east: 0,
                    bound_north: 0,
                    bound_south: 0,
                    bound_west: 0,
                    category: null,
                    country_code: null,
                    display_name: null,
                    id: null,
                    latitude: 0,
                    longitude: 0,
                    postal_code: null,
                    provided_by: null,
                    street_name: null,
                    street_number: null,
                    sub_locality: null,
                    locality: null,
                };
            },
            search() {
                this.searchDone = false;
                this.queryResults = [];
                let request = new XMLHttpRequest();
                request.onload = () => {
                    this.queryResults = JSON.parse(request.responseText);
                    this.searchDone = true;
                    this.dropdown.show();
                };
                request.open("GET", 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=' + encodeURI(this.query), true);
                request.send();
            },
            showDropdown() {
                if(this.searchDone) {
                    this.dropdown.show();
                }
            },
            selectResult(result) {
                this.query = result.display_name;
                console.log(result);
                this.fieldData = {
                    admin_levels: [],
                    bound_east: result.boundingbox[3],
                    bound_north: result.boundingbox[1],
                    bound_south: result.boundingbox[0],
                    bound_west: result.boundingbox[2],
                    category: result.osm_type,
                    country_code: result.address.country_code.toUpperCase(),
                    display_name: result.display_name,
                    id: result.osm_id,
                    latitude: result.lat,
                    longitude: result.lon,
                    postal_code: result.address.postcode,
                    provided_by: 'nominatim',
                    street_name: result.address.road || result.address.pedestrian || null,
                    street_number: result.address.house_number || null,
                    sub_locality: result.address.suburb || null,
                    locality: result.address.city || result.address.town || result.address.village || result.address.hamlet,
                };

                ['state', 'country'].forEach((level, delta) => {
                    if(typeof result.address[level] !== 'undefined') {
                        this.fieldData.admin_levels.push({
                            level: delta + 1,
                            name: result.address[level],
                            code: '',
                        });
                    }
                });

                this.dropdown.hide();
            }
        }
    }
</script>

<style lang="scss">
    @import "../../sass/base/variables";

    unite-cms-core-location-field {
        position: relative;
        display: block;

        .uk-form-controls {
            display: inline-block;
            width: 400px;
            box-sizing: border-box;
            max-width: 100%;
        }

        .uk-placeholder {
            padding: 10px;
            margin: 0;
        }

        .uk-search-default {
            width: 100%;
            box-sizing: border-box;
            padding-left: 15px;
        }

        .uk-search-default .uk-search-input {
            border: none;
        }
    }
</style>
