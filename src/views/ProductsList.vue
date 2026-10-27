<template>
  <v-container fluid class="products-container px-2 px-sm-4">
    <!-- ============================================================ -->
    <!-- HEADER MODERNISÉ -->
    <!-- ============================================================ -->
    <v-row class="mb-3">
      <v-col cols="12">
        <div class="d-flex flex-wrap align-center justify-space-between gap-2">
          <div>
            <div class="d-flex align-center gap-2 mb-1">
              <div class="icon-badge">
                <v-icon size="24" color="white">mdi-package-variant-closed</v-icon>
              </div>
              <h1 class="text-h4 font-weight-bold mb-0 gradient-text">Unsere Produkte</h1>
            </div>
            <p class="text-caption text-medium-emphasis mt-1 mb-0">
              Fitnessgeräte & Zubehör
              <span v-if="lastUpdate" class="ml-2">
                • Zuletzt aktualisiert: {{ lastUpdate }}
              </span>
            </p>
          </div>

          <div class="d-flex align-center gap-2 flex-wrap">
            <v-btn
              color="primary"
              variant="tonal"
              prepend-icon="mdi-refresh"
              size="small"
              rounded="lg"
              :loading="loading"
              class="btn-modern"
              @click="refreshProducts"
            >
              Aktualisieren
            </v-btn>

            <!-- ✅ MENU EXPORT -->
            <v-menu location="bottom end" :close-on-content-click="true">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  color="success"
                  variant="tonal"
                  prepend-icon="mdi-download"
                  append-icon="mdi-chevron-down"
                  size="small"
                  rounded="lg"
                  class="btn-modern"
                  :disabled="!products.length"
                >
                  Export
                </v-btn>
              </template>
              <v-list density="compact" class="export-menu">
                <v-list-subheader>
                  {{ filteredProducts.length }} Produkt(e) gefiltert
                </v-list-subheader>

                <template v-if="selectedIds.length">
                  <v-list-item @click="exportSelectedCsv">
                    <template #prepend>
                      <v-icon color="deep-orange">mdi-checkbox-multiple-marked</v-icon>
                    </template>
                    <v-list-item-title>
                      CSV – Auswahl ({{ selectedIds.length }})
                    </v-list-item-title>
                    <v-list-item-subtitle>
                      Nur markierte Produkte exportieren
                    </v-list-item-subtitle>
                  </v-list-item>
                  <v-divider class="my-1" />
                </template>

                <v-list-item @click="exportCsv(filteredProducts)">
                  <template #prepend>
                    <v-icon color="success">mdi-file-delimited</v-icon>
                  </template>
                  <v-list-item-title>CSV – gefilterte Produkte</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ filteredProducts.length }} Produkt(e) (client)
                  </v-list-item-subtitle>
                </v-list-item>

                <v-list-item @click="exportAllCsvServer">
                  <template #prepend>
                    <v-icon color="primary">mdi-file-delimited-outline</v-icon>
                  </template>
                  <v-list-item-title>CSV – alle Produkte</v-list-item-title>
                  <v-list-item-subtitle>
                    {{ products.length }} Produkt(e) gesamt (serverseitig)
                  </v-list-item-subtitle>
                </v-list-item>

                <v-divider class="my-1" />

                <v-list-item @click="exportJson">
                  <template #prepend>
                    <v-icon color="info">mdi-code-json</v-icon>
                  </template>
                  <v-list-item-title>JSON – gefilterte Produkte</v-list-item-title>
                  <v-list-item-subtitle>Format für Backup / Import</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-menu>

            <v-btn
              v-if="permissionStore.can('products-write')"
              color="primary"
              variant="elevated"
              prepend-icon="mdi-plus"
              size="small"
              rounded="lg"
              class="btn-modern btn-primary-modern"
              @click="addProduct"
            >
              Neu
            </v-btn>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- STATS CARDS -->
    <!-- ============================================================ -->
    <v-row v-if="!loading && products.length" class="mb-3">
      <v-col cols="6" sm="3">
        <v-card class="stat-card" variant="flat" rounded="lg">
          <div class="stat-icon-wrap stat-icon-blue">
            <v-icon size="22" color="white">mdi-package-variant</v-icon>
          </div>
          <div class="stat-content">
            <div class="stat-label">Gesamt</div>
            <div class="stat-value">{{ products.length }}</div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="6" sm="3">
        <v-card class="stat-card" variant="flat" rounded="lg">
          <div class="stat-icon-wrap stat-icon-green">
            <v-icon size="22" color="white">mdi-check-circle</v-icon>
          </div>
          <div class="stat-content">
            <div class="stat-label">Auf Lager</div>
            <div class="stat-value">{{ inStockCount }}</div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="6" sm="3">
        <v-card class="stat-card" variant="flat" rounded="lg">
          <div class="stat-icon-wrap stat-icon-orange">
            <v-icon size="22" color="white">mdi-recycle-variant</v-icon>
          </div>
          <div class="stat-content">
            <div class="stat-label">Gebraucht</div>
            <div class="stat-value">{{ usedCount }}</div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="6" sm="3">
        <v-card class="stat-card" variant="flat" rounded="lg">
          <div class="stat-icon-wrap stat-icon-red">
            <v-icon size="22" color="white">mdi-sale</v-icon>
          </div>
          <div class="stat-content">
            <div class="stat-label">Angebote</div>
            <div class="stat-value">{{ offerCount }}</div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- FILTRES (ordre optimisé) -->
    <!-- ============================================================ -->
    <v-row class="mb-3">
      <v-col cols="12">
        <v-card variant="flat" class="filter-card" rounded="lg">
          <v-card-text class="pa-2 pa-sm-3">
            <v-row dense align="center" class="mx-0">
              <!-- Recherche -->
              <v-col cols="12" sm="4" md="3" class="px-1">
                <v-text-field
                  v-model="search"
                  prepend-inner-icon="mdi-magnify"
                  placeholder="Suchen (Name, Marke, Art.-Nr.)..."
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                  @update:model-value="onFilterChange"
                />
              </v-col>

              <!-- Kategorie -->
              <v-col cols="6" sm="2" md="2" class="px-1">
                <v-select
                  v-model="selectedCategory"
                  :items="categoryOptions"
                  item-title="title"
                  item-value="value"
                  label="Kategorie"
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                  @update:model-value="onFilterChange"
                />
              </v-col>

              <!-- ✅ Zustand (Neu / Gebraucht) -->
              <v-col cols="6" sm="2" md="2" class="px-1">
                <v-select
                  v-model="usedFilter"
                  :items="usedOptions"
                  label="Zustand"
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                  @update:model-value="onFilterChange"
                />
              </v-col>

              <!-- Lager -->
              <v-col cols="6" sm="2" md="2" class="px-1">
                <v-select
                  v-model="stockFilter"
                  :items="stockOptions"
                  label="Lager"
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  clearable
                  rounded="lg"
                />
              </v-col>

              <!-- Sortierung -->
              <v-col cols="6" sm="2" md="2" class="px-1">
                <v-select
                  v-model="sortBy"
                  :items="sortOptions"
                  label="Sortierung"
                  variant="solo-filled"
                  density="compact"
                  hide-details
                  rounded="lg"
                />
              </v-col>

              <!-- Actions -->
              <v-col cols="12" sm="12" md="1" class="text-end px-1">
                <div class="d-flex justify-end gap-1 flex-wrap">
                  <v-btn
                    v-if="paginatedProducts.length"
                    variant="text"
                    size="small"
                    color="primary"
                    @click="toggleSelectAll"
                  >
                    <v-icon size="18">
                      {{ allPageSelected ? 'mdi-checkbox-multiple-marked' : 'mdi-checkbox-multiple-blank-outline' }}
                    </v-icon>
                    <v-tooltip activator="parent" location="top">
                      {{ allPageSelected ? 'Auswahl aufheben' : 'Alle auswählen' }}
                    </v-tooltip>
                  </v-btn>

                  <v-btn
                    icon="mdi-filter-remove"
                    variant="text"
                    size="small"
                    color="grey"
                    @click="resetFilters"
                    v-if="hasActiveFilters"
                  >
                    <v-icon>mdi-filter-remove</v-icon>
                    <v-tooltip activator="parent" location="top">Filter zurücksetzen</v-tooltip>
                  </v-btn>

                  <v-btn
                    :icon="viewMode === 'grid' ? 'mdi-view-list' : 'mdi-view-grid'"
                    variant="text"
                    size="small"
                    color="grey"
                    @click="toggleViewMode"
                  >
                    <v-tooltip activator="parent" location="top">
                      {{ viewMode === 'grid' ? 'Listenansicht' : 'Rasteransicht' }}
                    </v-tooltip>
                  </v-btn>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Résumé filtres actifs -->
    <div v-if="(hasActiveFilters || selectedIds.length) && !loading" class="filter-summary mb-3">
      <v-icon size="16" color="primary">mdi-filter</v-icon>
      <span class="text-caption">
        <strong>{{ filteredProducts.length }}</strong> von {{ products.length }} Produkten
        <span v-if="search">· Suche: "<strong>{{ search }}</strong>"</span>
        <span v-if="selectedCategory">· Kategorie: <strong>{{ selectedCategory }}</strong></span>
        <span v-if="usedFilter !== null">
          · Zustand: <strong>{{ usedFilter === 'new' ? 'Neu' : 'Gebraucht' }}</strong>
        </span>
        <span v-if="stockFilter !== null && stockFilter !== undefined">
          · Lager: <strong>{{ stockFilter ? 'Auf Lager' : 'Nicht lagernd' }}</strong>
        </span>
        <span v-if="selectedIds.length" class="ml-2 selection-pill">
          <v-icon size="12">mdi-checkbox-multiple-marked</v-icon>
          {{ selectedIds.length }} ausgewählt
          <v-btn
            icon="mdi-close"
            size="x-small"
            variant="text"
            density="compact"
            @click="clearSelection"
          />
        </span>
      </span>
    </div>

    <!-- ============================================================ -->
    <!-- CHARGEMENT -->
    <!-- ============================================================ -->
    <v-row v-if="loading" class="compact-grid">
      <v-col
        v-for="n in itemsPerPage"
        :key="n"
        :cols="6"
        :sm="4"
        :md="3"
        :lg="12/5"
        class="pa-1 pa-sm-2"
      >
        <v-skeleton-loader type="image, article, actions" class="rounded-lg" />
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- VUE GRILLE -->
    <!-- ============================================================ -->
    <v-row v-else-if="paginatedProducts.length && viewMode === 'grid'" class="compact-grid">
      <v-col
        v-for="product in paginatedProducts"
        :key="product.id"
        :cols="6"
        :sm="4"
        :md="3"
        :lg="12/5"
        class="pa-1 pa-sm-2"
      >
        <v-card
          class="product-card"
          :class="{
            'is-gebraucht': product.isUsed,
            'is-selected': isSelected(product.id)
          }"
          elevation="0"
        >
          <div class="selection-checkbox" @click.stop>
            <v-checkbox-btn
              :model-value="isSelected(product.id)"
              color="primary"
              density="compact"
              hide-details
              @update:model-value="toggleSelect(product.id)"
            />
          </div>

          <div class="card-image" @click="viewProduct(product.id)">
            <div class="image-container">
              <v-img
                :src="getImageUrl(product)"
                :alt="product.name"
                height="200"
                contain
                class="product-image"
                @error="() => handleImageError(product)"
                :lazy-src="placeholderImage"
              >
                <template v-slot:placeholder>
                  <div class="image-placeholder">
                    <v-icon size="64" color="grey-lighten-1">mdi-image</v-icon>
                    <div class="placeholder-text">{{ product.brand }}</div>
                  </div>
                </template>
              </v-img>

              <div class="absolute-badges">
                <div class="badges-container">
                  <v-chip v-if="product.isUsed" color="deep-orange" size="small" class="badge-modern gebraucht-badge">
                    <v-icon start size="12">mdi-recycle-variant</v-icon>
                    Gebraucht
                  </v-chip>
                  <v-chip v-if="product.isNew && !product.isUsed" color="success" size="small" class="badge-modern new-badge">
                    <v-icon start size="12">mdi-new-box</v-icon>
                    Neu
                  </v-chip>
                  <v-chip v-if="product.bestSeller" color="warning" size="small" class="badge-modern best-badge">
                    <v-icon start size="12">mdi-trophy</v-icon>
                    Bestseller
                  </v-chip>
                  <v-chip v-if="product.isOffer" color="error" size="small" class="badge-modern offer-badge">
                    <v-icon start size="12">mdi-sale</v-icon>
                    Angebot
                  </v-chip>
                </div>
              </div>

              <div class="stock-indicator" :class="product.inStock ? 'in-stock' : 'out-of-stock'">
                <v-icon size="12">{{ product.inStock ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
              </div>
            </div>
          </div>

          <v-card-title class="product-title" @click="viewProduct(product.id)">
            {{ truncate(product.name, 40) }}
          </v-card-title>

          <v-card-subtitle class="product-brand">
            <strong>{{ product.brand }}</strong>
            <span v-if="product.article_number" class="article-number">
              Art.-Nr.: {{ product.article_number }}
            </span>
          </v-card-subtitle>

          <div v-if="product.isUsed && product.condition" class="zustand-badge">
            <v-icon size="14" color="deep-orange">mdi-information-outline</v-icon>
            Zustand: <strong>{{ formatZustand(product.condition) }}</strong>
          </div>

          <v-card-text class="product-features">
            <div v-if="product.features && product.features.length">
              <div v-for="feature in product.features.slice(0, 3)" :key="feature" class="feature">
                <v-icon size="14" color="primary">mdi-check-circle</v-icon>
                <span>{{ feature }}</span>
              </div>
            </div>
            <div v-else>
              <div v-if="product.weight_capacity" class="feature">
                <v-icon size="14" color="primary">mdi-check-circle</v-icon>
                <span>Belastbarkeit: {{ product.weight_capacity }} kg</span>
              </div>
              <div v-if="product.warranty_years" class="feature">
                <v-icon size="14" color="primary">mdi-check-circle</v-icon>
                <span>Garantie: {{ product.warranty_years }} Jahre</span>
              </div>
              <div v-if="product.category" class="feature">
                <v-icon size="14" color="primary">mdi-check-circle</v-icon>
                <span>Kategorie: {{ product.category }}</span>
              </div>
            </div>
          </v-card-text>

          <v-card-actions class="product-actions">
            <div class="price-block">
              <div v-if="product.isUsed && product.usedPriceNet" class="old-price">
                {{ formatPrice(product.price) }}
              </div>
              <div class="price" :class="{ 'price-gebraucht': product.isUsed }">
                {{ formatPrice(prixAffiche(product)) }}
              </div>
            </div>
            <v-spacer />
            <v-btn
              v-if="permissionStore.can('products-write')"
              color="primary"
              size="small"
              variant="tonal"
              class="action-btn"
              @click.stop="editProduct(product.id)"
            >
              <v-icon size="16">mdi-pencil</v-icon>
              <span class="ml-1 action-label">Bearbeiten</span>
            </v-btn>
            <v-btn
              v-else
              color="primary"
              variant="tonal"
              size="small"
              class="action-btn"
              @click.stop="viewProduct(product.id)"
            >
              <v-icon size="16">mdi-eye</v-icon>
              <span class="ml-1 action-label">Ansehen</span>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- VUE LISTE -->
    <!-- ============================================================ -->
    <v-card v-else-if="paginatedProducts.length && viewMode === 'list'" variant="flat" class="list-view-card" rounded="lg">
      <v-table class="products-table" hover>
        <thead>
          <tr>
            <th class="text-center" style="width: 48px;">
              <v-checkbox-btn
                :model-value="allPageSelected"
                :indeterminate="somePageSelected && !allPageSelected"
                color="primary"
                density="compact"
                hide-details
                @update:model-value="toggleSelectAll"
              />
            </th>
            <th class="text-left">Produkt</th>
            <th class="text-left d-none d-md-table-cell">Kategorie</th>
            <th class="text-left d-none d-lg-table-cell">Art.-Nr.</th>
            <th class="text-center d-none d-sm-table-cell">Lager</th>
            <th class="text-right">Preis</th>
            <th class="text-right">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in paginatedProducts"
            :key="product.id"
            class="list-row"
            :class="{ 'row-selected': isSelected(product.id) }"
          >
            <td class="text-center">
              <v-checkbox-btn
                :model-value="isSelected(product.id)"
                color="primary"
                density="compact"
                hide-details
                @update:model-value="toggleSelect(product.id)"
              />
            </td>
            <td>
              <div class="d-flex align-center gap-2 product-cell" @click="viewProduct(product.id)">
                <v-img
                  :src="getImageUrl(product)"
                  :alt="product.name"
                  width="48"
                  height="48"
                  cover
                  class="rounded product-thumb"
                />
                <div class="min-w-0">
                  <div class="font-weight-medium text-truncate list-product-name">
                    {{ product.name }}
                  </div>
                  <div class="text-caption text-grey">{{ product.brand }}</div>
                </div>
                <v-chip v-if="product.isUsed" color="deep-orange" size="x-small" variant="flat" class="ml-1">
                  <v-icon start size="10">mdi-recycle-variant</v-icon>
                  Gebraucht
                </v-chip>
                <v-chip v-if="product.isNew && !product.isUsed" color="success" size="x-small" variant="flat" class="ml-1">
                  Neu
                </v-chip>
              </div>
            </td>
            <td class="d-none d-md-table-cell">
              <v-chip size="small" variant="tonal" color="primary">{{ product.category || '—' }}</v-chip>
            </td>
            <td class="d-none d-lg-table-cell text-caption">{{ product.article_number || '—' }}</td>
            <td class="text-center d-none d-sm-table-cell">
              <v-icon :color="product.inStock ? 'success' : 'error'" size="18">
                {{ product.inStock ? 'mdi-check-circle' : 'mdi-close-circle' }}
              </v-icon>
            </td>
            <td class="text-right">
              <div class="font-weight-bold" :class="product.isUsed ? 'text-deep-orange' : 'text-primary'">
                {{ formatPrice(prixAffiche(product)) }}
              </div>
              <div v-if="product.isUsed && product.usedPriceNet" class="text-caption text-grey text-decoration-line-through">
                {{ formatPrice(product.price) }}
              </div>
            </td>
            <td class="text-right">
  <div class="d-flex justify-end gap-1">
    <!-- ✅ Éditer -->
    <v-btn
      v-if="permissionStore.can('products-write')"
      icon="mdi-pencil"
      size="x-small"
      variant="text"
      color="primary"
      @click.stop="editProduct(product.id)"
    >
      <v-icon size="16">mdi-pencil</v-icon>
      <v-tooltip activator="parent" location="top">Bearbeiten</v-tooltip>
    </v-btn>

    <!-- ✅ Supprimer -->
    <v-btn
      v-if="permissionStore.can('products-delete')"
      icon="mdi-delete"
      size="x-small"
      variant="text"
      color="error"
      @click.stop="confirmDeleteProduct(product.id)"
    >
      <v-icon size="16">mdi-delete</v-icon>
      <v-tooltip activator="parent" location="top">Löschen</v-tooltip>
    </v-btn>

    <!-- ✅ Voir (toujours) -->
    <v-btn
      icon="mdi-eye"
      size="x-small"
      variant="text"
      color="grey"
      @click.stop="viewProduct(product.id)"
    >
      <v-icon size="16">mdi-eye</v-icon>
      <v-tooltip activator="parent" location="top">Ansehen</v-tooltip>
    </v-btn>
  </div>
</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- ============================================================ -->
    <!-- PAGINATION -->
    <!-- ============================================================ -->
    <v-row v-if="filteredProducts.length && !loading" class="mt-3">
      <v-col cols="12" class="d-flex flex-wrap justify-center align-center ga-3">
        <div class="d-flex align-center gap-2">
          <span class="text-caption text-medium-emphasis">Zeilen:</span>
          <v-select
            v-model="itemsPerPage"
            :items="[8, 12, 16, 24, 32, 48]"
            variant="outlined"
            density="compact"
            style="width: 80px"
            hide-details
            rounded="lg"
            @update:model-value="currentPage = 1"
          />
        </div>
        <v-pagination
          v-model="currentPage"
          :length="totalPages"
          :total-visible="5"
          size="small"
          rounded="circle"
          color="primary"
          variant="tonal"
        />
        <span class="text-caption text-medium-emphasis">
          {{ (currentPage - 1) * itemsPerPage + 1 }}–{{ Math.min(currentPage * itemsPerPage, filteredProducts.length) }}
          von {{ filteredProducts.length }}
        </span>
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- ERREUR -->
    <!-- ============================================================ -->
    <v-row v-if="error" class="mt-4">
      <v-col cols="12">
        <v-alert type="error" variant="tonal" closable rounded="lg" @click:close="error = null">
          {{ error }}
        </v-alert>
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- AUCUN RÉSULTAT -->
    <!-- ============================================================ -->
    <v-row v-if="!loading && !error && filteredProducts.length === 0" class="mt-8">
      <v-col cols="12" class="text-center">
        <v-icon size="64" color="grey-lighten-1" icon="mdi-package-variant-closed" />
        <p class="text-h6 mt-3 mb-1">Keine Produkte gefunden</p>
        <p class="text-body-2 text-grey mb-3">Versuchen Sie, Ihre Filter anzupassen.</p>
        <v-btn variant="tonal" color="primary" size="small" prepend-icon="mdi-filter-remove" @click="resetFilters">
          Filter zurücksetzen
        </v-btn>
      </v-col>
    </v-row>

    <!-- ============================================================ -->
    <!-- SNACKBAR -->
    <!-- ============================================================ -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" location="top end" variant="tonal">
      {{ snackbar.text }}
    </v-snackbar>

    <!-- ============================================================ -->
    <!-- ✅ BARRE FLOTTANTE SÉLECTION -->
    <!-- ============================================================ -->
    <v-slide-y-transition>
      <div v-if="selectedIds.length" class="selection-toolbar">
        <div class="d-flex align-center gap-2 flex-wrap">
          <v-icon color="white">mdi-checkbox-multiple-marked</v-icon>
          <span class="text-white font-weight-bold">
            {{ selectedIds.length }} ausgewählt
          </span>
          <v-divider vertical class="mx-2" color="white" opacity="0.3" />
          <v-btn
            size="small"
            variant="tonal"
            color="white"
            prepend-icon="mdi-file-delimited"
            @click="exportSelectedCsv"
          >
            CSV exportieren
          </v-btn>
          <v-btn
            size="small"
            variant="text"
            color="white"
            prepend-icon="mdi-close"
            @click="clearSelection"
          >
            Aufheben
          </v-btn>
        </div>
      </div>
    </v-slide-y-transition>
  </v-container>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePermissionStore } from '@/stores/permission'
import { apiUrl, imageUrl } from '@/config/api'

const router = useRouter()
const route = useRoute()
const permissionStore = usePermissionStore()

// ============================================================
// État
// ============================================================
const products = ref([])
const loading = ref(false)
const error = ref(null)
const lastUpdate = ref('')

const search = ref('')
const selectedCategory = ref(null)
const stockFilter = ref(null)
const usedFilter = ref(null)
const sortBy = ref('default')
const viewMode = ref('grid')

const currentPage = ref(1)
const itemsPerPage = ref(12)

const selectedIds = ref([])

const snackbar = ref({ show: false, text: '', color: 'success' })
const imageErrors = ref({})

const placeholderImage = 'https://placehold.co/400x300?text=Kein+Bild'

// ============================================================
// API
// ============================================================
async function loadProducts() {
  loading.value = true
  error.value = null
  try {
    const url = apiUrl('/get_all_products.php')
    const response = await fetch(url, { credentials: 'include' })
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    const data = await response.json()
    if (!data.success) throw new Error(data.error || 'Erreur API')
    products.value = data.products || []
    imageErrors.value = {}
    const existing = new Set(products.value.map(p => p.id))
    selectedIds.value = selectedIds.value.filter(id => existing.has(id))
  } catch (err) {
    console.error(err)
    error.value = err.message
    products.value = []
  } finally {
    loading.value = false
  }
}


const confirmDeleteProduct = (id) => {
  if (!permissionStore.can('products-delete')) {
    permissionStore.showAccessDenied()
    return
  }
  // Appel à l'API de suppression (à adapter selon ton backend)
  if (confirm('Produkt wirklich löschen?')) {
    axios.post(apiUrl('/delete_product.php'), { product_id: id }, { withCredentials: true })
      .then(() => {
        snackbar.value = { show: true, text: '🗑️ Produkt gelöscht', color: 'success' }
        loadProducts()
      })
      .catch(err => {
        snackbar.value = { show: true, text: `❌ ${err.message}`, color: 'error' }
      })
  }
}


async function refreshProducts() {
  await loadProducts()
  lastUpdate.value = new Date().toLocaleTimeString('de-DE', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
  snackbar.value = {
    show: true,
    text: '✅ Produkte aktualisiert',
    color: 'success'
  }
}

// ============================================================
// Helpers images
// ============================================================
function getImageUrl(product) {
  if (imageErrors.value[product.id]) return placeholderImage
  const image = product.main_image || product.image
  if (!image) return placeholderImage
  return imageUrl(image)
}

function handleImageError(product) {
  imageErrors.value[product.id] = true
}

// ============================================================
// Helpers prix
// ============================================================
function formatPrice(price) {
  if (!price && price !== 0) return 'Preis auf Anfrage'
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

function prixAffiche(product) {
  if (product.isUsed && product.usedPriceNet) {
    return parseFloat(product.usedPriceNet)
  }
  return parseFloat(product.price) || 0
}

function formatZustand(zustand) {
  const map = {
    'like_new': 'Wie neu',
    'very_good': 'Sehr gut',
    'good': 'Gut',
    'acceptable': 'Akzeptabel',
    'neu': 'Neu',
    'wie-neu': 'Wie neu',
    'sehr-gut': 'Sehr gut',
    'gut': 'Gut',
    'akzeptabel': 'Akzeptabel',
    'defekt': 'Defekt'
  }
  return map[zustand] || zustand
}

// ============================================================
// Stats
// ============================================================
const inStockCount = computed(() => products.value.filter(p => p.inStock).length)
const usedCount = computed(() => products.value.filter(p => p.isUsed).length)
const offerCount = computed(() => products.value.filter(p => p.isOffer).length)

// ============================================================
// Filtres
// ============================================================
const categoryOptions = computed(() => {
  const cats = new Set(products.value.map(p => p.category).filter(Boolean))
  cats.add('rehabilitation_accessory')
  return Array.from(cats).sort().map(cat => {
    if (cat === 'rehabilitation_accessory') {
      return { title: 'Zubehör', value: 'accessories' }
    }
    return { title: cat, value: cat }
  })
})

const stockOptions = [
  { title: 'Auf Lager', value: true },
  { title: 'Nicht lagernd', value: false }
]

// ✅ Options du filtre Zustand
const usedOptions = [
  { title: 'Alle', value: null },
  { title: 'Nur Neu', value: 'new' },
  { title: 'Nur Gebraucht', value: 'used' }
]

const sortOptions = [
  { title: 'Standard', value: 'default' },
  { title: 'Name (A→Z)', value: 'name_asc' },
  { title: 'Name (Z→A)', value: 'name_desc' },
  { title: 'Preis (aufsteigend)', value: 'price_asc' },
  { title: 'Preis (absteigend)', value: 'price_desc' },
  { title: 'Marke (A→Z)', value: 'brand_asc' }
]

const filteredByCategoryAndStock = computed(() => {
  let result = products.value
  if (selectedCategory.value) {
    result = result.filter(p => p.category === selectedCategory.value)
  }
  if (stockFilter.value !== null && stockFilter.value !== undefined) {
    result = result.filter(p => p.inStock === stockFilter.value)
  }
  // ✅ Filtre Neu / Gebraucht
  if (usedFilter.value === 'new') {
    result = result.filter(p => !p.isUsed)
  } else if (usedFilter.value === 'used') {
    result = result.filter(p => p.isUsed)
  }
  return result
})

const searchedProducts = computed(() => {
  if (!search.value) return filteredByCategoryAndStock.value
  const term = search.value.toLowerCase()
  return filteredByCategoryAndStock.value.filter(p =>
    p.name?.toLowerCase().includes(term) ||
    p.brand?.toLowerCase().includes(term) ||
    p.article_number?.toLowerCase().includes(term)
  )
})

const filteredProducts = computed(() => {
  const arr = [...searchedProducts.value]
  switch (sortBy.value) {
    case 'name_asc': return arr.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'de'))
    case 'name_desc': return arr.sort((a, b) => (b.name || '').localeCompare(a.name || '', 'de'))
    case 'price_asc': return arr.sort((a, b) => prixAffiche(a) - prixAffiche(b))
    case 'price_desc': return arr.sort((a, b) => prixAffiche(b) - prixAffiche(a))
    case 'brand_asc': return arr.sort((a, b) => (a.brand || '').localeCompare(b.brand || '', 'de'))
    default: return arr
  }
})

const totalPages = computed(() =>
  Math.ceil(filteredProducts.value.length / itemsPerPage.value)
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredProducts.value.slice(start, start + itemsPerPage.value)
})

