<template>
  <v-menu
    :model-value="modelValue"
    location="bottom end"
    :close-on-content-click="false"
    offset-y
    transition="slide-y-transition"
    max-width="380"
    @update:model-value="(v) => $emit('update:modelValue', v)"
  >
    <template #activator="{ props }">
      <v-badge
        :color="unreadCount > 0 ? 'error' : 'grey'"
        :content="unreadCount > 0 ? unreadCount : ''"
        overlap
      >
        <v-btn
          :icon="icon"
          v-bind="props"
          variant="text"
          density="comfortable"
          :aria-label="title"
          @click="$emit('open')"
        />
      </v-badge>
    </template>

    <v-card min-width="350" max-width="400" class="dropdown-card">
      <v-card-title class="d-flex align-center pa-3">
        <span class="text-subtitle-1 font-weight-bold">{{ title }}</span>
        <v-spacer />
        <v-btn
          v-if="unreadCount > 0"
          variant="text"
          color="primary"
          size="small"
          @click="$emit('mark-all-read')"
        >
          Alle gelesen
        </v-btn>
      </v-card-title>
      <v-divider />

      <div class="dropdown-scroll">
        <v-list v-if="items.length" density="compact">
          <v-list-item
            v-for="item in items"
            :key="item[itemKey]"
            :class="{ 'unread-item': !item.is_read }"
            class="dropdown-item"
            @click="$emit('item-click', item)"
          >
            <template #prepend>
              <slot name="prepend" :item="item">
                <v-icon :color="iconColor(item)" size="28">
                  {{ iconFor(item) }}
                </v-icon>
              </slot>
            </template>

            <slot name="content" :item="item">
              <span class="text-body-2">{{ item[itemKey] }}</span>
            </slot>
          </v-list-item>
        </v-list>
        <div v-else class="text-center pa-4 text-grey">{{ emptyText }}</div>
      </div>

      <v-divider />
      <v-card-actions class="pa-2">
        <v-btn
          variant="text"
          color="primary"
          block
          size="small"
          @click="$emit('action-click')"
        >
          {{ actionText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-menu>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  unreadCount: { type: Number, default: 0 },
  icon: { type: String, required: true },
  title: { type: String, required: true },
  emptyText: { type: String, default: 'Keine Einträge' },
  items: { type: Array, default: () => [] },
  itemKey: { type: String, default: 'id' },
  actionText: { type: String, required: true },
  iconFor: {
    type: Function,
    default: () => 'mdi-bell',
  },
  iconColor: {
    type: Function,
    default: () => 'grey',
  },
})

defineEmits([
  'update:modelValue',
  'open',
  'mark-all-read',
  'item-click',
  'action-click',
])
</script>

<style scoped>
.dropdown-card {
  border-radius: 12px !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
  overflow: hidden;
}

.dropdown-scroll {
  max-height: 350px;
  overflow-y: auto;
  scrollbar-gutter: stable;
}

.dropdown-item {
  cursor: pointer;
  transition: background 0.15s ease;
}
.dropdown-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.unread-item {
  background-color: rgba(33, 150, 243, 0.06);
  border-left: 3px solid rgb(var(--v-theme-primary));
}
.unread-item:hover {
  background-color: rgba(33, 150, 243, 0.12);
}

.dropdown-scroll::-webkit-scrollbar {
  width: 4px;
}
.dropdown-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.dropdown-scroll::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 4px;
}
.dropdown-scroll::-webkit-scrollbar-thumb:hover {
  background: #aaa;
}
</style>