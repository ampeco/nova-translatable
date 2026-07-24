<template>
    <PanelItem :field="field">
        <template #value>

            <a
                class="inline-block cursor-pointer mr-2 transition delay-500 select-none border-primary-500"
                :class="[
                    hasTranslation(localeKey) ? 'text-green-600' : 'text-gray-400',
                    localeKey === currentLocale ? 'font-bold border-b-2' : 'font-normal',
                ]"
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
        },

        hasTranslation(locale) {
            const val = this.field.value[locale]
            return typeof val === 'string' ? val.trim().length > 0 : !!val
        }
    },

    computed: {
        value() {
            return this.field.value[this.currentLocale] || '—'
        }
    },
}
</script>
