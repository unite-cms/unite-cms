
import Vue from 'vue';

export const TipTap = new Vue({

    data() {
        return {
            extensions: [],
            menuItems: [],
        }
    },
    created() {
        this.$on('registerExtension', (extension) => {
            this.extensions.push(extension);
        });

        this.$on('registerMenuItem', (menuItem) => {
            this.menuItems.push(menuItem);
        });
    },

    methods: {
        buildExtensionsForField(field) {
            return this.extensions.map((extension) => {
                return extension(field);
            });
        },
    }
});

export default TipTap;
