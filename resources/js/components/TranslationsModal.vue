<template>
    <teleport to="body">
        <div
            class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-gray-500/75 p-6"
            @mousedown.self="attemptClose"
        >
            <div
                ref="dialog"
                role="dialog"
                aria-modal="true"
                :aria-label="`Translations for ${fieldName}`"
                class="relative mt-12 w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-800"
                @keydown.esc.prevent="attemptClose"
                @keydown.tab="trapFocus"
            >
                <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-bold">Translations · {{ fieldName }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ translatedCount }} / {{ totalCount }} translated</p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-600" aria-label="Close" @click="attemptClose">&times;</button>
                </div>

                <div class="flex items-center gap-3 px-6 pt-4">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Find or add a language…"
                        class="form-control form-input form-input-bordered w-full"
                    />
                    <label class="flex shrink-0 items-center gap-2 text-sm text-gray-500">
                        <input v-model="showAll" type="checkbox" /> Show empty
                    </label>
                </div>

                <div class="max-h-[60vh] space-y-4 overflow-y-auto px-6 py-4">
                    <div v-for="localeKey in visibleLocales" :key="localeKey">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="flex items-center gap-2 text-sm font-semibold">
                                <span :class="hasValue(localeKey) ? 'text-green-600' : 'text-gray-400'">&#9679;</span>
                                {{ localeLabel(localeKey) }}
                                <span class="font-normal text-gray-400">· {{ localeKey }}</span>
                                <span
                                    v-if="localeKey === primaryLocale"
                                    class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-normal text-gray-500 dark:bg-gray-700"
                                >default</span>
                            </span>
                            <button
                                v-if="localeKey !== primaryLocale && hasValue(primaryLocale)"
                                type="button"
                                class="text-xs font-bold text-primary-500 hover:text-primary-400"
                                @click="copyFromPrimary(localeKey)"
                            >
                                Copy from {{ localeLabel(primaryLocale) }}
                            </button>
                        </div>

                        <trix
                            v-if="field.trix"
                            :value="draft[localeKey] || ''"
                            name="trixman"
                            placeholder=""
                            @change="(v) => setValue(localeKey, v)"
                        />
                        <input
                            v-else-if="field.singleLine"
                            :value="draft[localeKey] || ''"
                            dir="auto"
                            class="form-control form-input form-input-bordered w-full"
                            @input="setValue(localeKey, $event.target.value)"
                        />
                        <textarea
                            v-else
                            :value="draft[localeKey] || ''"
                            dir="auto"
                            rows="2"
                            class="form-control form-input form-input-bordered w-full py-2"
                            @input="setValue(localeKey, $event.target.value)"
                        ></textarea>
                    </div>

                    <p v-if="!visibleLocales.length" class="text-sm text-gray-400">No languages match “{{ search }}”.</p>
                </div>

                <div class="flex items-center justify-end gap-4 border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                    <button type="button" class="text-sm font-bold text-gray-500 hover:text-gray-700" @click="attemptClose">Cancel</button>
                    <button type="button" class="rounded bg-primary-500 px-4 py-2 text-sm font-bold text-white hover:bg-primary-400" @click="save">Save</button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script>
import Trix from '../Trix'

export default {
    components: { trix: Trix },

    props: ['field', 'locales', 'primaryLocale', 'value'],

    emits: ['save', 'close'],

    data() {
        return {
            draft: { ...(this.value || {}) },
            search: '',
            showAll: false,
        }
    },

    computed: {
        fieldName() {
            return this.field.singularLabel || this.field.name
        },
        localeKeys() {
            return Object.keys(this.locales)
        },
        orderedKeys() {
            return [this.primaryLocale, ...this.localeKeys.filter(k => k !== this.primaryLocale)]
        },
        totalCount() {
            return this.localeKeys.length
        },
        translatedCount() {
            return this.localeKeys.filter(k => this.hasValue(k)).length
        },
        dirty() {
            const base = this.value || {}
            return this.localeKeys.some(k => (this.draft[k] || '') !== (base[k] || ''))
        },
        visibleLocales() {
            const q = this.search.trim().toLowerCase()
            return this.orderedKeys.filter(k => {
                const matchesSearch = !q || this.localeLabel(k).toLowerCase().includes(q) || k.toLowerCase().includes(q)
                if (!matchesSearch) return false
                return this.hasValue(k) || this.showAll || !!q || k === this.primaryLocale
            })
        },
    },

    mounted() {
        this._returnFocusTo = document.activeElement
        this.$nextTick(() => {
            const focusable = this.$refs.dialog.querySelector('input, textarea, button')
            if (focusable) focusable.focus()
        })
    },

    beforeUnmount() {
        if (this._returnFocusTo && this._returnFocusTo.focus) this._returnFocusTo.focus()
    },

    methods: {
        hasValue(localeKey) {
            const v = this.draft[localeKey]
            return typeof v === 'string' ? v.trim().length > 0 : !!v
        },
        localeLabel(localeKey) {
            return this.locales[localeKey] || localeKey
        },
        setValue(localeKey, v) {
            this.draft[localeKey] = v
        },
        copyFromPrimary(localeKey) {
            this.draft[localeKey] = this.draft[this.primaryLocale] || ''
        },
        save() {
            this.$emit('save', { ...this.draft })
        },
        attemptClose() {
            if (this.dirty && !window.confirm('Discard unsaved translation changes?')) return
            this.$emit('close')
        },
        trapFocus(e) {
            const nodes = this.$refs.dialog.querySelectorAll('a[href], button, input, textarea, select, [tabindex]:not([tabindex="-1"])')
            const list = Array.from(nodes).filter(n => !n.disabled && n.offsetParent !== null)
            if (!list.length) return
            const first = list[0]
            const last = list[list.length - 1]
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault()
                last.focus()
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault()
                first.focus()
            }
        },
    },
}
</script>
