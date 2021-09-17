require('./bootstrap');

import Vue from 'vue';
import { App as InertiaApp, plugin as InertiaPlugin } from '@inertiajs/inertia-vue';
import PortalVue from 'portal-vue';
import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css';
import Vuelidate from 'vuelidate';
import axios from 'axios'
import VueAxios from 'vue-axios'
import VSnackbars from "v-snackbars"

Vue.mixin({ methods: { route } });
Vue.use(InertiaPlugin);
Vue.use(PortalVue);
Vue.use(Vuetify, {iconfont: 'mdi'})
Vue.use(Vuelidate);
Vue.use(VueAxios, axios)

const app = document.getElementById('app');

Vue.directive('blur', {
    inserted: function (el) {
        el.onfocus = (ev) => ev.target.blur()
    }
});

Vue.mixin({
    components: {
        VSnackbars
    },

    methods: {
        getCurrentDate() {
            const current = new Date();
            let day = `${current.getDate()}`;
            if (day.length === 1) day = '0' + day
            let month = `${current.getMonth()+1}`;
            if (month.length === 1) month = '0' + month
            let year = `${current.getFullYear()}`;
            if (year.length === 1) year = '0' + year
            return year + '-' + month + '-' + day
        },

        checkNestedKeyExists(obj, level, ...rest) {
            if (obj === undefined) return false
            if (rest.length === 0 && obj.hasOwnProperty(level)) return true
            return this.checkNestedKeyExists(obj[level], ...rest)
        },

        flattenObject(obj, prefix = '') {
            return Object.keys(obj).reduce((acc, k) => {
                const pre = prefix.length ? prefix + '.' : '';
                if (typeof obj[k] === 'object') Object.assign(acc, this.flattenObject(obj[k], pre + k));
                else acc[pre + k] = obj[k];
                return acc;
            }, {});
        },

        wildMatch(wildcard, str) {
            let w = wildcard.replace(/[.+^${}()|[\]\\]/g, '\\$&');
            const re = new RegExp(`^${w.replace(/\*/g,'.*').replace(/\?/g,'.')}$`,'i');
            return re.test(str);
        },

        wildArrayMatch(wildcards, key) {
            for (let i = 0; i < wildcards.length; i++) {
                if (this.wildMatch(wildcards[i], key)) {
                    return true
                }
            }
            return false
        },

        getValidationErrors (bag, wildcards = [], excludeMode = false) {
            if (!Array.isArray(wildcards)) wildcards = [wildcards]
            let flatBag = this.flattenObject(bag)
            let keys = Object.keys(flatBag)
            let errors = []
            keys.forEach((key) => {
                let matches = this.wildArrayMatch(wildcards, key)
                if ((!excludeMode && matches) || (excludeMode && !matches)) {
                    errors.push(flatBag[key])
                }
            })
            return errors
        }
    }
})

new Vue({
    vuetify: new Vuetify(),
    render: (h) =>
        h(InertiaApp, {
            props: {
                initialPage: JSON.parse(app.dataset.page),
                resolveComponent: (name) => require(`./Pages/${name}`).default,
            },
        }),
}).$mount(app);
