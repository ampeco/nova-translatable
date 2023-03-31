import IndexField from "./components/IndexField";
import DetailField from "./components/DetailField";
import FormField from "./components/FormField";
Nova.booting((Vue, router) => {
    Vue.component('index-translatable', IndexField);
    Vue.component('detail-translatable', DetailField);
    Vue.component('form-translatable', FormField);
})