const onFilterChange = () => { currentPage.value = 1 }

watch(stockFilter, () => { currentPage.value = 1 })
watch(usedFilter, () => { currentPage.value = 1 })

const hasActiveFilters = computed(() =>
  !!selectedCategory.value ||
  (stockFilter.value !== null && stockFilter.value !== undefined) ||
  !!search.value ||
  usedFilter.value !== null
)

function resetFilters() {
  search.value = ''
  selectedCategory.value = null
  stockFilter.value = null
  usedFilter.value = null
  sortBy.value = 'default'
  onFilterChange()
}

function toggleViewMode() {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

// ============================================================
// ✅ Applique les filtres depuis l'URL (query params)
// ============================================================
function applyQueryFilters() {
  const category = route.query.category
  const used = route.query.used
  const stock = route.query.stock

  if (category) {
    selectedCategory.value = category === 'accessories' ? 'rehabilitation_accessory' : category
  }

  if (used === '1') {
    usedFilter.value = 'used'
  } else if (used === '0') {
    usedFilter.value = 'new'
  }

  // ✅ Support du filtre stock=out
  if (stock === 'out') {
    stockFilter.value = false   // inStock = false
  } else if (stock === 'in') {
    stockFilter.value = true
  }
}

// ============================================================
// ✅ SÉLECTION MULTIPLE
// ============================================================
const isSelected = (id) => selectedIds.value.includes(id)

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) {
    selectedIds.value.push(id)
  } else {
    selectedIds.value.splice(idx, 1)
  }
}

