<template>
  <trix-editor
      ref="theEditor"
      @keydown.stop
      :input="uid"
      v-bind="$attrs"
      @trix-change="handleChange"
      @trix-initialize="initialize"
      @trix-attachment-add="handleAddFile"
      @trix-attachment-remove="handleRemoveFile"
      @trix-file-accept="handleFileAccept"
      :placeholder="placeholder"
      class="trix-content prose !max-w-full prose-sm dark:prose-invert"
  />
  <input type="hidden" :name="name" :id="uid" :value="value" />
</template>

<script>
import { uid } from 'uid/single'
import 'trix/dist/trix.css'

export default {
  name: 'trix-vue',

  inheritAttrs: false,

  emits: ['change', 'file-added', 'file-removed'],

  props: {
    name: { type: String },
    value: { type: String },
    placeholder: { type: String },
    withFiles: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
  },

  data: () => ({
    uid: uid(),
    loading: true,
  }),

  methods: {
    initialize() {
      if (this.disabled) {
        this.$refs.theEditor.setAttribute('contenteditable', false)
      }
      this.loading = false
      this.$refs.theEditor.editor.insertHTML(this.value)
    },

    handleChange() {
      if (!this.loading) {
        this.$emit('change', this.$refs.theEditor.value)
      }
    },

    handleFileAccept(e) {
      if (!this.withFiles) {
        e.preventDefault()
      }
    },

    handleAddFile(event) {
      this.$emit('file-added', event)
    },

    handleRemoveFile(event) {
      this.$emit('file-removed', event)
    },
    update() {
      this.$refs.theEditor.editor.loadHTML(this.value)
    },
  },
}
</script>
