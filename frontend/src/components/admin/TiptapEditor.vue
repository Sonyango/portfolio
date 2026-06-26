<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import { watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Underline,
    Image.configure({ inline: true }),
    Link.configure({ openOnClick: false }),
    Placeholder.configure({ placeholder: 'Start writing your post...'}),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
  editorProps: {
    attributes: {
      class: 'prose prose-invert max-w-none min-h-64 focus:outline-none p-4'
    }
  }
})

watch(() => props.modelValue, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    editor.value.commands.setContent(val || '', false)
  }
})
</script>

<template>
  <div class="border border-slate-600 rounded-xl overflow-hidden bg-slate-900">

    <!-- Toobar -->
     <div v-if="editor"
      class="flex flex-wrap gap-1 p-2 border-b border-slate-700 bg-slate-800">
      <button v-for="item in [
        { label: 'B',   action: () => editor.chain().focus().toggleBold().run(),
          active: editor.isActive('bold') },
        { label: 'I',   action: () => editor.chain().focus().toggleItalic().run(),
          active: editor.isActive('italic') },
        { label: 'U',   action: () => editor.chain().focus().toggleUnderline().run(),
          active: editor.isActive('underline') },
        { label: 'H2',  action: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
          active: editor.isActive('heading', { level: 2 }) },
        { label: 'H3',  action: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
          active: editor.isActive('heading', { level: 3 }) },
        { label: '• List', action: () => editor.chain().focus().toggleBulletList().run(),
          active: editor.isActive('bulletList') },
        { label: '1. List', action: () => editor.chain().focus().toggleOrderedList().run(),
          active: editor.isActive('orderedList') },
        { label: '❝',  action: () => editor.chain().focus().toggleBlockquote().run(),
          active: editor.isActive('blockquote') },
        { label: '</>',  action: () => editor.chain().focus().toggleCodeBlock().run(),
          active: editor.isActive('codeBlock') },
        { label: '—',  action: () => editor.chain().focus().setHorizontalRule().run(),
          active: false },
      ]" :key="item.label"
          @click="item.action"
        :class="['px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
          item.active
            ? 'bg-indigo-600 text-white'
            : 'text-slate-400 hover:bg-slate-700 hover:text-white']">
            {{ item.label }}
       </button>
     </div>

     <!-- Editor area-->
      <EditorContent :editor="editor" />
  </div>
</template>

<style>
.tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #64748b;
  pointer-events: none;
  height: 0;
}
</style>