const allPageSelected = computed(() => {
  if (!paginatedProducts.value.length) return false
  return paginatedProducts.value.every(p => isSelected(p.id))
})

const somePageSelected = computed(() =>
  paginatedProducts.value.some(p => isSelected(p.id))
)

const toggleSelectAll = () => {
  if (allPageSelected.value) {
    const pageIds = new Set(paginatedProducts.value.map(p => p.id))
    selectedIds.value = selectedIds.value.filter(id => !pageIds.has(id))
  } else {
    const pageIds = paginatedProducts.value.map(p => p.id)
    const set = new Set([...selectedIds.value, ...pageIds])
    selectedIds.value = Array.from(set)
  }
}

const clearSelection = () => { selectedIds.value = [] }

// ============================================================
// ✅ EXPORT CSV (client-side)
// ============================================================
const exportCsv = (list) => {
  if (!list || !list.length) {
    snackbar.value = { show: true, text: '❌ Keine Produkte zu exportieren', color: 'error' }
    return
  }

  const fixedColumns = [
    { key: 'id', label: 'ID' },
    { key: 'article_number', label: 'Artikelnummer' },
    { key: 'name', label: 'Bezeichnung' },
    { key: 'slug', label: 'Slug' },
    { key: 'brand', label: 'Marke' },
    { key: 'category', label: 'Kategorie' },
    { key: 'subcategory', label: 'Unterkategorie' },
    { key: 'article_type', label: 'Artikeltyp' },
    { key: '__isNew', label: 'Neu' },
    { key: '__bestSeller', label: 'Bestseller' },
    { key: '__isOffer', label: 'Angebot' },
    { key: '__isUsed', label: 'Gebraucht' },
    { key: '__condition', label: 'Zustand' },
    { key: '__inStock', label: 'Auf Lager' },
    { key: 'price', label: 'Preis (Netto)' },
    { key: '__priceBrutto', label: 'Preis (Brutto)' },
    { key: 'usedPriceNet', label: 'Gebrauchtpreis (Netto)' },
    { key: '__usedPriceBrutto', label: 'Gebrauchtpreis (Brutto)' },
    { key: 'currency', label: 'Währung' },
    { key: '__savings', label: 'Ersparnis (€)' },
    { key: 'weight_capacity', label: 'Tragfähigkeit (kg)' },
    { key: 'power_supply', label: 'Stromversorgung' },
    { key: 'application_area', label: 'Anwendungsbereich' },
    { key: 'warranty_years', label: 'Garantie (Jahre)' },
    { key: 'description', label: 'Beschreibung' },
    { key: 'usedDescription', label: 'Gebraucht-Beschreibung' },
    { key: '__features', label: 'Hauptmerkmale' },
    { key: 'main_image', label: 'Hauptbild-URL' },
    { key: 'defaultColor', label: 'Standardfarbe' },
    { key: 'defaultVariantNumber', label: 'Standard-Art.-Nr.' }
  ]

  const getCellValue = (product, colKey) => {
    switch (colKey) {
      case '__isNew': return product.isNew ? 'Ja' : 'Nein'
      case '__bestSeller': return product.bestSeller ? 'Ja' : 'Nein'
      case '__isOffer': return product.isOffer ? 'Ja' : 'Nein'
      case '__isUsed': return product.isUsed ? 'Ja' : 'Nein'
      case '__condition': return product.isUsed ? formatZustand(product.condition) || '' : ''
      case '__inStock': return product.inStock ? 'Ja' : 'Nein'
      case '__priceBrutto': return product.price ? (parseFloat(product.price) * 1.19).toFixed(2) : ''
      case '__usedPriceBrutto': return product.usedPriceNet ? (parseFloat(product.usedPriceNet) * 1.19).toFixed(2) : ''
      case '__savings': {
        if (!product.isUsed || !product.price || !product.usedPriceNet) return ''
        const s = parseFloat(product.price) - parseFloat(product.usedPriceNet)
        return s > 0 ? s.toFixed(2) : ''
      }
      case '__features': return (product.features || []).join(' | ')
      default: {
        const v = product[colKey]
        return v ?? ''
      }
    }
  }

  const allSpecKeys = new Set()
  list.forEach(p => {
    if (p.specifications && typeof p.specifications === 'object') {
      Object.keys(p.specifications).forEach(k => allSpecKeys.add(k))
    }
  })
  const specColumns = Array.from(allSpecKeys).sort().map(k => ({
    key: `__spec_${k}`,
    label: `Spez: ${k}`
  }))

  const columns = [...fixedColumns, ...specColumns]

  const escape = (val) => {
    if (val === null || val === undefined) return ''
    const s = String(val).replace(/\r?\n/g, ' ')
    if (s.includes('"') || s.includes(';') || s.includes('\n') || s.includes('\r')) {
      return `"${s.replace(/"/g, '""')}"`
    }
    return s
  }

  const headerLine = columns.map(c => escape(c.label)).join(';')

  const dataLines = list.map(product => {
    return columns.map(col => {
      if (col.key.startsWith('__spec_')) {
        const specKey = col.key.replace('__spec_', '')
        const v = product.specifications?.[specKey]
        if (v === true) return 'Ja'
        if (v === false) return 'Nein'
        return escape(v ?? '')
      }
      return escape(getCellValue(product, col.key))
    }).join(';')
  })

  const csv = '\uFEFF' + [headerLine, ...dataLines].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  const date = new Date().toISOString().slice(0, 10)
  let suffix = 'export'
  if (list === filteredProducts.value) suffix = 'gefiltert'
  else if (list === products.value) suffix = 'alle'
  a.href = url
  a.download = `produkte_${suffix}_${date}.csv`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)

  snackbar.value = {
    show: true,
    text: `✅ ${list.length} Produkt(e) als CSV exportiert`,
    color: 'success'
  }
}

