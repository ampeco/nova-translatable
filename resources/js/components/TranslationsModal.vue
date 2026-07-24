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
                    <div class="flex items-center gap-5">
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="showAll ? 'true' : 'false'"
                            class="flex cursor-pointer select-none items-center gap-2 text-sm text-gray-500"
                            @click="showAll = !showAll"
                        >
                            <span>Show empty</span>
                            <span
                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors"
                                :class="showAll ? 'bg-primary-500' : 'bg-gray-300 dark:bg-gray-600'"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                    :class="showAll ? 'translate-x-4' : 'translate-x-0.5'"
                                ></span>
                            </span>
                        </button>
                        <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-600" aria-label="Close" @click="attemptClose">&times;</button>
                    </div>
                </div>

                <div class="max-h-[60vh] overflow-y-auto px-6 py-4">
                  <div ref="list" class="space-y-4">
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
                  </div>

                    <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Find or add a language…"
                            class="form-control form-input form-input-bordered w-full"
                        />
                        <p v-if="noSearchMatch" class="mt-2 text-sm text-gray-400">No languages match “{{ search }}”.</p>
                    </div>
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
                if (this.hasValue(k) || k === this.primaryLocale) return true
                if (this.showAll) return true
                if (q) return this.matchesQuery(k, q)
                return false
            })
        },
        noSearchMatch() {
            const q = this.search.trim().toLowerCase()
            if (!q) return false
            return !this.localeKeys.some(k => this.matchesQuery(k, q))
        },
    },

    mounted() {
        this._returnFocusTo = document.activeElement
        this.$nextTick(() => {
            const firstField = this.$refs.list?.querySelector('input, textarea, trix-editor')
            const focusable = firstField || this.$refs.dialog.querySelector('input, textarea')
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
        matchesQuery(localeKey, q) {
            return this.localeLabel(localeKey).toLowerCase().includes(q) || localeKey.toLowerCase().includes(q)
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
