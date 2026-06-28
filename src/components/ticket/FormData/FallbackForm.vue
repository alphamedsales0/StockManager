<template>
  <div>
    <v-row>
      <v-col cols="12">
        <div class="info-item">
          <div class="item-label"><v-icon size="16">mdi-code-json</v-icon> Rohdaten</div>
          <div class="item-value">
            <pre class="json-preview" style="max-height:300px; overflow:auto;">{{ JSON.stringify(data, null, 2) }}</pre>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
defineProps(['data'])
</script>