// ============================================================
// ✅ EXPORT CSV – SÉLECTION
// ============================================================
const exportSelectedCsv = () => {
  if (!selectedIds.value.length) {
    snackbar.value = { show: true, text: '❌ Keine Auswahl', color: 'error' }
    return
  }
  const selectedProducts = products.value.filter(p => selectedIds.value.includes(p.id))
  exportCsv(selectedProducts)
}

// ============================================================
// ✅ EXPORT CSV SERVEUR
// ============================================================
const exportAllCsvServer = () => {
  if (!products.value.length) {
    snackbar.value = { show: true, text: '❌ Keine Produkte zu exportieren', color: 'error' }
    return
  }
  const params = new URLSearchParams()
  if (selectedCategory.value) params.append('kategorie', selectedCategory.value)
  if (search.value) params.append('suche', search.value)
  const qs = params.toString()
  window.open(apiUrl('/export_products_csv.php' + (qs ? `?${qs}` : '')), '_blank')
  snackbar.value = {
    show: true,
    text: '⏳ CSV wird generiert...',
    color: 'info'
  }
}

// ============================================================
// ✅ EXPORT JSON
// ============================================================
const exportJson = () => {
  if (!filteredProducts.value.length) {
    snackbar.value = { show: true, text: '❌ Keine Produkte zu exportieren', color: 'error' }
    return
  }
  const data = {
    exportDate: new Date().toISOString(),
    count: filteredProducts.value.length,
    filters: {
      search: search.value || null,
      category: selectedCategory.value || null,
      stock: stockFilter.value ?? null,
      used: usedFilter.value
    },
    products: filteredProducts.value
  }
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `produkte_${new Date().toISOString().slice(0, 10)}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
  snackbar.value = {
    show: true,
    text: `✅ ${filteredProducts.value.length} Produkt(e) als JSON exportiert`,
    color: 'success'
  }
}

// ============================================================
// Navigation avec permissions
// ============================================================
function addProduct() {
  if (!permissionStore.can('products-write')) {
    permissionStore.showAccessDenied()
    return
  }
  router.push('/products/add')
}

function viewProduct(id) {
  router.push(`/products/view/${id}`)
}

function editProduct(id) {
  if (!permissionStore.can('products-write')) {
    permissionStore.showAccessDenied()
    return
  }
  router.push(`/products/edit/${id}`)
}

const truncate = (str, len) =>
  str?.length > len ? str.slice(0, len) + '…' : str

// ============================================================
// ✅ INITIALISATION + WATCH URL
// ============================================================
onMounted(() => {
  applyQueryFilters()
  loadProducts()
})

// ✅ Re-applique les filtres si l'URL change (ex: depuis le Dashboard)
watch(
  () => route.query,
  () => {
    applyQueryFilters()
  },
  { deep: true }
)
</script>

<style scoped>
/* ============================================================
   BASE
   ============================================================ */
.products-container {
  max-width: 1800px;
  margin: 0 auto;
  padding-top: 16px;
  padding-bottom: 100px;
}

.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.min-w-0 { min-width: 0; }

.gradient-text {
  background: linear-gradient(135deg, #1976D2, #42A5F5);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

/* ============================================================
   HEADER
   ============================================================ */
.icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #1976D2 0%, #42A5F5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.25);
  flex-shrink: 0;
}

.btn-modern {
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0.2px;
  transition: transform 0.15s ease, box-shadow 0.2s ease;
}

.btn-modern:hover {
  transform: translateY(-1px);
}

.btn-primary-modern {
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.btn-primary-modern:hover {
  box-shadow: 0 6px 18px rgba(25, 118, 210, 0.4);
}

/* ============================================================
   STATS CARDS
   ============================================================ */
.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 12px !important;
  border: 1px solid #e8eaf0;
  background: #ffffff;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon-blue {
  background: linear-gradient(135deg, #1976D2 0%, #42A5F5 100%);
  box-shadow: 0 4px 10px rgba(25, 118, 210, 0.3);
}

.stat-icon-green {
  background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);
  box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
}

.stat-icon-orange {
  background: linear-gradient(135deg, #e65100 0%, #ff9800 100%);
  box-shadow: 0 4px 10px rgba(230, 81, 0, 0.3);
}

.stat-icon-red {
  background: linear-gradient(135deg, #c62828 0%, #ef5350 100%);
  box-shadow: 0 4px 10px rgba(198, 40, 40, 0.3);
}

.stat-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  font-size: 0.7rem;
  color: #78909c;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  line-height: 1.2;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: #1a202c;
  line-height: 1.2;
}

/* ============================================================
   FILTRES
   ============================================================ */
.filter-card {
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(0, 0, 0, 0.06);
  backdrop-filter: blur(8px);
}

.filter-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #e3f2fd;
  border-radius: 8px;
  border-left: 3px solid #1976d2;
  flex-wrap: wrap;
}

.selection-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  background: #1976d2;
  color: white;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* ============================================================
   GRILLE PRODUITS
   ============================================================ */
.compact-grid {
  margin: -4px -4px 0 -4px;
}

.compact-grid > [class*="col-"] {
  padding: 4px !important;
}

.product-card {
  height: 100%;
  display: flex;
  flex-direction: column;
  border: 1px solid #e8eaf0;
  border-radius: 12px !important;
  overflow: hidden;
  position: relative;
  background: #ffffff;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border-color: #90caf9;
}

.product-card.is-gebraucht {
  border-color: #ff9800;
  border-width: 2px;
}

.product-card.is-selected {
  border-color: #1976d2 !important;
  border-width: 2px;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2);
  background: #f5faff;
}

.selection-checkbox {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 20;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 6px;
  padding: 2px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.card-image {
  position: relative;
  overflow: hidden;
  cursor: pointer;
  min-height: 200px;
  background: #fafafa;
}

.image-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
}

.product-image {
  width: auto;
  max-width: 80%;
  height: auto;
  max-height: 170px;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image {
  transform: scale(1.05);
}

.absolute-badges {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 10;
}

.badges-container {
  position: absolute;
  top: 10px;
  left: 42px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  pointer-events: auto;
}

.badge-modern {
  font-weight: 700;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.new-badge { background-color: #4CAF50 !important; color: white !important; }
.best-badge { background-color: #FF9800 !important; color: white !important; }
.offer-badge { background-color: #e53935 !important; color: white !important; }
.gebraucht-badge { background-color: #ff9800 !important; color: white !important; }

.stock-indicator {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  z-index: 11;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.stock-indicator.in-stock { background: #4CAF50; }
.stock-indicator.out-of-stock { background: #e53935; }

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
}

.placeholder-text {
  font-size: 0.9rem;
  color: #999;
  font-weight: 500;
  text-align: center;
  max-width: 100%;
  padding: 0 8px;
  word-break: break-word;
}

.product-title {
  font-size: 1rem;
  font-weight: 600;
  padding: 14px 14px 6px;
  line-height: 1.35;
  min-height: 48px;
  cursor: pointer;
  color: #1a202c;
  transition: color 0.2s;
}

.product-title:hover {
  color: #1976d2;
}

.product-brand {
  color: #666;
  font-size: 0.85rem;
  padding: 0 14px 8px;
  min-height: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
}

.article-number {
  font-size: 0.75rem;
  color: #90a4ae;
  font-style: italic;
}

.zustand-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0 14px 8px;
  padding: 6px 10px;
  background: #fff3e0;
  border-left: 3px solid #ff9800;
  border-radius: 4px;
  font-size: 0.8rem;
  color: #e65100;
}

.zustand-badge strong { color: #bf360c; }

.product-features {
  padding: 4px 14px 8px;
  flex-grow: 1;
}

.feature {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 5px;
  font-size: 0.82rem;
  color: #555;
  line-height: 1.4;
}

.feature .v-icon {
  flex-shrink: 0;
  margin-top: 2px;
}

.product-actions {
  padding: 8px 14px 14px;
  display: flex;
  align-items: center;
  border-top: 1px solid #f0f0f0;
  margin-top: auto;
}

.price-block {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.old-price {
  font-size: 0.8rem;
  color: #999;
  text-decoration: line-through;
  margin-bottom: 2px;
}

.price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1976d2;
}

.price-gebraucht {
  color: #ff9800 !important;
}

.action-btn {
  text-transform: none;
  font-weight: 600;
}

/* ============================================================
   VUE LISTE
   ============================================================ */
.list-view-card {
  border: 1px solid #e8eaf0;
  border-radius: 12px !important;
  overflow: hidden;
}

.products-table {
  background: transparent;
}

.products-table thead {
  background: #f8f9fa;
}

.products-table thead th {
  font-weight: 700;
  font-size: 0.8rem;
  color: #546e7a;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  padding: 12px 16px !important;
  border-bottom: 1px solid #e8eaf0 !important;
}

.list-row {
  transition: background 0.15s;
}

.list-row:hover {
  background: #f5faff;
}

.list-row.row-selected {
  background: #e3f2fd !important;
}

.product-cell {
  cursor: pointer;
}

.product-thumb {
  border-radius: 8px !important;
  border: 1px solid #e8eaf0;
  background: #fafafa;
  flex-shrink: 0;
}

.list-product-name {
  color: #1a202c;
  max-width: 400px;
}

.list-product-name:hover {
  color: #1976d2;
}

/* ============================================================
   BARRE FLOTTANTE SÉLECTION
   ============================================================ */
.selection-toolbar {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #1976D2 0%, #42A5F5 100%);
  padding: 10px 20px;
  border-radius: 50px;
  box-shadow: 0 8px 24px rgba(25, 118, 210, 0.4);
  z-index: 1000;
  max-width: 90vw;
}

/* ============================================================
   EXPORT MENU
   ============================================================ */
.export-menu {
  border-radius: 12px !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 960px) {
  .products-container {
    padding-top: 8px;
  }
}

@media (max-width: 600px) {
  .compact-grid {
    margin: -2px -2px 0 -2px;
  }

  .compact-grid > [class*="col-"] {
    padding: 2px !important;
  }

  .product-title {
    font-size: 0.92rem;
    min-height: 42px;
    padding: 10px 10px 4px;
  }

  .product-brand {
    padding: 0 10px 6px;
    font-size: 0.78rem;
  }

  .product-features {
    padding: 4px 10px 6px;
  }

  .feature {
    font-size: 0.75rem;
  }

  .price {
    font-size: 1rem;
  }

  .card-image {
    min-height: 150px;
  }

  .product-image {
    max-height: 130px;
  }

  .image-container {
    padding: 10px;
  }

  .action-label {
    display: none;
  }

  .zustand-badge {
    margin: 0 10px 6px;
    font-size: 0.72rem;
    padding: 4px 8px;
  }

  .old-price {
    font-size: 0.72rem;
  }

  .price-gebraucht {
    font-size: 1rem;
  }

  .product-actions {
    padding: 6px 10px 10px;
  }

  .stat-card {
    padding: 10px 12px;
    gap: 8px;
  }

  .stat-icon-wrap {
    width: 36px;
    height: 36px;
  }

  .stat-value {
    font-size: 1.1rem;
  }

  .stat-label {
    font-size: 0.62rem;
  }

  .icon-badge {
    width: 36px;
    height: 36px;
  }

  .badges-container {
    left: 40px;
  }

  .selection-toolbar {
    bottom: 10px;
    padding: 8px 14px;
    border-radius: 40px;
  }
}

@media (min-width: 960px) {
  .card-image {
    min-height: 220px;
  }

  .product-image {
    max-height: 200px;
  }
}
</style>