<template>
    <PanelItem :field="field">
        <template #value>

            <a
                class="inline-block font-bold cursor-pointer mr-2 transition delay-500 select-none border-primary-500"
                :class="{ 'text-gray-400': localeKey !== currentLocale, 'text-primary-500 border-b-2': localeKey === currentLocale }"
                :key="`a-${localeKey}`"
                v-for="(locale, localeKey) in field.locales"
                @click="changeTab(localeKey)"
            >
                {{ locale }}
            </a>

            <div class="mt-4">
                <div v-if="field.asHtml" v-html="value"></div>
                <div v-else :class="{ 'truncate': field.truncate }">{{ value }}</div>
            </div>

        </template>
    </PanelItem>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    data() {
        return {
            currentLocale: Object.keys(this.field.locales)[0]
        }
    },

    methods: {
        changeTab(locale) {
            this.currentLocale = locale
        }
    },

    computed: {
        value() {
            return this.field.value[this.currentLocale] || '—'
        }
    },
}
</script>
