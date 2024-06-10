<template>
    <DefaultField :errors="errors" :field="field">
        <template #field>
            <div>
                <a
                    v-for="(locale, localeKey) in field.locales"
                    :key="`a-${localeKey}`"
                    :class="{ 'text-gray-400': localeKey !== currentLocale, 'text-primary-500 border-b-2': localeKey === currentLocale }"
                    class="inline-block font-bold cursor-pointer mr-2 transition delay-500 select-none border-primary-500"
                    @click="changeTab(localeKey)"
                >
                    {{ locale }}
                </a>

                <textarea
                    v-if="!field.singleLine && !field.trix"
                    :id="field.name"
                    ref="field"
                    v-model="value[currentLocale]"
                    :class="errorClasses"
                    :readonly="isAttributeReadOnly"
                    :disabled="isAttributeDisabled"
                    :placeholder="getPlaceholder"
                    class="mt-4 w-full form-control form-input form-control-bordered py-3 min-h-textarea"
                    @keydown.tab="handleTab"
                ></textarea>

                <div v-if="!field.singleField && field.trix" class="mt-4" @keydown.stop>
                    <trix
                        ref="field"
                        :value="value[currentLocale]"
                        name="trixman"
                        placeholder=""
                        @change="handleChange"
                    />
                </div>

                <input
                    v-if="field.singleLine"
                    :id="field.name"
                    ref="field"
                    v-model="value[currentLocale]"
                    :class="errorClasses"
                    :placeholder="field.name"
                    class="mt-4 w-full form-control form-input form-control-bordered"
                    type="text"
                    @keydown.tab="handleTab"
                />
                <help-text v-if="field.helpText" class="help-text mt-2">
                    {{ field.helpText }}
                </help-text>
            </div>
        </template>
    </DefaultField>
</template>

<script>

import Trix from '../Trix'

import { DependentFormField, FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [DependentFormField, FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    components: { Trix },

    data() {
        return {
            locales: Object.keys(this.field.locales),
            currentLocale: null,
        }
    },

    mounted() {
        this.currentLocale = this.locales[0] || null;

        // Explicitly trigger change in order to get nova send the input in the POST request
        // Fixes a case when you dynamically add an empty translatable input in update form
        if (!this.value[this.currentLocale]) {
            this.handleChange()
        }
    },

    methods: {
        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
          this.value = this.field.value || {}
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            Object.keys(this.value).forEach(locale => {
                formData.append(this.field.attribute + '[' + locale + ']', this.value[locale] || '')
            })
        },

        /**
         * Update the field's internal value.
         */
        handleChange(value) {
          this.value[this.currentLocale] = value
        },

        changeTab(locale, dontEmit) {
            if(this.currentLocale !== locale){
                if(!dontEmit){
                    //EventBus.$emit('localeChanged', locale);
                    this.changeTab(locale, true)
                }

                this.currentLocale = locale;

                this.$nextTick(() => {
                    if (this.field.trix) {
                        this.$refs.field.update()
                    } else {
                        this.$refs.field.focus()
                    }
                })
            }
        },

        handleTab(e) {
            const currentIndex = this.locales.indexOf(this.currentLocale)
            if (!e.shiftKey) {
                if (currentIndex < this.locales.length - 1) {
                    e.preventDefault();
                    this.changeTab(this.locales[currentIndex + 1]);
                }
            } else {
                if (currentIndex > 0) {
                    e.preventDefault();
                    this.changeTab(this.locales[currentIndex - 1]);
                }
            }
        }
    },

    computed: {
        fieldLabel() {
            return this.field.singularLabel || this.field.name
        },

        isAttributeReadOnly() {
            return this.field.extraAttributes?.readOnly
                ?? this.field.extraAttributes?.readonly
                ?? false
        },

        isAttributeDisabled() {
            return this.field.extraAttributes?.disabled
                ?? false
        },

        getPlaceholder() {
            return this.field.extraAttributes?.placeholder
                ?? this.field.name
        },
    }
}
</script>
